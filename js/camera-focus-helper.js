/**
 * カメラフォーカス最適化ヘルパー
 * バーコード読み取り用のカメラ設定を最適化
 */

class CameraFocusHelper {
    constructor() {
        this.stream = null;
        this.track = null;
        this.capabilities = null;
        this.settings = null;
    }
    
    /**
     * カメラストリームのフォーカスを最適化
     */
    async optimizeFocus(stream) {
        if (!stream) return false;
        
        this.stream = stream;
        const videoTrack = stream.getVideoTracks()[0];
        
        if (!videoTrack) return false;
        
        this.track = videoTrack;
        this.capabilities = videoTrack.getCapabilities();
        this.settings = videoTrack.getSettings();
        
        
        try {
            // フォーカス最適化設定を適用
            const constraints = {};
            
            // 1. フォーカス設定
            if (this.capabilities.focusMode && this.capabilities.focusMode.includes('continuous')) {
                constraints.focusMode = 'continuous';
            } else if (this.capabilities.focusMode && this.capabilities.focusMode.includes('single-shot')) {
                constraints.focusMode = 'single-shot';
            }
            
            // 2. フォーカス距離設定（近距離優先）
            if (this.capabilities.focusDistance) {
                const minDistance = this.capabilities.focusDistance.min || 0;
                const maxDistance = this.capabilities.focusDistance.max || 1;
                // バーコード読み取り最適距離（10-20cm程度）
                const optimalDistance = Math.min(0.2, maxDistance);
                constraints.focusDistance = Math.max(optimalDistance, minDistance);
            }
            
            // 3. 露出設定
            if (this.capabilities.exposureMode && this.capabilities.exposureMode.includes('continuous')) {
                constraints.exposureMode = 'continuous';
            }
            
            // 4. ホワイトバランス
            if (this.capabilities.whiteBalanceMode && this.capabilities.whiteBalanceMode.includes('continuous')) {
                constraints.whiteBalanceMode = 'continuous';
            }
            
            // 5. ISO設定（利用可能な場合）
            if (this.capabilities.iso) {
                const minIso = this.capabilities.iso.min || 100;
                const maxIso = this.capabilities.iso.max || 800;
                // バーコード読み取りに適したISO（明るさ重視）
                constraints.iso = Math.min(400, maxIso);
            }
            
            // 設定を適用
            if (Object.keys(constraints).length > 0) {
                await videoTrack.applyConstraints({ advanced: [constraints] });
                return true;
            }
            
        } catch (error) {
            console.warn('フォーカス最適化に失敗しました:', error);
            // フォーカス最適化に失敗してもスキャンは続行
        }
        
        return false;
    }
    
    /**
     * 手動フォーカストリガー（タップフォーカス用）
     */
    async triggerFocus() {
        if (!this.track || !this.capabilities) return false;
        
        try {
            if (this.capabilities.focusMode && this.capabilities.focusMode.includes('single-shot')) {
                await this.track.applyConstraints({
                    advanced: [{ focusMode: 'single-shot' }]
                });
                return true;
            }
        } catch (error) {
            console.warn('手動フォーカスに失敗しました:', error);
        }
        
        return false;
    }
    
    /**
     * フラッシュライト制御（対応デバイスのみ）
     */
    async toggleTorch(enable = true) {
        if (!this.track || !this.capabilities) return false;
        
        try {
            if (this.capabilities.torch) {
                await this.track.applyConstraints({
                    advanced: [{ torch: enable }]
                });
                return true;
            }
        } catch (error) {
            console.warn('フラッシュライト制御に失敗しました:', error);
        }
        
        return false;
    }
    
    /**
     * カメラ設定情報を取得
     */
    getCameraInfo() {
        if (!this.capabilities || !this.settings) {
            return null;
        }
        
        return {
            capabilities: this.capabilities,
            settings: this.settings,
            hasFocus: !!this.capabilities.focusMode,
            hasAutoFocus: this.capabilities.focusMode && 
                         (this.capabilities.focusMode.includes('continuous') || 
                          this.capabilities.focusMode.includes('single-shot')),
            hasTorch: !!this.capabilities.torch,
            focusDistance: this.capabilities.focusDistance,
            resolution: {
                width: this.settings.width,
                height: this.settings.height
            }
        };
    }
    
    /**
     * リソースクリーンアップ
     */
    cleanup() {
        this.stream = null;
        this.track = null;
        this.capabilities = null;
        this.settings = null;
    }
}

// グローバルで利用可能にする
window.CameraFocusHelper = CameraFocusHelper;