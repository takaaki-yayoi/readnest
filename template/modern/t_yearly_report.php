<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// メインコンテンツを生成
ob_start();

// Chart.js用のデータ準備
$monthly_labels = [];
$monthly_books = [];
$monthly_pages = [];
$cumulative_books = [];
$cumulative = 0;
foreach ($report_data['monthly_data'] as $m) {
    $monthly_labels[] = $m['month'] . '月';
    $monthly_books[] = $m['books'];
    $monthly_pages[] = $m['pages'];
    $cumulative += $m['books'];
    $cumulative_books[] = $cumulative;
}

$stats = $report_data['statistics'];
$books = $report_data['books'];

// URLパス用
$url_user_path = !$is_my_report ? "/user/{$target_user_id}" : "";

// 表示中の年がリストに含まれているか確認
$current_in_list = false;
foreach ($available_years as $y) {
    if ($y['year'] == $year) {
        $current_in_list = true;
        break;
    }
}
?>

<style>
/* 年間レポート用カスタムスタイル */
.book-cover-wrapper {
    position: relative;
    width: 100%;
    padding-bottom: 150%;
    background: #f0f0f0;
    overflow: hidden;
}

.book-cover-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: white;
}

.dark .book-cover-img {
    background: rgb(31, 41, 55);
}

.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.book-item {
    transition: transform 0.2s ease;
}

.book-item:hover {
    transform: translateY(-4px);
}

.highlight-card {
    transition: transform 0.2s ease;
}

.highlight-card:hover {
    transform: scale(1.02);
}
</style>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- ヘッダー -->
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold mb-2 text-gray-800 dark:text-gray-100">
            <i class="fas fa-calendar-alt mr-2 text-readnest-accent"></i>
            <?php if (!$is_my_report): ?>
            <?php echo html($display_nickname); ?>さんの年間読書レポート
            <?php else: ?>
            年間読書レポート
            <?php endif; ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
            <?php if (!$is_my_report): ?>
            <?php echo html($display_nickname); ?>さんの1年間の読書記録
            <?php else: ?>
            あなたの1年間の読書を振り返り
            <?php endif; ?>
        </p>
    </div>

    <!-- 年ナビゲーション -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <div class="flex items-center justify-between">
            <a href="/report/<?php echo $prev_year; ?><?php echo $url_user_path; ?>"
               class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                <i class="fas fa-chevron-left mr-2"></i>
                <span class="hidden sm:inline"><?php echo $prev_year; ?>年</span>
                <span class="sm:hidden">前年</span>
            </a>

            <div class="text-center">
                <!-- 年ドロップダウン -->
                <div class="relative inline-block">
                    <select id="year-selector"
                            onchange="if(this.value) location.href='/report/' + this.value + '<?php echo $url_user_path; ?>'"
                            class="appearance-none bg-transparent text-xl md:text-2xl font-bold text-gray-800 dark:text-gray-100 pr-8 cursor-pointer focus:outline-none border-b-2 border-transparent hover:border-readnest-accent focus:border-readnest-accent transition-colors">
                        <?php if (!$current_in_list): ?>
                        <option value="<?php echo $year; ?>" selected>
                            <?php echo $year; ?>年 (0冊)
                        </option>
                        <?php endif; ?>
                        <?php foreach ($available_years as $y): ?>
                        <option value="<?php echo $y['year']; ?>"
                                <?php echo ($y['year'] == $year) ? 'selected' : ''; ?>>
                            <?php echo $y['year']; ?>年 (<?php echo $y['book_count']; ?>冊)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
                <?php if ($year == $current_year): ?>
                <span class="text-xs text-readnest-accent font-medium block mt-1">今年</span>
                <?php endif; ?>
            </div>

            <?php if (!$is_next_future): ?>
            <a href="/report/<?php echo $next_year; ?><?php echo $url_user_path; ?>"
               class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                <span class="hidden sm:inline"><?php echo $next_year; ?>年</span>
                <span class="sm:hidden">次年</span>
                <i class="fas fa-chevron-right ml-2"></i>
            </a>
            <?php else: ?>
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 text-gray-400 rounded-lg cursor-not-allowed">
                <span class="hidden sm:inline">次年</span>
                <span class="sm:hidden">次年</span>
                <i class="fas fa-chevron-right ml-2"></i>
            </div>
            <?php endif; ?>
        </div>

        <!-- 今年へのリンク（今年以外を表示中の場合） -->
        <?php if ($year != $current_year): ?>
        <div class="mt-3 text-center">
            <a href="/report/<?php echo $current_year; ?><?php echo $url_user_path; ?>"
               class="inline-flex items-center px-4 py-2 bg-readnest-primary hover:bg-readnest-accent text-white text-sm rounded-lg transition-colors">
                <i class="fas fa-calendar-day mr-2"></i>今年のレポートを見る
            </a>
        </div>
        <?php endif; ?>

        <!-- 月間レポートへのリンク -->
        <?php if ($is_my_report): ?>
        <div class="mt-3 text-center">
            <a href="/report/<?php echo $year; ?>/<?php echo ($year == $current_year) ? date('n') : 12; ?>"
               class="inline-flex items-center text-sm text-readnest-accent hover:text-readnest-primary transition-colors">
                <i class="fas fa-calendar mr-1"></i><?php echo $year; ?>年の月間レポートを見る
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($report_data['has_data']): ?>

    <!-- 統計カード -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- 読了冊数 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-readnest-accent">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-book-reader text-2xl text-readnest-accent"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">年間読了</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo $stats['books_finished']; ?><span class="text-sm font-normal ml-1">冊</span></p>
                </div>
            </div>
        </div>

        <!-- ページ数 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-file-alt text-2xl text-blue-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">読んだページ</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo number_format($stats['pages_read']); ?><span class="text-sm font-normal ml-1">p</span></p>
                </div>
            </div>
        </div>

        <!-- 読書日数 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calendar-check text-2xl text-purple-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">読書日数</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo $stats['reading_days']; ?><span class="text-sm font-normal ml-1">日</span></p>
                </div>
            </div>
        </div>

        <!-- 月平均 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chart-line text-2xl text-yellow-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">月平均</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo $stats['monthly_average']; ?><span class="text-sm font-normal ml-1">冊</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 年間ハイライト -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            <i class="fas fa-star mr-2 text-yellow-500"></i>年間ハイライト
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- ベスト月 -->
            <div class="highlight-card bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <i class="fas fa-trophy text-green-600 dark:text-green-400 mr-2"></i>
                    <span class="text-sm text-gray-600 dark:text-gray-300">ベスト月</span>
                </div>
                <?php if ($stats['best_month']['count'] > 0): ?>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                    <?php echo $stats['best_month']['month']; ?>月
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <?php echo $stats['best_month']['count']; ?>冊読了
                </p>
                <a href="/report/<?php echo $year; ?>/<?php echo $stats['best_month']['month']; ?><?php echo $url_user_path; ?>"
                   class="inline-block mt-2 text-xs text-green-600 dark:text-green-400 hover:underline">
                    この月を見る <i class="fas fa-arrow-right ml-1"></i>
                </a>
                <?php else: ?>
                <p class="text-gray-400">データなし</p>
                <?php endif; ?>
            </div>

            <!-- 最長連続日数 -->
            <div class="highlight-card bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <i class="fas fa-fire text-orange-600 dark:text-orange-400 mr-2"></i>
                    <span class="text-sm text-gray-600 dark:text-gray-300">最長連続記録</span>
                </div>
                <?php if ($stats['longest_streak'] > 0): ?>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                    <?php echo $stats['longest_streak']; ?>日間
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    連続読書
                </p>
                <?php else: ?>
                <p class="text-gray-400">データなし</p>
                <?php endif; ?>
            </div>

            <!-- 最高評価の本 -->
            <div class="highlight-card bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <i class="fas fa-star text-purple-600 dark:text-purple-400 mr-2"></i>
                    <span class="text-sm text-gray-600 dark:text-gray-300">最高評価</span>
                </div>
                <?php if ($stats['best_rated_book']): ?>
                <p class="text-sm font-bold text-gray-800 dark:text-gray-100 line-clamp-2">
                    <?php echo html($stats['best_rated_book']['name']); ?>
                </p>
                <div class="text-yellow-500 text-sm mt-1">
                    <?php echo str_repeat('★', (int)$stats['best_rated_book']['rating']); ?>
                </div>
                <a href="/book/<?php echo $stats['best_rated_book']['book_id']; ?>"
                   class="inline-block mt-2 text-xs text-purple-600 dark:text-purple-400 hover:underline">
                    詳細を見る <i class="fas fa-arrow-right ml-1"></i>
                </a>
                <?php else: ?>
                <p class="text-gray-400">評価なし</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- AI要約セクション -->
    <?php if ($is_my_report || ($saved_summary && $saved_summary['is_public'])): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            <i class="fas fa-magic mr-2 text-purple-500"></i>AI年間振り返り
        </h3>

        <?php if ($saved_summary): ?>
        <!-- 保存済み要約 -->
        <div id="saved-summary-section">
            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                <?php echo nl2br(html($saved_summary['summary'])); ?>
            </div>
            <?php if ($is_my_report): ?>
            <div class="mt-4 flex items-center justify-between border-t dark:border-gray-700 pt-4">
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="summary-public-toggle"
                               <?php echo $saved_summary['is_public'] ? 'checked' : ''; ?>
                               onchange="updateSummaryVisibility(<?php echo $saved_summary['analysis_id']; ?>, this.checked)"
                               class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">公開する</span>
                    </label>
                </div>
                <button onclick="regenerateSummary()"
                        class="text-sm text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">
                    <i class="fas fa-sync-alt mr-1"></i>再生成
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($is_my_report): ?>
        <!-- 生成セクション（未生成時または再生成時に表示） -->
        <div id="generate-section" class="<?php echo $saved_summary ? 'hidden' : ''; ?>">
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                AIがあなたの1年間の読書を振り返り、温かいコメントを生成します。
            </p>
            <button onclick="generateYearlySummary()"
                    id="generate-btn"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-magic mr-2"></i>年間振り返りを生成
            </button>
        </div>

        <!-- 生成結果表示エリア -->
        <div id="summary-result" class="hidden">
            <div id="summary-text" class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed"></div>
            <div class="mt-4 flex items-center justify-between border-t dark:border-gray-700 pt-4">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="new-summary-public" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                    <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">公開する</span>
                </label>
                <button onclick="saveSummary()"
                        id="save-btn"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>保存
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 月別推移グラフ -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            <i class="fas fa-chart-bar mr-2 text-readnest-accent"></i>月別読了冊数
        </h3>
        <div class="h-64 md:h-80">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <!-- 月別リンク -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            <i class="fas fa-calendar mr-2 text-readnest-accent"></i>各月のレポート
        </h3>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
            <?php foreach ($report_data['monthly_data'] as $m): ?>
            <?php
            // 未来の月はスキップ
            if ($year == $current_year && $m['month'] > (int)date('n')) continue;
            ?>
            <a href="/report/<?php echo $year; ?>/<?php echo $m['month']; ?><?php echo $url_user_path; ?>"
               class="block p-3 rounded-lg text-center transition-colors <?php echo $m['books'] > 0 ? 'bg-readnest-accent/10 hover:bg-readnest-accent/20' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                <div class="text-lg font-bold text-gray-800 dark:text-gray-100"><?php echo $m['month']; ?>月</div>
                <div class="text-sm <?php echo $m['books'] > 0 ? 'text-readnest-accent' : 'text-gray-400'; ?>">
                    <?php echo $m['books']; ?>冊
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 読了本リスト -->
    <?php if (!empty($books)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            <i class="fas fa-book mr-2 text-readnest-accent"></i>読了した本（<?php echo count($books); ?>冊）
        </h3>

        <?php
        // 月ごとにグループ化
        $books_by_month = [];
        foreach ($books as $book) {
            $month = (int)$book['finished_month'];
            if (!isset($books_by_month[$month])) {
                $books_by_month[$month] = [];
            }
            $books_by_month[$month][] = $book;
        }
        ksort($books_by_month);
        ?>

        <?php foreach ($books_by_month as $month => $month_books): ?>
        <div class="mb-6">
            <h4 class="text-md font-semibold mb-3 text-gray-700 dark:text-gray-200 border-b dark:border-gray-700 pb-2">
                <a href="/report/<?php echo $year; ?>/<?php echo $month; ?><?php echo $url_user_path; ?>"
                   class="hover:text-readnest-accent transition-colors">
                    <?php echo $month; ?>月
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">（<?php echo count($month_books); ?>冊）</span>
                    <i class="fas fa-external-link-alt text-xs ml-1"></i>
                </a>
            </h4>

            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                <?php foreach ($month_books as $book): ?>
                <a href="/book/<?php echo html($book['book_id']); ?>" class="book-item block group">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                        <div class="book-cover-wrapper">
                            <?php
                            $bookCoverUrl = !empty($book['image_url']) ? $book['image_url'] : '/img/no-image-book.png';
                            ?>
                            <img src="<?php echo html($bookCoverUrl); ?>"
                                 alt="<?php echo html($book['name']); ?>"
                                 class="book-cover-img"
                                 loading="lazy"
                                 onerror="this.src='/img/no-image-book.png'">
                        </div>
                        <div class="p-2">
                            <div class="text-xs font-medium line-clamp-2 text-gray-800 dark:text-gray-100 group-hover:text-readnest-accent transition-colors">
                                <?php echo html($book['name']); ?>
                            </div>
                            <?php if (!empty($book['rating']) && $book['rating'] > 0): ?>
                            <div class="text-yellow-500 text-xs mt-1">
                                <?php echo str_repeat('★', (int)$book['rating']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- シェアセクション（自分のレポートのみ表示） -->
    <?php if ($is_my_report): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            <i class="fas fa-share-alt mr-2 text-readnest-accent"></i>レポートをシェア
        </h3>

        <?php
        $share_text = "{$year}年は{$stats['books_finished']}冊読みました！";
        if ($stats['pages_read'] > 0) {
            $share_text .= "（{$stats['pages_read']}ページ）";
        }
        $share_url = "https://readnest.jp/report/{$year}/user/{$target_user_id}";
        $x_share_url = getXShareUrl($share_text, $share_url, ['読書記録', 'ReadNest', '年間読書']);
        $og_image_url = "/og-image/report/{$year}/{$target_user_id}.png";
        ?>

        <div class="flex flex-wrap gap-3 items-center">
            <!-- Xシェアボタン -->
            <button id="xShareBtn"
                    data-share-url="<?php echo html($x_share_url); ?>"
                    data-og-image="<?php echo html($og_image_url); ?>"
                    class="flex items-center px-4 py-2 bg-black hover:bg-gray-800 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
                <span id="xShareBtnText">Xでシェア</span>
            </button>

            <!-- 画像ダウンロードリンク -->
            <a href="<?php echo html($og_image_url); ?>"
               download="reading_report_<?php echo $year; ?>.png"
               class="flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-lg transition-colors">
                <i class="fas fa-download mr-2"></i>画像を保存
            </a>
        </div>

        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            <i class="fas fa-info-circle mr-1"></i>シェア時に画像を自動生成します（初回は数秒かかります）
        </p>
    </div>
    <?php endif; ?>

    <?php else: ?>

    <!-- データなしの場合 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
        <div class="text-gray-400 mb-4">
            <i class="fas fa-book-open text-6xl"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-300 mb-2">
            この年の読書記録はありません
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">
            <?php if ($is_my_report): ?>
            <?php echo $year; ?>年に読了した本はまだ登録されていません。
            <?php else: ?>
            <?php echo html($display_nickname); ?>さんの<?php echo $year; ?>年の読書記録はありません。
            <?php endif; ?>
        </p>
        <?php if ($is_my_report): ?>
        <a href="/search.php" class="inline-flex items-center px-4 py-2 bg-readnest-primary hover:bg-readnest-accent text-white rounded-lg transition-colors">
            <i class="fas fa-plus mr-2"></i>本を登録する
        </a>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<script>
// Chart.js設定
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($report_data['has_data']): ?>
    const ctx = document.getElementById('monthlyChart');
    if (ctx) {
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#e5e7eb' : '#374151';
        const gridColor = isDark ? '#374151' : '#e5e7eb';

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($monthly_labels); ?>,
                datasets: [
                    {
                        label: '読了冊数',
                        type: 'bar',
                        data: <?php echo json_encode($monthly_books); ?>,
                        backgroundColor: 'rgba(56, 161, 130, 0.6)',
                        borderColor: 'rgba(56, 161, 130, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 2
                    },
                    {
                        label: '累積冊数',
                        type: 'line',
                        data: <?php echo json_encode($cumulative_books); ?>,
                        borderColor: 'rgba(168, 85, 247, 1)',
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgba(168, 85, 247, 1)',
                        fill: false,
                        tension: 0.3,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '冊';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor,
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // AI要約機能
    <?php if ($is_my_report && $report_data['has_data']): ?>
    window.yearlySummaryData = {
        year: <?php echo $year; ?>,
        reportData: {
            stats: <?php echo json_encode($stats, JSON_UNESCAPED_UNICODE); ?>,
            monthly_data: <?php echo json_encode($report_data['monthly_data'], JSON_UNESCAPED_UNICODE); ?>,
            books: <?php echo json_encode(array_map(function($b) {
                return [
                    'title' => mb_substr($b['name'] ?? $b['title'] ?? '', 0, 50),
                    'author' => mb_substr($b['author'] ?? '', 0, 30),
                    'rating' => $b['rating'] ?? 0,
                    'month' => $b['finished_month'] ?? 0,
                    'review' => mb_substr($b['memo'] ?? '', 0, 200)
                ];
            }, array_slice($books, 0, 20)), JSON_UNESCAPED_UNICODE); ?>
        }
    };
    <?php endif; ?>
});

// AI年間振り返りを生成
async function generateYearlySummary() {
    const btn = document.getElementById('generate-btn');
    const generateSection = document.getElementById('generate-section');
    const resultSection = document.getElementById('summary-result');
    const summaryText = document.getElementById('summary-text');

    if (!btn || !window.yearlySummaryData) return;

    // ボタンを無効化
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>生成中...';

    try {
        const response = await fetch('/ai_review_simple.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'generate_yearly_summary',
                year: window.yearlySummaryData.year,
                report_data: window.yearlySummaryData.reportData
            })
        });

        const data = await response.json();

        if (data.success && data.summary) {
            // 生成された要約を表示
            summaryText.innerHTML = data.summary.replace(/\n/g, '<br>');
            window.generatedSummary = data.summary;
            generateSection.classList.add('hidden');
            resultSection.classList.remove('hidden');
        } else {
            alert(data.error || '要約の生成に失敗しました');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic mr-2"></i>年間振り返りを生成';
        }
    } catch (error) {
        console.error('Generate summary error:', error);
        alert('通信エラーが発生しました');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic mr-2"></i>年間振り返りを生成';
    }
}

// 要約を保存
async function saveSummary() {
    const btn = document.getElementById('save-btn');
    const isPublic = document.getElementById('new-summary-public')?.checked || false;

    if (!btn || !window.generatedSummary || !window.yearlySummaryData) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>保存中...';

    try {
        // 保存するコンテンツ（JSON形式）
        const content = JSON.stringify({
            year: window.yearlySummaryData.year,
            summary: window.generatedSummary
        });

        const response = await fetch('/ajax/save_reading_analysis.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                analysis_type: 'yearly_report',
                analysis_content: content,
                is_public: isPublic ? 1 : 0
            })
        });

        const data = await response.json();

        if (data.success) {
            // ページをリロードして保存済み状態を表示
            location.reload();
        } else {
            alert(data.error || '保存に失敗しました');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-2"></i>保存';
        }
    } catch (error) {
        console.error('Save summary error:', error);
        alert('通信エラーが発生しました');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>保存';
    }
}

// 公開設定を更新
async function updateSummaryVisibility(analysisId, isPublic) {
    try {
        const response = await fetch('/ajax/update_analysis_visibility.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                analysis_id: analysisId,
                is_public: isPublic
            })
        });

        const data = await response.json();

        if (!data.success) {
            alert(data.error || '更新に失敗しました');
            // トグルを元に戻す
            document.getElementById('summary-public-toggle').checked = !isPublic;
        }
    } catch (error) {
        console.error('Update visibility error:', error);
        alert('通信エラーが発生しました');
        document.getElementById('summary-public-toggle').checked = !isPublic;
    }
}

// 要約を再生成
function regenerateSummary() {
    if (!confirm('現在の要約を破棄して、新しい要約を生成しますか？')) return;

    // 保存済みセクションを隠して生成セクションを表示
    const savedSection = document.getElementById('saved-summary-section');
    const generateSection = document.getElementById('generate-section');
    const resultSection = document.getElementById('summary-result');

    if (savedSection) savedSection.classList.add('hidden');
    if (generateSection) generateSection.classList.remove('hidden');
    if (resultSection) resultSection.classList.add('hidden');

    // 生成を開始
    generateYearlySummary();
}

document.addEventListener('DOMContentLoaded', function() {
    // Xシェアボタン処理
    const xShareBtn = document.getElementById('xShareBtn');
    if (xShareBtn) {
        xShareBtn.addEventListener('click', async function() {
            const shareUrl = this.dataset.shareUrl;
            const ogImageUrl = this.dataset.ogImage;
            const btnText = document.getElementById('xShareBtnText');
            const originalText = btnText.textContent;

            // ボタンを無効化して状態表示
            this.disabled = true;
            btnText.textContent = '画像を準備中...';

            try {
                // OGP画像を事前に取得（キャッシュ生成）
                const response = await fetch(ogImageUrl, { method: 'HEAD' });

                if (response.ok) {
                    // 画像生成完了、X画面を開く
                    window.open(shareUrl, '_blank', 'noopener,noreferrer');
                } else {
                    // HEADが失敗した場合はGETで試す
                    await fetch(ogImageUrl);
                    window.open(shareUrl, '_blank', 'noopener,noreferrer');
                }
            } catch (error) {
                console.error('OGP image prefetch error:', error);
                // エラーでもシェア画面は開く
                window.open(shareUrl, '_blank', 'noopener,noreferrer');
            } finally {
                this.disabled = false;
                btnText.textContent = originalText;
            }
        });
    }
});
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>
