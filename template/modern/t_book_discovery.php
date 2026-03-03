<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// データを取得
$top_genres = $discovery_data['top_genres'] ?? [];
$user_stats = $discovery_data['user_stats'] ?? [];

// メインコンテンツを生成
ob_start();
?>

<!-- 本の発見ページ -->
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- ヘッダー -->
    <div class="bg-gradient-to-r from-indigo-700 via-purple-700 to-pink-600 text-white shadow-md">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <div class="flex items-center mb-2">
                <i class="fas fa-compass text-2xl mr-3 opacity-90"></i>
                <div>
                    <h1 class="text-2xl font-bold">本の発見</h1>
                    <p class="text-sm opacity-90">あなたの読書傾向をAIが分析し、まだ出会っていない本を「なぜこの本？」付きで提案します</p>
                </div>
            </div>
            <?php if (!empty($user_stats['highly_rated'])): ?>
            <div class="mt-3 flex items-center gap-4 text-xs opacity-80">
                <span><i class="fas fa-book mr-1"></i>蔵書 <?php echo number_format((int)$user_stats['total']); ?>冊</span>
                <span><i class="fas fa-star mr-1"></i>高評価 <?php echo number_format((int)$user_stats['highly_rated']); ?>冊</span>
                <span><i class="fas fa-chart-line mr-1"></i>平均評価 <?php echo round((float)($user_stats['avg_rating'] ?? 0), 1); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6">

        <!-- サジェストチップ -->
        <div class="mb-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2"><i class="fas fa-lightbulb mr-1"></i>こんな気分で探してみましょう：</p>
            <div class="flex flex-wrap gap-2">
                <button onclick="handleChipClick('骨太な長編に没入したい')"
                        class="inline-block bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 px-3 py-1.5 rounded-full text-sm cursor-pointer hover:bg-purple-200 dark:hover:bg-purple-900/60 transition-colors">
                    骨太な長編に没入したい
                </button>
                <button onclick="handleChipClick('技術書に疲れた。知的好奇心は満たしたい')"
                        class="inline-block bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-3 py-1.5 rounded-full text-sm cursor-pointer hover:bg-blue-200 dark:hover:bg-blue-900/60 transition-colors">
                    技術書に疲れた。知的好奇心は満たしたい
                </button>
                <button onclick="handleChipClick('泣ける小説')"
                        class="inline-block bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300 px-3 py-1.5 rounded-full text-sm cursor-pointer hover:bg-pink-200 dark:hover:bg-pink-900/60 transition-colors">
                    泣ける小説
                </button>
                <button onclick="handleChipClick('視野を広げる本が読みたい')"
                        class="inline-block bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 px-3 py-1.5 rounded-full text-sm cursor-pointer hover:bg-green-200 dark:hover:bg-green-900/60 transition-colors">
                    視野を広げる本が読みたい
                </button>
                <?php foreach ($top_genres as $genre): ?>
                <button onclick="handleChipClick('<?php echo htmlspecialchars($genre['tag_name'], ENT_QUOTES); ?>のおすすめを教えて')"
                        class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-1.5 rounded-full text-sm cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <?php echo htmlspecialchars($genre['tag_name']); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 入力エリア -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex gap-3">
                <textarea id="discovery-input"
                          rows="2"
                          class="flex-1 resize-none border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none"
                          placeholder="今読みたい本の気分を教えてください...（例：「ヘイル・メアリー読んで泣いた。ああいうの読みたい」）"
                          onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();submitDiscovery()}"></textarea>
                <button id="discovery-submit"
                        onclick="submitDiscovery()"
                        class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition-all shadow-sm self-end disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-magic mr-1"></i>探す
                </button>
            </div>
        </div>

        <!-- ローディング -->
        <div id="discovery-loading" class="hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-purple-200 border-t-purple-600 mb-4"></div>
                <p id="loading-step" class="text-sm text-gray-600 dark:text-gray-300 font-medium">読書プロフィールを分析中...</p>
                <div class="mt-4 flex justify-center gap-2">
                    <div id="step-dot-1" class="w-2.5 h-2.5 rounded-full bg-purple-600"></div>
                    <div id="step-dot-2" class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                    <div id="step-dot-3" class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                </div>
            </div>
        </div>

        <!-- エラー表示 -->
        <div id="discovery-error" class="hidden">
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p id="error-message" class="text-sm text-red-700 dark:text-red-300"></p>
                </div>
                <button onclick="submitDiscovery()" class="mt-3 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 underline">
                    <i class="fas fa-redo mr-1"></i>再試行
                </button>
            </div>
        </div>

        <!-- 結果エリア -->
        <div id="discovery-results" class="hidden space-y-6">

            <!-- プロファイルセクション -->
            <div id="profile-section" x-data="{ open: false }" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <button @click="open = !open" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-user-circle text-purple-600 dark:text-purple-400 mr-3 text-lg"></i>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">あなたの読書プロファイル</h2>
                            <p id="profile-summary" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-show="open" x-transition class="px-4 pb-4 border-t border-gray-100 dark:border-gray-700">
                    <div id="profile-details" class="pt-4 space-y-4"></div>
                </div>
            </div>

            <!-- 推薦カードリスト -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        <i class="fas fa-book-open text-purple-600 dark:text-purple-400 mr-2"></i>おすすめの本
                    </h2>
                    <span id="result-count" class="text-xs text-gray-500 dark:text-gray-400"></span>
                </div>
                <div id="recommendation-cards" class="space-y-4"></div>
            </div>

            <!-- フィルタリング統計 -->
            <div id="filter-stats" class="text-center">
                <p class="text-xs text-gray-400 dark:text-gray-500"></p>
            </div>
        </div>
    </div>
</div>

<!-- 本追加モーダル -->
<div id="add-book-modal" class="fixed inset-0 bg-black/50 z-50 overflow-y-auto"
     style="display: none; align-items: center; justify-content: center;"
     onclick="if(event.target===this) closeAddBookModal()">
    <div class="relative mx-auto w-full max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 my-8 mx-4">
        <!-- ヘッダー -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                <i class="fas fa-search mr-2 text-purple-600"></i>本を検索して追加
            </h3>
            <button onclick="closeAddBookModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- 検索クエリ表示 -->
        <div class="px-4 pt-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                検索: <span id="modal-search-query" class="font-medium text-gray-700 dark:text-gray-300"></span>
            </p>
        </div>

        <!-- ローディング -->
        <div id="modal-loading" class="hidden p-8 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-purple-200 border-t-purple-600 mb-3"></div>
            <p class="text-sm text-gray-600 dark:text-gray-300">Google Booksを検索中...</p>
        </div>

        <!-- エラー表示 -->
        <p id="modal-error" class="hidden mx-4 mt-3 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 p-3 rounded-lg"></p>

        <!-- 検索結果 -->
        <div id="modal-results" class="p-4 space-y-2 max-h-96 overflow-y-auto"></div>

        <!-- フッター -->
        <div class="px-4 pb-4 text-center">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                見つからない場合は<button onclick="closeAddBookModal()" class="text-purple-600 dark:text-purple-400 hover:underline">閉じて</button>「手動で追加」をお試しください
            </p>
        </div>
    </div>
</div>

<script src="/js/book_discovery.js"></script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>
