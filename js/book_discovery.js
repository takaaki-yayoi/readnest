/**
 * 本の発見 - フロントエンドJS
 *
 * 対話型ディスカバリーUIの操作を管理する。
 * サジェストチップ、API呼び出し、推薦カード描画、プロファイル表示、
 * 表紙画像遅延読み込み、本棚追加モーダルを担当。
 */

// ローディングステップのメッセージ
const LOADING_STEPS = [
    '読書プロフィールを分析中...',
    'おすすめ候補を生成中...',
    '既読チェック＆カード整形中...'
];

let loadingStepTimer = null;

// モーダル用: 検索結果を保持
var _modalSearchResults = [];

/**
 * CSRFトークンを取得
 */
function getCSRFToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * CSRF付きfetch
 */
function fetchWithCSRF(url, options) {
    options = options || {};
    options.headers = options.headers || {};
    options.headers['X-CSRF-Token'] = getCSRFToken();
    return fetch(url, options);
}

/**
 * サジェストチップクリック時
 */
function handleChipClick(query) {
    const input = document.getElementById('discovery-input');
    input.value = query;
    submitDiscovery();
}

/**
 * ディスカバリーリクエストを送信
 */
async function submitDiscovery() {
    const input = document.getElementById('discovery-input');
    const query = input.value.trim();
    if (!query) return;

    const submitBtn = document.getElementById('discovery-submit');

    // UI状態の切り替え
    submitBtn.disabled = true;
    hideElement('discovery-error');
    hideElement('discovery-results');
    showElement('discovery-loading');
    startLoadingSteps();

    try {
        const response = await fetchWithCSRF('/api/book_discovery.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: query })
        });

        const data = await response.json();

        if (data.success) {
            if (data.profile) {
                renderProfile(data.profile);
            }
            if (data.recommendations && data.recommendations.length > 0) {
                renderRecommendations(data.recommendations);
                loadCoverImages(data.recommendations);
                if (data.stats) {
                    renderFilterStats(data.stats);
                }
                showElement('discovery-results');
            } else {
                showError(data.message || '推薦候補が見つかりませんでした。別の気分で試してみてください。');
            }
        } else {
            showError(data.message || 'エラーが発生しました。');
        }
    } catch (error) {
        console.error('Discovery error:', error);
        showError('通信エラーが発生しました。しばらくしてからお試しください。');
    } finally {
        stopLoadingSteps();
        hideElement('discovery-loading');
        submitBtn.disabled = false;
    }
}

/**
 * ローディングステップの表示を開始
 */
function startLoadingSteps() {
    let step = 0;
    updateLoadingStep(step);

    loadingStepTimer = setInterval(function() {
        step++;
        if (step < LOADING_STEPS.length) {
            updateLoadingStep(step);
        }
    }, 5000);
}

function updateLoadingStep(step) {
    const stepEl = document.getElementById('loading-step');
    if (stepEl) {
        stepEl.textContent = LOADING_STEPS[step] || LOADING_STEPS[LOADING_STEPS.length - 1];
    }

    for (let i = 1; i <= 3; i++) {
        const dot = document.getElementById('step-dot-' + i);
        if (dot) {
            if (i <= step + 1) {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-purple-600';
            } else {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600';
            }
        }
    }
}

function stopLoadingSteps() {
    if (loadingStepTimer) {
        clearInterval(loadingStepTimer);
        loadingStepTimer = null;
    }
}

// ============================================================
// 表紙画像の遅延読み込み
// ============================================================

/**
 * 推薦カードに表紙画像を遅延読み込み
 */
async function loadCoverImages(recommendations) {
    var batchSize = 3;
    for (var i = 0; i < recommendations.length; i += batchSize) {
        var batch = recommendations.slice(i, i + batchSize);
        await Promise.allSettled(batch.map(function(rec, batchIndex) {
            return loadSingleCover(rec, i + batchIndex);
        }));
    }
}

/**
 * 1冊分の表紙画像を読み込む
 */
async function loadSingleCover(rec, index) {
    var imgEl = document.getElementById('cover-' + index);
    if (!imgEl) return;

    try {
        var query = rec.title + ' ' + rec.author;
        var response = await fetchWithCSRF('/api/book_search_quick.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: query, limit: 1 })
        });
        var data = await response.json();

        if (data.success && data.books && data.books.length > 0 && data.books[0].image_url) {
            imgEl.src = data.books[0].image_url;
            imgEl.classList.remove('animate-pulse', 'bg-gray-200', 'dark:bg-gray-700');
        }
    } catch (error) {
        // プレースホルダーを維持
    }
}

// ============================================================
// 本棚追加モーダル
// ============================================================

/**
 * 本棚に追加モーダルを表示
 */
async function openAddBookModal(title, author, cardIndex) {
    var modal = document.getElementById('add-book-modal');
    if (!modal) return;

    modal.style.display = 'flex';
    document.getElementById('modal-search-query').textContent = title + ' ' + author;
    document.getElementById('modal-results').innerHTML = '';
    document.getElementById('modal-error').classList.add('hidden');
    document.getElementById('modal-loading').classList.remove('hidden');

    modal.dataset.cardIndex = cardIndex;
    _modalSearchResults = [];

    try {
        var query = title + ' ' + author;
        var response = await fetchWithCSRF('/api/book_search_quick.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: query, limit: 5 })
        });
        var data = await response.json();

        document.getElementById('modal-loading').classList.add('hidden');

        if (data.success && data.books && data.books.length > 0) {
            _modalSearchResults = data.books;
            renderModalResults(data.books);
        } else {
            showModalError('検索結果が見つかりませんでした。');
            showManualAddButton(title, author);
        }
    } catch (error) {
        document.getElementById('modal-loading').classList.add('hidden');
        showModalError('検索中にエラーが発生しました。');
    }
}

/**
 * モーダルを閉じる
 */
function closeAddBookModal() {
    var modal = document.getElementById('add-book-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * モーダル内に検索結果を描画
 */
function renderModalResults(books) {
    var container = document.getElementById('modal-results');
    var html = '';

    books.forEach(function(book, i) {
        var imgSrc = book.image_url || '/img/no-image-book.png';
        html += '<div class="flex gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors" onclick="selectBookToAdd(' + i + ')">';
        html += '<div class="flex-shrink-0 w-14">';
        html += '<img src="' + escapeAttr(imgSrc) + '" alt="" class="w-14 h-20 object-contain rounded" onerror="this.onerror=null;this.src=\'/img/no-image-book.png\';">';
        html += '</div>';
        html += '<div class="flex-1 min-w-0">';
        html += '<h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 line-clamp-2">' + escapeHtml(book.title) + '</h4>';
        html += '<p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">' + escapeHtml(book.author) + '</p>';
        if (book.pages > 0) {
            html += '<p class="text-xs text-gray-500 mt-0.5">' + book.pages + 'ページ</p>';
        }
        html += '</div>';
        html += '<div class="flex-shrink-0 self-center">';
        html += '<i class="fas fa-plus-circle text-purple-500 text-lg"></i>';
        html += '</div>';
        html += '</div>';
    });

    container.innerHTML = html;
}

/**
 * 検索結果から本を選択 → ステータス選択画面
 */
function selectBookToAdd(index) {
    var book = _modalSearchResults[index];
    if (!book) return;

    var container = document.getElementById('modal-results');
    var imgSrc = book.image_url || '/img/no-image-book.png';

    var html = '<div class="p-4 space-y-4">';
    html += '<div class="flex gap-3 items-center">';
    html += '<img src="' + escapeAttr(imgSrc) + '" class="w-16 h-24 object-contain rounded" onerror="this.onerror=null;this.src=\'/img/no-image-book.png\';">';
    html += '<div>';
    html += '<h4 class="font-semibold text-gray-900 dark:text-gray-100">' + escapeHtml(book.title) + '</h4>';
    html += '<p class="text-sm text-gray-600 dark:text-gray-400">' + escapeHtml(book.author) + '</p>';
    html += '</div></div>';

    html += '<div>';
    html += '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ステータス</label>';
    html += '<select id="modal-status-select" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md text-sm">';
    html += '<option value="0">いつか買う</option>';
    html += '<option value="1" selected>積読</option>';
    html += '<option value="2">読書中</option>';
    html += '<option value="3">読了</option>';
    html += '<option value="4">既読</option>';
    html += '</select></div>';

    html += '<div class="flex gap-2">';
    html += '<button type="button" id="modal-add-btn" onclick="addBookToShelf(' + index + ')" class="flex-1 bg-purple-600 text-white py-2 px-4 rounded-lg text-sm font-semibold hover:bg-purple-700 transition-colors">';
    html += '<i class="fas fa-plus mr-1"></i>本棚に追加</button>';
    html += '<button type="button" onclick="renderModalResults(_modalSearchResults)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">';
    html += '戻る</button>';
    html += '</div>';
    html += '</div>';

    container.innerHTML = html;
}

/**
 * 本を本棚に追加
 */
async function addBookToShelf(index) {
    var book = _modalSearchResults[index];
    if (!book) return;

    var statusSelect = document.getElementById('modal-status-select');
    var status = statusSelect ? parseInt(statusSelect.value) : 1;

    var submitBtn = document.getElementById('modal-add-btn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>追加中...';
    }

    try {
        var response = await fetchWithCSRF('/api/add_book_from_search.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                asin: book.asin,
                isbn: book.isbn || '',
                title: book.title,
                author: book.author,
                image_url: book.image_url || '',
                detail_url: book.detail_url || '',
                pages: book.pages || 0,
                status: status,
                categories: book.categories || []
            })
        });

        var data = await response.json();

        if (data.success) {
            var container = document.getElementById('modal-results');
            container.innerHTML = '<div class="p-6 text-center">' +
                '<i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>' +
                '<p class="text-lg font-semibold text-gray-900 dark:text-gray-100">追加しました！</p>' +
                '<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">' + escapeHtml(book.title) + '</p>' +
                '</div>';

            var modal = document.getElementById('add-book-modal');
            var cardIndex = modal ? modal.dataset.cardIndex : null;
            if (cardIndex !== null && cardIndex !== undefined) {
                markCardAsAdded(cardIndex, data.book);
            }

            setTimeout(function() {
                closeAddBookModal();
            }, 1500);
        } else {
            if (data.error && data.error.indexOf('既に本棚にあります') !== -1) {
                showModalError('この本は既に本棚にあります。');
                var modal = document.getElementById('add-book-modal');
                var cardIndex = modal ? modal.dataset.cardIndex : null;
                if (cardIndex !== null && cardIndex !== undefined) {
                    markCardAsAdded(cardIndex, { book_id: data.book_id || 0, title: book.title });
                }
                setTimeout(function() {
                    closeAddBookModal();
                }, 1500);
            } else {
                showModalError(data.error || 'エラーが発生しました。');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-plus mr-1"></i>本棚に追加';
                }
            }
        }
    } catch (error) {
        showModalError('通信エラーが発生しました。');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-plus mr-1"></i>本棚に追加';
        }
    }
}

/**
 * カードを「追加済み」状態に更新
 */
function markCardAsAdded(cardIndex, bookInfo) {
    var card = document.querySelector('[data-card-index="' + cardIndex + '"]');
    if (!card) return;

    var actionDiv = card.querySelector('.card-actions');
    if (actionDiv) {
        var bookId = bookInfo.book_id || 0;
        var linkHtml = '';
        if (bookId > 0) {
            linkHtml = '<a href="/book/' + bookId + '" class="inline-flex items-center bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-4 py-2 rounded-lg text-sm hover:bg-green-200 dark:hover:bg-green-800/40 transition-colors">' +
                '<i class="fas fa-check mr-1"></i>追加済み — 詳細を見る</a>';
        } else {
            linkHtml = '<span class="inline-flex items-center bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-4 py-2 rounded-lg text-sm">' +
                '<i class="fas fa-check mr-1"></i>追加済み</span>';
        }
        actionDiv.innerHTML = linkHtml;
    }
}

/**
 * モーダルのエラー表示
 */
function showModalError(message) {
    var errorEl = document.getElementById('modal-error');
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
    }
}

/**
 * 手動追加ボタンを表示
 */
function showManualAddButton(title, author) {
    var container = document.getElementById('modal-results');
    var url = '/add_original_book.php?title=' + encodeURIComponent(title);
    if (author) url += '&author=' + encodeURIComponent(author);

    container.innerHTML = '<div class="p-4 text-center">' +
        '<a href="' + url + '" target="_blank" class="inline-flex items-center bg-gray-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-600 transition-colors">' +
        '<i class="fas fa-edit mr-1"></i>手動で追加</a></div>';
}

// ============================================================
// 読書プロファイル描画
// ============================================================

/**
 * 読書プロファイルを描画
 */
function renderProfile(profile) {
    var summaryEl = document.getElementById('profile-summary');
    if (summaryEl && profile.summary) {
        summaryEl.textContent = profile.summary;
    }

    var detailsEl = document.getElementById('profile-details');
    if (!detailsEl) return;

    var html = '';

    if (profile.preferred_themes && profile.preferred_themes.length > 0) {
        html += '<div>';
        html += '<h3 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2"><i class="fas fa-heart text-pink-500 mr-1"></i>好きなテーマ</h3>';
        html += '<div class="flex flex-wrap gap-1.5">';
        profile.preferred_themes.forEach(function(theme) {
            html += '<span class="inline-block bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 px-2.5 py-1 rounded-full text-xs">' + escapeHtml(theme) + '</span>';
        });
        html += '</div></div>';
    }

    if (profile.preferred_styles && profile.preferred_styles.length > 0) {
        html += '<div>';
        html += '<h3 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2"><i class="fas fa-feather text-blue-500 mr-1"></i>好きなスタイル</h3>';
        html += '<div class="flex flex-wrap gap-1.5">';
        profile.preferred_styles.forEach(function(style) {
            html += '<span class="inline-block bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2.5 py-1 rounded-full text-xs">' + escapeHtml(style) + '</span>';
        });
        html += '</div></div>';
    }

    if (profile.dislikes && profile.dislikes.length > 0) {
        html += '<div>';
        html += '<h3 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2"><i class="fas fa-ban text-red-400 mr-1"></i>苦手そうなパターン</h3>';
        html += '<div class="flex flex-wrap gap-1.5">';
        profile.dislikes.forEach(function(d) {
            html += '<span class="inline-block bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-2.5 py-1 rounded-full text-xs">' + escapeHtml(d) + '</span>';
        });
        html += '</div></div>';
    }

    if (profile.favorite_authors_with_reasons) {
        var authors = Object.entries(profile.favorite_authors_with_reasons);
        if (authors.length > 0) {
            html += '<div>';
            html += '<h3 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2"><i class="fas fa-user-edit text-indigo-500 mr-1"></i>お気に入り著者</h3>';
            html += '<div class="space-y-1">';
            authors.forEach(function(entry) {
                html += '<div class="text-xs text-gray-600 dark:text-gray-400">';
                html += '<span class="font-medium text-gray-800 dark:text-gray-200">' + escapeHtml(entry[0]) + '</span>';
                html += ' — ' + escapeHtml(entry[1]);
                html += '</div>';
            });
            html += '</div></div>';
        }
    }

    if (profile.blind_spots && profile.blind_spots.length > 0) {
        html += '<div>';
        html += '<h3 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2"><i class="fas fa-eye-slash text-yellow-500 mr-1"></i>まだ未開拓のジャンル</h3>';
        html += '<div class="flex flex-wrap gap-1.5">';
        profile.blind_spots.forEach(function(bs) {
            html += '<span class="inline-block bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 px-2.5 py-1 rounded-full text-xs">' + escapeHtml(bs) + '</span>';
        });
        html += '</div></div>';
    }

    detailsEl.innerHTML = html;
}

// ============================================================
// 推薦カード描画
// ============================================================

/**
 * 推薦カードを描画
 */
function renderRecommendations(recommendations) {
    var container = document.getElementById('recommendation-cards');
    if (!container) return;

    var countEl = document.getElementById('result-count');
    if (countEl) {
        countEl.textContent = recommendations.length + '冊の推薦';
    }

    var html = '';

    recommendations.forEach(function(rec, index) {
        var safeTitle = escapeAttr(rec.title);
        var safeAuthor = escapeAttr(rec.author);

        html += '<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" data-card-index="' + index + '" x-data="{ expanded: false }">';

        // カードヘッダー
        html += '<div class="p-4">';

        // 表紙画像 + タイトル行
        html += '<div class="flex gap-3">';

        // 表紙画像プレースホルダー
        html += '<div class="flex-shrink-0">';
        html += '<img id="cover-' + index + '" src="/img/no-image-book.png" alt="" class="w-16 h-24 object-contain rounded bg-gray-200 dark:bg-gray-700 animate-pulse" onerror="this.onerror=null;this.src=\'/img/no-image-book.png\';">';
        html += '</div>';

        // タイトル・著者・言語バッジ
        html += '<div class="flex-1 min-w-0">';
        html += '<div class="flex items-start justify-between">';
        html += '<h3 class="text-base font-bold text-gray-900 dark:text-gray-100">' + escapeHtml(rec.title) + '</h3>';
        if (rec.language === 'en') {
            html += '<span class="ml-2 flex-shrink-0 inline-block bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs px-2 py-0.5 rounded font-medium">EN</span>';
        }
        html += '</div>';
        html += '<p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">' + escapeHtml(rec.author) + '</p>';

        // ジャンルタグ
        if (rec.genre) {
            html += '<span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs px-2 py-0.5 rounded mt-1">' + escapeHtml(rec.genre) + '</span>';
        }
        html += '</div>';
        html += '</div>'; // /flex gap-3

        // 接続元バッジ
        if (rec.connection && rec.connection.from_book) {
            html += '<div class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400">';
            html += '<span class="inline-flex items-center bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-1 rounded">';
            html += '<i class="fas fa-link mr-1"></i>';
            html += escapeHtml(rec.connection.from_book);
            if (rec.connection.from_rating) {
                html += ' <span class="text-yellow-500 ml-1">';
                for (var s = 0; s < rec.connection.from_rating; s++) {
                    html += '&#9733;';
                }
                html += '</span>';
            }
            html += ' からの接続</span>';
            html += '</div>';
        }

        // 推薦理由（先頭100文字）
        if (rec.connection && rec.connection.reasoning) {
            var reasoning = rec.connection.reasoning;
            var shortReasoning = reasoning.length > 100 ? reasoning.substring(0, 100) + '...' : reasoning;
            html += '<p class="mt-2 text-sm text-gray-700 dark:text-gray-300">' + escapeHtml(shortReasoning) + '</p>';
        }

        // 展開ボタン
        html += '<button @click="expanded = !expanded" class="mt-3 text-xs text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 flex items-center">';
        html += '<span x-text="expanded ? \'詳細を閉じる\' : \'なぜこの本？ 詳細を見る\'"></span>';
        html += '<i class="fas fa-chevron-down ml-1 text-[10px] transition-transform" :class="{ \'rotate-180\': expanded }"></i>';
        html += '</button>';

        html += '</div>'; // /カードヘッダー

        // 展開セクション
        html += '<div x-show="expanded" x-transition class="border-t border-gray-100 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50 space-y-4">';

        // 推薦理由全文
        if (rec.connection && rec.connection.reasoning) {
            html += '<div>';
            html += '<h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1"><i class="fas fa-comment-dots mr-1"></i>推薦理由</h4>';
            html += '<p class="text-sm text-gray-700 dark:text-gray-300">' + escapeHtml(rec.connection.reasoning) + '</p>';
            html += '</div>';
        }

        // マッチ要素バー
        if (rec.match_factors && rec.match_factors.length > 0) {
            html += '<div>';
            html += '<h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2"><i class="fas fa-chart-bar mr-1"></i>マッチ要素</h4>';
            html += '<div class="space-y-2">';
            rec.match_factors.forEach(function(factor) {
                var percent = Math.round(factor.strength * 100);
                html += '<div>';
                html += '<div class="flex items-center justify-between text-xs mb-0.5">';
                html += '<span class="text-gray-700 dark:text-gray-300">' + escapeHtml(factor.label) + '</span>';
                html += '<span class="text-gray-500 dark:text-gray-400">' + percent + '%</span>';
                html += '</div>';
                html += '<div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">';
                html += '<div class="bg-gradient-to-r from-purple-500 to-pink-500 h-1.5 rounded-full" style="width: ' + percent + '%"></div>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div></div>';
        }

        // 意外性ポイント
        if (rec.surprise_factor) {
            html += '<div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">';
            html += '<h4 class="text-xs font-semibold text-yellow-700 dark:text-yellow-300 mb-1"><i class="fas fa-bolt mr-1"></i>意外性ポイント</h4>';
            html += '<p class="text-sm text-yellow-800 dark:text-yellow-200">' + escapeHtml(rec.surprise_factor) + '</p>';
            html += '</div>';
        }

        // アクションボタン
        html += '<div class="card-actions flex items-center gap-2 pt-2">';
        html += '<button type="button" onclick="openAddBookModal(\'' + safeTitle + '\', \'' + safeAuthor + '\', ' + index + ')"';
        html += ' class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700 transition-colors">';
        html += '<i class="fas fa-plus mr-1"></i>本棚に追加</button>';
        html += '<button type="button" onclick="addBookManually(\'' + safeTitle + '\', \'' + safeAuthor + '\')"';
        html += ' class="text-gray-500 dark:text-gray-400 px-3 py-2 text-sm hover:text-gray-700 dark:hover:text-gray-300 transition-colors">';
        html += '<i class="fas fa-edit mr-1"></i>手動で追加</button>';
        html += '</div>';

        html += '</div>'; // /展開セクション
        html += '</div>'; // /カード
    });

    container.innerHTML = html;
}

// ============================================================
// フィルタリング統計
// ============================================================

function renderFilterStats(stats) {
    var el = document.getElementById('filter-stats');
    if (!el) return;

    var text = 'LLMが ' + stats.total_candidates + ' 冊の候補を生成';
    if (stats.filtered_out > 0) {
        text += ' → 既読 ' + stats.filtered_out + ' 冊を除外';
    }
    text += ' → ' + stats.final_count + ' 冊を推薦';

    el.querySelector('p').textContent = text;
}

// ============================================================
// ユーティリティ
// ============================================================

/**
 * 手動で本を追加ページへ遷移
 */
function addBookManually(title, author) {
    var url = '/add_original_book.php?title=' + encodeURIComponent(title);
    if (author) {
        url += '&author=' + encodeURIComponent(author);
    }
    window.open(url, '_blank');
}

function showError(message) {
    var errorEl = document.getElementById('error-message');
    if (errorEl) {
        errorEl.textContent = message;
    }
    showElement('discovery-error');
}

function showElement(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('hidden');
}

function hideElement(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('hidden');
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeAttr(text) {
    if (!text) return '';
    return text.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
