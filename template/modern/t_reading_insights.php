<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');
require_once(dirname(dirname(__DIR__)) . '/library/book_image_helper.php');

// 画像ヘルパーの初期化
$imageHelper = getBookImageHelper();

// メインコンテンツを生成
ob_start();
?>

<style>
/* 読書インサイト用カスタムスタイル */
/* 本の表紙コンテナ */
.book-cover-wrapper {
    position: relative;
    width: 100%;
    padding-bottom: 150%; /* 2:3 aspect ratio (standard book) */
    background: #f0f0f0;
    overflow: hidden;
    contain: layout style paint;
}

.book-cover-img,
.book-cover-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: white;
    will-change: transform;
}
.dark .book-cover-img,
.dark .book-cover-image {
    background: rgb(31, 41, 55);
}
.book-cover-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
}

.book-cover-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.book-cover-hover:hover {
    transform: translateY(-4px) scale(1.02);
}

/* 本の表紙のシャドウエフェクト */
.book-shadow {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 
                0 1px 2px rgba(0, 0, 0, 0.24);
    will-change: transform, box-shadow;
}
.book-shadow:hover {
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 
                0 3px 6px rgba(0, 0, 0, 0.23);
}

/* グラデーションオーバーレイ */
.book-overlay {
    background: linear-gradient(to top, 
                rgba(0,0,0,0.8) 0%, 
                rgba(0,0,0,0.4) 30%, 
                transparent 100%);
}

/* アニメーション */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}

/* レスポンシブ対応 */
@media (max-width: 640px) {
    .book-cover-container {
        padding-bottom: 140%; /* モバイルでは少し高めに */
    }
}
</style>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- ヘッダー -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2 text-gray-800 dark:text-gray-100">
            <?php if (!$is_my_insights): ?>
                <?php echo html($display_nickname); ?>さんの
            <?php endif; ?>
            📊 読書分析
            <a href="/help.php#reading-insights" class="ml-3 text-base text-gray-500 hover:text-gray-700 transition-colors" title="読書分析のヘルプ">
                <i class="fas fa-question-circle"></i>
            </a>
        </h1>
        <p class="text-gray-600 dark:text-gray-300">AIが分析するあなたの読書世界</p>
    </div>
    
    <!-- ビューモード切り替えタブ -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <nav class="flex flex-wrap border-b">
            <a href="?mode=overview<?php echo !$is_my_insights ? '&user=' . $user_id : ''; ?>" 
               class="px-6 py-3 text-sm font-medium <?php echo $view_mode === 'overview' ? 'border-b-2 border-purple-600 text-purple-600' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-chart-pie mr-2"></i>概要
            </a>
            <a href="?mode=clusters<?php echo !$is_my_insights ? '&user=' . $user_id : ''; ?>" 
               class="px-6 py-3 text-sm font-medium <?php echo $view_mode === 'clusters' ? 'border-b-2 border-purple-600 text-purple-600' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-brain mr-2"></i>AI分類
            </a>
            <a href="?mode=map<?php echo !$is_my_insights ? '&user=' . $user_id : ''; ?>" 
               class="px-6 py-3 text-sm font-medium <?php echo $view_mode === 'map' ? 'border-b-2 border-purple-600 text-purple-600' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-map mr-2"></i>読書マップ
            </a>
            <a href="?mode=pace<?php echo !$is_my_insights ? '&user=' . $user_id : ''; ?>"
               class="px-6 py-3 text-sm font-medium <?php echo $view_mode === 'pace' ? 'border-b-2 border-purple-600 text-purple-600' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-gauge-high mr-2"></i>読書ペース
            </a>
            <?php if ($is_my_insights): ?>
            <a href="?mode=trend"
               class="px-6 py-3 text-sm font-medium <?php echo $view_mode === 'trend' ? 'border-b-2 border-purple-600 text-purple-600' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-magic mr-2"></i>AI傾向診断
            </a>
            <?php endif; ?>
        </nav>
    </div>
    
    <?php if ($view_mode === 'overview'): ?>
    <!-- 概要ビュー -->
    <?php
    // Chart.js用のデータ準備
    // 年別データの処理
    $yearly_labels = [];
    $yearly_books = [];
    $yearly_pages = [];
    $yearly_cumulative_books = [];
    $yearly_cumulative_pages = [];
    $cumulative_books_total = 0;
    $cumulative_pages_total = 0;
    
    foreach ($stats['yearly_data'] as $year => $data) {
        $yearly_labels[] = $year;
        $books = isset($data['books']) ? $data['books'] : 0;
        $pages = isset($data['pages']) ? $data['pages'] : 0;
        
        $yearly_books[] = $books;
        $yearly_pages[] = $pages;
        
        // 累積値を計算
        $cumulative_books_total += $books;
        $cumulative_pages_total += $pages;
        $yearly_cumulative_books[] = $cumulative_books_total;
        $yearly_cumulative_pages[] = $cumulative_pages_total;
    }
    
    // 月別データ（古い順から新しい順へ）と累積値の計算
    $monthly_labels = [];
    $monthly_books = [];
    $monthly_pages = [];
    $monthly_cumulative_books = [];
    $monthly_cumulative_pages = [];
    $cumulative_books_total_m = 0;
    $cumulative_pages_total_m = 0;
    
    foreach ($stats['monthly_data'] as $month => $data) {
        $monthly_labels[] = $month;
        $books = isset($data['books']) ? $data['books'] : 0;
        $pages = isset($data['pages']) ? $data['pages'] : 0;
        
        $monthly_books[] = $books;
        $monthly_pages[] = $pages;
        
        // 累積値を計算
        $cumulative_books_total_m += $books;
        $cumulative_pages_total_m += $pages;
        $monthly_cumulative_books[] = $cumulative_books_total_m;
        $monthly_cumulative_pages[] = $cumulative_pages_total_m;
    }
    
    // 日別データ
    $daily_dates = array_keys($stats['daily_pages']);
    $daily_labels = array_map(function($date) {
        return date('n/j', strtotime($date));
    }, $daily_dates);
    $daily_pages = array_values($stats['daily_pages']);
    $daily_books = array_values($stats['daily_books']);
    $cumulative_pages = array_values($stats['cumulative_pages']);
    $cumulative_books = array_values($stats['cumulative_books']);
    ?>
    
    <!-- サマリーカード -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-book text-2xl text-blue-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">総登録数</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($stats['total_books']); ?>冊</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-2xl text-green-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">読了</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($stats['finished_books']); ?>冊</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-book-reader text-2xl text-orange-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">読書中</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($stats['reading_books']); ?>冊</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-file-alt text-2xl text-purple-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">総ページ数</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($stats['total_pages']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 月間レポートへのリンク -->
    <?php if ($is_my_insights): ?>
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center text-white">
                <i class="fas fa-calendar-check text-2xl mr-3"></i>
                <div>
                    <p class="font-semibold">月間読書レポート</p>
                    <p class="text-sm text-purple-100">毎月の読書記録をレポート形式で振り返れます</p>
                </div>
            </div>
            <a href="/monthly_report.php" class="inline-flex items-center px-4 py-2 bg-white text-purple-600 text-sm font-medium rounded-lg hover:bg-purple-50 transition-colors">
                <i class="fas fa-arrow-right mr-2"></i>レポートを見る
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- 読書冊数グラフ -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">📚 読書冊数</h2>
        <p class="text-sm text-gray-600 mb-6">
            <i class="fas fa-hand-pointer mr-1"></i>
            グラフの棒をクリックすると、その期間に読了した本の一覧が表示されます
        </p>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-alt text-green-500 mr-2"></i>年別</h3>
                <div style="height: 250px;"><canvas id="yearlyBooksChart"></canvas></div>
            </div>
            <div>
                <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar text-blue-500 mr-2"></i>月別（過去12ヶ月）</h3>
                <div style="height: 250px;"><canvas id="monthlyBooksChart"></canvas></div>
            </div>
            <div>
                <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-day text-purple-500 mr-2"></i>日別（過去30日）</h3>
                <div style="height: 250px;"><canvas id="dailyBooksChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- 読書ページ数グラフ -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">📖 読書ページ数</h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-alt text-green-500 mr-2"></i>年別</h3>
                <div style="height: 250px;"><canvas id="yearlyPagesChart"></canvas></div>
            </div>
            <div>
                <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar text-blue-500 mr-2"></i>月別（過去12ヶ月）</h3>
                <div style="height: 250px;"><canvas id="monthlyPagesChart"></canvas></div>
            </div>
            <div>
                <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-day text-purple-500 mr-2"></i>日別（過去30日）</h3>
                <div style="height: 250px;"><canvas id="dailyPagesChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- 評価分布 -->
    <?php if (!empty($stats['rating_distribution'])): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">⭐ 評価分布</h2>
        <div class="grid grid-cols-5 gap-4">
            <?php for ($rating = 5; $rating >= 1; $rating--): ?>
            <div class="text-center">
                <div class="text-2xl mb-2"><?php echo str_repeat('★', $rating); ?></div>
                <div class="text-3xl font-bold text-gray-800 dark:text-gray-200">
                    <?php echo $stats['rating_distribution'][$rating] ?? 0; ?>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">冊</div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 再読サマリー（再読データがある場合のみ表示） -->
    <?php if (!empty($stats['reread_book_count'])): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">🔁 再読</h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">複数回読んだ本</p>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-200"><?php echo number_format($stats['reread_book_count']); ?><span class="text-base font-medium">冊</span></p>
            </div>
            <div class="text-center">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">通算再読回数</p>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-200"><?php echo number_format($stats['total_reread_count']); ?><span class="text-base font-medium">回</span></p>
            </div>
        </div>

        <?php if (!empty($stats['reread_ranking'])): ?>
        <div class="border-t border-gray-100 dark:border-gray-700 mt-4 pt-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">よく再読している本</p>
            <ul class="space-y-3">
                <?php foreach ($stats['reread_ranking'] as $i => $book): ?>
                <?php
                    if (!empty($book['image_url']) && strpos($book['image_url'], 'noimage') === false) {
                        $rrImage = $book['image_url'];
                    } else if (!empty($book['amazon_id'])) {
                        $rrImage = "https://images-fe.ssl-images-amazon.com/images/P/{$book['amazon_id']}.09.LZZZZZZZ";
                    } else {
                        $rrImage = '/img/no-image-book.png';
                    }
                ?>
                <li class="flex items-center">
                    <span class="w-5 flex-shrink-0 text-sm font-semibold text-gray-400"><?php echo $i + 1; ?></span>
                    <a href="/book_detail.php?book_id=<?php echo urlencode($book['book_id']); ?>"
                       class="flex items-center flex-1 min-w-0 group"
                       title="<?php echo html($book['name']); ?>">
                        <img src="<?php echo html($rrImage); ?>"
                             alt="<?php echo html($book['name']); ?>"
                             class="w-8 h-11 object-cover rounded shadow-sm flex-shrink-0"
                             loading="lazy" decoding="async"
                             onerror="this.onerror=null; this.src='/img/no-image-book.png';">
                        <span class="ml-3 text-sm text-gray-700 dark:text-gray-300 truncate group-hover:text-purple-600"><?php echo html($book['name']); ?></span>
                    </a>
                    <span class="ml-2 flex-shrink-0 text-sm font-semibold text-gray-600 dark:text-gray-400"><?php echo number_format($book['count']); ?>回</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- AIクラスタサマリー -->
    <?php if (!empty($clusters)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">🤖 AIが発見した読書パターン</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach (array_slice($clusters, 0, 3) as $cluster): ?>
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow bg-white dark:bg-gray-800">
                <div class="cursor-pointer" onclick="location.href='?mode=clusters<?php echo !$is_my_insights ? '&user=' . $user_id : ''; ?>#cluster-<?php echo $cluster['id']; ?>'">
                    <h3 class="font-semibold mb-2 text-gray-900 dark:text-gray-100"><?php echo html($cluster['name']); ?></h3>
                    <?php if (!empty($cluster['description'])): ?>
                    <p class="text-xs text-gray-700 dark:text-gray-300 mb-2 line-clamp-2"><?php echo html($cluster['description']); ?></p>
                    <?php endif; ?>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <?php echo $cluster['size']; ?>冊 | ★<?php echo number_format($cluster['avg_rating'], 1); ?>
                    </div>
                </div>
                <?php if (!empty($cluster['themes'])): ?>
                <div class="flex flex-wrap gap-1 mb-3">
                    <?php foreach (array_slice($cluster['themes'], 0, 3) as $theme): ?>
                    <span class="text-xs bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 text-purple-700 dark:text-purple-300 px-2 py-1 rounded">
                        <?php echo html($theme); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php elseif (!empty($cluster['keywords'])): ?>
                <div class="flex flex-wrap gap-1 mb-3">
                    <?php foreach (array_slice($cluster['keywords'], 0, 3) as $keyword): ?>
                    <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded">
                        <?php echo html($keyword); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- 代表的な本を表示（最大3冊） -->
                <?php if (!empty($cluster['books'])): ?>
                <div class="border-t pt-3 mt-2">
                    <div class="grid grid-cols-3 gap-2">
                        <?php foreach (array_slice($cluster['books'], 0, 3) as $book): ?>
                        <?php 
                            if (!empty($book['image_url']) && strpos($book['image_url'], 'noimage') === false) {
                                $bookImage = $book['image_url'];
                            } else if (!empty($book['amazon_id'])) {
                                $bookImage = "https://images-fe.ssl-images-amazon.com/images/P/{$book['amazon_id']}.09.LZZZZZZZ";
                            } else {
                                $bookImage = '/img/no-image-book.png';
                            }
                        ?>
                        <a href="/book_detail.php?book_id=<?php echo urlencode($book['book_id']); ?>" 
                           class="block group" 
                           title="<?php echo html($book['title']); ?>">
                            <div class="book-cover-wrapper rounded shadow-sm">
                                <img src="<?php echo html($bookImage); ?>" 
                                     alt="<?php echo html($book['title']); ?>"
                                     class="book-cover-img"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.onerror=null; this.src='/img/no-image-book.png';">
                            </div>
                            <div class="text-xs mt-1 truncate group-hover:text-purple-600">
                                <?php echo html($book['title']); ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($clusters) > 3): ?>
        <div class="text-center mt-4">
            <a href="?mode=clusters<?php echo !$is_my_insights ? '&user=' . $user_id : ''; ?>"
               class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300">
                全<?php echo count($clusters); ?>パターンを見る →
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php elseif ($view_mode === 'clusters'): ?>
    <!-- AI分類ビュー -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-2xl font-semibold mb-2 text-gray-900 dark:text-gray-100">🤖 AI による読書傾向の自動分類</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            レビューの内容をAIが分析し、意味的に似た本を自動的にグループ化しました。
            タグやジャンルに依存せず、実際のレビュー内容から傾向を発見します。
        </p>
        
        <?php if (empty($clusters)): ?>
        <div class="text-center py-12 text-gray-500">
            レビューが少ないため、AI分類を生成できません。<br>
            もっとレビューを書いてみましょう！
        </div>
        <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($clusters as $cluster): ?>
            <div id="cluster-<?php echo $cluster['id']; ?>" class="border-2 border-purple-200 dark:border-purple-600 rounded-lg p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-gray-100">
                            <span class="inline-block w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 text-white text-center leading-10 mr-3">
                                <?php echo $cluster['id'] + 1; ?>
                            </span>
                            <?php echo html($cluster['name']); ?>
                        </h3>
                        <?php if (!empty($cluster['description'])): ?>
                        <p class="text-gray-700 dark:text-gray-300 mb-2"><?php echo html($cluster['description']); ?></p>
                        <?php endif; ?>
                        <div class="text-gray-600 dark:text-gray-400">
                            <span class="mr-4"><?php echo $cluster['size']; ?>冊</span>
                            <span class="mr-4">平均評価: ★<?php echo number_format($cluster['avg_rating'], 1); ?></span>
                            <span>平均レビュー: <?php echo number_format($cluster['characteristics']['review_length_avg']); ?>文字</span>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($cluster['themes'])): ?>
                <div class="mb-3">
                    <span class="text-sm text-gray-600 mr-2">🏷️ テーマ:</span>
                    <?php foreach ($cluster['themes'] as $theme): ?>
                    <span class="inline-block px-3 py-1 text-sm bg-gradient-to-r from-purple-100 to-pink-100 text-purple-700 rounded-full mr-2 mb-2">
                        <?php echo html($theme); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($cluster['keywords'])): ?>
                <div class="mb-3">
                    <span class="text-sm text-gray-600 mr-2">🔑 特徴語:</span>
                    <?php foreach ($cluster['keywords'] as $keyword): ?>
                    <span class="inline-block px-3 py-1 text-sm bg-purple-100 text-purple-700 rounded-full mr-2 mb-2">
                        <?php echo html($keyword); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($cluster['reading_suggestions'])): ?>
                <div class="mb-4 p-3 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg border-l-4 border-blue-400">
                    <p class="text-sm text-blue-800">
                        <span class="font-semibold">💡 読書提案:</span> <?php echo html($cluster['reading_suggestions']); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- 本のギャラリー表示 -->
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                    <?php foreach ($cluster['books'] as $book): ?>
                    <?php 
                        // 本の表紙画像を取得
                        if (!empty($book['image_url']) && strpos($book['image_url'], 'noimage') === false) {
                            $bookImage = $book['image_url'];
                        } else if (!empty($book['amazon_id'])) {
                            $bookImage = "https://images-fe.ssl-images-amazon.com/images/P/{$book['amazon_id']}.09.LZZZZZZZ";
                        } else {
                            $bookImage = '/img/no-image-book.png';
                        }
                    ?>
                    <a href="/book_detail.php?book_id=<?php echo urlencode($book['book_id']); ?>" 
                       class="block group">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                            <!-- 表紙画像コンテナ -->
                            <div class="book-cover-wrapper">
                                <img src="<?php echo html($bookImage); ?>" 
                                     alt="<?php echo html($book['title']); ?>"
                                     class="book-cover-img"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.onerror=null; this.src='/img/no-image-book.png';">
                                
                                <?php if($book['rating'] > 0): ?>
                                <div class="absolute top-2 right-2 bg-yellow-500 text-white text-xs px-1.5 py-0.5 rounded-full shadow-sm font-bold z-10">
                                    <?php echo number_format($book['rating'], 1); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 本の情報 -->
                            <div class="p-2">
                                <div class="text-xs font-medium line-clamp-2 text-gray-900 dark:text-gray-100 group-hover:text-purple-600 dark:group-hover:text-purple-400" title="<?php echo html($book['title']); ?>">
                                    <?php echo html($book['title']); ?>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                    <?php echo html($book['author']); ?>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php elseif ($view_mode === 'map'): ?>
    <!-- 読書マップビュー -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-2xl font-semibold mb-2 text-gray-900 dark:text-gray-100">🗺️ インテリジェント読書マップ</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            AIがレビュー内容を分析し、意味的に近い本をグループ化した読書マップです。
            大きなブロックほど多くの本が含まれています。
        </p>
        
        <!-- ビュー切り替えボタン -->
        <div class="flex gap-2 mb-4">
            <button id="map-view-enhanced" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm">
                <i class="fas fa-brain mr-2"></i>AI分析マップ
            </button>
            <button id="map-view-classic" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600">
                <i class="fas fa-tags mr-2"></i>タグベースマップ
            </button>
        </div>
        
        <!-- マップコンテナ -->
        <div id="reading-map-container" class="relative" style="min-height: 600px;">
            <div id="map-loading" class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-800">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                    <p class="mt-4 text-gray-600">AIがマップを生成中...</p>
                </div>
            </div>
            <div id="reading-map" style="width: 100%; height: 600px;"></div>
        </div>
        
        <!-- マップ凡例 -->
        <div id="map-legend" class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hidden">
            <h4 class="font-semibold mb-2 text-sm text-gray-900 dark:text-gray-100">マップの見方</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-purple-500 rounded"></div>
                    <span class="text-gray-700 dark:text-gray-300">AIによる意味的グループ</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-gray-400 rounded"></div>
                    <span class="text-gray-700 dark:text-gray-300">タグベースグループ</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-star text-yellow-500"></i>
                    <span class="text-gray-700 dark:text-gray-300">評価の高さ</span>
                </div>
            </div>
        </div>
        
        <!-- マップ統計 -->
        <div id="map-stats" class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hidden">
            <h3 class="font-semibold mb-3 text-gray-900 dark:text-gray-100">📊 マップ統計</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-600 dark:text-gray-400">総冊数:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100" id="map-total-books">0</span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">AIグループ:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100" id="map-semantic-clusters">0</span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">多様性スコア:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100" id="map-diversity">0</span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">平均評価:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100" id="map-avg-rating">0</span>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($view_mode === 'pace'): ?>
    <!-- 読書ペース分析ビュー -->
    <?php include(getTemplatePath('t_reading_pace_analysis.php')); ?>

    <?php elseif ($view_mode === 'trend' && $is_my_insights): ?>
    <!-- AI傾向診断ビュー -->
    <div class="space-y-6">
        <!-- 説明セクション -->
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-magic text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">AI傾向診断</h2>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">
                        AIがあなたの読書履歴を分析し、読書傾向や好みの特徴をテキストで解説します。<br>
                        分析結果はプロフィールに保存して公開することもできます。
                    </p>
                </div>
            </div>
        </div>

        <!-- 分析実行ボタン -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-center">
                <button id="analyze-trends-btn"
                        onclick="analyzeReadingTrends()"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-magic mr-2"></i>
                    傾向を分析する
                </button>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    分析には数秒かかる場合があります
                </p>
            </div>

            <!-- ローディング表示 -->
            <div id="trend-loading" class="hidden mt-6">
                <div class="flex items-center justify-center space-x-3">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    <span class="text-gray-600 dark:text-gray-300">AIが分析中...</span>
                </div>
            </div>

            <!-- 分析結果表示エリア -->
            <div id="trend-result" class="hidden mt-6">
                <div class="border-t dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <i class="fas fa-chart-line mr-2 text-purple-600"></i>分析結果
                    </h3>
                    <div id="trend-content" class="prose prose-sm dark:prose-invert max-w-none bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <!-- 分析結果がここに表示される -->
                    </div>

                    <!-- 保存・共有オプション -->
                    <div class="mt-6 p-4 bg-purple-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <i class="fas fa-info-circle mr-1"></i>
                            保存するとマイページに表示されます。公開にチェックを入れると他のユーザーにも表示されます。
                        </p>
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="trend-public-toggle" class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                                    <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-globe mr-1"></i>公開する
                                    </span>
                                </label>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button id="save-analysis-btn" onclick="saveAnalysisToProfile()"
                                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-save mr-2"></i>マイページに保存
                                </button>
                            </div>
                        </div>
                        <!-- 保存成功メッセージ -->
                        <div id="save-success-message" class="hidden mt-4 p-3 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-green-800 dark:text-green-300">
                                    <i class="fas fa-check-circle mr-2"></i>マイページに保存しました
                                </span>
                                <a href="/profile.php" class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700 transition-colors">
                                    <i class="fas fa-user mr-1"></i>マイページを見る
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 分析履歴 -->
        <?php if (!empty($analysis_history) && count($analysis_history) > 1): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                <i class="fas fa-history mr-2 text-gray-500"></i>過去の分析履歴
            </h3>
            <div class="space-y-3">
                <?php foreach ($analysis_history as $index => $history): ?>
                <?php if ($index === 0) continue; // 最新はスキップ（上に表示済み） ?>
                <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            <?php echo date('Y年n月j日', strtotime($history['created_at'])); ?>
                        </span>
                        <button onclick="showFullAnalysis(<?php echo $history['analysis_id']; ?>)"
                                class="text-sm text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 font-medium">
                            <i class="fas fa-eye mr-1"></i>表示
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 最新の保存済み分析を表示 -->
        <?php if (!empty($latest_analysis)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                <i class="fas fa-bookmark mr-2 text-purple-600"></i>保存済みの分析
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                    (<?php echo date('Y年n月j日', strtotime($latest_analysis['created_at'])); ?>)
                </span>
            </h3>
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <?php echo nl2br(html($latest_analysis['analysis_content'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
/* グラフのキャンバスにカーソルポインタを表示 */
#yearlyBooksChart,
#monthlyBooksChart,
#dailyBooksChart,
#ratingChart {
    cursor: pointer;
}
</style>

<!-- 本一覧モーダル -->
<div id="bookListModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
            <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 dark:text-gray-100"></h3>
                <button onclick="closeBookListModal()" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="modalContent" class="p-4 overflow-y-auto" style="max-height: calc(80vh - 120px);">
                <!-- 動的にコンテンツが挿入されます -->
            </div>
        </div>
    </div>
</div>

<!-- D3.js for map visualization -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ビューモードに応じた初期化
<?php if ($view_mode === 'map'): ?>
// 読書マップの描画
let currentMapType = 'enhanced';

document.addEventListener('DOMContentLoaded', function() {
    loadMapData('enhanced');
    
    // ビュー切り替えボタン
    document.getElementById('map-view-enhanced').addEventListener('click', function() {
        currentMapType = 'enhanced';
        this.classList.remove('bg-gray-200', 'text-gray-700');
        this.classList.add('bg-purple-600', 'text-white');
        document.getElementById('map-view-classic').classList.remove('bg-purple-600', 'text-white');
        document.getElementById('map-view-classic').classList.add('bg-gray-200', 'text-gray-700');
        loadMapData('enhanced');
    });
    
    document.getElementById('map-view-classic').addEventListener('click', function() {
        currentMapType = 'classic';
        this.classList.remove('bg-gray-200', 'text-gray-700');
        this.classList.add('bg-purple-600', 'text-white');
        document.getElementById('map-view-enhanced').classList.remove('bg-purple-600', 'text-white');
        document.getElementById('map-view-enhanced').classList.add('bg-gray-200', 'text-gray-700');
        loadMapData('classic');
    });
});

function loadMapData(type) {
    document.getElementById('map-loading').classList.remove('hidden');
    document.getElementById('reading-map').innerHTML = '';
    
    const apiUrl = type === 'enhanced' 
        ? '/api/reading_map_enhanced.php?user=<?php echo $user_id; ?>'
        : '/api/reading_map_data.php?user=<?php echo $user_id; ?>';
    
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            document.getElementById('map-loading').classList.add('hidden');
            
            // 統計情報を表示
            if (data.stats) {
                document.getElementById('map-total-books').textContent = data.stats.total_books || 0;
                
                if (type === 'enhanced') {
                    document.getElementById('map-semantic-clusters').textContent = data.stats.semantic_clusters || 0;
                    document.getElementById('map-diversity').textContent = Math.round(data.stats.diversity_score || 0);
                    
                    // 平均評価を計算
                    let totalRating = 0;
                    let ratedBooks = 0;
                    if (data.data.children) {
                        data.data.children.forEach(group => {
                            if (group.avgRating) {
                                totalRating += group.avgRating * group.value;
                                ratedBooks += group.value;
                            }
                        });
                    }
                    const avgRating = ratedBooks > 0 ? (totalRating / ratedBooks).toFixed(1) : '-';
                    document.getElementById('map-avg-rating').textContent = avgRating;
                } else {
                    document.getElementById('map-semantic-clusters').textContent = data.stats.genres_explored || 0;
                    document.getElementById('map-diversity').textContent = '-';
                    document.getElementById('map-avg-rating').textContent = '-';
                }
                
                document.getElementById('map-stats').classList.remove('hidden');
                document.getElementById('map-legend').classList.remove('hidden');
            }
            
            // マップを描画
            if (type === 'enhanced') {
                drawEnhancedTreemap(data.data);
            } else {
                drawClassicTreemap(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading map data:', error);
            document.getElementById('map-loading').innerHTML = '<p class="text-red-600">マップの読み込みに失敗しました</p>';
        });
}

function drawEnhancedTreemap(data) {
    const container = document.getElementById('reading-map');
    const width = container.clientWidth;
    const height = 600;
    
    // Clear existing content
    d3.select(container).selectAll("*").remove();
    
    const svg = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height);
    
    // Treemap レイアウト
    const treemap = d3.treemap()
        .size([width, height])
        .padding(3)
        .paddingOuter(5)
        .round(true);
    
    // 階層データを作成
    const root = d3.hierarchy(data)
        .sum(d => d.value || 0)
        .sort((a, b) => b.value - a.value);
    
    treemap(root);
    
    // グループを描画
    const groups = svg.selectAll('g')
        .data(root.children || [])
        .enter().append('g')
        .attr('transform', d => `translate(${d.x0},${d.y0})`);
    
    // グループの背景
    groups.append('rect')
        .attr('width', d => d.x1 - d.x0)
        .attr('height', d => d.y1 - d.y0)
        .attr('fill', d => d.data.color || '#8B5CF6')
        .attr('stroke', 'white')
        .attr('stroke-width', 3)
        .attr('rx', 4)
        .style('cursor', 'pointer')
        .style('opacity', 0.9)
        .on('mouseover', function(event, d) {
            d3.select(this).style('opacity', 1);
            showTooltip(event, d.data);
        })
        .on('mouseout', function(event, d) {
            d3.select(this).style('opacity', 0.9);
            hideTooltip();
        })
        .on('click', function(event, d) {
            showEnhancedCategoryDetails(d.data);
        });
    
    // グループ名
    groups.append('text')
        .attr('x', 6)
        .attr('y', 20)
        .text(d => {
            const maxWidth = (d.x1 - d.x0) / 7;
            const name = d.data.name;
            return name.length > maxWidth ? name.substring(0, maxWidth - 2) + '...' : name;
        })
        .style('font-size', d => {
            const area = (d.x1 - d.x0) * (d.y1 - d.y0);
            if (area > 20000) return '16px';
            if (area > 10000) return '14px';
            return '12px';
        })
        .style('fill', 'white')
        .style('font-weight', 'bold')
        .style('pointer-events', 'none');
    
    // キーワード表示（大きなグループのみ）
    groups.each(function(d) {
        const area = (d.x1 - d.x0) * (d.y1 - d.y0);
        if (area > 15000 && d.data.keywords && d.data.keywords.length > 0) {
            const g = d3.select(this);
            const keywords = d.data.keywords.slice(0, 3);
            
            g.append('text')
                .attr('x', 6)
                .attr('y', 38)
                .text(keywords.join(' / '))
                .style('font-size', '10px')
                .style('fill', 'rgba(255,255,255,0.8)')
                .style('pointer-events', 'none');
        }
    });
    
    // 冊数と評価
    groups.append('text')
        .attr('x', 6)
        .attr('y', d => {
            const height = d.y1 - d.y0;
            return Math.min(height - 10, 55);
        })
        .text(d => {
            let text = d.data.value + '冊';
            if (d.data.avgRating > 0) {
                text += ' ★' + d.data.avgRating.toFixed(1);
            }
            return text;
        })
        .style('font-size', '11px')
        .style('fill', 'rgba(255,255,255,0.9)')
        .style('pointer-events', 'none');
}

function drawClassicTreemap(data) {
    const container = document.getElementById('reading-map');
    const width = container.clientWidth;
    const height = 600;
    
    d3.select(container).selectAll("*").remove();
    
    const svg = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height);
    
    const color = d3.scaleOrdinal()
        .range(['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
                '#DDA0DD', '#FFA07A', '#98D8C8', '#FFD93D', '#6BCB77']);
    
    const treemap = d3.treemap()
        .size([width, height])
        .padding(2)
        .round(true);
    
    const root = d3.hierarchy(data)
        .sum(d => d.value || 0)
        .sort((a, b) => b.value - a.value);
    
    treemap(root);
    
    const cell = svg.selectAll('g')
        .data(root.leaves())
        .enter().append('g')
        .attr('transform', d => `translate(${d.x0},${d.y0})`);
    
    cell.append('rect')
        .attr('width', d => d.x1 - d.x0)
        .attr('height', d => d.y1 - d.y0)
        .attr('fill', d => color(d.parent.data.name))
        .attr('stroke', 'white')
        .attr('stroke-width', 2)
        .style('cursor', 'pointer')
        .on('mouseover', function(event, d) {
            d3.select(this).attr('opacity', 0.8);
        })
        .on('mouseout', function(event, d) {
            d3.select(this).attr('opacity', 1);
        })
        .on('click', function(event, d) {
            showCategoryBooks(d.data.name);
        });
    
    cell.append('text')
        .attr('x', 4)
        .attr('y', 20)
        .text(d => d.data.name)
        .style('font-size', '12px')
        .style('fill', 'white')
        .style('font-weight', 'bold')
        .style('pointer-events', 'none');
    
    cell.append('text')
        .attr('x', 4)
        .attr('y', 36)
        .text(d => d.value + '冊')
        .style('font-size', '10px')
        .style('fill', 'white')
        .style('pointer-events', 'none');
}

// ツールチップ関連
function showTooltip(event, data) {
    const tooltip = d3.select('body').append('div')
        .attr('id', 'map-tooltip')
        .style('position', 'absolute')
        .style('background', 'rgba(0,0,0,0.9)')
        .style('color', 'white')
        .style('padding', '10px')
        .style('border-radius', '5px')
        .style('font-size', '12px')
        .style('pointer-events', 'none')
        .style('z-index', '1000');
    
    let content = `<strong>${data.name}</strong><br/>`;
    content += `📚 ${data.value}冊<br/>`;
    if (data.avgRating) content += `⭐ 平均評価: ${data.avgRating.toFixed(1)}<br/>`;
    if (data.keywords && data.keywords.length > 0) {
        content += `🏷️ ${data.keywords.join(', ')}`;
    }
    
    tooltip.html(content)
        .style('left', (event.pageX + 10) + 'px')
        .style('top', (event.pageY - 28) + 'px');
}

function hideTooltip() {
    d3.select('#map-tooltip').remove();
}

// 拡張版カテゴリ詳細表示
function showEnhancedCategoryDetails(category) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    
    let booksHtml = '';
    if (category.children && category.children.length > 0) {
        booksHtml = category.children.map(book => `
            <a href="/book_detail.php?book_id=${book.bookId}" 
               class="block border rounded-lg p-4 hover:shadow-lg transition-shadow bg-white dark:bg-gray-800 dark:border-gray-600">
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        ${book.imageUrl ? `
                            <div class="book-cover-wrapper" style="width: 60px;">
                                <img src="${book.imageUrl}" 
                                     alt="${book.fullTitle || book.name}"
                                     class="book-cover-image"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="book-cover-placeholder" style="display: none;">
                                    <i class="fas fa-book text-gray-400"></i>
                                </div>
                            </div>
                        ` : `
                            <div class="book-cover-wrapper" style="width: 60px;">
                                <div class="book-cover-placeholder">
                                    <i class="fas fa-book text-gray-400"></i>
                                </div>
                            </div>
                        `}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm mb-1">${book.fullTitle || book.name}</div>
                        ${book.author ? `<div class="text-xs text-gray-600 mb-1">${book.author}</div>` : ''}
                        ${book.rating > 0 ? `<div class="text-xs text-yellow-600">★${book.rating}</div>` : ''}
                        ${book.tags ? `<div class="text-xs text-gray-500 mt-2">🏷️ ${book.tags.join(', ')}</div>` : ''}
                    </div>
                </div>
            </a>
        `).join('');
    } else {
        booksHtml = '<p class="text-gray-500 text-center py-8">詳細データを読み込み中...</p>';
    }
    
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl max-h-[90vh] w-full overflow-hidden">
            <div class="p-6 border-b dark:border-gray-700" style="background-color: ${category.color}20;">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold mb-2">${category.name}</h3>
                        <div class="text-sm text-gray-600">
                            📚 ${category.value}冊
                            ${category.avgRating ? `⭐ 平均評価: ${category.avgRating.toFixed(1)}` : ''}
                        </div>
                        ${category.keywords ? `
                            <div class="mt-2 flex flex-wrap gap-2">
                                ${category.keywords.map(k => `<span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">${k}</span>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-150px)]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${booksHtml}
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// カテゴリの本一覧を表示
function showCategoryBooks(category) {
    // 本一覧を取得
    fetch(`/api/reading_map_books.php?user=<?php echo $user_id; ?>&category=${encodeURIComponent(category)}`)
        .then(response => response.json())
        .then(data => {
            // モーダルを作成
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl max-h-[90vh] w-full overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">${category} の本一覧（${data.count}冊）</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-100px)]">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            ${data.books.map(book => `
                                <a href="/book_detail.php?book_id=${book.book_id}" 
                                   class="block border rounded-lg p-4 hover:shadow-lg transition-shadow">
                                    <div class="flex items-start gap-3">
                                        ${book.image_url ? `
                                            <img src="${book.image_url}" alt="${book.title}" 
                                                 class="w-16 h-20 object-cover rounded shadow-sm"
                                                 loading="lazy"
                                                 onerror="this.onerror=null; this.src='/img/no-image-book.png';">
                                        ` : `
                                            <div class="w-16 h-20 bg-gradient-to-br from-gray-200 to-gray-300 rounded shadow-sm flex items-center justify-center">
                                                <i class="fas fa-book text-gray-500"></i>
                                            </div>
                                        `}
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-sm truncate">${book.title}</h4>
                                            <p class="text-xs text-gray-600 truncate">${book.author || ''}</p>
                                            ${book.rating > 0 ? `
                                                <div class="text-xs text-yellow-600 mt-1">
                                                    ${'★'.repeat(Math.floor(book.rating))}
                                                </div>
                                            ` : ''}
                                            <span class="inline-block mt-1 px-2 py-1 text-xs rounded-full bg-${book.status_color}-100 text-${book.status_color}-700">
                                                ${book.status_text}
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        })
        .catch(error => {
            console.error('Error loading books:', error);
        });
}
<?php endif; ?>

<?php if ($view_mode === 'overview'): ?>
// モーダル関連の関数
function showBookListModal(title, books) {
    const modal = document.getElementById('bookListModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    modalTitle.textContent = title;
    
    if (books.length === 0) {
        modalContent.innerHTML = '<p class="text-gray-500 text-center py-8">該当する本がありません</p>';
    } else {
        let html = '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">';
        books.forEach(book => {
            const imageUrl = book.image_url || book.repo_image || '/img/no-image-book.png';
            const bookName = book.name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
            html += `
                <a href="/book/${book.book_id}" class="group">
                    <div class="aspect-[3/4] bg-gray-100 rounded overflow-hidden shadow-sm group-hover:shadow-md transition-shadow">
                        <img src="${imageUrl}" 
                             alt="${bookName}" 
                             class="w-full h-full object-cover">
                    </div>
                    <p class="mt-1 text-xs font-medium text-gray-900 line-clamp-2 group-hover:text-blue-600">
                        ${bookName}
                    </p>
                    ${book.finished_date ? `<p class="text-xs text-gray-500">${book.finished_date}</p>` : ''}
                </a>
            `;
        });
        html += '</div>';
        modalContent.innerHTML = html;
    }
    
    modal.classList.remove('hidden');
}

function closeBookListModal() {
    document.getElementById('bookListModal').classList.add('hidden');
}

// モーダル外クリックで閉じる
document.getElementById('bookListModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBookListModal();
    }
});

// Ajaxで本一覧を取得
function fetchBooksByPeriod(period, value, callback) {
    fetch(`/api/get_books_by_period.php?period=${period}&value=${value}`)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => {
            console.error('Error:', error);
            callback([]);
        });
}

// 年別読書冊数グラフ
const yearlyBooksChart = new Chart(document.getElementById('yearlyBooksChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($yearly_labels); ?>,
        datasets: [{
            label: '読了冊数',
            data: <?php echo json_encode($yearly_books); ?>,
            backgroundColor: 'rgba(34, 197, 94, 0.5)',
            borderColor: 'rgba(34, 197, 94, 1)',
            borderWidth: 1,
            order: 2
        }, {
            label: '累積冊数',
            data: <?php echo json_encode($yearly_cumulative_books); ?>,
            type: 'line',
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: 'rgb(34, 197, 94)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                ticks: {
                    stepSize: 10
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            }
        },
        onClick: (event, elements) => {
            if (elements.length > 0 && elements[0].datasetIndex === 0) {
                const index = elements[0].index;
                const year = yearlyBooksChart.data.labels[index];
                fetchBooksByPeriod('year', year, (books) => {
                    showBookListModal(`${year}年に読了した本`, books);
                });
            }
        }
    }
});

// 月別読書冊数グラフ
const monthlyBooksChart = new Chart(document.getElementById('monthlyBooksChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthly_labels); ?>,
        datasets: [{
            label: '読了冊数',
            data: <?php echo json_encode($monthly_books); ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1,
            order: 2
        }, {
            label: '累積冊数',
            data: <?php echo json_encode($monthly_cumulative_books); ?>,
            type: 'line',
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: 'rgb(59, 130, 246)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            }
        },
        onClick: (event, elements) => {
            if (elements.length > 0 && elements[0].datasetIndex === 0) {
                const index = elements[0].index;
                const month = monthlyBooksChart.data.labels[index];
                fetchBooksByPeriod('month', month, (books) => {
                    showBookListModal(`${month}に読了した本`, books);
                });
            }
        }
    }
});

// 日別読書冊数グラフ（累積付き）
const dailyDates = <?php echo json_encode(array_keys($stats['daily_pages'])); ?>;
const dailyBooksChart = new Chart(document.getElementById('dailyBooksChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            label: '読了冊数',
            data: <?php echo json_encode($daily_books); ?>,
            backgroundColor: 'rgba(147, 51, 234, 0.5)',
            borderColor: 'rgba(147, 51, 234, 1)',
            borderWidth: 1,
            yAxisID: 'y',
            order: 2
        }, {
            label: '累積冊数',
            data: <?php echo json_encode($cumulative_books); ?>,
            type: 'line',
            borderColor: 'rgb(168, 85, 247)',
            backgroundColor: 'rgba(168, 85, 247, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: 'rgb(168, 85, 247)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                },
                title: {
                    display: true,
                    text: '冊数'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        },
        onClick: (event, elements) => {
            if (elements.length > 0 && elements[0].datasetIndex === 0) {
                const index = elements[0].index;
                const date = dailyDates[index];
                fetchBooksByPeriod('day', date, (books) => {
                    const formattedDate = new Date(date).toLocaleDateString('ja-JP');
                    showBookListModal(`${formattedDate}に読了した本`, books);
                });
            }
        }
    }
});

// 年別ページ数グラフ（累積付き）
new Chart(document.getElementById('yearlyPagesChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($yearly_labels); ?>,
        datasets: [{
            label: '総ページ数',
            data: <?php echo json_encode($yearly_pages); ?>,
            backgroundColor: 'rgba(34, 197, 94, 0.5)',
            borderColor: 'rgba(34, 197, 94, 1)',
            borderWidth: 1,
            order: 2
        }, {
            label: '累積ページ',
            data: <?php echo json_encode($yearly_cumulative_pages); ?>,
            type: 'line',
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: 'rgb(34, 197, 94)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.dataset.label === '累積ページ') {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + 'ページ';
                        }
                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + 'ページ';
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString();
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString();
                    }
                },
                title: {
                    display: true,
                    text: '累積'
                }
            }
        }
    }
});

// 月別ページ数グラフ（累積付き）
new Chart(document.getElementById('monthlyPagesChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthly_labels); ?>,
        datasets: [{
            label: 'ページ数',
            data: <?php echo json_encode($monthly_pages); ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1,
            order: 2
        }, {
            label: '累積ページ',
            data: <?php echo json_encode($monthly_cumulative_pages); ?>,
            type: 'line',
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: 'rgb(59, 130, 246)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            }
        }
    }
});

// 日別ページ数グラフ（累積付き）
new Chart(document.getElementById('dailyPagesChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            label: 'ページ数',
            data: <?php echo json_encode($daily_pages); ?>,
            backgroundColor: 'rgba(147, 51, 234, 0.5)',
            borderColor: 'rgba(147, 51, 234, 1)',
            borderWidth: 1,
            yAxisID: 'y',
            order: 2
        }, {
            label: '累積ページ',
            data: <?php echo json_encode($cumulative_pages); ?>,
            type: 'line',
            borderColor: 'rgb(168, 85, 247)',
            backgroundColor: 'rgba(168, 85, 247, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: 'rgb(168, 85, 247)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'ページ数'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        }
    }
});
<?php endif; ?>

<?php if ($view_mode === 'pace' && !empty($summary['reading_pace']['monthly_data'])): ?>
// 読書ペースグラフ
const paceCtx = document.getElementById('paceChart');
if (paceCtx) {
    const paceData = <?php echo json_encode($summary['reading_pace']['monthly_data'] ?? []); ?>;
    
    const monthNames = paceData.map(d => {
        const year = d.year;
        const month = String(d.month).padStart(2, '0');
        return `${year}/${month}`;
    }).reverse();
    
    new Chart(paceCtx, {
        type: 'line',
        data: {
            labels: monthNames,
            datasets: [{
                label: '読了冊数',
                data: paceData.map(d => d.books_read).reverse(),
                borderColor: '#8B5CF6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}
<?php endif; ?>

<?php if ($view_mode === 'trend' && $is_my_insights): ?>
// AI傾向診断の変数
let currentAnalysisData = null;

// 読書傾向分析を実行
async function analyzeReadingTrends() {
    const btn = document.getElementById('analyze-trends-btn');
    const loading = document.getElementById('trend-loading');
    const result = document.getElementById('trend-result');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>分析中...';
    loading.classList.remove('hidden');
    result.classList.add('hidden');

    try {
        // 読書履歴を取得
        const historyResponse = await fetch('/api/get_reading_history.php?limit=50');
        const historyData = await historyResponse.json();

        if (!historyData.success || !historyData.books || historyData.books.length < 3) {
            throw new Error('分析に必要な読書履歴が不足しています（最低3冊必要）');
        }

        // AI分析を実行
        const analysisResponse = await fetch('/ai_review_simple.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'analyze_trends',
                reading_history: historyData.books
            })
        });

        const analysisData = await analysisResponse.json();

        if (!analysisData.success) {
            throw new Error(analysisData.error || '分析に失敗しました');
        }

        // 結果を表示
        currentAnalysisData = analysisData;
        const contentEl = document.getElementById('trend-content');
        contentEl.innerHTML = formatAnalysisResult(analysisData.analysis || analysisData.content);

        result.classList.remove('hidden');

    } catch (error) {
        alert('エラー: ' + error.message);
        console.error('Analysis error:', error);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic mr-2"></i>傾向を分析する';
        loading.classList.add('hidden');
    }
}

// 分析結果をフォーマット
function formatAnalysisResult(text) {
    if (!text) return '';
    // Markdown風の変換
    let html = text
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/^## (.+)$/gm, '<h3 class="text-lg font-semibold mt-4 mb-2">$1</h3>')
        .replace(/^### (.+)$/gm, '<h4 class="font-semibold mt-3 mb-1">$1</h4>')
        .replace(/^- (.+)$/gm, '<li class="ml-4">$1</li>')
        .replace(/\n/g, '<br>');
    return html;
}

// プロフィールに保存
async function saveAnalysisToProfile() {
    if (!currentAnalysisData) {
        alert('先に分析を実行してください');
        return;
    }

    const isPublic = document.getElementById('trend-public-toggle').checked;
    const saveBtn = document.getElementById('save-analysis-btn');
    const successMsg = document.getElementById('save-success-message');

    // ボタンを無効化
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>保存中...';

    try {
        const response = await fetch('/ajax/save_reading_analysis.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                analysis_content: currentAnalysisData.analysis || currentAnalysisData.content,
                analysis_type: 'trend',
                is_public: isPublic ? 1 : 0
            })
        });

        const data = await response.json();

        if (data.success) {
            // 保存ボタンを保存済み表示に変更
            saveBtn.innerHTML = '<i class="fas fa-check mr-2"></i>保存済み';
            saveBtn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
            saveBtn.classList.add('bg-gray-400', 'cursor-not-allowed');

            // 成功メッセージを表示
            successMsg.classList.remove('hidden');
            successMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            throw new Error(data.error || '保存に失敗しました');
        }
    } catch (error) {
        // エラー時はボタンを元に戻す
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save mr-2"></i>マイページに保存';
        alert('エラー: ' + error.message);
        console.error('Save error:', error);
    }
}

// 過去の分析を全文表示
function showFullAnalysis(analysisId) {
    // モーダルで表示
    fetch(`/ajax/get_analysis_history.php?analysis_id=${analysisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.analysis) {
                const createdAt = new Date(data.analysis.created_at);
                const dateStr = createdAt.toLocaleDateString('ja-JP', {year: 'numeric', month: 'long', day: 'numeric'});

                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
                modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
                modal.innerHTML = `
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                        <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                <i class="fas fa-magic mr-2 text-purple-500"></i>${dateStr} の分析
                            </h3>
                            <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto max-h-[60vh]">
                            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-800 dark:text-gray-200">
                                ${formatAnalysisResult(data.analysis.analysis_content)}
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            } else {
                alert('分析データを取得できませんでした');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました');
        });
}
<?php endif; ?>
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>