/**
 * AIアシスタント機能
 * 画面右下にオーバーレイ表示、コンテキスト認識、会話履歴保持
 */

class AIAssistant {
    constructor() {
        this.isOpen = false;
        this.isExpanded = false;
        this.messages = [];
        this.context = this.detectContext();
        this.isFirstTime = false;
        this.isLoading = false;
        this.conversationHistory = [];
        
        this.init();
        this.loadConversationHistory();
    }
    
    init() {
        // /reading_assistant.php ページではオーバーレイアシスタントを表示しない
        if (window.location.pathname === '/reading_assistant.php' || window.DISABLE_OVERLAY_ASSISTANT) {
            return;
        }
        
        this.createUI();
        this.attachEventListeners();
        this.checkFirstTime();
        this.setupPageChangeDetection();
    }
    
    detectContext() {
        const path = window.location.pathname;
        const pageData = {};
        
        if (path === '/' || path === '/index.php') {
            return { type: 'home', data: pageData };
        } else if (path.includes('bookshelf')) {
            // URLパラメータから検索情報を取得
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search_word')) {
                pageData.search_word = urlParams.get('search_word');
            }
            return { type: 'bookshelf', data: pageData };
        } else if (path.includes('book_detail') || path.includes('/book/')) {
            // 本のIDを取得
            const urlParams = new URLSearchParams(window.location.search);
            const pathMatch = path.match(/\/book\/(\d+)/);
            
            if (urlParams.has('book_id')) {
                pageData.book_id = urlParams.get('book_id');
            } else if (pathMatch) {
                pageData.book_id = pathMatch[1];
            }
            
            // ページから本の情報を取得
            this.extractBookInfo(pageData);
            
            return { type: 'book_detail', data: pageData };
        }
        
        return { type: 'general', data: pageData };
    }
    
    createUI() {
        // メインコンテナ
        const container = document.createElement('div');
        container.id = 'ai-assistant-container';
        
        // モバイルとデスクトップで位置を調整
        if (this.isMobile()) {
            // モバイル: 画面下部中央、ナビゲーションバーの上
            container.className = 'fixed z-50 bottom-16 right-4';
        } else {
            // デスクトップ: 画面右下、フッターの上
            container.className = 'fixed z-50 bottom-20 right-4';
        }
        
        // チャットウィンドウのスタイルも動的に設定
        const chatWindowStyle = this.isMobile() 
            ? 'width: 90vw; max-width: 384px; height: 70vh; max-height: 500px; position: fixed; bottom: 80px; right: 50%; transform: translateX(50%);'
            : 'width: 384px; height: calc(100vh - 200px); max-height: 500px; position: fixed; bottom: 100px; right: 20px;';
        
        container.innerHTML = `
            <!-- チャットボタン -->
            <div id="ai-assistant-button" class="bg-white border-2 border-readnest-primary hover:border-readnest-accent text-readnest-primary rounded-full p-4 shadow-lg cursor-pointer transition-all duration-300 hover:scale-110">
                <img src="/favicon.png" alt="AI Assistant" class="w-8 h-8">
            </div>
            
            <!-- チャットウィンドウ -->
            <div id="ai-assistant-window" class="hidden bg-white rounded-lg shadow-2xl flex flex-col transition-all duration-300" style="${chatWindowStyle}">
                <!-- ヘッダー -->
                <div class="bg-readnest-primary text-white p-4 rounded-t-lg flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <img src="/favicon.png" alt="AI Assistant" class="w-6 h-6">
                        <h3 class="font-semibold">読書アシスタント</h3>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="/reading_assistant.php" class="hover:bg-readnest-accent p-1 rounded transition-colors" title="読書アシスタントページへ">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                        <button id="ai-expand-btn" class="hover:bg-readnest-accent p-1 rounded transition-colors" title="拡大表示">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                            </svg>
                        </button>
                        <button id="ai-close-btn" class="hover:bg-readnest-accent p-1 rounded transition-colors" title="閉じる">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- メッセージエリア -->
                <div id="ai-messages" class="flex-1 overflow-y-auto p-4 space-y-4">
                    <!-- ウェルカムメッセージ -->
                    <div class="ai-message assistant">
                        <div class="flex items-start space-x-2">
                            <img src="/favicon.png" alt="AI" class="w-8 h-8 rounded-full">
                            <div class="bg-gray-100 rounded-lg p-3 max-w-[80%]">
                                <p class="text-sm">こんにちは！読書アシスタントです。読書に関することなら何でもお聞きください。</p>
                                <p class="text-xs text-gray-500 mt-2">より詳細なやり取りがしたい場合は<a href="/reading_assistant.php" class="text-readnest-primary hover:underline">読書アシスタントページ</a>へ</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 入力エリア -->
                <div class="border-t p-4 input-area">
                    <!-- サンプル質問ボタン -->
                    <div id="ai-sample-questions" class="mb-3 space-y-2"></div>
                    
                    <div class="flex space-x-2">
                        <input type="text" id="ai-input" placeholder="メッセージを入力..." 
                               class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary">
                        <button id="ai-send-btn" class="bg-readnest-primary hover:bg-readnest-accent text-white px-4 py-2 rounded-lg transition-colors">
                            送信
                        </button>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">
                        ${this.getContextHint()}
                    </div>
                </div>
            </div>
            
        `;
        
        document.body.appendChild(container);
    }
    
    extractBookInfo(pageData) {
        // メタタグから情報を取得（最も確実）
        const titleMeta = document.querySelector('meta[name="book-title"]');
        if (titleMeta) {
            pageData.title = titleMeta.content;
        }
        
        const authorMeta = document.querySelector('meta[name="book-author"]');
        if (authorMeta) {
            pageData.author = authorMeta.content;
        }
        
        const reviewMeta = document.querySelector('meta[name="user-review"]');
        if (reviewMeta && reviewMeta.content) {
            pageData.user_review = reviewMeta.content;
        }
        
        // ページから追加情報を取得
        // タイトル（メタタグから取得できなかった場合）
        if (!pageData.title) {
            const titleElement = document.querySelector('h1');
            if (titleElement) {
                pageData.title = titleElement.textContent.trim();
            }
        }
        
        // 出版社を取得
        const publisherElement = document.querySelector('dd');
        if (publisherElement) {
            const publisherLabel = Array.from(document.querySelectorAll('dt')).find(dt => dt.textContent.includes('出版社'));
            if (publisherLabel && publisherLabel.nextElementSibling) {
                pageData.publisher = publisherLabel.nextElementSibling.textContent.trim();
            }
        }
        
        // ISBNを取得
        const isbnLabel = Array.from(document.querySelectorAll('dt')).find(dt => dt.textContent.includes('ISBN'));
        if (isbnLabel && isbnLabel.nextElementSibling) {
            pageData.isbn = isbnLabel.nextElementSibling.textContent.trim();
        }
        
        // ページ数を取得
        const pagesLabel = Array.from(document.querySelectorAll('dt')).find(dt => dt.textContent.includes('ページ数'));
        if (pagesLabel && pagesLabel.nextElementSibling) {
            const pagesText = pagesLabel.nextElementSibling.textContent.trim();
            const pagesMatch = pagesText.match(/(\d+)/);
            if (pagesMatch) {
                pageData.pages = parseInt(pagesMatch[1]);
            }
        }
        
        // 評価を取得
        const ratingElement = document.querySelector('.rating');
        if (ratingElement) {
            const filledStars = ratingElement.querySelectorAll('.fa-star:not(.fa-star-o)').length;
            if (filledStars > 0) {
                pageData.rating = filledStars;
            }
        }
        
        // 読書ステータスを取得
        const statusElement = document.querySelector('select[name="status"]');
        if (statusElement) {
            const selectedOption = statusElement.options[statusElement.selectedIndex];
            pageData.status = selectedOption ? selectedOption.text : '';
            pageData.status_value = statusElement.value;
        }
        
        // 現在のページ数（進捗）を取得
        const progressElement = document.querySelector('input[name="current_page"]');
        if (progressElement) {
            pageData.current_page = progressElement.value;
        }
        
        // タグを取得
        const tagElements = document.querySelectorAll('.tag-item');
        if (tagElements.length > 0) {
            pageData.tags = Array.from(tagElements).map(el => el.textContent.trim()).filter(tag => tag && !tag.includes('×'));
        }
        
        // レビュー/メモを取得
        const memoElement = document.querySelector('textarea[name="memo"]');
        if (memoElement) {
            pageData.memo = memoElement.value;
        }
        
        // 画像URLを取得
        const imageElement = document.querySelector('.book-cover img, img[alt*="表紙"]');
        if (imageElement) {
            pageData.image_url = imageElement.src;
        }
    }
    
    getContextHint() {
        switch (this.context.type) {
            case 'home':
                return '💡 本の推薦や読書の相談をどうぞ';
            case 'bookshelf':
                return '📚 本棚の整理や読書計画についてご相談ください';
            case 'book_detail':
                if (this.context.data.title) {
                    return `📖 「${this.context.data.title}」について質問してみてください`;
                }
                return '📖 この本について質問してみてください';
            default:
                return '🤖 ReadNestの使い方や読書についてお聞きください';
        }
    }
    
    showSampleQuestions(customQuestions = null, isFallback = false) {
        const container = document.getElementById('ai-sample-questions');
        container.innerHTML = '';
        
        let questions = customQuestions || this.getDefaultQuestions();
        
        // フォールバックの場合は小さなインジケーターを表示
        if (isFallback) {
            const indicator = document.createElement('span');
            indicator.className = 'text-xs text-gray-400 mr-2';
            indicator.innerHTML = '💡 <span class="text-[10px]">提案</span>';
            indicator.title = 'AIが生成した質問ではなく、デフォルトの質問を表示しています';
            container.appendChild(indicator);
        }
        
        questions.forEach((question, index) => {
            const button = document.createElement('button');
            button.className = 'sample-question-btn';
            
            // 絵文字を保持しつつ表示
            button.textContent = question;
            
            button.addEventListener('click', () => {
                document.getElementById('ai-input').value = question;
                this.sendMessage();
            });
            container.appendChild(button);
        });
    }
    
    showSampleQuestionsLoading() {
        const container = document.getElementById('ai-sample-questions');
        container.innerHTML = '';
        
        // ローディングアニメーション
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'flex items-center space-x-2';
        loadingDiv.innerHTML = `
            <div class="sample-questions-loading">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="text-xs text-gray-500">質問を生成中...</span>
        `;
        container.appendChild(loadingDiv);
    }
    
    getDefaultQuestions() {
        switch (this.context.type) {
            case 'home':
                return [
                    '📚 おすすめの本を教えて',
                    '📊 今年読了した本は？',
                    '🔍 評価が4以上の本を見せて'
                ];
            case 'bookshelf':
                return [
                    '📊 積読の本を10冊教えて',
                    '📅 今月読了した本の一覧',
                    '⭐ 最高評価の本は？'
                ];
            case 'book_detail':
                return [
                    'この本について教えて',
                    'この本の特徴や見どころは？',
                    '似たような本を探したい'
                ];
            default:
                return [
                    '📚 読書中の本を表示',
                    '📊 今年の読了数は？',
                    '⭐ 評価が高い順に5冊'
                ];
        }
    }
    
    updateSampleQuestionsBasedOnResponse(responseText) {
        // ローディング表示
        this.showSampleQuestionsLoading();
        
        // OpenAI APIを使用してコンテキストに応じた質問を生成
        fetch('/api/ai_assistant.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'generate_questions',
                last_response: responseText,
                context: this.context.type,
                page_data: this.context.data
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.questions) {
                // 質問を少し遅延させて表示（スムーズな遷移のため）
                setTimeout(() => {
                    this.showSampleQuestions(data.questions, data.is_fallback);
                }, 300);
            } else {
                // エラーの場合はデフォルトの質問を表示
                console.warn('Failed to generate AI questions, using default questions');
                this.showSampleQuestions(this.getDefaultQuestions(), true);
            }
        })
        .catch(error => {
            console.error('Failed to generate questions:', error);
            // エラーの場合はデフォルトの質問を表示
            this.showSampleQuestions(this.getDefaultQuestions(), true);
        });
    }
    
    attachEventListeners() {
        // チャットボタンクリック
        document.getElementById('ai-assistant-button').addEventListener('click', () => {
            this.open();
        });
        
        // 閉じるボタン
        document.getElementById('ai-close-btn').addEventListener('click', () => {
            this.close();
        });
        
        // 拡大ボタン
        document.getElementById('ai-expand-btn').addEventListener('click', () => {
            this.toggleExpand();
        });
        
        // 送信ボタン
        document.getElementById('ai-send-btn').addEventListener('click', () => {
            this.sendMessage();
        });
        
        // Enterキーで送信
        document.getElementById('ai-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
    }
    
    open() {
        this.isOpen = true;
        document.getElementById('ai-assistant-button').classList.add('hidden');
        const window = document.getElementById('ai-assistant-window');
        window.classList.remove('hidden');
        
        // モバイルデバイスでの処理
        if (this.isMobile()) {
            // モバイル用のスタイルはCSSで定義済み
            this.setupMobileKeyboardHandling();
            // モバイルでは自動フォーカスを避ける（キーボードが勝手に出るのを防ぐ）
            setTimeout(() => {
                document.getElementById('ai-input').focus();
            }, 300);
        } else {
            document.getElementById('ai-input').focus();
        }
        
        // 初回起動時のオンボーディング
        if (this.isFirstTime) {
            this.showOnboarding();
        } else {
            // サンプル質問ボタンを表示（初回以外）
            this.showSampleQuestions();
        }
    }
    
    close() {
        this.isOpen = false;
        this.isExpanded = false;
        document.getElementById('ai-assistant-window').classList.add('hidden');
        document.getElementById('ai-assistant-window').classList.remove('keyboard-open');
        document.getElementById('ai-assistant-button').classList.remove('hidden');
        
        // 拡大状態を元に戻す
        const window = document.getElementById('ai-assistant-window');
        window.style.width = '384px';
        window.style.height = 'calc(100vh - 200px)';
        window.style.maxHeight = '500px';
        window.style.position = '';
        window.style.top = '';
        window.style.right = '';
        window.style.bottom = '';
        window.style.left = '';
        window.style.transform = '';
        window.style.zIndex = '';
        
        // モバイルのイベントリスナーをクリーンアップ
        if (this.isMobile()) {
            this.cleanupMobileKeyboardHandling();
        }
    }
    
    toggleExpand() {
        const window = document.getElementById('ai-assistant-window');
        const expandBtn = document.getElementById('ai-expand-btn');
        
        if (!this.isExpanded) {
            // 拡大表示
            this.isExpanded = true;
            window.style.position = 'fixed';
            
            if (this.isMobile()) {
                // モバイル: 全画面表示
                window.style.top = '10px';
                window.style.right = '10px';
                window.style.bottom = '10px';
                window.style.left = '10px';
                window.style.width = 'calc(100vw - 20px)';
                window.style.transform = 'none';
            } else {
                // デスクトップ: 右半分
                window.style.top = '20px';
                window.style.right = '20px';
                window.style.bottom = '20px';
                window.style.left = '50%';
                window.style.width = 'calc(50% - 40px)';
                window.style.transform = 'none';
            }
            
            window.style.height = 'auto';
            window.style.maxHeight = 'calc(100vh - 40px)';
            window.style.zIndex = '9999';
            
            // アイコンを縮小に変更（内向きの4方向矢印）
            expandBtn.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5v4m0 0H5m4 0l-4-4m10 0v4m0 0h4m0-4l-4 4M5 15h4m0 0v4m0-4l-4 4m14-4h-4m0 0v4m0-4l4 4"></path>
                </svg>
            `;
            expandBtn.title = '元のサイズに戻す';
        } else {
            // 元のサイズに戻す
            this.isExpanded = false;
            
            if (this.isMobile()) {
                // モバイル: 元のモバイルサイズに戻す
                window.style.position = 'fixed';
                window.style.top = '';
                window.style.right = '50%';
                window.style.bottom = '80px';
                window.style.left = '';
                window.style.width = '90vw';
                window.style.maxWidth = '384px';
                window.style.height = '70vh';
                window.style.maxHeight = '500px';
                window.style.transform = 'translateX(50%)';
            } else {
                // デスクトップ: 元のサイズに戻す
                window.style.position = '';
                window.style.top = '';
                window.style.right = '';
                window.style.bottom = '';
                window.style.left = '';
                window.style.width = '384px';
                window.style.height = 'calc(100vh - 200px)';
                window.style.maxHeight = '500px';
                window.style.transform = '';
            }
            window.style.zIndex = '';
            
            // アイコンを拡大に変更
            expandBtn.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                </svg>
            `;
            expandBtn.title = '拡大表示';
        }
    }
    
    async sendMessage() {
        const input = document.getElementById('ai-input');
        const message = input.value.trim();
        
        if (!message || this.isLoading) return;
        
        // ユーザーメッセージを追加
        this.addMessage(message, 'user');
        input.value = '';
        
        // ローディング表示
        this.showLoading();
        
        try {
            // 現在のコンテキストを再取得（最新の情報を確実に送信）
            this.context = this.detectContext();
            
            // APIエンドポイント（Text2SQL対応の新しいエンドポイント）
            const apiEndpoint = '/api/ai_assistant_chat.php';
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    context: this.context.type,
                    page_data: this.context.data,
                    conversation_history: this.conversationHistory.slice(-10) // 最近の10件のメッセージをlocalStorageから送信
                })
            });
            
            const responseText = await response.text();
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Response Text:', responseText);
                throw new Error('サーバーからの応答が正しくありません');
            }
            
            if (!response.ok) {
                // 404エラーの場合の特別処理
                if (response.status === 404) {
                    console.error('AI Assistant API not found. Using fallback.');
                    // フォールバック応答を直接生成
                    data = {
                        response: this.getFallbackMessage(message),
                        fallback: true,
                        is_first_time: this.isFirstTime,
                        context: this.context.type
                    };
                } else {
                    throw new Error(data.error || `HTTPエラー: ${response.status}`);
                }
            }
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            // AIの応答を追加
            this.addMessage(data.response, 'assistant');
            
            // 初回フラグを更新
            if (data.is_first_time) {
                this.isFirstTime = false;
            }
            
            // AIの回答に基づいてサンプル質問を更新
            this.updateSampleQuestionsBasedOnResponse(data.response);
            
        } catch (error) {
            console.error('AI Assistant Error:', error);
            let errorMessage = '申し訳ございません。エラーが発生しました。';
            
            if (error.message) {
                errorMessage += `\n\n詳細: ${error.message}`;
            }
            
            // ネットワークエラーの場合
            if (!navigator.onLine) {
                errorMessage = 'インターネット接続を確認してください。';
            }
            
            this.addMessage(errorMessage, 'assistant');
        } finally {
            this.hideLoading();
        }
    }
    
    addMessage(text, sender) {
        const messagesContainer = document.getElementById('ai-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-message ${sender}`;
        
        if (sender === 'user') {
            messageDiv.innerHTML = `
                <div class="flex items-start space-x-2 justify-end">
                    <div class="bg-readnest-primary text-white rounded-xl p-3 max-w-[80%]">
                        <p class="text-sm">${this.escapeHtml(text)}</p>
                    </div>
                </div>
            `;
        } else if (sender === 'system') {
            messageDiv.innerHTML = `
                <div class="text-center text-gray-500 text-xs my-2">
                    <p>${this.escapeHtml(text)}</p>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="flex items-start space-x-2">
                    <img src="/favicon.png" alt="AI" class="w-8 h-8 rounded-full">
                    <div class="bg-gray-100 rounded-xl p-3 max-w-[80%]">
                        <p class="text-sm">${this.formatAIResponse(text)}</p>
                    </div>
                </div>
            `;
        }
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // メッセージを履歴に追加
        this.messages.push({ text, sender, timestamp: new Date() });
        
        // 会話履歴をlocalStorageに保存（システムメッセージは除外）
        if (sender !== 'system') {
            this.conversationHistory.push({
                role: sender,
                content: text,
                timestamp: new Date().toISOString()
            });
            this.saveConversationHistory();
        }
    }
    
    loadConversationHistory() {
        try {
            const stored = localStorage.getItem('aiAssistantHistory');
            if (stored) {
                this.conversationHistory = JSON.parse(stored);
            }
        } catch (e) {
            console.error('Failed to load conversation history:', e);
        }
    }
    
    saveConversationHistory() {
        try {
            // 最新50件のみ保存（フローティングアシスタント用なので少なめ）
            const toSave = this.conversationHistory.slice(-50);
            localStorage.setItem('aiAssistantHistory', JSON.stringify(toSave));
        } catch (e) {
            console.error('Failed to save conversation history:', e);
        }
    }
    
    showLoading() {
        this.isLoading = true;
        const messagesContainer = document.getElementById('ai-messages');
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'ai-loading';
        loadingDiv.className = 'ai-message assistant';
        loadingDiv.innerHTML = `
            <div class="flex items-start space-x-2">
                <img src="/favicon.png" alt="AI" class="w-8 h-8 rounded-full">
                <div class="bg-gray-100 rounded-lg p-3">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        `;
        messagesContainer.appendChild(loadingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    hideLoading() {
        this.isLoading = false;
        const loadingDiv = document.getElementById('ai-loading');
        if (loadingDiv) {
            loadingDiv.remove();
        }
    }
    
    async checkFirstTime() {
        // サーバーから初回ログインフラグをチェック（PHPで設定される）
        if (window.isFirstLogin === true) {
            this.isFirstTime = true;
            // オンボーディングを自動的に開始
            setTimeout(() => {
                this.open();
                this.showOnboarding();
            }, 1000);
        } else if (!sessionStorage.getItem('ai_assistant_shown')) {
            // セッション初回表示時にパルスアニメーション
            const button = document.getElementById('ai-assistant-button');
            if (button) {
                button.classList.add('animate-pulse');
                setTimeout(() => {
                    button.classList.remove('animate-pulse');
                }, 3000);
            }
            sessionStorage.setItem('ai_assistant_shown', 'true');
        }
    }
    
    showOnboarding() {
        const onboardingMessage = `
ようこそReadNestへ！🎉

私はあなたの読書をサポートするAIアシスタントです。

まず最初に、ReadNestの基本的な使い方をご紹介しますね：

1. **本を登録する** 📚
   - 検索バーから本を検索
   - 手動で本の情報を入力

2. **読書状態を管理** 📖
   - 「読みたい」「読んでる」「読了」で分類
   - 読書進捗をページ数で記録

3. **レビューを書く** ✍️
   - 5段階評価とコメントで記録
   - 他のユーザーと感想を共有

4. **読書目標を設定** 🎯
   - 年間読書目標を立てる
   - 進捗をグラフで確認

さっそく最初の本を登録してみませんか？
本の検索方法や、おすすめの本などもご案内できます！

何か質問があれば、いつでもお聞きください。
        `;
        this.addMessage(onboardingMessage, 'assistant');
    }
    
    getRecentMessages(count = 5) {
        // 最近のメッセージを取得（システムメッセージは除外）
        const recentMessages = this.messages
            .filter(msg => !msg.text.includes('のページに移動しました')) // ページ移動メッセージを除外
            .slice(-count * 2); // user/assistantのペアを考慮
        
        return recentMessages.map(msg => ({
            role: msg.sender === 'user' ? 'user' : 'assistant',
            content: msg.text
        }));
    }
    
    setupPageChangeDetection() {
        // ページ変更を検出する
        let lastUrl = window.location.href;
        
        // pushState/replaceStateをフック
        const originalPushState = history.pushState;
        const originalReplaceState = history.replaceState;
        
        history.pushState = (...args) => {
            originalPushState.apply(history, args);
            this.onPageChange();
        };
        
        history.replaceState = (...args) => {
            originalReplaceState.apply(history, args);
            this.onPageChange();
        };
        
        // popstateイベント（戻る/進むボタン）
        window.addEventListener('popstate', () => {
            this.onPageChange();
        });
        
        // 定期的にURLの変更をチェック（フォールバック）
        setInterval(() => {
            if (window.location.href !== lastUrl) {
                lastUrl = window.location.href;
                this.onPageChange();
            }
        }, 1000);
    }
    
    onPageChange() {
        // コンテキストを再検出
        const newContext = this.detectContext();
        
        // コンテキストが変わった場合、またはbook_detailページの場合は更新
        if (JSON.stringify(this.context) !== JSON.stringify(newContext) || newContext.type === 'book_detail') {
            this.context = newContext;
            
            // ヒントテキストを更新
            const hintElement = document.querySelector('#ai-assistant-window .text-xs.text-gray-500');
            if (hintElement) {
                hintElement.textContent = this.getContextHint();
            }
            
            // book_detailページで本が変わった場合、会話をリセットするか確認
            if (newContext.type === 'book_detail' && newContext.data.title && this.isOpen) {
                // 前の本のタイトルと異なる場合
                const lastBookTitle = this.lastBookTitle || '';
                if (lastBookTitle && lastBookTitle !== newContext.data.title) {
                    // 会話履歴に区切りメッセージを追加
                    this.addMessage(`--- 「${newContext.data.title}」のページに移動しました ---`, 'system');
                    // セッション情報をクリア（AIが前の本の情報を参照しないように）
                    this.clearSessionContext();
                }
                this.lastBookTitle = newContext.data.title;
            }
        }
    }
    
    clearSessionContext() {
        // APIのセッション情報をクリアするためのリクエストを送信
        fetch('/api/ai_assistant.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'clear_context',
                context: this.context.type,
                page_data: this.context.data
            })
        }).catch(error => {
            console.error('Failed to clear session context:', error);
        });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatAIResponse(text) {
        let formatted = text;
        
        // 既存の<br>タグを一時的に改行に戻す（AIからの応答に<br>が含まれている場合）
        formatted = formatted.replace(/<br>/g, '\n');
        
        // 処理済みの部分を保護するためのプレースホルダー
        const placeholders = [];
        let placeholderIndex = 0;
        
        // HTMLタグ（details, summary）を先に処理して保護（Text2SQL用）
        formatted = formatted.replace(/<details[^>]*>([\s\S]*?)<\/details>/g, (match) => {
            const placeholder = `__PLACEHOLDER_${placeholderIndex++}__`;
            placeholders.push(match);
            return placeholder;
        });
        
        // コードブロック（```code```）を先に処理して保護
        formatted = formatted.replace(/```(\w*)\n?([\s\S]*?)```/g, (match, lang, code) => {
            const escapedCode = this.escapeHtml(code.trim());
            const placeholder = `__PLACEHOLDER_${placeholderIndex++}__`;
            placeholders.push(`<pre class="bg-gray-100 p-3 rounded overflow-x-auto my-2"><code>${escapedCode}</code></pre>`);
            return placeholder;
        });
        
        // インラインコード（`code`）を処理して保護
        formatted = formatted.replace(/`([^`]+)`/g, (match, code) => {
            const placeholder = `__PLACEHOLDER_${placeholderIndex++}__`;
            placeholders.push(`<code class="bg-gray-100 px-1 py-0.5 rounded text-sm">${this.escapeHtml(code)}</code>`);
            return placeholder;
        });
        
        // マークダウンリンク [text](url) を処理
        formatted = formatted.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (match, text, url) => {
            const placeholder = `__PLACEHOLDER_${placeholderIndex++}__`;
            placeholders.push(`<a href="${url}" target="_blank" class="text-readnest-primary hover:underline">${text}</a>`);
            return placeholder;
        });
        
        // 見出し（# ## ###）を変換 - 行頭のみ
        formatted = formatted.replace(/^### (.+)$/gm, '<h3 class="font-semibold text-lg mt-3 mb-1">$1</h3>');
        formatted = formatted.replace(/^## (.+)$/gm, '<h2 class="font-bold text-xl mt-4 mb-2">$1</h2>');
        formatted = formatted.replace(/^# (.+)$/gm, '<h1 class="font-bold text-2xl mt-4 mb-2">$1</h1>');
        
        // 太字（**text**）を変換
        formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        // イタリック（*text*）を変換 - 太字と区別するため単一アスタリスクのみ
        // 前後が*でないことを確認
        formatted = formatted.replace(/(\s|^)\*([^*\n]+)\*(\s|$)/g, '$1<em>$2</em>$3');
        
        // アンダースコアのイタリック（_text_）
        formatted = formatted.replace(/(\s|^)_([^_\n]+)_(\s|$)/g, '$1<em>$2</em>$3');
        
        // 本の名前を検索リンクに変換（「」で囲まれたテキスト）
        formatted = formatted.replace(/「([^」]+)」/g, (match, bookTitle) => {
            const encodedTitle = encodeURIComponent(bookTitle);
            const placeholder = `__PLACEHOLDER_${placeholderIndex++}__`;
            placeholders.push(`<a href="/add_book.php?keyword=${encodedTitle}" class="text-readnest-primary hover:underline font-medium" target="_blank" title="${bookTitle}を検索">「${bookTitle}」</a>`);
            return placeholder;
        });
        
        // 単独のURL（プレースホルダー化されていないもの）を検出して変換
        formatted = formatted.replace(
            /(\b(?:https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim,
            (match) => {
                // 既にリンクタグ内にある場合はスキップ
                if (match.includes('__PLACEHOLDER_')) {
                    return match;
                }
                return `<a href="${match}" target="_blank" class="text-readnest-primary hover:underline">${match}</a>`;
            }
        );
        
        // 箇条書きを処理（連続する箇条書き項目をグループ化）
        const lines = formatted.split('\n');
        const processedLines = [];
        let inUList = false;
        let inOList = false;
        let listItems = [];
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            // 番号なしリスト（-, •）
            const uListMatch = line.match(/^[•\-]\s(.+)$/);
            // 番号付きリスト（1. 2. など）
            const oListMatch = line.match(/^\d+\.\s(.+)$/);
            
            if (uListMatch) {
                if (inOList && listItems.length > 0) {
                    // 番号付きリストを終了
                    processedLines.push(`<ol class="list-decimal list-inside mt-2 mb-2 space-y-1">${listItems.join('')}</ol>`);
                    listItems = [];
                    inOList = false;
                }
                if (!inUList) {
                    inUList = true;
                }
                listItems.push(`<li>${uListMatch[1]}</li>`);
            } else if (oListMatch) {
                if (inUList && listItems.length > 0) {
                    // 番号なしリストを終了
                    processedLines.push(`<ul class="list-disc list-inside mt-2 mb-2 space-y-1">${listItems.join('')}</ul>`);
                    listItems = [];
                    inUList = false;
                }
                if (!inOList) {
                    inOList = true;
                }
                listItems.push(`<li>${oListMatch[1]}</li>`);
            } else {
                // リストが終了した場合
                if (inUList && listItems.length > 0) {
                    processedLines.push(`<ul class="list-disc list-inside mt-2 mb-2 space-y-1">${listItems.join('')}</ul>`);
                    listItems = [];
                    inUList = false;
                } else if (inOList && listItems.length > 0) {
                    processedLines.push(`<ol class="list-decimal list-inside mt-2 mb-2 space-y-1">${listItems.join('')}</ol>`);
                    listItems = [];
                    inOList = false;
                }
                processedLines.push(line);
            }
        }
        
        // 最後にリストが残っている場合
        if (inUList && listItems.length > 0) {
            processedLines.push(`<ul class="list-disc list-inside mt-2 mb-2 space-y-1">${listItems.join('')}</ul>`);
        } else if (inOList && listItems.length > 0) {
            processedLines.push(`<ol class="list-decimal list-inside mt-2 mb-2 space-y-1">${listItems.join('')}</ol>`);
        }
        
        formatted = processedLines.join('\n');
        
        // 空行を<br><br>に変換（段落の区切り）
        formatted = formatted.replace(/\n\n/g, '<br><br>');
        
        // 残りの改行を<br>に変換（HTMLタグの直後を除く）
        formatted = formatted.split('\n').map((line, index, array) => {
            // 最後の行でない場合
            if (index < array.length - 1) {
                // HTMLブロックタグで終わっている場合は<br>を追加しない
                if (line.match(/<\/(h[1-3]|ul|pre|div|p)>$/)) {
                    return line;
                }
                return line + '<br>';
            }
            return line;
        }).join('\n');
        
        // プレースホルダーを元に戻す
        placeholders.forEach((content, index) => {
            formatted = formatted.replace(`__PLACEHOLDER_${index}__`, content);
        });
        
        return formatted;
    }
    
    getFallbackMessage(message) {
        const messageLower = message.toLowerCase();
        
        // 挨拶への応答
        if (messageLower.includes('こんにちは') || messageLower.includes('はじめまして')) {
            return `こんにちは！ReadNest AIアシスタントです。

現在、AI機能が一時的に制限されていますが、以下のお手伝いができます：
• 本の検索・追加方法の案内
• 読書記録の管理方法
• 基本的な使い方の説明

何かお困りのことがあれば、お聞きください。`;
        }
        
        // ヘルプ要求への応答
        if (messageLower.includes('使い方') || messageLower.includes('ヘルプ') || messageLower.includes('help')) {
            return `ReadNestの主要機能：

📚 本の管理
• 検索して追加、または手動で追加
• 読書ステータスの管理
• 進捗記録

⭐ レビュー・評価
• 5段階評価
• 感想の記録

🏷️ タグ機能
• 本の分類
• タグで検索

🗺️ 読書マップ
• 視覚的な読書傾向表示
• 著者別・タグ別表示

詳しくはヘルプページをご確認ください。`;
        }
        
        // コンテキストに応じた応答
        switch (this.context.type) {
            case 'bookshelf':
                return `本棚の管理についてお答えします。

本棚では以下のことができます：
• ステータスで絞り込み（未読、読書中など）
• キーワードで検索
• タグで分類
• 読書マップで全体を把握

効率的な管理のコツ：
• タグを活用して整理
• 定期的に進捗を更新
• レビューを残して記録`;
                
            case 'book_detail':
                return `この本について何かお手伝いできることがあれば教えてください。

本の詳細ページでは：
• 読書ステータスの変更
• 進捗の記録（ページ数）
• レビューの投稿
• タグの追加・編集
• 評価（5段階）

が可能です。`;
                
            default:
                return `申し訳ございません。現在、AIアシスタントが一時的に利用できません。

ReadNestの使い方については以下をご確認ください：
• 本の追加：ヘッダーの検索ボックスまたは「本を追加」ページから
• 読書管理：本の詳細ページでステータスや進捗を更新
• レビュー：読了後に感想を記録

ヘルプページもご活用ください。`;
        }
    }
    
    // モバイルデバイス判定
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
               window.innerWidth <= 768;
    }
    
    // モバイルキーボード対応のセットアップ
    setupMobileKeyboardHandling() {
        const window = document.getElementById('ai-assistant-window');
        const input = document.getElementById('ai-input');
        
        // 入力欄にフォーカスしたときの処理
        this.inputFocusHandler = () => {
            setTimeout(() => {
                window.classList.add('keyboard-open');
                // ビューポートの変更を検出してウィンドウを調整
                this.adjustWindowForKeyboard();
            }, 100);
        };
        
        // 入力欄からフォーカスが外れたときの処理
        this.inputBlurHandler = () => {
            setTimeout(() => {
                window.classList.remove('keyboard-open');
            }, 100);
        };
        
        // ビューポートサイズ変更の検出（キーボード表示/非表示）
        this.viewportHandler = () => {
            if (document.activeElement === input) {
                this.adjustWindowForKeyboard();
            }
        };
        
        input.addEventListener('focus', this.inputFocusHandler);
        input.addEventListener('blur', this.inputBlurHandler);
        window.visualViewport?.addEventListener('resize', this.viewportHandler);
    }
    
    // モバイルキーボード対応のクリーンアップ
    cleanupMobileKeyboardHandling() {
        const input = document.getElementById('ai-input');
        
        if (this.inputFocusHandler) {
            input.removeEventListener('focus', this.inputFocusHandler);
        }
        if (this.inputBlurHandler) {
            input.removeEventListener('blur', this.inputBlurHandler);
        }
        if (this.viewportHandler) {
            window.visualViewport?.removeEventListener('resize', this.viewportHandler);
        }
    }
    
    // キーボード表示時のウィンドウ調整
    adjustWindowForKeyboard() {
        const window = document.getElementById('ai-assistant-window');
        const input = document.getElementById('ai-input');
        
        if (window.visualViewport) {
            // Visual Viewport APIが利用可能な場合
            const keyboardHeight = window.innerHeight - window.visualViewport.height;
            if (keyboardHeight > 50) {
                // キーボードが表示されている
                window.style.bottom = `${keyboardHeight}px`;
                // 入力欄が見えるようにスクロール
                input.scrollIntoView({ behavior: 'smooth', block: 'end' });
            }
        } else {
            // フォールバック: タイムアウトで高さ調整
            setTimeout(() => {
                input.scrollIntoView({ behavior: 'smooth', block: 'end' });
            }, 300);
        }
    }
}

// DOMContentLoadedでAIアシスタントを初期化
document.addEventListener('DOMContentLoaded', () => {
    // ログインチェック（セッション変数から判定）
    if (typeof window.isLoggedIn !== 'undefined' && window.isLoggedIn) {
        window.aiAssistant = new AIAssistant();
    }
});