/**
 * ReadNest Barcode Scanner
 * ISBNバーコードをカメラで読み取って本を検索する機能
 */

class BarcodeScanner {
    constructor() {
        this.video = null;
        this.canvas = null;
        this.context = null;
        this.stream = null;
        this.scanning = false;
        this.lastResult = '';
        this.lastScanTime = 0;
        this.scanInterval = null;
        this.focusHelper = null;
        
        // デバイス判定
        this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        // QuaggaJSの設定
        this.quaggaConfig = {
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: null, // 後で設定
                constraints: {
                    width: { min: 320, ideal: 640, max: 1280 },
                    height: { min: 240, ideal: 480, max: 720 },
                    facingMode: this.isMobile ? "environment" : "user" // PCでは前面カメラを使用
                }
            },
            locator: {
                patchSize: "medium",
                halfSample: true
            },
            numOfWorkers: Math.min(navigator.hardwareConcurrency || 2, 4), // PCでの安定性を向上
            frequency: 10,
            decoder: {
                readers: [
                    "ean_reader",      // EAN-13 (ISBN-13)
                    "ean_8_reader",    // EAN-8
                    "code_128_reader", // Code 128
                    "code_39_reader"   // Code 39
                ]
            },
            locate: true
        };
    }
    
    /**
     * バーコードスキャナーを初期化
     */
    async init(videoElement, resultCallback) {
        try {
            this.video = videoElement;
            this.resultCallback = resultCallback;
            
            // QuaggaJSの設定にターゲット要素を追加
            this.quaggaConfig.inputStream.target = this.video.parentElement;
            
            // カメラの権限を段階的に確認（PC対応）
            let stream;
            try {
                // まずは環境設定（背面カメラ）を試す
                // フォーカス最適化設定を追加
                const enhancedConstraints = {
                    ...this.quaggaConfig.inputStream.constraints,
                    focusMode: "continuous",      // 連続オートフォーカス
                    focusDistance: 0.1,           // 近距離フォーカス（10cm程度）
                    whiteBalanceMode: "auto",     // 自動ホワイトバランス
                    exposureMode: "auto",         // 自動露出
                    torch: false                  // フラッシュOFF
                };
                
                stream = await navigator.mediaDevices.getUserMedia({
                    video: enhancedConstraints
                });
            } catch (error) {
                console.warn('環境カメラでの初期化に失敗。ユーザーカメラを試します:', error);
                try {
                    // 背面カメラが使えない場合は前面カメラを試す
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: { min: 320, ideal: 640, max: 1280 },
                            height: { min: 240, ideal: 480, max: 720 },
                            facingMode: "user",
                            focusMode: "continuous",
                            focusDistance: 0.1,
                            whiteBalanceMode: "auto",
                            exposureMode: "auto"
                        }
                    });
                    // PC用の設定に変更
                    this.quaggaConfig.inputStream.constraints.facingMode = "user";
                } catch (error2) {
                    console.warn('ユーザーカメラでの初期化に失敗。基本設定を試します:', error2);
                    // どちらも失敗した場合は基本設定で試す
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: true
                    });
                    // 基本設定に変更
                    this.quaggaConfig.inputStream.constraints = {
                        width: { min: 320, ideal: 640 },
                        height: { min: 240, ideal: 480 }
                    };
                }
            }
            
            // ストリームをビデオ要素に設定
            this.video.srcObject = stream;
            this.stream = stream;
            
            // ビデオが読み込まれるまで待機
            await new Promise((resolve) => {
                this.video.onloadedmetadata = resolve;
            });
            
            // カメラフォーカス最適化
            if (typeof CameraFocusHelper !== 'undefined') {
                this.focusHelper = new CameraFocusHelper();
                await this.focusHelper.optimizeFocus(stream);
                
                // カメラ情報を取得
                const cameraInfo = this.focusHelper.getCameraInfo();
            }
            
            // タップフォーカス機能を追加
            this.addTapToFocus();
            
            // QuaggaJSを初期化
            await this.initQuagga();
            
            return true;
        } catch (error) {
            console.error('バーコードスキャナーの初期化に失敗しました:', error);
            this.handleError(error);
            return false;
        }
    }
    
    /**
     * QuaggaJSを初期化
     */
    initQuagga() {
        return new Promise((resolve, reject) => {
            Quagga.init(this.quaggaConfig, (err) => {
                if (err) {
                    reject(err);
                    return;
                }
                
                // バーコード検出時のイベントハンドラ
                Quagga.onDetected((result) => {
                    this.onBarcodeDetected(result);
                });
                
                resolve();
            });
        });
    }
    
    /**
     * スキャンを開始
     */
    start() {
        if (!this.scanning) {
            this.scanning = true;
            Quagga.start();
        }
    }
    
    /**
     * スキャンを停止
     */
    stop() {
        if (this.scanning) {
            this.scanning = false;
            Quagga.stop();
        }
    }
    
    /**
     * タップフォーカス機能を追加
     */
    addTapToFocus() {
        if (!this.video || !this.focusHelper) return;
        
        this.video.addEventListener('click', async (event) => {
            // タップ位置を視覚的に表示
            this.showFocusIndicator(event);
            
            // 手動フォーカストリガー
            await this.focusHelper.triggerFocus();
        });
    }
    
    /**
     * フォーカスインジケーターを表示
     */
    showFocusIndicator(event) {
        const rect = this.video.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        // 既存のインジケーターを削除
        const existingIndicator = this.video.parentElement.querySelector('.focus-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }
        
        // 新しいインジケーターを作成
        const indicator = document.createElement('div');
        indicator.className = 'focus-indicator';
        indicator.style.cssText = `
            position: absolute;
            left: ${x - 25}px;
            top: ${y - 25}px;
            width: 50px;
            height: 50px;
            border: 2px solid #00ff00;
            border-radius: 50%;
            pointer-events: none;
            z-index: 10;
            animation: focusPulse 1s ease-out;
        `;
        
        // アニメーション用のスタイルを追加
        if (!document.querySelector('#focus-animation-style')) {
            const style = document.createElement('style');
            style.id = 'focus-animation-style';
            style.textContent = `
                @keyframes focusPulse {
                    0% { transform: scale(1.5); opacity: 0.8; }
                    50% { transform: scale(1); opacity: 1; }
                    100% { transform: scale(0.8); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        this.video.parentElement.appendChild(indicator);
        
        // 1秒後にインジケーターを削除
        setTimeout(() => {
            if (indicator.parentElement) {
                indicator.remove();
            }
        }, 1000);
    }
    
    /**
     * リソースを解放
     */
    destroy() {
        this.stop();
        
        if (this.focusHelper) {
            this.focusHelper.cleanup();
            this.focusHelper = null;
        }
        
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        
        if (this.video) {
            this.video.srcObject = null;
        }
        
        Quagga.offDetected();
    }
    
    /**
     * バーコードが検出された時の処理
     */
    onBarcodeDetected(result) {
        const code = result.codeResult.code;
        const format = result.codeResult.format;
        
        // 同じコードを連続して読まないようにする（1秒のクールダウン）
        const now = Date.now();
        if (code === this.lastResult && (now - this.lastScanTime) < 1000) {
            return;
        }
        
        this.lastResult = code;
        this.lastScanTime = now;
        
        // ISBNかどうかを確認
        if (this.isValidISBN(code)) {
            // 音を鳴らす（オプション）
            this.playBeep();
            
            // 結果をコールバックに渡す
            if (this.resultCallback) {
                this.resultCallback({
                    code: code,
                    format: format,
                    isISBN: true
                });
            }
        }
    }
    
    /**
     * ISBNの妥当性をチェック
     */
    isValidISBN(code) {
        // ISBN-10またはISBN-13の形式をチェック
        const isbn10Regex = /^[0-9]{9}[0-9Xx]$/;
        const isbn13Regex = /^(978|979)[0-9]{10}$/;
        
        return isbn10Regex.test(code) || isbn13Regex.test(code);
    }
    
    /**
     * ビープ音を再生（オプション）
     */
    playBeep() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 1000;
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        } catch (e) {
            // ビープ音の再生に失敗
        }
    }
    
    /**
     * エラーハンドリング
     */
    handleError(error) {
        let message = 'エラーが発生しました';
        
        if (error.name === 'NotAllowedError') {
            message = 'カメラへのアクセスが許可されていません。ブラウザの設定を確認してください。';
        } else if (error.name === 'NotFoundError') {
            message = 'カメラが見つかりません。デバイスにカメラが接続されているか確認してください。';
        } else if (error.name === 'NotReadableError') {
            message = 'カメラが使用できません。他のアプリケーションで使用されていないか確認してください。';
        } else if (error.name === 'OverconstrainedError') {
            message = 'カメラの設定に問題があります。別のカメラモードを試しています...';
        } else if (error.message && error.message.includes('QuaggaJS')) {
            message = 'バーコードスキャナーの初期化に失敗しました。ページを再読み込みして再度お試しください。';
        }
        
        console.error('バーコードスキャナーエラー:', error);
        
        if (this.resultCallback) {
            this.resultCallback({
                error: true,
                message: message,
                details: error.message
            });
        }
    }
}

/**
 * ZXing-js/libraryを使用した代替実装
 * QuaggaJSがうまく動作しない場合のフォールバック
 */
class ZXingBarcodeScanner {
    constructor() {
        this.codeReader = null;
        this.selectedDeviceId = null;
        this.scanning = false;
    }
    
    async init(videoElement, resultCallback) {
        try {
            // ZXing MultiFormatReaderを初期化
            this.codeReader = new ZXing.BrowserMultiFormatReader();
            this.video = videoElement;
            this.resultCallback = resultCallback;
            
            // 利用可能なカメラデバイスを取得
            const videoInputDevices = await this.codeReader.listVideoInputDevices();
            
            if (videoInputDevices.length === 0) {
                throw new Error('カメラが見つかりません');
            }
            
            // 背面カメラを優先的に選択
            this.selectedDeviceId = videoInputDevices.find(device => 
                device.label.toLowerCase().includes('back') || 
                device.label.toLowerCase().includes('rear')
            )?.deviceId || videoInputDevices[0].deviceId;
            
            return true;
        } catch (error) {
            console.error('ZXingスキャナーの初期化に失敗しました:', error);
            this.handleError(error);
            return false;
        }
    }
    
    async start() {
        if (!this.scanning && this.codeReader && this.selectedDeviceId) {
            this.scanning = true;
            
            try {
                await this.codeReader.decodeFromVideoDevice(
                    this.selectedDeviceId, 
                    this.video, 
                    (result, err) => {
                        if (result) {
                            this.onBarcodeDetected(result);
                        }
                    }
                );
            } catch (error) {
                this.handleError(error);
            }
        }
    }
    
    stop() {
        if (this.scanning && this.codeReader) {
            this.scanning = false;
            this.codeReader.reset();
        }
    }
    
    destroy() {
        this.stop();
        this.codeReader = null;
    }
    
    onBarcodeDetected(result) {
        const code = result.text;
        
        if (this.isValidISBN(code)) {
            this.playBeep();
            
            if (this.resultCallback) {
                this.resultCallback({
                    code: code,
                    format: result.format,
                    isISBN: true
                });
            }
        }
    }
    
    isValidISBN(code) {
        const isbn10Regex = /^[0-9]{9}[0-9Xx]$/;
        const isbn13Regex = /^(978|979)[0-9]{10}$/;
        
        return isbn10Regex.test(code) || isbn13Regex.test(code);
    }
    
    playBeep() {
        // BarcodeScanner と同じ実装
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 1000;
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        } catch (e) {
            // ビープ音の再生に失敗
        }
    }
    
    handleError(error) {
        let message = 'エラーが発生しました';
        
        if (error.name === 'NotAllowedError') {
            message = 'カメラへのアクセスが許可されていません。';
        } else if (error.name === 'NotFoundError') {
            message = 'カメラが見つかりません。';
        }
        
        if (this.resultCallback) {
            this.resultCallback({
                error: true,
                message: message
            });
        }
    }
}

// グローバルに公開
window.BarcodeScanner = BarcodeScanner;
window.ZXingBarcodeScanner = ZXingBarcodeScanner;