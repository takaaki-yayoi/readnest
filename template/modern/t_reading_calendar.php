<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// 画像ヘルパーを読み込み
require_once(dirname(__DIR__, 2) . '/library/image_helpers.php');

// カレンダー生成関数
function generateCalendar($year, $month, $reading_map) {
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = date('t', $first_day);
    $day_of_week = date('w', $first_day);
    
    $calendar = [];
    $week = [];
    
    // 最初の週の空白を埋める
    for ($i = 0; $i < $day_of_week; $i++) {
        $week[] = null;
    }
    
    // 日付を追加
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $reading_info = $reading_map[$date] ?? null;
        
        $week[] = [
            'day' => $day,
            'date' => $date,
            'has_reading' => isset($reading_map[$date]),
            'event_count' => $reading_info['event_count'] ?? 0,
            'book_count' => $reading_info['book_count'] ?? 0,
            'books' => $reading_info['books'] ?? [],
            'is_today' => $date === date('Y-m-d'),
            'is_future' => strtotime($date) > time()
        ];
        
        if (count($week) == 7) {
            $calendar[] = $week;
            $week = [];
        }
    }
    
    // 最後の週を埋める
    if (!empty($week)) {
        while (count($week) < 7) {
            $week[] = null;
        }
        $calendar[] = $week;
    }
    
    return $calendar;
}

$calendar = generateCalendar($year, $month, $reading_map);

// 前月・次月の計算
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// コンテンツ部分を生成
ob_start();
?>

<style>
.reading-day {
    position: relative;
}

/* モバイル用のサムネイル設定 */
.book-thumbnail {
    width: 16px;
    height: 22px;
    object-fit: cover;
    border-radius: 2px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

/* タブレット用のサムネイル設定 */
@media (min-width: 768px) {
    .book-thumbnail {
        width: 20px;
        height: 28px;
    }
}

/* デスクトップ用のサムネイル設定 */
@media (min-width: 1024px) {
    .book-thumbnail {
        width: 24px;
        height: 34px;
    }
}

/* ランドスケープモード用の調整 */
@media (orientation: landscape) and (max-height: 500px) {
    .book-thumbnail {
        width: 14px;
        height: 20px;
    }
}

.book-thumbnail:hover {
    transform: scale(1.1);
    z-index: 10;
}

.book-thumbnails {
    display: flex;
    gap: 1px;
    justify-content: center;
    margin-top: 2px;
    flex-wrap: wrap;
}

@media (min-width: 768px) {
    .book-thumbnails {
        gap: 2px;
        margin-top: 4px;
    }
}

.book-link {
    position: relative;
}

/* ツールチップはデスクトップのみ表示 */
.book-link .tooltip {
    display: none;
}

@media (min-width: 1024px) {
    .book-link .tooltip {
        display: block;
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s;
        margin-bottom: 4px;
    }
    
    .book-link:hover .tooltip {
        opacity: 1;
    }
    
    .book-link .tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: rgba(0, 0, 0, 0.9);
    }
}
</style>

<!-- ヘッダーセクション -->
<section class="bg-gradient-to-r from-emerald-500 to-green-600 dark:from-gray-800 dark:to-gray-700 text-white py-4 sm:py-6 md:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-1 sm:mb-2">
                    <i class="fas fa-calendar-check mr-2 sm:mr-3 text-lg sm:text-2xl"></i>
                    読書カレンダー
                </h1>
                <p class="text-sm sm:text-lg md:text-xl text-white opacity-90 hidden sm:block">
                    毎日の読書習慣を記録して、継続する力を育てましょう
                </p>
                <p class="text-sm text-white opacity-90 sm:hidden">
                    読書習慣を記録
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 今日の読書セクション -->
<?php if (!empty($today_books) || !empty($reading_books)): ?>
<section class="bg-emerald-50 dark:bg-emerald-900/20 py-6 border-b dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
            <i class="fas fa-book-open text-emerald-600 mr-2"></i>
            今日の読書
        </h2>
        
        <?php if (!empty($today_books)): ?>
        <div class="mb-6">
            <h3 class="text-sm font-medium text-gray-700 mb-3">今日読んだ本</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($today_books as $book): ?>
                <div class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3">
                        <a href="/book/<?php echo $book['book_id']; ?>" class="flex-shrink-0">
                            <img src="<?php echo normalizeBookImageUrl($book['image_url']); ?>" 
                                 alt="<?php echo html($book['name']); ?>"
                                 class="w-12 h-16 object-cover rounded shadow-sm"
                                 onerror="this.src='/img/no-image-book.png'">
                        </a>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 truncate">
                                <a href="/book/<?php echo $book['book_id']; ?>" class="hover:text-readnest-primary">
                                    <?php echo html($book['name']); ?>
                                </a>
                            </h4>
                            <p class="text-sm text-gray-600 truncate"><?php echo html($book['author']); ?></p>
                            <?php if ($book['total_page'] > 0): ?>
                            <div class="mt-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">
                                        <?php echo $book['current_page']; ?> / <?php echo $book['total_page']; ?>ページ
                                    </span>
                                    <span class="text-emerald-600 font-medium">
                                        <?php echo round(($book['current_page'] / $book['total_page']) * 100); ?>%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-emerald-500 h-2 rounded-full" 
                                         style="width: <?php echo min(100, ($book['current_page'] / $book['total_page']) * 100); ?>%"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($reading_books)): ?>
        <div>
            <h3 class="text-sm font-medium text-gray-700 mb-3">
                読書中の本（今日はまだ読んでいません）
                <span class="text-xs text-gray-500 ml-2">本をクリックして進捗を更新しましょう</span>
            </h3>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($reading_books as $book): ?>
                <a href="/book/<?php echo $book['book_id']; ?>" 
                   class="bg-white rounded-lg px-4 py-3 shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5 flex items-center gap-3">
                    <img src="<?php echo normalizeBookImageUrl($book['image_url']); ?>" 
                         alt="<?php echo html($book['name']); ?>"
                         class="w-10 h-14 object-cover rounded shadow-sm"
                         onerror="this.src='/img/no-image-book.png'">
                    <div>
                        <div class="font-medium text-gray-900"><?php echo html($book['name']); ?></div>
                        <div class="text-xs text-gray-600">
                            <?php echo $book['current_page']; ?> / <?php echo $book['total_page']; ?>ページ
                            <?php if ($book['total_page'] > 0): ?>
                            <span class="text-emerald-600 ml-1">
                                (<?php echo round(($book['current_page'] / $book['total_page']) * 100); ?>%)
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (empty($today_books) && empty($reading_books)): ?>
        <div class="bg-white rounded-lg p-6 text-center">
            <i class="fas fa-book text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-600 mb-4">読書中の本がありません</p>
            <a href="/bookshelf.php" class="btn bg-readnest-primary text-white hover:bg-readnest-primary-dark px-6 py-2">
                <i class="fas fa-plus-circle mr-2"></i>
                本を追加する
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- 統計セクション -->
<section class="bg-white dark:bg-gray-800 py-4 sm:py-6 border-b dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 xs:grid-cols-3 gap-3 sm:gap-4 md:gap-6">
            <!-- モバイル横スクロール対応 -->
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-3 sm:p-4 md:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-1">連続記録</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold text-blue-600">
                            <?php echo $current_streak; ?>
                            <span class="text-sm sm:text-base md:text-lg font-normal">日</span>
                        </p>
                    </div>
                    <div class="text-2xl sm:text-3xl md:text-4xl text-blue-400">
                        <i class="fas fa-fire"></i>
                    </div>
                </div>
                <?php
                // 年数計算（365日単位）
                $years = floor($current_streak / 365);
                if ($years >= 1):
                ?>
                <p class="text-xs text-blue-600 mt-2 hidden sm:block">
                    <i class="fas fa-crown mr-1"></i>
                    驚異的！<?php echo $years; ?>年達成！
                </p>
                <?php elseif ($current_streak >= 200): ?>
                <p class="text-xs text-blue-600 mt-2 hidden sm:block">
                    <i class="fas fa-award mr-1"></i>
                    200日突破！
                </p>
                <?php elseif ($current_streak >= 100): ?>
                <p class="text-xs text-blue-600 mt-2 hidden sm:block">
                    <i class="fas fa-medal mr-1"></i>
                    100日突破！
                </p>
                <?php elseif ($current_streak >= 30): ?>
                <p class="text-xs text-blue-600 mt-2 hidden sm:block">
                    <i class="fas fa-flag mr-1"></i>
                    1ヶ月達成！
                </p>
                <?php elseif ($current_streak >= 7): ?>
                <p class="text-xs text-blue-600 mt-2 hidden sm:block">
                    <i class="fas fa-fire mr-1"></i>
                    素晴らしい！1週間継続中です
                </p>
                <?php elseif ($current_streak >= 3): ?>
                <p class="text-xs text-blue-600 mt-2 hidden sm:block">
                    <i class="fas fa-thumbs-up mr-1"></i>
                    いい調子です！
                </p>
                <?php endif; ?>
            </div>
            
            <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-3 sm:p-4 md:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-1">今月の読書</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold text-green-600">
                            <?php echo $total_reading_days; ?>
                            <span class="text-sm sm:text-base md:text-lg font-normal">日</span>
                        </p>
                    </div>
                    <div class="text-2xl sm:text-3xl md:text-4xl text-green-400">
                        <i class="fas fa-book-reader"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                        <span>今月の進捗</span>
                        <span><?php echo round(($total_reading_days / date('t', mktime(0, 0, 0, $month, 1, $year))) * 100); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1 sm:h-2">
                        <div class="bg-green-600 h-1 sm:h-2 rounded-full" 
                             style="width: <?php echo min(100, ($total_reading_days / date('t', mktime(0, 0, 0, $month, 1, $year))) * 100); ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">今月の日数に対する読書日数の割合</p>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-3 sm:p-4 md:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-1">最長連続</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold text-purple-600">
                            <?php echo $longest_streak; ?>
                            <span class="text-sm sm:text-base md:text-lg font-normal">日</span>
                        </p>
                    </div>
                    <div class="text-2xl sm:text-3xl md:text-4xl text-purple-400">
                        <i class="fas fa-medal"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ビュー切り替えタブ -->
<section class="py-4">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-center mb-4">
            <div class="bg-gray-200 p-1 rounded-lg inline-flex relative">
                <a href="?view=calendar&year=<?php echo $year; ?>&month=<?php echo $month; ?>" 
                   class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $view_mode === 'calendar' ? 'bg-white text-gray-900 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i class="fas fa-calendar mr-2"></i>カレンダー
                </a>
                <a href="?view=heatmap&year=<?php echo $year; ?>" 
                   class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $view_mode === 'heatmap' ? 'bg-white text-gray-900 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i class="fas fa-th mr-2"></i>ヒートマップ
                </a>
                <a href="/help.php#reading-calendar" target="_blank" 
                   class="absolute -top-2 -right-2 bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-blue-600 transition-colors"
                   title="読書カレンダーの使い方">
                    <i class="fas fa-question"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- カレンダーセクション -->
<section class="py-4 sm:py-6 md:py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($view_mode === 'calendar'): ?>
        <!-- 月ナビゲーション -->
        <div class="flex flex-col sm:flex-row items-center justify-between mb-4 sm:mb-6 gap-3 sm:gap-4">
            <!-- モバイル用のナビゲーション -->
            <div class="flex items-center justify-between w-full sm:w-auto gap-2">
                <a href="?year=<?php echo $prev_year; ?>&month=<?php echo $prev_month; ?>" 
                   class="btn bg-gray-200 text-gray-700 hover:bg-gray-300 px-3 py-2 text-sm sm:text-base">
                    <i class="fas fa-chevron-left mr-1 sm:mr-2"></i>
                    <span class="hidden xs:inline">前月</span>
                </a>
                
                <!-- モバイル用の年月表示 -->
                <div class="sm:hidden flex items-center">
                    <span class="font-bold text-lg"><?php echo $year; ?>年<?php echo $month; ?>月</span>
                </div>
                
                <?php if (!($year >= date('Y') && $month >= date('n'))): ?>
                <a href="?year=<?php echo $next_year; ?>&month=<?php echo $next_month; ?>" 
                   class="btn bg-gray-200 text-gray-700 hover:bg-gray-300 px-3 py-2 text-sm sm:text-base">
                    <span class="hidden xs:inline">次月</span>
                    <i class="fas fa-chevron-right ml-1 sm:ml-2"></i>
                </a>
                <?php else: ?>
                <div class="btn bg-gray-100 text-gray-400 px-3 py-2 cursor-not-allowed text-sm sm:text-base">
                    <span class="hidden xs:inline">次月</span>
                    <i class="fas fa-chevron-right ml-1 sm:ml-2"></i>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- デスクトップ用のセレクターと今月ボタン -->
            <div class="hidden sm:flex items-center gap-3">
                <form method="get" action="" class="flex items-center gap-2">
                    <select name="year" class="px-2 sm:px-3 py-1 sm:py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-readnest-primary">
                        <?php 
                        $current_year = date('Y');
                        $start_year = 2000; // サービス開始以前のデータも対応
                        for ($y = $current_year; $y >= $start_year; $y--): 
                        ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                            <?php echo $y; ?>年
                        </option>
                        <?php endfor; ?>
                    </select>
                    
                    <select name="month" class="px-2 sm:px-3 py-1 sm:py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-readnest-primary">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                            <?php echo $m; ?>月
                        </option>
                        <?php endfor; ?>
                    </select>
                    
                    <button type="submit" class="btn bg-readnest-primary text-white hover:bg-readnest-primary-dark px-3 sm:px-4 py-1 sm:py-2 text-sm">
                        表示
                    </button>
                </form>
                
                <?php if (!($year == date('Y') && $month == date('n'))): ?>
                <a href="/reading_calendar.php" class="btn bg-gray-300 text-gray-700 hover:bg-gray-400 px-3 sm:px-4 py-1 sm:py-2 text-sm">
                    今月
                </a>
                <?php endif; ?>

                <a href="/report/<?php echo $year; ?>/<?php echo $month; ?>"
                   class="btn bg-teal-500 text-white hover:bg-teal-600 px-3 sm:px-4 py-1 sm:py-2 text-sm"
                   title="この月のレポートを見る">
                    <i class="fas fa-chart-bar mr-1"></i>レポート
                </a>
            </div>

            <!-- モバイル用の今月ボタンとセレクター -->
            <div class="sm:hidden flex items-center gap-2 w-full">
                <?php if (!($year == date('Y') && $month == date('n'))): ?>
                <a href="/reading_calendar.php" class="btn bg-gray-300 text-gray-700 hover:bg-gray-400 px-3 py-2 text-sm flex-1 text-center">
                    今月へ
                </a>
                <?php endif; ?>
                <a href="/report/<?php echo $year; ?>/<?php echo $month; ?>"
                   class="btn bg-teal-500 text-white hover:bg-teal-600 px-3 py-2 text-sm flex-1 text-center">
                    <i class="fas fa-chart-bar mr-1"></i>レポート
                </a>
                
                <!-- モバイル用のセレクター（アコーディオン的な表示） -->
                <details class="flex-1">
                    <summary class="btn bg-readnest-primary text-white px-3 py-2 text-sm cursor-pointer text-center">
                        年月を選択
                    </summary>
                    <form method="get" action="" class="mt-2 p-2 bg-white border rounded-md shadow-lg">
                        <select name="year" class="w-full mb-2 px-2 py-1 border border-gray-300 rounded text-sm">
                            <?php 
                            for ($y = $current_year; $y >= $start_year; $y--): 
                            ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                <?php echo $y; ?>年
                            </option>
                            <?php endfor; ?>
                        </select>
                        
                        <select name="month" class="w-full mb-2 px-2 py-1 border border-gray-300 rounded text-sm">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                <?php echo $m; ?>月
                            </option>
                            <?php endfor; ?>
                        </select>
                        
                        <button type="submit" class="w-full btn bg-readnest-primary text-white px-3 py-1 text-sm">
                            表示
                        </button>
                    </form>
                </details>
            </div>
        </div>
        
        <!-- カレンダー本体 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-x-auto">
            <table class="w-full min-w-[280px]">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="py-2 sm:py-3 text-center text-xs sm:text-sm font-semibold text-red-600 dark:text-red-400 px-1">日</th>
                        <th class="py-2 sm:py-3 text-center text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 px-1">月</th>
                        <th class="py-2 sm:py-3 text-center text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 px-1">火</th>
                        <th class="py-2 sm:py-3 text-center text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 px-1">水</th>
                        <th class="py-2 sm:py-3 text-center text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 px-1">木</th>
                        <th class="py-2 sm:py-3 text-center text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 px-1">金</th>
                        <th class="py-2 sm:py-3 text-center text-xs sm:text-sm font-semibold text-blue-600 dark:text-blue-400 px-1">土</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calendar as $week): ?>
                    <tr class="border-t dark:border-gray-600">
                        <?php foreach ($week as $day_info): ?>
                        <td class="relative h-20 xs:h-24 sm:h-28 md:h-32 border-r dark:border-gray-600 last:border-r-0 landscape:h-16">
                            <?php if ($day_info): ?>
                            <div class="p-0.5 sm:p-1 md:p-2 h-full <?php echo $day_info['is_today'] ? 'bg-yellow-50 dark:bg-yellow-900/20' : ''; ?>">
                                <div class="text-xs sm:text-sm md:text-base <?php echo $day_info['is_future'] ? 'text-gray-400 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300'; ?> <?php echo $day_info['has_reading'] ? 'font-semibold' : ''; ?>">
                                    <?php echo $day_info['day']; ?>
                                </div>
                                
                                <?php if ($day_info['has_reading'] && !empty($day_info['books'])): ?>
                                <div class="book-thumbnails">
                                    <?php 
                                    // モバイルでは最大2冊、タブレット以上では3冊表示
                                    $max_books = 2;
                                    if (isset($_SERVER['HTTP_USER_AGENT'])) {
                                        $is_mobile = preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT']);
                                        $max_books = $is_mobile ? 2 : 3;
                                    }
                                    $display_books = array_slice($day_info['books'], 0, $max_books);
                                    foreach ($display_books as $book): 
                                    ?>
                                        <?php 
                                        $image_url = normalizeBookImageUrl($book['image']);
                                        ?>
                                        <a href="/book/<?php echo $book['id']; ?>" 
                                           class="book-link block">
                                            <img src="<?php echo html($image_url); ?>" 
                                                 alt="<?php echo html($book['name']); ?>"
                                                 class="book-thumbnail"
                                                 onerror="this.src='/img/no-image-book.png'">
                                            <span class="tooltip"><?php echo html($book['name']); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if ($day_info['book_count'] > $max_books): ?>
                                <div class="text-xs text-center text-gray-500 dark:text-gray-400 mt-0.5 sm:mt-1">
                                    <span class="hidden xs:inline">+<?php echo $day_info['book_count'] - $max_books; ?></span>
                                    <span class="xs:hidden text-xs">他</span>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($day_info['is_today']): ?>
                                <div class="absolute bottom-0.5 right-0.5 sm:bottom-1 sm:right-1 text-xs text-yellow-600 landscape:text-xs">
                                    <span class="hidden xs:inline">今日</span>
                                    <span class="xs:hidden">●</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 凡例 -->
        <div class="mt-6 flex flex-wrap items-center justify-center gap-4 text-sm">
            <div class="flex items-center">
                <img src="/img/no-image-book.png" class="w-5 h-7 mr-2 rounded shadow-sm">
                <span class="text-gray-600">読んだ本</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-yellow-50 border border-yellow-200 mr-2"></div>
                <span class="text-gray-600">今日</span>
            </div>
            <div class="text-gray-500 text-xs">
                ※ 本の表紙をクリックすると詳細ページへ
            </div>
        </div>
        
        <!-- モチベーションメッセージ -->
        <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
            <div class="text-center">
                <?php
                // 年数計算（365日単位）
                $years = floor($current_streak / 365);
                if ($years >= 2):
                ?>
                    <i class="fas fa-crown text-4xl text-purple-600 mb-3"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">伝説の読書家！</h3>
                    <p class="text-gray-700"><?php echo $years; ?>年の連続記録は前人未到の領域です。あなたは真の読書家です！</p>
                <?php elseif ($years >= 1): ?>
                    <i class="fas fa-trophy text-4xl text-yellow-500 mb-3"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">1年達成！読書レジェンド！</h3>
                    <p class="text-gray-700">365日以上の連続記録は驚異的です。読書があなたの人生の一部になっています！</p>
                <?php elseif ($current_streak >= 100): ?>
                    <i class="fas fa-medal text-4xl text-orange-500 mb-3"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">100日突破！読書の達人！</h3>
                    <p class="text-gray-700">100日以上の継続は素晴らしい偉業です。読書が完全に習慣化されていますね！</p>
                <?php elseif ($current_streak >= 30): ?>
                    <i class="fas fa-crown text-4xl text-yellow-500 mb-3"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">読書マスター！</h3>
                    <p class="text-gray-700">30日以上の連続記録は素晴らしい成果です。この調子で続けましょう！</p>
                <?php elseif ($current_streak >= 7): ?>
                    <i class="fas fa-star text-4xl text-yellow-500 mb-3"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">習慣化成功！</h3>
                    <p class="text-gray-700">1週間の継続は大きな一歩です。読書が日常の一部になってきましたね。</p>
                <?php elseif ($current_streak >= 3): ?>
                    <i class="fas fa-seedling text-4xl text-green-500 mb-3"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">順調に成長中！</h3>
                    <p class="text-gray-700">3日連続は素晴らしいスタートです。この勢いを保ちましょう。</p>
                <?php else: ?>
                    <i class="fas fa-book-open text-4xl text-blue-500 mb-3"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">今日から始めよう！</h3>
                    <p class="text-gray-700">毎日少しずつでも大丈夫。10分の読書から始めてみませんか？</p>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="/bookshelf.php" class="btn bg-readnest-primary text-white hover:bg-readnest-primary-dark px-6 py-3">
                        <i class="fas fa-book mr-2"></i>
                        本棚へ行く
                    </a>
                </div>
            </div>
        </div>
        
        <!-- ヒント -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    読書習慣のコツ
                </h3>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li>• 毎日同じ時間に読む（朝起きてすぐ、寝る前など）</li>
                    <li>• 最初は10分から始める</li>
                    <li>• 読みやすい本から始める</li>
                    <li>• 読書する場所を決める</li>
                    <li>• 進捗を記録して達成感を味わう</li>
                </ul>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                    <i class="fas fa-chart-line text-green-500 mr-2"></i>
                    読書の効果
                </h3>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li>• ストレス軽減</li>
                    <li>• 語彙力の向上</li>
                    <li>• 集中力の改善</li>
                    <li>• 創造性の向上</li>
                    <li>• 睡眠の質の改善</li>
                </ul>
            </div>
        </div>
        
        <?php else: ?>
        <!-- ヒートマップビュー -->
        <?php
        // ヒートマップ用の関数
        function generateHeatmap($year, $heatmap_data) {
            $start_date = new DateTime("$year-01-01");
            $end_date = new DateTime("$year-12-31");
            
            // 最初の週の開始位置を計算
            $start_day_of_week = (int)$start_date->format('w');
            
            $weeks = [];
            $current_week = array_fill(0, $start_day_of_week, null);
            
            $current_date = clone $start_date;
            while ($current_date <= $end_date) {
                $date_str = $current_date->format('Y-m-d');
                $day_data = $heatmap_data[$date_str] ?? null;
                
                $current_week[] = [
                    'date' => $date_str,
                    'month' => (int)$current_date->format('n'),
                    'day' => (int)$current_date->format('j'),
                    'has_reading' => isset($heatmap_data[$date_str]),
                    'book_count' => $day_data['book_count'] ?? 0,
                    'event_count' => $day_data['event_count'] ?? 0,
                    'is_today' => $date_str === date('Y-m-d'),
                    'is_future' => $current_date > new DateTime()
                ];
                
                if (count($current_week) === 7) {
                    $weeks[] = $current_week;
                    $current_week = [];
                }
                
                $current_date->modify('+1 day');
            }
            
            // 最後の週を埋める
            if (!empty($current_week)) {
                while (count($current_week) < 7) {
                    $current_week[] = null;
                }
                $weeks[] = $current_week;
            }
            
            return $weeks;
        }
        
        $heatmap_weeks = generateHeatmap($year, $heatmap_data);
        
        // 月ラベルの位置を計算
        $month_positions = [];
        $last_month = 0;
        foreach ($heatmap_weeks as $week_index => $week) {
            foreach ($week as $day) {
                if ($day && $day['month'] !== $last_month) {
                    $month_positions[$day['month']] = $week_index;
                    $last_month = $day['month'];
                }
            }
        }
        ?>
        
        <!-- 年ナビゲーション -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <a href="?view=heatmap&year=<?php echo $year - 1; ?>" 
                   class="btn bg-gray-200 text-gray-700 hover:bg-gray-300 px-3 py-2">
                    <i class="fas fa-chevron-left mr-2"></i><?php echo $year - 1; ?>年
                </a>
                <span class="text-xl font-bold"><?php echo $year; ?>年</span>
                <?php if ($year < date('Y')): ?>
                <a href="?view=heatmap&year=<?php echo $year + 1; ?>" 
                   class="btn bg-gray-200 text-gray-700 hover:bg-gray-300 px-3 py-2">
                    <?php echo $year + 1; ?>年<i class="fas fa-chevron-right ml-2"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php if ($year != date('Y')): ?>
            <a href="?view=heatmap" class="btn bg-gray-300 text-gray-700 hover:bg-gray-400 px-4 py-2">
                今年へ
            </a>
            <?php endif; ?>
        </div>
        
        <!-- 年間統計 -->
        <?php
        $yearly_stats = [
            'total_days' => 0,
            'total_books' => 0,
            'max_books_day' => 0,
            'longest_streak' => 0
        ];
        
        // 統計を計算
        $current_streak = 0;
        $last_date = null;
        
        foreach ($heatmap_data as $date => $data) {
            $yearly_stats['total_days']++;
            $yearly_stats['total_books'] += $data['book_count'];
            $yearly_stats['max_books_day'] = max($yearly_stats['max_books_day'], $data['book_count']);
            
            // 連続記録の計算
            if ($last_date) {
                $diff = (new DateTime($date))->diff(new DateTime($last_date))->days;
                if ($diff === 1) {
                    $current_streak++;
                } else {
                    $yearly_stats['longest_streak'] = max($yearly_stats['longest_streak'], $current_streak);
                    $current_streak = 1;
                }
            } else {
                $current_streak = 1;
            }
            $last_date = $date;
        }
        $yearly_stats['longest_streak'] = max($yearly_stats['longest_streak'], $current_streak);
        ?>
        
        <div class="mb-8 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-sm text-gray-600">読書日数</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $yearly_stats['total_days']; ?>日</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-sm text-gray-600">読了冊数</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $yearly_stats['total_books']; ?>冊</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-sm text-gray-600">最多日</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $yearly_stats['max_books_day']; ?>冊</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-sm text-gray-600">最長連続</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $yearly_stats['longest_streak']; ?>日</p>
            </div>
        </div>
        
        <!-- ヒートマップ -->
        <div class="bg-white rounded-lg shadow-lg p-2 sm:p-4 overflow-x-auto">
            <div class="inline-block mx-auto">
                <!-- ヒートマップグリッド -->
                <div>
                    <!-- 月ラベル -->
                    <div class="flex mb-2">
                        <div class="w-5 sm:w-8 mr-1 sm:mr-2"></div>
                        <?php 
                        $month_names = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
                        
                        // 各週をチェックして月の開始位置を特定
                        $month_labels = [];
                        $last_month = 0;
                        
                        foreach ($heatmap_weeks as $week_index => $week) {
                            foreach ($week as $day) {
                                if ($day && $day['month'] !== $last_month) {
                                    $month_labels[] = [
                                        'month' => $day['month'],
                                        'position' => $week_index
                                    ];
                                    $last_month = $day['month'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <div class="relative h-4">
                            <?php 
                            $index = 0;
                            foreach ($month_labels as $label): 
                                $index++;
                                // モバイル: w-3(12px) + gap(4px) = 16px
                                // PC: w-4(16px) + gap(4px) = 20px
                                $mobile_position = $label['position'] * 16;
                                $desktop_position = $label['position'] * 20;
                            ?>
                                <span class="absolute text-xs text-gray-600 whitespace-nowrap month-label-<?php echo $index; ?>" 
                                      style="left: <?php echo $mobile_position; ?>px;">
                                    <?php echo $month_names[$label['month'] - 1]; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <style>
                            @media (min-width: 640px) {
                                <?php 
                                $index = 0;
                                foreach ($month_labels as $label): 
                                    $index++;
                                    $desktop_position = $label['position'] * 20;
                                ?>
                                .month-label-<?php echo $index; ?> {
                                    left: <?php echo $desktop_position; ?>px !important;
                                }
                                <?php endforeach; ?>
                            }
                        </style>
                    </div>
                    
                    <!-- グリッドコンテナ -->
                    <div class="flex">
                        <!-- 曜日ラベル -->
                        <div class="flex flex-col gap-1 mr-1 sm:mr-2 w-5 sm:w-8">
                            <div class="h-3 sm:h-4 flex items-center justify-end text-xs text-gray-600"></div>
                            <div class="h-3 sm:h-4 flex items-center justify-end text-xs text-gray-600">月</div>
                            <div class="h-3 sm:h-4 flex items-center justify-end text-xs text-gray-600"></div>
                            <div class="h-3 sm:h-4 flex items-center justify-end text-xs text-gray-600">水</div>
                            <div class="h-3 sm:h-4 flex items-center justify-end text-xs text-gray-600"></div>
                            <div class="h-3 sm:h-4 flex items-center justify-end text-xs text-gray-600">金</div>
                            <div class="h-3 sm:h-4 flex items-center justify-end text-xs text-gray-600"></div>
                        </div>
                        
                        <!-- グリッド -->
                        <div class="flex gap-1">
                            <?php foreach ($heatmap_weeks as $week): ?>
                        <div class="flex flex-col gap-1">
                            <?php foreach ($week as $day): ?>
                            <?php if ($day): ?>
                                <?php
                                $intensity = 0;
                                if ($day['book_count'] > 0) {
                                    if ($day['book_count'] >= 5) $intensity = 4;
                                    elseif ($day['book_count'] >= 3) $intensity = 3;
                                    elseif ($day['book_count'] >= 2) $intensity = 2;
                                    else $intensity = 1;
                                }
                                
                                $color_classes = [
                                    0 => 'bg-gray-100 hover:bg-gray-200',
                                    1 => 'bg-emerald-200 hover:bg-emerald-300',
                                    2 => 'bg-emerald-300 hover:bg-emerald-400',
                                    3 => 'bg-emerald-400 hover:bg-emerald-500',
                                    4 => 'bg-emerald-500 hover:bg-emerald-600'
                                ];
                                
                                $color_class = $day['is_future'] ? 'bg-gray-50' : $color_classes[$intensity];
                                if ($day['is_today']) {
                                    $color_class .= ' ring-1 ring-yellow-400';
                                }
                                ?>
                                <div class="w-3 h-3 sm:w-4 sm:h-4 rounded-sm <?php echo $color_class; ?> cursor-pointer relative group"
                                     data-date="<?php echo $day['date']; ?>"
                                     data-books="<?php echo $day['book_count']; ?>">
                                    <!-- ツールチップ -->
                                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                                        <?php echo date('n月j日', strtotime($day['date'])); ?>
                                        <?php if ($day['book_count'] > 0): ?>
                                        <br><?php echo $day['book_count']; ?>冊
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="w-3 h-3 sm:w-4 sm:h-4"></div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
        </div>
        
        </div>
        
        <!-- 凡例 -->
        <div class="mt-4 flex items-center justify-center gap-2 sm:gap-4 text-xs sm:text-sm">
            <span class="text-gray-600">少ない</span>
            <div class="flex items-center gap-0.5 sm:gap-1">
                <div class="w-3 h-3 sm:w-4 sm:h-4 bg-gray-100 rounded-sm"></div>
                <div class="w-3 h-3 sm:w-4 sm:h-4 bg-emerald-200 rounded-sm"></div>
                <div class="w-3 h-3 sm:w-4 sm:h-4 bg-emerald-300 rounded-sm"></div>
                <div class="w-3 h-3 sm:w-4 sm:h-4 bg-emerald-400 rounded-sm"></div>
                <div class="w-3 h-3 sm:w-4 sm:h-4 bg-emerald-500 rounded-sm"></div>
            </div>
            <span class="text-gray-600">多い</span>
        <?php endif; ?>
    </div>
</section>

<?php
$d_content = ob_get_clean();

// ベーステンプレートに渡す
include(getTemplatePath('t_base.php'));
?>