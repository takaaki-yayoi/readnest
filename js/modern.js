// モダンな読書管理アプリケーションのJavaScript

// ページローダーの制御
document.addEventListener('DOMContentLoaded', function() {
    // ページローダーを非表示
    const loader = document.getElementById('page-loader');
    if (loader) {
        loader.classList.add('hidden');
    }
});

// Ajax リクエストのヘルパー関数
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

// 本の検索（オートコンプリート）
let searchTimeout;
function setupBookSearch() {
    const searchInputs = document.querySelectorAll('input[name="q"]');
    
    searchInputs.forEach(input => {
        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'absolute top-full left-0 right-0 mt-1 bg-white rounded-lg shadow-lg z-50 hidden';
        input.parentElement.appendChild(resultsContainer);
        
        input.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                resultsContainer.classList.add('hidden');
                return;
            }
            
            searchTimeout = setTimeout(async () => {
                try {
                    const data = await fetchData(`/search_book_ajax.php?q=${encodeURIComponent(query)}`);
                    displaySearchResults(data, resultsContainer);
                } catch (error) {
                    console.error('Search error:', error);
                }
            }, 300);
        });
        
        // クリック外で結果を非表示
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.classList.add('hidden');
            }
        });
    });
}

// 検索結果の表示
function displaySearchResults(results, container) {
    if (!results || results.length === 0) {
        container.innerHTML = '<div class="p-4 text-gray-500 text-sm">検索結果がありません</div>';
        container.classList.remove('hidden');
        return;
    }
    
    const html = results.map(book => `
        <a href="/book_detail.php?book_id=${book.book_id}" class="block p-3 hover:bg-gray-50 border-b last:border-b-0">
            <div class="flex items-center">
                <img src="${book.image_url || '/img/noimage.jpg'}" alt="${book.title}" class="w-12 h-16 object-cover rounded">
                <div class="ml-3 flex-1">
                    <div class="font-medium text-sm">${book.title}</div>
                    <div class="text-xs text-gray-600">${book.author}</div>
                </div>
            </div>
        </a>
    `).join('');
    
    container.innerHTML = html;
    container.classList.remove('hidden');
}

// 本棚の無限スクロール
function setupInfiniteScroll() {
    const bookshelf = document.getElementById('bookshelf-container');
    if (!bookshelf) return;
    
    let loading = false;
    let page = 1;
    const userId = bookshelf.dataset.userId;
    
    const observer = new IntersectionObserver((entries) => {
        const lastEntry = entries[0];
        if (lastEntry.isIntersecting && !loading) {
            loadMoreBooks();
        }
    });
    
    // 最後の要素を監視
    const sentinel = document.createElement('div');
    sentinel.id = 'scroll-sentinel';
    sentinel.className = 'h-10';
    bookshelf.appendChild(sentinel);
    observer.observe(sentinel);
    
    async function loadMoreBooks() {
        loading = true;
        page++;
        
        try {
            const data = await fetchData(`/bookshelf_api.php?user_id=${userId}&page=${page}`);
            if (data.books && data.books.length > 0) {
                appendBooks(data.books);
            } else {
                observer.unobserve(sentinel);
            }
        } catch (error) {
            console.error('Load more books error:', error);
        } finally {
            loading = false;
        }
    }
    
    function appendBooks(books) {
        const grid = bookshelf.querySelector('.book-grid');
        const html = books.map(book => createBookCard(book)).join('');
        grid.insertAdjacentHTML('beforeend', html);
    }
}

// 本のカード作成
function createBookCard(book) {
    const statusClasses = {
        0: 'status-buy-someday',
        1: 'status-not-started',
        2: 'status-reading',
        3: 'status-finished',
        4: 'status-read-before'
    };
    
    const statusTexts = {
        0: 'いつか買う',
        1: '積読',
        2: '読書中',
        3: '読了',
        4: '既読'
    };
    
    return `
        <div class="book-card" data-book-id="${book.book_id}">
            <div class="relative">
                <img src="${book.image_url || '/img/noimage.jpg'}" 
                     alt="${book.title}" 
                     class="book-cover"
                     loading="lazy">
                ${book.status == 2 && book.progress ? `
                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-2">
                        <div class="progress-bar mb-1">
                            <div class="progress-bar-fill bg-white" style="width: ${book.progress}%"></div>
                        </div>
                        <span>${book.current_page}/${book.total_page}ページ</span>
                    </div>
                ` : ''}
            </div>
            <div class="book-info">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author">${book.author}</p>
                <div class="flex items-center justify-between mt-2">
                    <span class="${statusClasses[book.status]}">${statusTexts[book.status]}</span>
                    ${book.rating ? `
                        <div class="rating">
                            ${[...Array(5)].map((_, i) => 
                                i < book.rating 
                                    ? '<i class="fas fa-star star"></i>' 
                                    : '<i class="far fa-star star-empty"></i>'
                            ).join('')}
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

// 本の詳細モーダル
function setupBookModal() {
    document.addEventListener('click', async (e) => {
        const bookCard = e.target.closest('.book-card');
        if (!bookCard) return;
        
        // お気に入りページではモーダルを無効化（直接リンクで遷移）
        if (window.location.pathname === '/favorites.php' || document.body.classList.contains('favorites-page')) {
            return; // デフォルトのリンク動作を許可
        }
        
        e.preventDefault();
        const bookId = bookCard.dataset.bookId;
        
        try {
            const data = await fetchData(`/book_detail_api.php?book_id=${bookId}`);
            showBookModal(data);
        } catch (error) {
            console.error('Book detail error:', error);
        }
    });
}

// モーダル表示
function showBookModal(book) {
    // 既存のモーダルを削除
    const existingModal = document.getElementById('book-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'book-modal';
    modal.className = 'fixed inset-0 z-50 overflow-y-auto';
    modal.innerHTML = `
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <button onclick="closeBookModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                    
                    <div class="sm:flex sm:items-start">
                        <div class="sm:w-1/3">
                            <img src="${book.image_url || '/img/noimage.jpg'}" 
                                 alt="${book.title}" 
                                 class="w-full rounded-lg shadow-md">
                        </div>
                        
                        <div class="mt-3 sm:mt-0 sm:ml-6 sm:w-2/3">
                            <h3 class="text-2xl font-bold text-gray-900">${book.title}</h3>
                            <p class="text-lg text-gray-600 mt-1">${book.author}</p>
                            
                            <div class="mt-4 space-y-3">
                                ${book.publisher ? `<p class="text-sm"><span class="font-medium">出版社:</span> ${book.publisher}</p>` : ''}
                                ${book.published_date ? `<p class="text-sm"><span class="font-medium">出版日:</span> ${book.published_date}</p>` : ''}
                                ${book.isbn ? `<p class="text-sm"><span class="font-medium">ISBN:</span> ${book.isbn}</p>` : ''}
                            </div>
                            
                            <div class="mt-6">
                                <a href="/book_detail.php?book_id=${book.book_id}" 
                                   class="btn-primary">
                                    詳細を見る
                                </a>
                                <button onclick="closeBookModal()" 
                                        class="btn-outline ml-2">
                                    閉じる
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // ESCキーで閉じる
    document.addEventListener('keydown', function closeOnEsc(e) {
        if (e.key === 'Escape') {
            closeBookModal();
            document.removeEventListener('keydown', closeOnEsc);
        }
    });
}

// モーダルを閉じる
function closeBookModal() {
    const modal = document.getElementById('book-modal');
    if (modal) {
        modal.remove();
    }
}

// Toast通知
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 animate-slide-up ${
        type === 'success' ? 'bg-green-600' :
        type === 'error' ? 'bg-red-600' :
        type === 'warning' ? 'bg-yellow-600' :
        'bg-blue-600'
    }`;
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${
                type === 'success' ? 'fa-check-circle' :
                type === 'error' ? 'fa-exclamation-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                'fa-info-circle'
            } mr-3"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('animate-fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// フォームのAjax送信
function setupAjaxForms() {
    const forms = document.querySelectorAll('[data-ajax-form]');
    
    forms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitButton = form.querySelector('[type="submit"]');
            
            // ボタンを無効化
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>処理中...';
            }
            
            try {
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message || '処理が完了しました', 'success');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                } else {
                    showToast(data.message || 'エラーが発生しました', 'error');
                }
            } catch (error) {
                console.error('Form submission error:', error);
                showToast('通信エラーが発生しました', 'error');
            } finally {
                // ボタンを有効化
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = form.dataset.submitText || '送信';
                }
            }
        });
    });
}

// 初期化
document.addEventListener('DOMContentLoaded', () => {
    setupBookSearch();
    setupInfiniteScroll();
    
    // お気に入りページではモーダル機能を無効化
    if (window.location.pathname !== '/favorites.php' && !document.body.classList.contains('favorites-page')) {
        setupBookModal();
    }
    
    setupAjaxForms();
});

// グローバル関数として公開
window.showToast = showToast;
window.closeBookModal = closeBookModal;