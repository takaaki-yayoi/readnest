<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// メインコンテンツを生成
ob_start();
?>

<!-- 読書アシスタントページ -->
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- ヘッダー -->
    <div class="bg-green-700 dark:bg-green-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center">
                <div class="w-10 h-10 mr-3">
                    <img src="/favicon.png" alt="読書アシスタント" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-xl font-bold">読書アシスタント</h1>
                    <p class="text-xs opacity-90">読書に関することなら何でもお聞きください</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <?php if (!$login_flag): ?>
        <!-- 未ログイン時の案内 -->
        <div class="bg-white dark:bg-gray-800 rounded shadow-sm border border-gray-200 dark:border-gray-700 p-8 mb-8">
            <div class="text-center">
                <i class="fas fa-lock text-5xl text-gray-400 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">ログインが必要です</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    読書アシスタントを利用するには、ログインが必要です。<br>
                    あなたの読書履歴に基づいた、パーソナライズされた推薦を提供します。
                </p>
                <div class="flex justify-center space-x-4">
                    <a href="/register.php" class="bg-green-700 text-white px-6 py-3 rounded font-semibold hover:bg-green-800 transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>新規登録
                    </a>
                    <a href="/" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-3 rounded font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>ログイン
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- 左側：機能紹介とユーザー情報 -->
            <div class="lg:col-span-1 space-y-4">
                <!-- 読書アシスタントの使い方 -->
                <div class="bg-white dark:bg-gray-800 rounded shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-300 mb-3">こんにちは！読書アシスタントです。読書に関することなら何でもお聞きください。</p>
                        
                        <!-- 使い方のヒント -->
                        <div class="mb-3 p-2 bg-blue-50 dark:bg-blue-900/30 rounded text-xs">
                            <p class="text-blue-800 dark:text-blue-300">
                                <i class="fas fa-lightbulb mr-1"></i>
                                <strong>ヒント：</strong>自然な言葉で質問できます。「今年読んだ本は？」「積読の数は？」など
                            </p>
                        </div>
                        
                        <!-- データベース検索系の質問 -->
                        <div class="mb-3">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">📊 あなたの読書データを分析：</p>
                            <div class="space-y-1">
                                <button onclick="quickQuestion('読了した本は何冊？')" class="w-full text-left p-2 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded text-xs transition-colors">
                                    <span class="text-gray-700 dark:text-gray-300">🔢 読了した本は何冊？</span>
                                </button>
                                <button onclick="quickQuestion('評価が4以上の本を見せて')" class="w-full text-left p-2 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded text-xs transition-colors">
                                    <span class="text-gray-700 dark:text-gray-300">⭐ 評価が4以上の本を見せて</span>
                                </button>
                                <button onclick="quickQuestion('今月読んだ本の一覧')" class="w-full text-left p-2 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded text-xs transition-colors">
                                    <span class="text-gray-700 dark:text-gray-300">📅 今月読んだ本の一覧</span>
                                </button>
                                <button onclick="quickQuestion('積読は何冊ある？')" class="w-full text-left p-2 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded text-xs transition-colors">
                                    <span class="text-gray-700 dark:text-gray-300">📖 積読は何冊ある？</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- 一般的な質問 -->
                        <div>
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">💡 おすすめの質問：</p>
                            <div class="space-y-1">
                                <button onclick="quickQuestion('次に読むべき本を推薦して')" class="w-full text-left p-2 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded text-xs transition-colors">
                                    <span class="text-gray-700 dark:text-gray-300">📚 次に読むべき本を推薦して</span>
                                </button>
                                <button onclick="quickQuestion('感動する小説を教えて')" class="w-full text-left p-2 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded text-xs transition-colors">
                                    <span class="text-gray-700 dark:text-gray-300">❤️ 感動する小説を教えて</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($template_recent_books) || !empty($template_favorite_genres)): ?>
                <!-- 最近読んだ本 -->
                <?php if (!empty($template_recent_books)): ?>
                <div class="bg-white dark:bg-gray-800 rounded shadow-sm border border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-bold text-gray-700 dark:text-gray-300 p-3 border-b dark:border-gray-700">
                        📚 最近読んだ本
                    </h2>
                    <div class="p-4 space-y-2">
                        <?php foreach (array_slice($template_recent_books, 0, 5) as $book): ?>
                        <div class="flex items-center p-2 bg-gray-50 dark:bg-gray-700 rounded hover:bg-green-50 dark:hover:bg-green-900/30 cursor-pointer transition-colors" 
                             onclick="handleBookClick('<?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($book['author'], ENT_QUOTES); ?>')">
                            <img src="<?php echo htmlspecialchars($book['image_url'] ?? '/img/book-placeholder.svg'); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 class="w-10 h-14 object-cover rounded shadow-sm mr-3"
                                 onerror="this.src='/img/book-placeholder.svg'">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate"><?php echo htmlspecialchars($book['title']); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo htmlspecialchars($book['author']); ?></p>
                                <?php if (isset($book['rating']) && $book['rating'] > 0): ?>
                                <div class="flex items-center mt-1">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                    <span class="text-xs <?php echo $i <= $book['rating'] ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- お気に入りのジャンル -->
                <?php if (!empty($template_favorite_genres)): ?>
                <div class="bg-white dark:bg-gray-800 rounded shadow-sm border border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-bold text-gray-700 dark:text-gray-300 p-3 border-b dark:border-gray-700">
                        🏷️ よく読むジャンル
                    </h2>
                    <div class="p-4">
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (array_slice($template_favorite_genres, 0, 10) as $genre): ?>
                            <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs cursor-pointer hover:bg-green-200 dark:hover:bg-green-900/40 transition-colors"
                                  onclick="handleTagClick('<?php echo htmlspecialchars($genre['tag_name'], ENT_QUOTES); ?>')">
                                <?php echo htmlspecialchars($genre['tag_name']); ?>
                                <span class="text-xs opacity-75">(<?php echo htmlspecialchars($genre['count']); ?>)</span>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 読書統計 -->
                <?php if (!empty($template_reading_stats)): ?>
                <div class="bg-white dark:bg-gray-800 rounded shadow-sm border border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-bold text-gray-700 dark:text-gray-300 p-3 border-b dark:border-gray-700">
                        📊 読書統計
                    </h2>
                    <div class="p-4 grid grid-cols-2 gap-3">
                        <?php if (isset($template_reading_stats['finished_count'])): ?>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-700 dark:text-green-400"><?php echo htmlspecialchars($template_reading_stats['finished_count']); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">読了</p>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($template_reading_stats['reading_count'])): ?>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo htmlspecialchars($template_reading_stats['reading_count']); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">読書中</p>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($template_reading_stats['avg_rating']) && $template_reading_stats['avg_rating'] > 0): ?>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-500 dark:text-yellow-400"><?php echo number_format($template_reading_stats['avg_rating'], 1); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">平均評価</p>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($template_reading_stats['review_count'])): ?>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?php echo htmlspecialchars($template_reading_stats['review_count']); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">レビュー</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- 右側：チャットエリア -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col" id="chatContainer" style="height: auto;">
                    <!-- チャットヘッダー -->
                    <div class="bg-green-700 dark:bg-green-800 text-white px-4 py-3 rounded-t-lg flex items-center justify-between">
                        <div class="flex items-center">
                            <!-- 読書アシスタントアイコン -->
                            <img src="/favicon.png" alt="読書アシスタント" class="w-10 h-10 object-contain mr-3">
                            <div>
                                <p class="text-sm text-green-100">何でもお聞きください</p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="toggleHistoryPanel()" class="text-white hover:bg-green-600 dark:hover:bg-green-700 p-2 rounded relative" title="履歴を表示">
                                <i class="fas fa-history"></i>
                                <span id="historyCount" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center"></span>
                            </button>
                            <button onclick="clearChat()" class="text-white hover:bg-green-600 dark:hover:bg-green-700 p-2 rounded" title="会話をクリア">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- チャットメッセージエリア -->
                    <div class="relative flex-1 overflow-hidden">
                        <div id="chatMessages" class="absolute inset-0 overflow-y-auto p-4 space-y-3 bg-white dark:bg-gray-800">
                            <!-- メッセージはJavaScriptで動的に追加 -->
                        </div>
                        
                        <!-- 履歴パネル -->
                        <div id="historyPanel" class="absolute inset-0 bg-white dark:bg-gray-800 z-10 hidden">
                            <div class="h-full flex flex-col">
                                <div class="bg-gray-100 dark:bg-gray-700 border-b dark:border-gray-600 p-3 flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">
                                        <i class="fas fa-history mr-2"></i>会話履歴
                                    </h3>
                                    <button onclick="toggleHistoryPanel()" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div id="historyList" class="flex-1 overflow-y-auto p-3">
                                    <!-- 履歴リストはJavaScriptで生成 -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 入力エリア -->
                    <div class="border-t dark:border-gray-700 px-4 py-3 bg-gray-50 dark:bg-gray-900">
                        <div class="flex space-x-2">
                            <input type="text" id="chatInput"
                                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-1 focus:ring-green-500 dark:focus:ring-green-400 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                   placeholder="メッセージを入力..."
                                   onkeypress="if(event.key==='Enter')sendChat()">
                            <button id="sendButton" onclick="sendChat()"
                                    class="bg-green-700 text-white px-4 py-2 rounded hover:bg-green-800 transition-colors text-sm font-medium">
                                送信
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- オーバーレイアシスタントをこのページでは無効化 -->
<script>
window.DISABLE_OVERLAY_ASSISTANT = true;
</script>

<script>
// 共有ストレージキー
const ASSISTANT_STORAGE_KEY = 'readnest_assistant_conversation';
const ASSISTANT_CONTEXT_KEY = 'readnest_assistant_context';

// グローバル変数
let conversation = [];
let isProcessing = false;
let userBookTitles = []; // ユーザーの本棚にある本のタイトルリスト

// PHP変数をJavaScriptに安全に変換
<?php
// userInfo用のデータを準備
$js_user_info = $template_user_info ?? [];
// photoフィールドのバイナリデータを除去
if (isset($js_user_info['photo'])) {
    unset($js_user_info['photo']);
}

// 他の変数を準備
$js_recent_books = $template_recent_books ?? [];
$js_favorite_genres = $template_favorite_genres ?? [];
$js_reading_stats = $template_reading_stats ?? [];
?>
const userInfo = <?php echo json_encode($js_user_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const recentBooks = <?php echo json_encode($js_recent_books, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const favoriteGenres = <?php echo json_encode($js_favorite_genres, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const readingStats = <?php echo json_encode($js_reading_stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

// ユーザーの本棚にある本のタイトルを収集
<?php
// ユーザーの本棚からタイトル一覧を取得
$user_book_titles = [];
if ($login_flag && $mine_user_id) {
    $sql = "SELECT DISTINCT name FROM b_book_list WHERE user_id = ?";
    $result = $g_db->getAll($sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
    if (!DB::isError($result)) {
        foreach ($result as $book) {
            $user_book_titles[] = $book['name'];
        }
    }
}
?>
userBookTitles = <?php echo json_encode($user_book_titles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

// 会話履歴を共有ストレージから読み込む
function loadSharedConversation() {
    try {
        const stored = localStorage.getItem(ASSISTANT_STORAGE_KEY);
        if (stored) {
            const data = JSON.parse(stored);
            if (data && Array.isArray(data.messages)) {
                return data.messages;
            }
        }
    } catch (e) {
        console.error('Failed to load shared conversation:', e);
    }
    return [];
}

// 会話履歴を共有ストレージに保存
function saveSharedConversation(messages) {
    try {
        localStorage.setItem(ASSISTANT_STORAGE_KEY, JSON.stringify({
            messages: messages,
            timestamp: Date.now()
        }));
        
        // ストレージイベントを発火（他のタブ/ウィンドウに通知）
        window.dispatchEvent(new StorageEvent('storage', {
            key: ASSISTANT_STORAGE_KEY,
            newValue: JSON.stringify({
                messages: messages,
                timestamp: Date.now()
            })
        }));
    } catch (e) {
        console.error('Failed to save shared conversation:', e);
    }
}

// マークダウンをHTMLに変換する関数
function renderMarkdown(text) {
    let html = text;
    
    // コードブロック（最初に処理して他の変換から保護）
    const codeBlocks = [];
    html = html.replace(/```([^`]+)```/g, function(match, code) {
        const placeholder = `__CODE_BLOCK_${codeBlocks.length}__`;
        codeBlocks.push(`<pre class="bg-gray-100 p-2 rounded overflow-x-auto"><code>${code}</code></pre>`);
        return placeholder;
    });
    
    // インラインコード
    html = html.replace(/`([^`]+)`/g, '<code class="bg-gray-200 px-1 py-0.5 rounded text-sm">$1</code>');
    
    // 太字
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-bold">$1</strong>');
    
    // 斜体
    html = html.replace(/\*([^*]+)\*/g, '<em class="italic">$1</em>');
    
    // リンク
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-blue-600 hover:underline" target="_blank">$1</a>');
    
    // 見出し
    html = html.replace(/^### (.+)$/gm, '<h3 class="font-bold text-lg mt-2 mb-1">$1</h3>');
    html = html.replace(/^## (.+)$/gm, '<h2 class="font-bold text-xl mt-3 mb-2">$1</h2>');
    html = html.replace(/^# (.+)$/gm, '<h1 class="font-bold text-2xl mt-4 mb-2">$1</h1>');
    
    // 番号付きリストの処理（連続する番号付きリストを一つのolタグにまとめる）
    html = html.replace(/(^(\d+)\. .+$\n?)+/gm, function(match) {
        const lines = match.trim().split('\n');
        const items = lines.map((line, index) => {
            const content = line.replace(/^\d+\. /, '');
            const actualNumber = line.match(/^(\d+)\./)[1];
            // value属性を使って正しい番号を設定
            return `<li class="ml-4 list-decimal" value="${actualNumber}">${content}</li>`;
        });
        return '<ol class="my-2">' + items.join('') + '</ol>\n';
    });
    
    // 箇条書き（番号なしリスト）の処理
    html = html.replace(/(^- .+$\n?)+/gm, function(match) {
        const lines = match.trim().split('\n');
        const items = lines.map(line => {
            const content = line.replace(/^- /, '');
            return `<li class="ml-4 list-disc">${content}</li>`;
        });
        return '<ul class="my-2">' + items.join('') + '</ul>\n';
    });
    
    // 改行
    html = html.replace(/\n/g, '<br>');
    
    // 書籍タイトルをリンクに（本棚にあるかどうかでリンク先を変更）
    html = html.replace(/「([^」]+)」/g, function(match, title) {
        const encoded = encodeURIComponent(title);
        
        // ユーザーの本棚にある本かチェック
        const isInBookshelf = userBookTitles.some(bookTitle => 
            bookTitle && bookTitle.toLowerCase().includes(title.toLowerCase()) || 
            title.toLowerCase().includes(bookTitle.toLowerCase())
        );
        
        if (isInBookshelf) {
            // 本棚にある本は本棚検索へ
            return `<a href="/bookshelf.php?search_word=${encoded}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium" target="_blank" title="本棚で表示">「${title}」📖</a>`;
        } else {
            // 新しい本は追加ページへ
            return `<a href="/add_book.php?keyword=${encoded}" class="text-green-600 dark:text-green-400 hover:underline font-medium" target="_blank" title="本を追加">「${title}」➕</a>`;
        }
    });
    
    // コードブロックを復元
    codeBlocks.forEach((code, index) => {
        html = html.replace(`__CODE_BLOCK_${index}__`, code);
    });
    
    return html;
}

// フォローアップ質問の生成
function generateFollowUpQuestions(message, response) {
    const questions = [];
    
    // 数値結果が返ってきた場合
    if (response.includes('冊') || response.includes('件')) {
        if (response.includes('読了')) {
            questions.push('読了した本の中で評価が高いものを見せて');
            questions.push('今年読了した本の一覧');
        }
        if (response.includes('積読')) {
            questions.push('積読の中からおすすめを教えて');
            questions.push('一番古い積読は何？');
        }
    }
    
    // リストが返ってきた場合
    if (response.includes('「')) {
        questions.push('この中で一番おすすめは？');
        questions.push('似たような本を他にも教えて');
    }
    
    // 評価に関する質問の場合
    if (message.includes('評価')) {
        questions.push('評価が低い本も見せて');
        questions.push('最近評価した本は？');
    }
    
    // 期間に関する質問の場合
    if (message.includes('今月') || message.includes('今年')) {
        questions.push('先月と比較してどう？');
        questions.push('最も読書が進んだ月は？');
    }
    
    return questions.slice(0, 3); // 最大3つまで
}

// フォローアップ質問ボタンの表示
function displayFollowUpQuestions(questions) {
    if (questions.length === 0) return '';
    
    let html = '<div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded">';
    html += '<p class="text-xs text-gray-600 dark:text-gray-300 mb-2">🔄 関連する質問：</p>';
    html += '<div class="space-y-1">';
    
    questions.forEach(q => {
        html += `<button onclick="quickQuestion('${q}')" class="w-full text-left p-2 bg-white dark:bg-gray-800 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded text-xs transition-colors border border-gray-200 dark:border-gray-600">`;
        html += `<span class="text-gray-700 dark:text-gray-300">${q}</span>`;
        html += '</button>';
    });
    
    html += '</div></div>';
    return html;
}

// メッセージ表示関数
function displayMessage(content, sender) {
    const chatMessages = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `flex ${sender === 'user' ? 'justify-end' : 'justify-start'} mb-4`;
    
    // アシスタントのメッセージはマークダウンをレンダリング
    const renderedContent = sender === 'assistant' ? renderMarkdown(content) : content.replace(/\n/g, '<br>');
    
    if (sender === 'user') {
        messageDiv.innerHTML = `
            <div class="flex items-start max-w-xs lg:max-w-md">
                <div class="bg-green-500 text-white px-4 py-2 rounded-lg">
                    ${renderedContent}
                </div>
                <?php if (isset($template_user_info['photo_url']) && !empty($template_user_info['photo_url']) && $template_user_info['photo_url'] !== '/img/no-photo.png'): ?>
                <img src="<?php echo htmlspecialchars($template_user_info['photo_url']); ?>" alt="ユーザー" class="w-8 h-8 rounded-full ml-2 flex-shrink-0 object-cover">
                <?php else: ?>
                <div class="w-8 h-8 bg-gray-400 dark:bg-gray-600 rounded-full ml-2 flex-shrink-0 flex items-center justify-center">
                    <i class="fas fa-user text-white text-xs"></i>
                </div>
                <?php endif; ?>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="flex items-start max-w-xs lg:max-w-md">
                <img src="/favicon.png" alt="アシスタント" class="w-8 h-8 object-contain mr-2 flex-shrink-0">
                <div class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-lg">
                    ${renderedContent}
                </div>
            </div>
        `;
    }
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// チャット送信関数
async function sendChat() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message || isProcessing) return;
    
    isProcessing = true;
    const sendButton = document.getElementById('sendButton');
    sendButton.disabled = true;
    
    // ユーザーメッセージを表示
    displayMessage(message, 'user');
    input.value = '';
    
    // 会話履歴に追加
    conversation.push({ 
        role: 'user', 
        content: message, 
        isUser: true,
        timestamp: Date.now()
    });
    
    // タイピングインジケーター表示
    showTypingIndicator();
    
    try {
        // 会話履歴を準備（MCPツールで直接データ取得するため、コンテキストはシンプルに）
        const conversationHistory = conversation
            .slice(-10) // 直近10件の会話
            .map(msg => ({
                role: msg.isUser ? 'user' : 'assistant',
                content: msg.content
            }));

        const response = await fetch('/api/ai_assistant_mcp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                conversation_history: conversationHistory
            })
        });
        
        const data = await response.json();
        
        removeTypingIndicator();
        
        if (data.success) {
            // メッセージを表示
            displayMessage(data.response, 'assistant');
            
            // フォローアップ質問を生成して表示
            const followUpQuestions = generateFollowUpQuestions(message, data.response);
            if (followUpQuestions.length > 0) {
                const chatMessages = document.getElementById('chatMessages');
                const followUpDiv = document.createElement('div');
                followUpDiv.innerHTML = displayFollowUpQuestions(followUpQuestions);
                chatMessages.appendChild(followUpDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // 会話履歴に追加
            conversation.push({ 
                role: 'assistant', 
                content: data.response, 
                isUser: false,
                timestamp: Date.now()
            });
            
            // 共有ストレージに保存
            saveSharedConversation(conversation);
        } else {
            displayMessage('申し訳ございません。エラーが発生しました。', 'assistant');
        }
    } catch (error) {
        console.error('Error:', error);
        removeTypingIndicator();
        displayMessage('通信エラーが発生しました。', 'assistant');
    } finally {
        isProcessing = false;
        sendButton.disabled = false;
    }
}

// クイック質問
function quickQuestion(question) {
    document.getElementById('chatInput').value = question;
    sendChat();
}

// 本をクリックしたときの処理
function handleBookClick(title, author) {
    const message = `「${title}」（${author}）について詳しく教えてください。この本の評価やレビュー、似た本のおすすめなども知りたいです。`;
    document.getElementById('chatInput').value = message;
    sendChat();
}

// タグをクリックしたときの処理
function handleTagClick(tag) {
    const message = `「${tag}」ジャンルのおすすめの本を教えてください。最近のベストセラーや評価の高い作品を知りたいです。`;
    document.getElementById('chatInput').value = message;
    sendChat();
}

// 会話をクリア（履歴は保持）
function clearChat() {
    if (confirm('現在の会話をリセットしますか？（履歴は保持されます）')) {
        // 現在の会話を履歴として保存
        if (conversation.length > 0) {
            const history = loadConversationHistory();
            history.push({
                id: Date.now(),
                date: new Date().toISOString(),
                messages: [...conversation]
            });
            saveConversationHistory(history);
        }
        
        // 現在の会話のみクリア
        conversation = [];
        document.getElementById('chatMessages').innerHTML = '';
        showWelcomeMessage();
        // 共有会話もクリア（履歴は別管理）
        saveSharedConversation([]);
    }
}

// 会話履歴の保存
function saveConversationHistory(history) {
    try {
        localStorage.setItem('readnest_assistant_history', JSON.stringify(history));
        updateHistoryCount(); // バッジを更新
    } catch (e) {
        console.error('Failed to save conversation history:', e);
    }
}

// 会話履歴の読み込み
function loadConversationHistory() {
    try {
        const stored = localStorage.getItem('readnest_assistant_history');
        if (stored) {
            return JSON.parse(stored);
        }
    } catch (e) {
        console.error('Failed to load conversation history:', e);
    }
    return [];
}

// タイピングインジケーター表示
let typingIndicatorDiv = null;
function showTypingIndicator() {
    const chatMessages = document.getElementById('chatMessages');
    typingIndicatorDiv = document.createElement('div');
    typingIndicatorDiv.className = 'flex justify-start mb-4';
    typingIndicatorDiv.innerHTML = `
        <div class="flex items-start max-w-xs lg:max-w-md">
            <img src="/favicon.png" alt="アシスタント" class="w-8 h-8 object-contain mr-2 flex-shrink-0">
            <div class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-lg">
                <div class="flex space-x-1">
                    <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
            </div>
        </div>
    `;
    chatMessages.appendChild(typingIndicatorDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// タイピングインジケーター削除
function removeTypingIndicator() {
    if (typingIndicatorDiv) {
        typingIndicatorDiv.remove();
        typingIndicatorDiv = null;
    }
}

// 履歴件数バッジを更新
function updateHistoryCount() {
    const history = loadConversationHistory();
    const countBadge = document.getElementById('historyCount');
    if (history.length > 0) {
        countBadge.textContent = history.length;
        countBadge.classList.remove('hidden');
    } else {
        countBadge.classList.add('hidden');
    }
}

// 履歴パネルの表示切り替え
function toggleHistoryPanel() {
    const panel = document.getElementById('historyPanel');
    if (panel.classList.contains('hidden')) {
        showHistoryList();
        panel.classList.remove('hidden');
    } else {
        panel.classList.add('hidden');
    }
}

// 履歴リストの表示
function showHistoryList() {
    const historyList = document.getElementById('historyList');
    const history = loadConversationHistory();
    
    if (history.length === 0) {
        historyList.innerHTML = '<p class="text-gray-500 text-center py-8">履歴がありません</p>';
        return;
    }
    
    // 新しい順に表示
    const sortedHistory = history.sort((a, b) => b.id - a.id);
    
    historyList.innerHTML = sortedHistory.map(session => {
        const date = new Date(session.date);
        const dateStr = date.toLocaleDateString('ja-JP') + ' ' + date.toLocaleTimeString('ja-JP', {hour: '2-digit', minute: '2-digit'});
        const firstMessage = session.messages.find(m => m.isUser)?.content || 'メッセージなし';
        const truncatedMessage = firstMessage.length > 50 ? firstMessage.substring(0, 50) + '...' : firstMessage;
        
        return `
            <div class="bg-gray-50 border rounded-lg p-3 mb-3 hover:bg-gray-100 cursor-pointer transition-colors">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1" onclick="restoreHistory(${session.id})">
                        <div class="text-xs text-gray-500 mb-1">${dateStr}</div>
                        <div class="text-sm text-gray-700">${escapeHtml(truncatedMessage)}</div>
                        <div class="text-xs text-gray-500 mt-1">${session.messages.length}件のメッセージ</div>
                    </div>
                    <button onclick="deleteHistory(${session.id})" class="text-red-500 hover:text-red-700 ml-2" title="削除">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// 履歴の復元
function restoreHistory(historyId) {
    const history = loadConversationHistory();
    const session = history.find(h => h.id === historyId);
    
    if (!session) {
        alert('履歴が見つかりませんでした');
        return;
    }
    
    if (confirm('この履歴を復元しますか？現在の会話は保存されます。')) {
        // 現在の会話を保存
        if (conversation.length > 0) {
            history.push({
                id: Date.now(),
                date: new Date().toISOString(),
                messages: [...conversation]
            });
            saveConversationHistory(history);
        }
        
        // 履歴を復元
        conversation = [...session.messages];
        saveSharedConversation(conversation);
        
        // チャット画面を再描画
        document.getElementById('chatMessages').innerHTML = '';
        conversation.forEach(msg => {
            displayMessage(msg.content, msg.isUser ? 'user' : 'assistant');
        });
        
        // 履歴パネルを閉じる
        toggleHistoryPanel();
    }
}

// 履歴の削除
function deleteHistory(historyId) {
    if (confirm('この履歴を削除しますか？')) {
        let history = loadConversationHistory();
        history = history.filter(h => h.id !== historyId);
        saveConversationHistory(history);
        showHistoryList();
    }
}

// HTMLエスケープ
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ウェルカムメッセージ表示
function showWelcomeMessage() {
    const welcomeMessage = `こんにちは！ReadNestの読書アシスタントです。

あなたの読書に関する質問にお答えしたり、おすすめの本を提案したりできます。

左側のリストから本やタグをクリックして質問することもできます。

何かお手伝いできることはありますか？`;
    
    displayMessage(welcomeMessage, 'assistant');
    conversation.push({ 
        role: 'assistant', 
        content: welcomeMessage, 
        isUser: false,
        timestamp: Date.now()
    });
    saveSharedConversation(conversation);
}

// ストレージの変更を監視（他のタブ/ウィンドウとの同期）
window.addEventListener('storage', function(e) {
    if (e.key === ASSISTANT_STORAGE_KEY) {
        try {
            const data = JSON.parse(e.newValue);
            if (data && data.messages) {
                // 新しいメッセージがある場合は表示
                const newMessages = data.messages.slice(conversation.length);
                newMessages.forEach(msg => {
                    displayMessage(msg.content, msg.isUser ? 'user' : 'assistant');
                });
                conversation = data.messages;
            }
        } catch (error) {
            console.error('Failed to sync conversation:', error);
        }
    }
});

// チャットウィンドウの高さを調整する関数
function adjustChatHeight() {
    const leftColumn = document.querySelector('.lg\\:col-span-1');
    const chatContainer = document.getElementById('chatContainer');
    
    if (leftColumn && chatContainer && window.innerWidth >= 1024) { // lg以上の画面サイズ
        const leftHeight = leftColumn.offsetHeight;
        // 左側のコンテンツと同じ高さにする
        chatContainer.style.height = leftHeight + 'px';
    } else if (chatContainer) {
        // モバイルやタブレットでは固定の高さ
        chatContainer.style.height = '600px';
    }
}

// ページ読み込み時の初期化
window.addEventListener('DOMContentLoaded', function() {
    // 共有会話を復元
    const sharedMessages = loadSharedConversation();
    if (sharedMessages.length > 0) {
        conversation = sharedMessages;
        sharedMessages.forEach(msg => {
            displayMessage(msg.content, msg.isUser ? 'user' : 'assistant');
        });
    } else {
        // 新規セッションの場合のみ初期メッセージを表示
        showWelcomeMessage();
    }
    
    // チャット高さを調整
    adjustChatHeight();
    
    // ウィンドウリサイズ時にも高さを調整
    window.addEventListener('resize', adjustChatHeight);
    
    // 履歴件数バッジを更新
    updateHistoryCount();
    
    // Enterキーでの送信を有効化
    document.getElementById('chatInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChat();
        }
    });
});
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>