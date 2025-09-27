/**
 * AI自然言語検索のフロントエンド実装
 */

class AISearch {
    constructor() {
        this.searchInput = null;
        this.resultsContainer = null;
        this.searchTimeout = null;
        this.init();
    }
    
    init() {
        // 検索フォームを拡張
        this.enhanceSearchForm();
        
        // イベントリスナー設定
        this.attachEventListeners();
    }
    
    enhanceSearchForm() {
        // add_book.phpページでは、既にHTMLにチェックボックスがあるので追加しない
        if (window.location.pathname === '/add_book.php') {
            return;
        }
        
        // その他のページの検索フォームにAI検索オプションを追加
        const searchForms = document.querySelectorAll('form[action="/add_book.php"]');
        
        searchForms.forEach(form => {
            // 既にAI検索トグルがある場合はスキップ
            if (form.querySelector('#use-ai-search')) {
                return;
            }
            
            // AI検索トグルを追加
            const aiToggle = document.createElement('div');
            aiToggle.className = 'ai-search-toggle mt-2';
            aiToggle.innerHTML = `
                <label class="flex items-center text-sm cursor-pointer">
                    <input type="checkbox" id="use-ai-search" name="ai_search" class="mr-2">
                    <span>AI検索を使用（例：「泣ける恋愛小説」「夏に読みたい本」）</span>
                </label>
            `;
            form.appendChild(aiToggle);
        });
    }
    
    attachEventListeners() {
        // AI検索チェックボックス - 直接要素に対してイベントを設定
        const aiCheckbox = document.getElementById('use-ai-search');
        if (aiCheckbox) {
            aiCheckbox.addEventListener('change', (e) => {
                e.stopPropagation();
                this.toggleAISearch(e.target.checked);
            });
            
            // 初期状態を反映
            this.toggleAISearch(aiCheckbox.checked);
        }
        
        // 検索入力フィールド
        const searchInputs = document.querySelectorAll('input[name="keyword"]');
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                const isAISearch = document.getElementById('use-ai-search')?.checked || false;
                this.handleSearchInput(e.target, isAISearch);
            });
            
            // フォーカスが外れたらサジェストを非表示
            input.addEventListener('blur', () => {
                setTimeout(() => {
                    this.removeExistingSuggestions();
                }, 200); // クリックイベントが先に処理されるように遅延
            });
        });
    }
    
    toggleAISearch(enabled) {
        const searchInputs = document.querySelectorAll('input[name="keyword"]');
        
        searchInputs.forEach(input => {
            if (enabled) {
                input.placeholder = '例：泣ける恋愛小説、元気が出るビジネス書...';
                input.setAttribute('data-ai-search', 'true');
            } else {
                input.placeholder = '本を検索...';
                input.removeAttribute('data-ai-search');
            }
        });
    }
    
    handleSearchInput(input, isAISearch) {
        // デバウンス処理
        clearTimeout(this.searchTimeout);
        
        const query = input.value.trim();
        if (query.length < 2) {
            this.removeExistingSuggestions();
            return;
        }
        
        // ローディング表示を即座に表示
        this.showLoadingIndicator(input, isAISearch);
        
        // 検索サジェストを表示
        this.searchTimeout = setTimeout(() => {
            if (isAISearch) {
                this.showAISearchSuggestions(input, query);
            } else {
                this.showNormalSearchSuggestions(input, query);
            }
        }, 300);
    }
    
    async showAISearchSuggestions(input, query) {
        try {
            // AI検索の自然言語候補を取得
            const response = await fetch(`/api/ai_search_api.php?q=${encodeURIComponent(query)}&autocomplete=1`);
            
            // レスポンスヘッダーをチェック
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // JSONでない場合はテキストとして取得してログに出力
                const text = await response.text();
                console.error('AI search API returned non-JSON response:', text.substring(0, 200));
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.suggestions && data.suggestions.length > 0) {
                this.displayAISuggestions(input, data.suggestions);
            } else {
                this.removeExistingSuggestions();
            }
        } catch (error) {
            console.error('AI search error:', error);
            this.removeExistingSuggestions();
        }
    }
    
    async showNormalSearchSuggestions(input, query) {
        try {
            // 通常検索の候補を取得
            const response = await fetch(`/api/search_suggestions_api.php?q=${encodeURIComponent(query)}`);
            
            // レスポンスヘッダーをチェック
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Search suggestions API returned non-JSON response:', text.substring(0, 200));
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.suggestions && data.suggestions.length > 0) {
                this.displayNormalSuggestions(input, data.suggestions);
            } else {
                this.removeExistingSuggestions();
            }
        } catch (error) {
            console.error('Search suggestions error:', error);
            this.removeExistingSuggestions();
        }
    }
    
    removeExistingSuggestions() {
        const existingSuggest = document.getElementById('ai-search-suggestions');
        if (existingSuggest) {
            existingSuggest.remove();
        }
    }
    
    displayNoResults(input, query) {
        this.removeExistingSuggestions();
        
        const noResultsContainer = document.createElement('div');
        noResultsContainer.id = 'ai-search-suggestions';
        noResultsContainer.className = 'absolute bg-white border rounded-lg shadow-lg mt-1 w-full z-50';
        
        const noResultsDiv = document.createElement('div');
        noResultsDiv.className = 'px-4 py-3 text-center';
        noResultsDiv.innerHTML = `
            <div class="text-gray-500">
                <i class="fas fa-search text-gray-400 mb-2"></i>
                <p class="text-sm">「${this.escapeHtml(query)}」に該当する本が見つかりませんでした</p>
                <p class="text-xs text-gray-400 mt-1">別のキーワードをお試しください</p>
            </div>
        `;
        
        noResultsContainer.appendChild(noResultsDiv);
        
        const parent = input.parentElement;
        parent.style.position = 'relative';
        parent.appendChild(noResultsContainer);
    }
    
    showLoadingIndicator(input, isAISearch) {
        this.removeExistingSuggestions();
        
        const loadingContainer = document.createElement('div');
        loadingContainer.id = 'ai-search-suggestions';
        loadingContainer.className = 'absolute bg-white border rounded-lg shadow-lg mt-1 w-full z-50';
        
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'px-4 py-3 flex items-center justify-center';
        loadingDiv.innerHTML = `
            <div class="flex items-center space-x-2">
                <svg class="animate-spin h-5 w-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600">${isAISearch ? 'AI検索候補を取得中...' : '検索候補を取得中...'}</span>
            </div>
        `;
        
        loadingContainer.appendChild(loadingDiv);
        
        // 入力フィールドの下に配置
        const inputRect = input.getBoundingClientRect();
        const parent = input.parentElement;
        parent.style.position = 'relative';
        parent.appendChild(loadingContainer);
    }
    
    displaySuggestions(input, data) {
        // 既存のサジェストを削除（ローディング含む）
        this.removeExistingSuggestions();
        
        // サジェストコンテナを作成
        const suggestContainer = document.createElement('div');
        suggestContainer.id = 'ai-search-suggestions';
        suggestContainer.className = 'absolute bg-white border rounded-lg shadow-lg mt-1 w-full z-50';
        
        // 検索意図を表示
        if (data.detected_intent.length > 0) {
            const intentDiv = document.createElement('div');
            intentDiv.className = 'px-4 py-2 text-sm text-gray-600 border-b';
            intentDiv.innerHTML = `
                <span class="font-medium">検索意図:</span>
                ${this.formatIntent(data.detected_intent)}
            `;
            suggestContainer.appendChild(intentDiv);
        }
        
        // 本のリストを表示
        data.books.forEach(book => {
            const bookDiv = document.createElement('div');
            bookDiv.className = 'flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer border-b';
            bookDiv.innerHTML = `
                <img src="${book.image_url}" alt="${book.title}" class="w-12 h-16 object-cover mr-3">
                <div class="flex-1">
                    <div class="font-medium text-sm">${this.escapeHtml(book.title)}</div>
                    <div class="text-xs text-gray-600">${this.escapeHtml(book.author)}</div>
                    ${book.ai_relevance_score > 80 ? '<span class="text-xs text-green-600">高関連性</span>' : ''}
                </div>
            `;
            
            bookDiv.addEventListener('click', () => {
                window.location.href = `/add_book.php?keyword=${encodeURIComponent(book.title)}`;
            });
            
            suggestContainer.appendChild(bookDiv);
        });
        
        // すべて見るリンク
        const viewAllDiv = document.createElement('div');
        viewAllDiv.className = 'px-4 py-3 text-center text-sm text-blue-600 hover:bg-gray-50 cursor-pointer';
        viewAllDiv.innerHTML = 'すべての結果を見る →';
        viewAllDiv.addEventListener('click', () => {
            this.submitAISearch(input.form, data.query);
        });
        suggestContainer.appendChild(viewAllDiv);
        
        // フォームの下に追加
        input.parentElement.style.position = 'relative';
        input.parentElement.appendChild(suggestContainer);
        
        // クリック外で閉じる
        document.addEventListener('click', (e) => {
            if (!input.parentElement.contains(e.target)) {
                suggestContainer.remove();
            }
        }, { once: true });
    }
    
    displayNormalSuggestions(input, suggestions) {
        // 既存のサジェストを削除（ローディング含む）
        this.removeExistingSuggestions();
        
        // サジェストコンテナを作成
        const suggestContainer = document.createElement('div');
        suggestContainer.id = 'ai-search-suggestions';
        suggestContainer.className = 'absolute bg-white border rounded-lg shadow-lg mt-1 w-full z-50';
        
        // 候補を表示
        suggestions.forEach((item, index) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'px-4 py-3 hover:bg-gray-50 cursor-pointer';
            if (index < suggestions.length - 1) {
                itemDiv.className += ' border-b';
            }
            
            if (item.type === 'book') {
                // 本のタイトル候補
                itemDiv.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-book text-gray-400 mr-3"></i>
                        <div class="flex-1">
                            <div class="font-medium text-sm">${this.escapeHtml(item.title)}</div>
                            ${item.author ? `<div class="text-xs text-gray-600">${this.escapeHtml(item.author)}</div>` : ''}
                        </div>
                    </div>
                `;
            } else {
                // 検索パターン候補
                itemDiv.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-search text-gray-400 mr-3"></i>
                        <div class="text-sm">${this.escapeHtml(item.title)}</div>
                    </div>
                `;
            }
            
            itemDiv.addEventListener('click', () => {
                input.value = item.title;
                this.removeExistingSuggestions();
                // フォームを送信
                const form = input.closest('form');
                if (form) {
                    // submitイベントを発火させるためにrequestSubmitを使用
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        // 古いブラウザ用のフォールバック
                        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
                        form.dispatchEvent(submitEvent);
                        if (!submitEvent.defaultPrevented) {
                            form.submit();
                        }
                    }
                }
            });
            
            suggestContainer.appendChild(itemDiv);
        });
        
        // フォームの下に追加
        input.parentElement.style.position = 'relative';
        input.parentElement.appendChild(suggestContainer);
        
        // クリック外で閉じる
        document.addEventListener('click', (e) => {
            if (!input.parentElement.contains(e.target)) {
                suggestContainer.remove();
            }
        }, { once: true });
    }
    
    displayAISuggestions(input, suggestions) {
        // 既存のサジェストを削除（ローディング含む）
        this.removeExistingSuggestions();
        
        // サジェストコンテナを作成
        const suggestContainer = document.createElement('div');
        suggestContainer.id = 'ai-search-suggestions';
        suggestContainer.className = 'absolute bg-white border rounded-lg shadow-lg mt-1 w-full z-50';
        
        // AI検索の説明を表示
        const headerDiv = document.createElement('div');
        headerDiv.className = 'px-4 py-2 bg-purple-50 text-sm text-purple-700 border-b';
        headerDiv.innerHTML = '<i class="fas fa-magic mr-2"></i>AI検索の候補';
        suggestContainer.appendChild(headerDiv);
        
        // 候補を表示
        suggestions.forEach((item, index) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'px-4 py-3 hover:bg-gray-50 cursor-pointer';
            if (index < suggestions.length - 1) {
                itemDiv.className += ' border-b';
            }
            
            itemDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-sparkles text-purple-400 mr-3"></i>
                    <div class="text-sm">${this.escapeHtml(item.title)}</div>
                </div>
            `;
            
            itemDiv.addEventListener('click', () => {
                input.value = item.title;
                this.removeExistingSuggestions();
                // フォームを送信
                const form = input.closest('form');
                if (form) {
                    // submitイベントを発火させるためにrequestSubmitを使用
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        // 古いブラウザ用のフォールバック
                        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
                        form.dispatchEvent(submitEvent);
                        if (!submitEvent.defaultPrevented) {
                            form.submit();
                        }
                    }
                }
            });
            
            suggestContainer.appendChild(itemDiv);
        });
        
        // フォームの下に追加
        input.parentElement.style.position = 'relative';
        input.parentElement.appendChild(suggestContainer);
        
        // クリック外で閉じる
        document.addEventListener('click', (e) => {
            if (!input.parentElement.contains(e.target)) {
                suggestContainer.remove();
            }
        }, { once: true });
    }
    
    formatIntent(intents) {
        const intentLabels = {
            'genre': 'ジャンル検索',
            'mood': '気分・雰囲気',
            'similar': '類似本',
            'author': '著者',
            'theme': 'テーマ',
            'specific': '特定の本'
        };
        
        return intents.map(intent => intentLabels[intent] || intent).join('、');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    submitAISearch(form, query) {
        // AI検索結果ページへ遷移（将来的に実装）
        const input = form.querySelector('input[name="keyword"]');
        input.value = query;
        form.submit();
    }
}

// ページ読み込み時に初期化
document.addEventListener('DOMContentLoaded', () => {
    new AISearch();
});