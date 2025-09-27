<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// メインコンテンツを生成
ob_start();
?>

<!-- ReadNest AIアシスタントページ -->
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-indigo-50">
    <!-- ヘッダー -->
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-gray-800 dark:to-gray-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white bg-opacity-20 rounded-full mb-4">
                    <i class="fas fa-robot text-4xl"></i>
                </div>
                <h1 class="text-4xl font-bold mb-4">
                    ReadNest AIアシスタント
                </h1>
                <p class="text-xl opacity-90 max-w-3xl mx-auto">
                    あなたの気分や状況に合わせて、最適な本をご提案します
                </p>
                <div class="mt-6 flex items-center justify-center space-x-2 text-sm">
                    <span class="bg-yellow-400 text-black px-3 py-1 rounded-full font-semibold">
                        GPT-4搭載
                    </span>
                    <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full">
                        24時間対応
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!$login_flag): ?>
        <!-- 未ログイン時の案内 -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <div class="text-center">
                <i class="fas fa-lock text-5xl text-gray-400 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">ログインして全機能を利用</h2>
                <p class="text-gray-600 mb-6">
                    ログインすると、あなたの読書履歴に基づいたパーソナライズされた提案が受けられます
                </p>
                <div class="flex justify-center space-x-4">
                    <a href="/register.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>新規登録
                    </a>
                    <a href="/" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>ログイン
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- クイックアクション -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- 今の気分で探す -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer" 
                 onclick="selectMood()">
                <div class="w-12 h-12 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-smile text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">今の気分で探す</h3>
                <p class="text-sm text-gray-600">
                    「元気になりたい」「泣きたい」など、気分に合わせた本を提案
                </p>
            </div>

            <!-- シチュエーション別 -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer"
                 onclick="selectSituation()">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-indigo-500 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-map-signs text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">シチュエーション別</h3>
                <p class="text-sm text-gray-600">
                    通勤時間、寝る前、休日など、状況に最適な本を提案
                </p>
            </div>

            <!-- 読書相談 -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer"
                 onclick="startConsultation()">
                <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-teal-500 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-comments text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">読書相談</h3>
                <p class="text-sm text-gray-600">
                    読書の悩みや質問にAIがお答えします
                </p>
            </div>

            <!-- 類似本を探す -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer"
                 onclick="findSimilarBooks()">
                <div class="w-12 h-12 bg-gradient-to-r from-purple-400 to-pink-500 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-search text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">類似本を探す</h3>
                <p class="text-sm text-gray-600">
                    お気に入りの本に似た作品を見つけます
                </p>
            </div>
        </div>

        <!-- チャットインターフェース -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-4">
                <h2 class="text-xl font-bold flex items-center">
                    <i class="fas fa-robot mr-3"></i>
                    ReadNest AIアシスタント
                </h2>
            </div>
            
            <div class="p-6">
                <!-- チャット履歴表示エリア -->
                <div id="chat-messages" class="h-96 overflow-y-auto mb-6 p-4 bg-gray-50 rounded-lg">
                    <!-- 初期メッセージ -->
                    <div class="flex items-start mb-4">
                        <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-robot text-white"></i>
                        </div>
                        <div class="ml-3 bg-white p-4 rounded-lg shadow-sm max-w-lg">
                            <p class="text-gray-800">
                                こんにちは！ReadNest AIアシスタントです。<br>
                                どのような本をお探しですか？気分や読みたいジャンル、最近読んで面白かった本など、なんでもお聞かせください。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 入力エリア -->
                <div class="flex space-x-3">
                    <input type="text" 
                           id="chat-input"
                           placeholder="例：元気が出る本を教えて / ミステリー小説のおすすめは？"
                           class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400"
                           onkeypress="if(event.key === 'Enter') sendMessage()">
                    <button onclick="sendMessage()"
                            class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition-all">
                        <i class="fas fa-paper-plane mr-2"></i>送信
                    </button>
                </div>

                <!-- サンプル質問 -->
                <div class="mt-4">
                    <p class="text-sm text-gray-600 mb-2">こんな質問もできます：</p>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="sendSampleMessage('今週末に読むのにおすすめの本は？')"
                                class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition-colors">
                            今週末に読むのにおすすめの本は？
                        </button>
                        <button onclick="sendSampleMessage('初心者向けの投資の本を教えて')"
                                class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition-colors">
                            初心者向けの投資の本
                        </button>
                        <button onclick="sendSampleMessage('子供と一緒に読める本')"
                                class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition-colors">
                            子供と一緒に読める本
                        </button>
                        <button onclick="sendSampleMessage('通勤時間30分で読める短編集')"
                                class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition-colors">
                            通勤時間に読める短編
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($login_flag && !empty($recent_books)): ?>
        <!-- あなたの読書傾向 -->
        <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-chart-line text-purple-600 mr-3"></i>
                あなたの読書傾向
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 最近読んだ本 -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">最近読んだ本</h3>
                    <div class="space-y-2">
                        <?php if (is_array($recent_books) && count($recent_books) > 0): ?>
                            <?php foreach (array_slice($recent_books, 0, 5) as $book): ?>
                            <div class="flex items-center space-x-2">
                                <img src="<?php echo htmlspecialchars($book['image_url'] ?? '/img/no-image-book.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                                     class="w-8 h-10 object-cover rounded"
                                     onerror="this.src='/img/no-image-book.png'">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800 truncate"><?php echo htmlspecialchars($book['title']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($book['author']); ?></p>
                                </div>
                                <?php if (!empty($book['rating'])): ?>
                                <div class="text-yellow-500 text-xs">
                                    <?php for ($i = 0; $i < $book['rating']; $i++): ?>★<?php endfor; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">まだ読了した本がありません</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- よく読むジャンル -->
                <?php if (!empty($favorite_genres) && is_array($favorite_genres)): ?>
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">よく読むジャンル</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($favorite_genres as $genre): ?>
                        <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm">
                            <?php echo htmlspecialchars($genre['tag_name']); ?>
                            <span class="text-xs opacity-75">(<?php echo $genre['count']; ?>冊)</span>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">よく読むジャンル</h3>
                    <p class="text-sm text-gray-500">ジャンル分析にはもう少し読書記録が必要です</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 p-3 bg-purple-50 rounded-lg">
                <p class="text-sm text-purple-700">
                    <i class="fas fa-lightbulb mr-2"></i>
                    AIはあなたの読書履歴を分析して、より精度の高い提案をします
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- 機能説明 -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg p-6 border-2 border-purple-200">
                <div class="text-purple-600 text-3xl mb-3">
                    <i class="fas fa-brain"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">高度なAI分析</h3>
                <p class="text-sm text-gray-600">
                    GPT-4を活用し、あなたの好みや状況を深く理解して最適な本を提案
                </p>
            </div>

            <div class="bg-white rounded-lg p-6 border-2 border-pink-200">
                <div class="text-pink-600 text-3xl mb-3">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">パーソナライズ</h3>
                <p class="text-sm text-gray-600">
                    読書履歴と評価から、あなただけの推薦リストを作成
                </p>
            </div>

            <div class="bg-white rounded-lg p-6 border-2 border-indigo-200">
                <div class="text-indigo-600 text-3xl mb-3">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">対話型サポート</h3>
                <p class="text-sm text-gray-600">
                    チャット形式で気軽に相談。読書の悩みも解決
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// チャット機能
function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // ユーザーメッセージを追加
    addMessageToChat(message, 'user');
    input.value = '';
    
    // AIの応答を取得（ここでは仮実装）
    setTimeout(() => {
        addMessageToChat('ご質問ありがとうございます。現在、この機能は開発中です。まもなくご利用いただけるようになります。', 'ai');
    }, 1000);
}

function sendSampleMessage(message) {
    document.getElementById('chat-input').value = message;
    sendMessage();
}

function addMessageToChat(message, sender) {
    const chatMessages = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'flex items-start mb-4';
    
    if (sender === 'user') {
        messageDiv.innerHTML = `
            <div class="ml-auto flex items-start">
                <div class="mr-3 bg-purple-600 text-white p-4 rounded-lg max-w-lg">
                    <p>${message}</p>
                </div>
                <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
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
                <p class="text-gray-800">${message}</p>
            </div>
        `;
    }
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// クイックアクション
function selectMood() {
    document.getElementById('chat-input').value = '今、元気になれる本を教えてください';
    sendMessage();
}

function selectSituation() {
    document.getElementById('chat-input').value = '通勤時間に読むのにおすすめの本は？';
    sendMessage();
}

function startConsultation() {
    document.getElementById('chat-input').value = '読書を習慣化するコツを教えてください';
    sendMessage();
}

function findSimilarBooks() {
    document.getElementById('chat-input').value = '「ハリー・ポッター」に似た本を教えて';
    sendMessage();
}
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>