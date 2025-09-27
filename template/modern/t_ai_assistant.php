<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// メインコンテンツを生成
ob_start();
?>

<!-- 読書アシスタントページ -->
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <!-- ヘッダー -->
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-gray-800 dark:to-gray-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white dark:bg-gray-700 bg-opacity-20 dark:bg-opacity-30 rounded-full mb-4">
                    <i class="fas fa-robot text-4xl"></i>
                </div>
                <h1 class="text-4xl font-bold mb-4">
                    読書アシスタント
                </h1>
                <p class="text-xl opacity-90 max-w-3xl mx-auto">
                    あなたの読書相談パートナー
                </p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!$login_flag): ?>
        <!-- 未ログイン時の案内 -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-8">
            <div class="text-center">
                <i class="fas fa-lock text-5xl text-gray-400 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">ログインが必要です</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    読書アシスタントを利用するには、ログインが必要です。<br>
                    あなたの読書履歴に基づいた、パーソナライズされた推薦を提供します。
                </p>
                <div class="flex justify-center space-x-4">
                    <a href="/register.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors shadow-md hover:shadow-lg">
                        <i class="fas fa-user-plus mr-2"></i>新規登録
                    </a>
                    <a href="/" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>ログイン
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 左側：機能紹介 -->
            <div class="lg:col-span-1 space-y-6">
                <!-- できること -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <i class="fas fa-sparkles text-purple-600 mr-2"></i>
                        読書アシスタントができること
                    </h2>
                    <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                            <span>あなたの読書履歴を分析して、次に読むべき本を提案</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                            <span>気分やシチュエーションに合わせた本の推薦</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                            <span>読書の悩みや質問への回答</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                            <span>特定の本に似た作品の紹介</span>
                        </li>
                    </ul>
                </div>

                <?php if (!empty($recent_books) || !empty($favorite_genres)): ?>
                <!-- あなたの読書傾向 -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <i class="fas fa-chart-line text-purple-600 mr-2"></i>
                        あなたの読書傾向
                    </h2>
                    
                    <?php if (!empty($recent_books) && is_array($recent_books)): ?>
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">最近読んだ本</h3>
                        <div class="space-y-2">
                            <?php foreach (array_slice($recent_books, 0, 3) as $book): ?>
                            <div class="flex items-center space-x-2">
                                <img src="<?php echo htmlspecialchars($book['image_url'] ?? '/img/no-image-book.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                                     class="w-8 h-10 object-cover rounded shadow-sm"
                                     onerror="this.src='/img/no-image-book.png'">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-800 dark:text-gray-200 truncate"><?php echo htmlspecialchars($book['title']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($favorite_genres) && is_array($favorite_genres)): ?>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">よく読むジャンル</h3>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach (array_slice($favorite_genres, 0, 5) as $genre): ?>
                            <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-1 rounded-full text-xs">
                                <?php echo htmlspecialchars($genre['tag_name']); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- 右側：チャットインターフェース -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-gray-700 dark:to-gray-600 text-white p-4">
                        <h2 class="text-xl font-bold flex items-center">
                            <i class="fas fa-comments mr-3"></i>
                            チャット
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <!-- チャット履歴表示エリア -->
                        <div id="chat-messages" class="h-96 overflow-y-auto mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <!-- 初期メッセージ -->
                            <div class="flex items-start mb-4">
                                <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-robot text-white"></i>
                                </div>
                                <div class="ml-3 bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm max-w-lg">
                                    <p class="text-gray-800 dark:text-gray-200">
                                        こんにちは！読書アシスタントです。<br>
                                        どのような本をお探しですか？お気軽にご相談ください。
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 入力エリア -->
                        <div class="flex space-x-3">
                            <input type="text" 
                                   id="chat-input"
                                   placeholder="例：ミステリー小説でおすすめは？"
                                   class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   onkeypress="if(event.key === 'Enter' && !event.shiftKey) sendMessage()">
                            <button onclick="sendMessage()"
                                    id="send-button"
                                    class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 text-white px-6 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 dark:hover:from-purple-800 dark:hover:to-pink-800 transition-all shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-paper-plane mr-2"></i>送信
                            </button>
                        </div>

                        <!-- クイック質問 -->
                        <div class="mt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">クイック質問：</p>
                            <div class="flex flex-wrap gap-2">
                                <button onclick="quickQuestion('今月のベストセラーを教えて')"
                                        class="text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-3 py-1.5 rounded-full hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors cursor-pointer">
                                    今月のベストセラー
                                </button>
                                <button onclick="quickQuestion('初心者向けのミステリー小説')"
                                        class="text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-3 py-1.5 rounded-full hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors cursor-pointer">
                                    初心者向けミステリー
                                </button>
                                <button onclick="quickQuestion('1時間で読める短編集')"
                                        class="text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-3 py-1.5 rounded-full hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors cursor-pointer">
                                    短編集
                                </button>
                                <button onclick="quickQuestion('感動する小説')"
                                        class="text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-3 py-1.5 rounded-full hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors cursor-pointer">
                                    感動小説
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
let isProcessing = false;

function sendMessage() {
    if (isProcessing) return;
    
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    const sendButton = document.getElementById('send-button');
    
    if (!message) return;
    
    isProcessing = true;
    sendButton.disabled = true;
    
    // ユーザーメッセージを追加
    addMessageToChat(message, 'user');
    input.value = '';
    
    // 入力中インジケーターを表示
    const typingId = showTypingIndicator();
    
    // APIを呼び出し
    fetch('/api/ai_assistant_chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        removeTypingIndicator(typingId);
        if (data.success) {
            addMessageToChat(data.response, 'ai');
        } else {
            addMessageToChat(data.message || 'エラーが発生しました', 'ai');
        }
    })
    .catch(error => {
        removeTypingIndicator(typingId);
        addMessageToChat('通信エラーが発生しました。もう一度お試しください。', 'ai');
    })
    .finally(() => {
        isProcessing = false;
        sendButton.disabled = false;
    });
}

function quickQuestion(question) {
    document.getElementById('chat-input').value = question;
    sendMessage();
}

function addMessageToChat(message, sender) {
    const chatMessages = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'flex items-start mb-4';
    
    if (sender === 'user') {
        messageDiv.innerHTML = `
            <div class="ml-auto flex items-start">
                <div class="mr-3 bg-purple-600 dark:bg-purple-700 text-white p-4 rounded-lg max-w-lg">
                    <p>${escapeHtml(message)}</p>
                </div>
                <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user text-white"></i>
                </div>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-robot text-white"></i>
            </div>
            <div class="ml-3 bg-white p-4 rounded-lg shadow-sm max-w-lg">
                <p class="text-gray-800 dark:text-gray-200">${escapeHtml(message).replace(/\n/g, '<br>')}</p>
            </div>
        `;
    }
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function showTypingIndicator() {
    const chatMessages = document.getElementById('chat-messages');
    const indicatorDiv = document.createElement('div');
    const indicatorId = 'typing-' + Date.now();
    indicatorDiv.id = indicatorId;
    indicatorDiv.className = 'flex items-start mb-4';
    indicatorDiv.innerHTML = `
        <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
            <i class="fas fa-robot text-white"></i>
        </div>
        <div class="ml-3 bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm">
            <div class="flex space-x-2">
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
        </div>
    `;
    chatMessages.appendChild(indicatorDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return indicatorId;
}

function removeTypingIndicator(indicatorId) {
    const indicator = document.getElementById(indicatorId);
    if (indicator) {
        indicator.remove();
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>