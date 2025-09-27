/**
 * ReadNest オンボーディング・チュートリアル
 * 初回ログインユーザー向けのインタラクティブなガイド
 */

class ReadNestOnboarding {
    constructor() {
        this.currentStep = 0;
        this.steps = [
            {
                id: 'welcome',
                title: 'ReadNestへようこそ！ 📚',
                content: `
                    <div class="text-center">
                        <div class="mb-4">
                            <i class="fas fa-book-open text-6xl text-readnest-primary"></i>
                        </div>
                        <p class="text-lg mb-4">ReadNestは、あなたの読書体験を豊かにする読書管理サービスです。</p>
                        <p class="text-gray-600">このチュートリアルでは、主要な機能を3分でご紹介します。</p>
                    </div>
                `,
                target: null,
                position: 'center',
                showSkip: true
            },
            {
                id: 'search-books',
                title: '📖 本を検索して追加',
                content: `
                    <p class="mb-3">まずは本を追加してみましょう！</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>検索ボックスにタイトルや著者名を入力</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>ISBNコードでの検索も可能</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>気になる本を「本棚に追加」ボタンで登録</span>
                        </li>
                    </ul>
                `,
                target: '#search-input',
                position: 'bottom',
                action: () => {
                    // 検索ボックスをハイライト
                    const searchBox = document.querySelector('#search-input');
                    if (searchBox) {
                        searchBox.classList.add('ring-4', 'ring-blue-400', 'ring-opacity-50');
                        setTimeout(() => {
                            searchBox.classList.remove('ring-4', 'ring-blue-400', 'ring-opacity-50');
                        }, 3000);
                    }
                }
            },
            {
                id: 'reading-status',
                title: '📊 読書ステータスを管理',
                content: `
                    <p class="mb-3">本を追加したら、読書状態を設定できます：</p>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm mr-3">読みたい</span>
                            <span class="text-sm">これから読む本をリストアップ</span>
                        </div>
                        <div class="flex items-center">
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-sm mr-3">読書中</span>
                            <span class="text-sm">現在読んでいる本と進捗を記録</span>
                        </div>
                        <div class="flex items-center">
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm mr-3">読了</span>
                            <span class="text-sm">読み終えた本の記録を保存</span>
                        </div>
                    </div>
                `,
                target: null,
                position: 'bottom'
            },
            {
                id: 'progress-tracking',
                title: '📈 読書進捗を記録',
                content: `
                    <div class="space-y-3">
                        <p>「読書中」の本では、詳細な進捗を記録できます：</p>
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium">現在のページ</span>
                                <span class="text-sm text-gray-600">150 / 300ページ</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: 50%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-2">進捗率: 50%</p>
                        </div>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
                            毎日の読書記録が、あなたの読書習慣を可視化します
                        </p>
                    </div>
                `,
                target: null,
                position: 'center'
            },
            {
                id: 'favorites',
                title: '⭐ お気に入り機能',
                content: `
                    <div class="space-y-3">
                        <p>特別な本は「お気に入り」に登録しましょう：</p>
                        <div class="flex items-center space-x-3 bg-yellow-50 p-3 rounded">
                            <button class="text-2xl text-yellow-500">
                                <i class="fas fa-star"></i>
                            </button>
                            <div class="text-sm">
                                <p class="font-medium">お気に入りに追加</p>
                                <p class="text-gray-600">クリックで登録/解除</p>
                            </div>
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start">
                                <i class="fas fa-arrows-alt text-blue-500 mt-1 mr-2"></i>
                                <span>ドラッグ&ドロップで並び替え可能</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-share text-green-500 mt-1 mr-2"></i>
                                <span>プロフィールで公開/非公開を選択</span>
                            </li>
                        </ul>
                    </div>
                `,
                target: null,
                position: 'center'
            },
            {
                id: 'reviews',
                title: '✍️ レビューと評価',
                content: `
                    <div class="space-y-3">
                        <p>読了した本には、レビューと評価を残せます：</p>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="flex items-center mb-3">
                                <span class="mr-3">評価:</span>
                                <div class="flex space-x-1">
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <i class="far fa-star text-yellow-400"></i>
                                </div>
                            </div>
                            <div class="text-sm text-gray-700">
                                <p class="italic">"とても感動的な物語でした..."</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-users mr-1"></i>
                            レビューを公開して、他の読者と感想を共有できます
                        </p>
                    </div>
                `,
                target: null,
                position: 'center'
            },
            {
                id: 'statistics',
                title: '📊 読書統計',
                content: `
                    <div class="space-y-3">
                        <p>あなたの読書活動を詳細に分析：</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-blue-50 p-3 rounded text-center">
                                <div class="text-2xl font-bold text-blue-600">42</div>
                                <div class="text-xs text-gray-600">今年の読了冊数</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded text-center">
                                <div class="text-2xl font-bold text-green-600">12,500</div>
                                <div class="text-xs text-gray-600">総読書ページ数</div>
                            </div>
                        </div>
                        <ul class="space-y-1 text-sm text-gray-700">
                            <li>• 月別・年別の読書グラフ</li>
                            <li>• ジャンル別の読書傾向</li>
                            <li>• 読書ペースの分析</li>
                        </ul>
                    </div>
                `,
                target: null,
                position: 'center'
            },
            {
                id: 'profile',
                title: '👤 プロフィールをカスタマイズ',
                content: `
                    <div class="space-y-3">
                        <p>プロフィールページで自己紹介を充実させましょう：</p>
                        <div class="bg-gray-50 p-3 rounded space-y-2">
                            <div class="flex items-center">
                                <i class="fas fa-user-circle text-3xl text-gray-400 mr-3"></i>
                                <div>
                                    <p class="font-medium">アバター画像</p>
                                    <p class="text-xs text-gray-600">お気に入りの画像を設定</p>
                                </div>
                            </div>
                            <div class="text-sm">
                                <p class="font-medium">自己紹介文</p>
                                <p class="text-xs text-gray-600">好きなジャンルや読書の目標など</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-lock mr-1"></i>
                            公開/非公開の設定も自由に変更できます
                        </p>
                    </div>
                `,
                target: null,
                position: 'center'
            },
            {
                id: 'tips',
                title: '💡 便利な使い方のヒント',
                content: `
                    <div class="space-y-3">
                        <div class="space-y-2">
                            <div class="flex items-start">
                                <i class="fas fa-calendar text-blue-500 mt-1 mr-2"></i>
                                <div class="text-sm">
                                    <p class="font-medium">読書カレンダー</p>
                                    <p class="text-gray-600">日々の読書記録をカレンダーで確認</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-mobile-alt text-green-500 mt-1 mr-2"></i>
                                <div class="text-sm">
                                    <p class="font-medium">スマホ対応</p>
                                    <p class="text-gray-600">外出先でも読書記録を更新</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-users text-purple-500 mt-1 mr-2"></i>
                                <div class="text-sm">
                                    <p class="font-medium">コミュニティ機能</p>
                                    <p class="text-gray-600">他の読者のレビューを参考に</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                target: null,
                position: 'center'
            },
            {
                id: 'complete',
                title: '🎉 準備完了！',
                content: `
                    <div class="text-center">
                        <div class="mb-4">
                            <i class="fas fa-trophy text-6xl text-yellow-500"></i>
                        </div>
                        <p class="text-lg font-bold mb-3">チュートリアル完了！</p>
                        <p class="text-gray-600 mb-4">さあ、読書の記録を始めましょう</p>
                        <p class="text-sm text-gray-500 mb-4">
                            <i class="fas fa-info-circle mr-1"></i>
                            完了ボタンを押すとホームページに移動します
                        </p>
                    </div>
                `,
                target: null,
                position: 'center',
                showSkip: false
            }
        ];
        
        this.overlay = null;
        this.tooltip = null;
        this.isRunning = false;
    }

    start() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.currentStep = 0;
        this.createOverlay();
        this.showStep();
    }

    createOverlay() {
        // オーバーレイを作成
        this.overlay = document.createElement('div');
        this.overlay.className = 'fixed inset-0 bg-black bg-opacity-50 transition-opacity';
        this.overlay.style.zIndex = '9998';
        this.overlay.style.opacity = '0';
        document.body.appendChild(this.overlay);

        // フェードイン
        setTimeout(() => {
            this.overlay.style.opacity = '1';
        }, 10);

        // オーバーレイクリックでスキップ確認
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay && this.steps[this.currentStep].showSkip !== false) {
                this.confirmSkip();
            }
        });
    }

    showStep() {
        const step = this.steps[this.currentStep];
        
        // 既存のツールチップを削除
        if (this.tooltip) {
            this.tooltip.remove();
        }

        // ツールチップを作成
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'fixed bg-white rounded-lg shadow-2xl max-w-md w-full mx-4';
        this.tooltip.style.zIndex = '9999';
        this.tooltip.style.transition = 'none'; // 初期配置時はトランジションなし
        this.tooltip.style.opacity = '0'; // 初期状態で非表示
        this.tooltip.style.visibility = 'hidden'; // レイアウトからも隠す
        
        // コンテンツを構築
        let content = `
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold text-gray-900">${step.title}</h3>
                    ${step.showSkip !== false ? `
                        <button onclick="readNestOnboarding.skip()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    ` : ''}
                </div>
                <div class="mb-6">
                    ${step.content}
                </div>
                <div class="flex justify-between items-center">
                    <div class="flex space-x-1">
                        ${this.steps.map((_, index) => `
                            <div class="w-2 h-2 rounded-full ${index === this.currentStep ? 'bg-readnest-primary' : 'bg-gray-300'}"></div>
                        `).join('')}
                    </div>
                    <div class="flex space-x-2">
                        ${this.currentStep > 0 ? `
                            <button onclick="readNestOnboarding.previousStep()" 
                                    class="px-4 py-2 text-gray-600 hover:text-gray-900 transition-colors">
                                <i class="fas fa-arrow-left mr-1"></i>戻る
                            </button>
                        ` : ''}
                        ${this.currentStep < this.steps.length - 1 ? `
                            <button onclick="readNestOnboarding.nextStep()" 
                                    class="px-4 py-2 bg-readnest-primary text-white rounded hover:bg-readnest-primary-dark transition-colors">
                                次へ<i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        ` : `
                            <button onclick="readNestOnboarding.complete()" 
                                    class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                                <i class="fas fa-check mr-1"></i>始める
                            </button>
                        `}
                    </div>
                </div>
            </div>
        `;
        
        this.tooltip.innerHTML = content;
        document.body.appendChild(this.tooltip);

        // 強制的にレイアウトを計算させる
        this.tooltip.offsetHeight;

        // 位置を設定（表示前に）
        this.positionTooltip(step);

        // 位置設定後に表示（2フレーム待つ）
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                this.tooltip.style.visibility = 'visible';
                this.tooltip.style.transition = 'opacity 0.3s ease-out';
                this.tooltip.style.opacity = '1';
            });
        });

        // アクションを実行
        if (step.action) {
            step.action();
        }

        // ターゲット要素をハイライト
        if (step.target) {
            this.highlightTarget(step.target);
        }
    }

    positionTooltip(step) {
        const tooltip = this.tooltip;
        
        // 中央表示の場合
        if (step.position === 'center' || !step.target) {
            tooltip.style.top = '50%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
            return;
        }
        
        // ターゲット要素がある場合
        const target = document.querySelector(step.target);
        if (target) {
            const rect = target.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            switch(step.position) {
                case 'bottom':
                    tooltip.style.top = `${rect.bottom + 10}px`;
                    tooltip.style.left = `${rect.left + (rect.width - tooltipRect.width) / 2}px`;
                    tooltip.style.transform = 'none';
                    break;
                case 'top':
                    tooltip.style.top = `${rect.top - tooltipRect.height - 10}px`;
                    tooltip.style.left = `${rect.left + (rect.width - tooltipRect.width) / 2}px`;
                    tooltip.style.transform = 'none';
                    break;
                case 'left':
                    tooltip.style.top = `${rect.top + (rect.height - tooltipRect.height) / 2}px`;
                    tooltip.style.left = `${rect.left - tooltipRect.width - 10}px`;
                    tooltip.style.transform = 'none';
                    break;
                case 'right':
                    tooltip.style.top = `${rect.top + (rect.height - tooltipRect.height) / 2}px`;
                    tooltip.style.left = `${rect.right + 10}px`;
                    tooltip.style.transform = 'none';
                    break;
                default:
                    // デフォルトは下
                    tooltip.style.top = `${rect.bottom + 10}px`;
                    tooltip.style.left = `${rect.left}px`;
                    tooltip.style.transform = 'none';
            }
        } else {
            // ターゲットが見つからない場合は中央に表示
            tooltip.style.top = '50%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
        }
    }

    highlightTarget(selector) {
        const target = document.querySelector(selector);
        if (target) {
            target.classList.add('onboarding-highlight', 'relative', 'bg-white', 'rounded', 'shadow-lg');
            target.style.zIndex = '9997';
            target.style.pointerEvents = 'none';
        }
    }

    removeHighlight() {
        // すべてのハイライトを削除
        document.querySelectorAll('.onboarding-highlight').forEach(el => {
            el.classList.remove('onboarding-highlight', 'relative', 'bg-white', 'rounded', 'shadow-lg');
            el.style.zIndex = '';
            el.style.pointerEvents = '';
        });
    }

    nextStep() {
        if (this.currentStep < this.steps.length - 1) {
            this.removeHighlight();
            this.currentStep++;
            this.showStep();
        }
    }

    previousStep() {
        if (this.currentStep > 0) {
            this.removeHighlight();
            this.currentStep--;
            this.showStep();
        }
    }

    skip() {
        this.confirmSkip();
    }

    confirmSkip() {
        if (confirm('チュートリアルをスキップしますか？\n後でヘルプメニューから再度確認できます。')) {
            this.end();
        }
    }

    complete() {
        // チュートリアル完了をサーバーに記録
        this.markAsCompleted();
        
        // オーバーレイとツールチップを削除
        this.end();
        
        // ホームページに遷移
        setTimeout(() => {
            window.location.href = '/';
        }, 500);
    }

    end() {
        this.removeHighlight();
        
        if (this.overlay) {
            this.overlay.style.opacity = '0';
            setTimeout(() => {
                this.overlay.remove();
            }, 300);
        }
        
        if (this.tooltip) {
            this.tooltip.remove();
        }
        
        this.isRunning = false;
    }

    markAsCompleted() {
        // サーバーにチュートリアル完了を記録
        fetch('/ajax/complete_tutorial.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'completed=1'
        });
    }
}

// グローバルインスタンスを作成
const readNestOnboarding = new ReadNestOnboarding();

// 登録完了ページでのみ自動起動
document.addEventListener('DOMContentLoaded', function() {
    // user_activate.phpページでのみ自動起動
    if (window.location.pathname === '/user_activate.php') {
        // data-activation-success属性がある場合のみ起動
        if (document.body.dataset.activationSuccess === 'true') {
            setTimeout(() => {
                readNestOnboarding.start();
            }, 1500);
        }
    }
});

// スタイルを追加（一度だけ）
if (!document.getElementById('onboarding-styles')) {
    const style = document.createElement('style');
    style.id = 'onboarding-styles';
    style.textContent = `
    .onboarding-tooltip {
        position: fixed !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
    }
    `;
    document.head.appendChild(style);
}