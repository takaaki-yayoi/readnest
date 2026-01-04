<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();

// 他のユーザーの本棚を見ている場合のuser_idパラメータを準備
$user_id_param = !$is_own_bookshelf ? '&user_id=' . urlencode($user_id) : '';

// パンくずリストを表示
if (isset($breadcrumbs)) {
    include(getTemplatePath('components/breadcrumb.php'));
}
?>

<!-- ヘッダーセクション -->
<section class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-4 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-1 sm:mb-2">
                    <i class="fas fa-book-open mr-2 sm:mr-3 text-lg sm:text-2xl"></i>
                    <?php echo $is_own_bookshelf ? 'あなたの本棚' : html($d_target_nickname) . 'さんの本棚'; ?>
                    <?php if (!$is_own_bookshelf && isset($user_level_info) && $user_level_info): ?>
                        <span class="ml-2 align-middle"><?php echo getLevelBadgeHtml($user_level_info, 'md'); ?></span>
                    <?php endif; ?>
                    <a href="/help.php#bookshelf" class="ml-3 text-base text-white opacity-75 hover:opacity-100 transition-opacity" title="本棚の使い方">
                        <i class="fas fa-question-circle"></i>
                    </a>
                </h1>
                <p class="text-sm sm:text-lg md:text-xl text-white opacity-90 hidden sm:block">
                    <?php echo $is_own_bookshelf ? '読書の記録と管理' : '読書の軌跡をご覧ください'; ?>
                </p>
            </div>
            <?php if ($is_own_bookshelf): ?>
            <!-- モバイル用グリッドボタン -->
            <div class="mt-3 grid grid-cols-2 gap-1 tablet:hidden">
                <a href="/reading_calendar.php" class="btn bg-white dark:bg-gray-800 text-readnest-primary dark:text-white hover:bg-readnest-beige dark:hover:bg-gray-700 px-1 py-2 text-xs font-medium text-center">
                    <i class="fas fa-calendar-check text-sm"></i>
                    <span class="block mt-1" style="font-size: 9px;">カレンダー</span>
                </a>
                <a href="/reading_insights.php" class="btn bg-white dark:bg-gray-800 text-readnest-primary dark:text-white hover:bg-readnest-beige dark:hover:bg-gray-700 px-1 py-2 text-xs font-medium text-center">
                    <i class="fas fa-chart-pie text-sm"></i>
                    <span class="block mt-1" style="font-size: 9px;">読書分析</span>
                </a>
            </div>
            <div class="mt-1 grid grid-cols-3 gap-1 tablet:hidden">
                <a href="/my_reviews.php" class="btn bg-white dark:bg-gray-800 text-readnest-primary dark:text-white hover:bg-readnest-beige dark:hover:bg-gray-700 px-1 py-2 text-xs font-medium text-center">
                    <i class="fas fa-pen-to-square text-sm"></i>
                    <span class="block mt-1" style="font-size: 9px;">レビュー</span>
                </a>
                <a href="/add_book.php" class="btn bg-transparent border border-white dark:border-gray-600 text-white hover:bg-white dark:hover:bg-gray-800 hover:text-readnest-primary dark:hover:text-white px-1 py-2 text-xs font-medium text-center">
                    <i class="fas fa-plus-circle text-sm"></i>
                    <span class="block mt-1" style="font-size: 9px;">本を追加</span>
                </a>
                <a href="/add_original_book.php" class="btn bg-transparent border border-white dark:border-gray-600 text-white hover:bg-white dark:hover:bg-gray-800 hover:text-readnest-primary dark:hover:text-white px-1 py-2 text-xs font-medium text-center">
                    <i class="fas fa-edit text-sm"></i>
                    <span class="block mt-1" style="font-size: 9px;">手動追加</span>
                </a>
            </div>
            
            <!-- タブレット用ボタン -->
            <div class="hidden tablet:flex tablet-lg:hidden mt-4 gap-2 flex-wrap">
                <a href="/reading_calendar.php" class="btn bg-white dark:bg-gray-800 text-readnest-primary dark:text-white hover:bg-readnest-beige dark:hover:bg-gray-700 px-4 py-2 text-sm font-medium">
                    <i class="fas fa-calendar-check mr-1"></i>カレンダー
                </a>
                <a href="/reading_insights.php" class="btn bg-white dark:bg-gray-800 text-readnest-primary dark:text-white hover:bg-readnest-beige dark:hover:bg-gray-700 px-4 py-2 text-sm font-medium">
                    <i class="fas fa-chart-pie mr-1"></i>読書分析
                </a>
                <a href="/my_reviews.php" class="btn bg-white dark:bg-gray-800 text-readnest-primary dark:text-white hover:bg-readnest-beige dark:hover:bg-gray-700 px-4 py-2 text-sm font-medium">
                    <i class="fas fa-pen-to-square mr-1"></i>レビュー
                </a>
                <a href="/add_book.php" class="btn bg-transparent border-2 border-white dark:border-gray-600 text-white hover:bg-white dark:hover:bg-gray-800 hover:text-readnest-primary dark:hover:text-white px-4 py-2 text-sm font-medium">
                    <i class="fas fa-plus-circle mr-1"></i>本を追加
                </a>
                <a href="/add_original_book.php" class="btn bg-transparent border-2 border-white dark:border-gray-600 text-white hover:bg-white dark:hover:bg-gray-800 hover:text-readnest-primary dark:hover:text-white px-4 py-2 text-sm font-medium">
                    <i class="fas fa-edit mr-1"></i>手動追加
                </a>
            </div>
            
            <!-- デスクトップ用ボタン -->
            <div class="hidden tablet-lg:flex mt-0 flex-row gap-3">
                <a href="/reading_insights.php" class="btn bg-white dark:bg-gray-800 text-readnest-primary dark:text-white hover:bg-readnest-beige dark:hover:bg-gray-700 px-6 py-3 font-semibold">
                    <i class="fas fa-chart-pie mr-2"></i>読書分析
                </a>
                <a href="/add_book.php" class="btn bg-transparent border-2 border-white dark:border-gray-600 text-white hover:bg-white dark:hover:bg-gray-800 hover:text-readnest-primary dark:hover:text-white px-6 py-3 font-semibold">
                    <i class="fas fa-plus-circle mr-2"></i>本を追加
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 統合タブセクション（読書統計、AI、作家クラウド、タグクラウド） -->
<?php include(__DIR__ . '/t_bookshelf_tabs.php'); ?>

<!-- 旧統計セクション（コメントアウト） -->
<?php if (false): // コメントアウト ?>
<!-- Alpine.jsコードをHTMLコメント内に移動
<section class="bg-white dark:bg-gray-900 py-4 sm:py-8 border-b dark:border-gray-700" x-data="{ 
    statsOpen: localStorage.getItem('bookshelfStatsOpen') !== 'false',
    toggle() {
        this.statsOpen = !this.statsOpen;
        localStorage.setItem('bookshelfStatsOpen', this.statsOpen);
    }
}">
-->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between" :class="statsOpen ? 'mb-3 sm:mb-6' : ''">
            <button @click="toggle()" class="flex items-center justify-between w-full sm:w-auto text-gray-700 dark:text-gray-300 font-medium hover:text-gray-900 dark:hover:text-gray-100 focus:outline-none">
                <span class="flex items-center">
                    <i class="fas fa-chart-bar mr-2"></i>
                    読書統計
                </span>
                <i class="fas fa-chevron-down ml-2 transform transition-transform text-sm" :class="{ 'rotate-180': statsOpen }"></i>
            </button>
            <?php if ($is_own_bookshelf): ?>
            <a href="/reading_calendar.php" 
               x-show="statsOpen"
               x-cloak
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="opacity-0"
               x-transition:enter-end="opacity-100"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="opacity-100"
               x-transition:leave-end="opacity-0"
               class="inline-flex items-center px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors mt-2 sm:mt-0">
                <i class="fas fa-calendar-check mr-2"></i>
                今日の読書を確認
            </a>
            <?php endif; ?>
        </div>
        <div x-show="statsOpen" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-6 text-center">
            <div>
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-readnest-primary dark:text-readnest-accent">
                    <?php echo number_format(isset($bookshelf_stats[BUY_SOMEDAY]) ? $bookshelf_stats[BUY_SOMEDAY] : 0); ?>
                </div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">いつか買う</div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-blue-600">
                    <?php echo number_format(isset($bookshelf_stats[NOT_STARTED]) ? $bookshelf_stats[NOT_STARTED] : 0); ?>
                </div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">未読</div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-yellow-600">
                    <?php echo number_format(isset($bookshelf_stats[READING_NOW]) ? $bookshelf_stats[READING_NOW] : 0); ?>
                </div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">読書中</div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-green-600">
                    <?php 
                    $finished_count = (isset($bookshelf_stats[READING_FINISH]) ? $bookshelf_stats[READING_FINISH] : 0) + 
                                     (isset($bookshelf_stats[READ_BEFORE]) ? $bookshelf_stats[READ_BEFORE] : 0);
                    echo number_format($finished_count); 
                    ?>
                </div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">読了</div>
            </div>
            <div class="col-span-2 md:col-span-1">
                <div class="text-xl sm:text-2xl md:text-3xl font-bold text-readnest-accent">
                    <?php echo number_format(isset($read_stats[1]) ? $read_stats[1] : 0); ?>
                </div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">総ページ数</div>
            </div>
        </div>
    </div>
</section>
<?php endif; // 旧統計セクション終了 ?>

<!-- フィルター・ソートセクション -->
<section class="bg-gray-50 dark:bg-gray-900 py-4 sm:py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 検索ボックス -->
        <div class="mb-4">
            <form method="get" action="/bookshelf.php" class="flex flex-col sm:flex-row gap-2">
                <div class="flex-1 relative">
                    <input type="text" 
                           id="bookshelf-search"
                           name="search_word" 
                           value="<?php echo html($search_word); ?>" 
                           placeholder="タイトルまたは著者名で検索..."
                           class="w-full px-4 py-2 pl-10 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <select name="search_type" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-readnest-primary">
                    <option value="">すべて</option>
                    <option value="title" <?php echo $search_type === 'title' ? 'selected' : ''; ?>>タイトル</option>
                    <option value="author" <?php echo $search_type === 'author' ? 'selected' : ''; ?>>著者</option>
                    <option value="tag" <?php echo $search_type === 'tag' ? 'selected' : ''; ?>>タグ</option>
                </select>
                <button type="submit" class="px-4 py-2 text-sm bg-readnest-primary text-white rounded-lg hover:bg-readnest-accent transition-colors whitespace-nowrap">
                    <i class="fas fa-search mr-1"></i>検索
                </button>
                <?php if (!$is_own_bookshelf): ?>
                <input type="hidden" name="user_id" value="<?php echo html($user_id); ?>">
                <?php endif; ?>
                <?php if (!empty($status_filter)): ?>
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <?php endif; ?>
                <?php if (!empty($filter_year)): ?>
                <input type="hidden" name="filter_year" value="<?php echo $filter_year; ?>">
                <?php endif; ?>
                <?php if (!empty($filter_month)): ?>
                <input type="hidden" name="filter_month" value="<?php echo $filter_month; ?>">
                <?php endif; ?>
            </form>
            
            <!-- アクティブなフィルターの表示 -->
            <?php if (!empty($filter_year) || !empty($filter_month)): ?>
            <div class="mt-3 flex items-center gap-2 text-sm">
                <span class="text-gray-600">フィルター中:</span>
                <?php if (!empty($filter_year) && empty($filter_month)): ?>
                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full">
                    <i class="fas fa-calendar mr-1"></i><?php echo html($filter_year); ?>年
                </span>
                <?php elseif (!empty($filter_month)): ?>
                <span class="inline-block px-3 py-1 bg-purple-100 text-purple-800 rounded-full">
                    <i class="fas fa-calendar-alt mr-1"></i><?php echo date('Y年n月', strtotime($filter_month . '-01')); ?>
                </span>
                <?php endif; ?>
                <a href="/bookshelf.php?status=<?php echo $status_filter; ?><?php echo $user_id_param; ?>"
                   class="inline-block px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-1"></i>解除
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 検索結果とフィルター/ソート -->
        <div>
            
            <!-- 検索結果表示 -->
            <?php if (!empty($search_word) || $tag_filter === 'no_tags' || $cover_filter === 'no_cover'): ?>
            <div class="mt-4 mb-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-gray-700 dark:text-gray-300">
                        <?php if ($tag_filter === 'no_tags'): ?>
                            <i class="fas fa-tags mr-1 opacity-50"></i>タグのない本
                        <?php elseif ($cover_filter === 'no_cover'): ?>
                            <i class="fas fa-image-slash mr-1"></i>表紙のない本
                        <?php elseif ($search_type === 'author'): ?>
                            著者「<strong><?php echo html($search_word); ?></strong>」の検索結果
                        <?php elseif ($search_type === 'title'): ?>
                            タイトル「<strong><?php echo html($search_word); ?></strong>」の検索結果
                        <?php elseif ($search_type === 'tag'): ?>
                            タグ「<strong><?php echo html($search_word); ?></strong>」の検索結果
                        <?php else: ?>
                            「<strong><?php echo html($search_word); ?></strong>」の検索結果
                        <?php endif; ?>
                        （<?php echo count($books); ?>件）
                    </p>
                    <div class="flex items-center gap-2">
                        <?php if ($tag_filter === 'no_tags' || $cover_filter === 'no_cover'): ?>
                        <?php
                        // フィルター解除用のパラメータを構築
                        $clear_params = $_GET;
                        unset($clear_params['tag_filter']);
                        unset($clear_params['cover_filter']);
                        $clear_query = !empty($clear_params) ? '?' . http_build_query($clear_params) : '?';
                        ?>
                        <a href="<?php echo $clear_query; ?>"
                           class="inline-flex items-center px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-times mr-1"></i>
                            フィルター解除
                        </a>
                        <?php endif; ?>
                        <?php if ($search_type === 'author' && !empty($search_word)): ?>
                        <a href="/add_book.php?keyword=<?php echo urlencode($search_word); ?>" 
                           class="inline-flex items-center px-3 py-1 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-search-plus mr-1"></i>
                            <?php echo html($search_word); ?>の本を探す
                        </a>
                        <?php endif; ?>
                        <?php if ($is_own_bookshelf && ($search_type === 'author' || $search_type === 'tag')): ?>
                        <a href="/reading_insights.php?mode=map" class="inline-flex items-center px-3 py-1 text-xs bg-readnest-primary text-white rounded-lg hover:bg-readnest-primary-dark transition-colors">
                            <i class="fas fa-map-marked-alt mr-1"></i>
                            マップで確認
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- フィルター（ステータス＋特殊フィルター） -->
            <div class="flex flex-wrap gap-2">
                <!-- ステータスフィルター -->
                <a href="?status=<?php echo !empty($search_word) ? '&search_type=' . urlencode($search_type) . '&search_word=' . urlencode($search_word) : ''; ?><?php echo $user_id_param; ?>"
                   class="px-4 py-2 rounded-full text-sm font-medium transition-colors <?php echo empty($status_filter) ? 'bg-readnest-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    すべて
                </a>
                <a href="?status=<?php echo BUY_SOMEDAY; ?><?php echo !empty($search_word) ? '&search_type=' . urlencode($search_type) . '&search_word=' . urlencode($search_word) : ''; ?><?php echo $user_id_param; ?>"
                   class="px-4 py-2 rounded-full text-sm font-medium transition-colors <?php echo $status_filter == BUY_SOMEDAY ? 'bg-gray-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    いつか買う
                </a>
                <a href="?status=<?php echo NOT_STARTED; ?><?php echo !empty($search_word) ? '&search_type=' . urlencode($search_type) . '&search_word=' . urlencode($search_word) : ''; ?><?php echo $user_id_param; ?>"
                   class="px-4 py-2 rounded-full text-sm font-medium transition-colors <?php echo $status_filter == NOT_STARTED ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    未読
                </a>
                <a href="?status=<?php echo READING_NOW; ?><?php echo !empty($search_word) ? '&search_type=' . urlencode($search_type) . '&search_word=' . urlencode($search_word) : ''; ?><?php echo $user_id_param; ?>"
                   class="px-4 py-2 rounded-full text-sm font-medium transition-colors <?php echo $status_filter == READING_NOW ? 'bg-yellow-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    読書中
                </a>
                <a href="?status=<?php echo READING_FINISH; ?><?php echo !empty($search_word) ? '&search_type=' . urlencode($search_type) . '&search_word=' . urlencode($search_word) : ''; ?><?php echo $user_id_param; ?>"
                   class="px-4 py-2 rounded-full text-sm font-medium transition-colors <?php echo $status_filter == READING_FINISH ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                    読了
                </a>
                
                <!-- 特殊フィルター（表紙なし・タグなし） -->
                <?php if ($is_own_bookshelf): ?>
                <div class="inline-flex gap-2 pl-2 border-l-2 border-gray-300 dark:border-gray-600">
                    <a href="?cover_filter=<?php echo $cover_filter === 'no_cover' ? '' : 'no_cover'; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($search_word) ? '&search_type=' . urlencode($search_type) . '&search_word=' . urlencode($search_word) : ''; ?><?php echo $user_id_param; ?>"
                       class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium transition-all
                              <?php echo $cover_filter === 'no_cover'
                                     ? 'bg-purple-600 text-white'
                                     : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                        <i class="fas fa-image-slash mr-1.5 text-xs"></i>
                        表紙なし
                    </a>

                    <a href="?tag_filter=<?php echo $tag_filter === 'no_tags' ? '' : 'no_tags'; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($search_word) ? '&search_type=' . urlencode($search_type) . '&search_word=' . urlencode($search_word) : ''; ?><?php echo $user_id_param; ?>"
                       class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium transition-all
                              <?php echo $tag_filter === 'no_tags'
                                     ? 'bg-indigo-600 text-white'
                                     : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                        <i class="fas fa-tags mr-1.5 text-xs"></i>
                        タグなし
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- ソート -->
            <div class="flex items-center gap-2">
                <label for="sort-select" class="text-sm text-gray-600 hidden lg:inline font-medium">
                    <i class="fas fa-sort mr-1"></i>並び順:
                </label>
                <div class="relative">
                    <select id="sort-select"
                            onchange="location.href='?status=<?php echo $status_filter; ?>&sort=' + this.value + '<?php echo !empty($search_word) ? '&search_type=' . urlencode($search_type) . '&search_word=' . urlencode($search_word) : ''; ?><?php echo !empty($tag_filter) ? '&tag_filter=' . urlencode($tag_filter) : ''; ?><?php echo !empty($cover_filter) ? '&cover_filter=' . urlencode($cover_filter) : ''; ?><?php echo $user_id_param; ?>'"
                            class="appearance-none px-3 sm:px-4 py-2.5 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent transition-all duration-200 shadow-sm cursor-pointer max-w-[200px] sm:max-w-none">
                        <optgroup label="📅 日付で並べ替え">
                            <option value="update_date_desc" <?php echo $sort_order === 'update_date_desc' ? 'selected' : ''; ?>>更新日（新しい→古い）</option>
                            <option value="update_date_asc" <?php echo $sort_order === 'update_date_asc' ? 'selected' : ''; ?>>更新日（古い→新しい）</option>
                            <option value="finished_date_desc" <?php echo $sort_order === 'finished_date_desc' ? 'selected' : ''; ?>>読了日（新しい→古い）</option>
                            <option value="finished_date_asc" <?php echo $sort_order === 'finished_date_asc' ? 'selected' : ''; ?>>読了日（古い→新しい）</option>
                            <option value="created_date_desc" <?php echo $sort_order === 'created_date_desc' ? 'selected' : ''; ?>>登録日（新しい→古い）</option>
                            <option value="created_date_asc" <?php echo $sort_order === 'created_date_asc' ? 'selected' : ''; ?>>登録日（古い→新しい）</option>
                        </optgroup>
                        <optgroup label="⭐ 評価で並べ替え">
                            <option value="rating_desc" <?php echo $sort_order === 'rating_desc' ? 'selected' : ''; ?>>評価（高い→低い）</option>
                            <option value="rating_asc" <?php echo $sort_order === 'rating_asc' ? 'selected' : ''; ?>>評価（低い→高い）</option>
                        </optgroup>
                        <optgroup label="📖 タイトル・著者で並べ替え">
                            <option value="title_asc" <?php echo $sort_order === 'title_asc' || $sort_order === 'name' ? 'selected' : ''; ?>>タイトル（あ→ん）</option>
                            <option value="title_desc" <?php echo $sort_order === 'title_desc' ? 'selected' : ''; ?>>タイトル（ん→あ）</option>
                            <option value="author_asc" <?php echo $sort_order === 'author_asc' || $sort_order === 'author' ? 'selected' : ''; ?>>著者名（あ→ん）</option>
                            <option value="author_desc" <?php echo $sort_order === 'author_desc' ? 'selected' : ''; ?>>著者名（ん→あ）</option>
                        </optgroup>
                        <optgroup label="📏 ページ数で並べ替え">
                            <option value="pages_desc" <?php echo $sort_order === 'pages_desc' ? 'selected' : ''; ?>>ページ数（多い→少ない）</option>
                            <option value="pages_asc" <?php echo $sort_order === 'pages_asc' ? 'selected' : ''; ?>>ページ数（少ない→多い）</option>
                        </optgroup>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <!-- クイック反転ボタン -->
                <button onclick="toggleSortOrder()" 
                        class="p-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-readnest-primary transition-all duration-200 shadow-sm group" 
                        title="並び順を反転">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400 group-hover:text-readnest-primary transition-colors" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                </button>
            </div>
            </div>
        </div>
    </div>
</section>

<!-- AI読書推薦セクション（コメントアウト） -->
<?php if (false): // 完全に無効化 ?>
<!-- Alpine.jsコードをHTMLコメント内に移動
<section class="py-4 sm:py-8" x-data="{ 
    aiAdvisorOpen: localStorage.getItem('bookshelfAIAdvisorOpen') === 'true',
    toggle() {
        this.aiAdvisorOpen = !this.aiAdvisorOpen;
        localStorage.setItem('bookshelfAIAdvisorOpen', this.aiAdvisorOpen);
    }
}">
-->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- ヘッダー -->
            <div class="px-4 sm:px-6 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 cursor-pointer"
                 @click="toggle()">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-robot text-gray-500 mr-2"></i>AI読書アドバイザー
                        </h2>
                    </div>
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        <i class="fas" :class="aiAdvisorOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                </div>
            </div>
            
            <!-- コンテンツ -->
            <div x-show="aiAdvisorOpen" x-collapse>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- AI推薦へのリンク -->
                        <a href="/recommendations.php"
                           class="block bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-lg p-6 border-2 border-purple-200 dark:border-purple-700 hover:shadow-lg transition-all duration-300 group">
                            <div class="flex items-center mb-3">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-800 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-magic text-purple-600 dark:text-purple-300 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-purple-900 dark:text-purple-100 text-lg">AI推薦</h3>
                                    <span class="text-xs text-purple-600 dark:text-purple-300">おすすめの本を発見</span>
                                </div>
                            </div>
                            <p class="text-sm text-purple-700 dark:text-purple-200 mb-4">あなたの読書傾向を分析して、次に読むべき本を提案します</p>
                            <div class="flex items-center text-purple-600 dark:text-purple-300 font-medium text-sm group-hover:translate-x-1 transition-transform">
                                <span>AI推薦を見る</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </div>
                        </a>

                        <!-- 読書分析へのリンク -->
                        <a href="/reading_insights.php"
                           class="block bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-900/30 dark:to-blue-900/30 rounded-lg p-6 border-2 border-indigo-200 dark:border-indigo-700 hover:shadow-lg transition-all duration-300 group">
                            <div class="flex items-center mb-3">
                                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-800 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-chart-pie text-indigo-600 dark:text-indigo-300 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-indigo-900 dark:text-indigo-100 text-lg">読書分析</h3>
                                    <span class="text-xs text-indigo-600 dark:text-indigo-300">統計・傾向・マップ</span>
                                </div>
                            </div>
                            <p class="text-sm text-indigo-700 dark:text-indigo-200 mb-4">読書統計、AI傾向診断、読書マップなど詳細な分析を確認できます</p>
                            <div class="flex items-center text-indigo-600 dark:text-indigo-300 font-medium text-sm group-hover:translate-x-1 transition-transform">
                                <span>分析を見る</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- </section> -->
<?php endif; ?>

<!-- 作家クラウドセクション（コメントアウト） -->
<?php if (false): // 完全に無効化 ?>
<!-- Alpine.jsコードをHTMLコメント内に移動
<section id="author-cloud" class="py-4 sm:py-8" x-data="{ 
    sectionOpen: localStorage.getItem('bookshelfAuthorCloudOpen') !== 'false',
    toggleSection() {
        this.sectionOpen = !this.sectionOpen;
        localStorage.setItem('bookshelfAuthorCloudOpen', this.sectionOpen);
    }
}">
-->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- ヘッダー -->
            <div class="px-4 sm:px-6 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 cursor-pointer"
                 @click="toggleSection()">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-feather-alt text-gray-500 mr-2"></i>あなたの作家クラウド
                        </h2>
                    </div>
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        <i class="fas" :class="sectionOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                </div>
            </div>
            
            <!-- コンテンツ -->
            <div x-show="sectionOpen" x-collapse>
                <div class="p-4 sm:p-6">
                    <!-- 作家クラウド -->
                    <div class="text-center">
                        <?php
                        // 作家クラウドを表示
                        foreach ($author_cloud_data as $author) {
                            $colorClass = $author['color_class'] ?? 'from-gray-500 to-gray-600';
                            $fontSize = $author['font_size'] ?? 14;
                            $isFavorite = $author['is_favorite'] ?? false;
                            ?>
                            <a href="/bookshelf.php?search_word=<?php echo urlencode($author['author']); ?>&search_type=author<?php echo $user_id_param; ?>"
                               class="inline-block px-2 py-1 m-1 rounded-lg transition-all duration-300 hover:scale-110 bg-gradient-to-r <?php echo $colorClass; ?> text-white <?php echo $isFavorite ? 'ring-2 ring-yellow-400' : ''; ?>"
                               style="font-size: <?php echo $fontSize; ?>px;"
                               title="<?php echo htmlspecialchars($author['author']); ?> (<?php echo $author['book_count']; ?>冊)">
                                <?php echo htmlspecialchars($author['author']); ?>
                                <?php echo $isFavorite ? '⭐' : ''; ?>
                            </a>
                        <?php } ?>
                    </div>
                    
                    <!-- すべて見るリンク -->
                    <div class="mt-4 text-center">
                        <a href="/my_authors.php" class="inline-flex items-center px-4 py-2 text-sm text-blue-600 hover:text-blue-700 font-medium">
                            <i class="fas fa-th mr-2"></i>すべての作家を見る
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    
                    <!-- 凡例 -->
                    <div class="mt-6 flex flex-wrap justify-center gap-4 text-xs">
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded"></span>
                            読了が多い
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded"></span>
                            読書中
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-gradient-to-r from-gray-500 to-gray-600 rounded"></span>
                            その他
                        </span>
                        <span class="flex items-center gap-1">
                            ⭐ 高評価
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- </section> -->
<?php endif; ?>

<!-- タグクラウドセクション（コメントアウト） -->
<?php if (false): // 完全に無効化 ?>
<!--
<section class="py-4 sm:py-8" x-data="{ 
    tagCloudComponentData: {},
    sectionOpen: localStorage.getItem('bookshelfTagCloudOpen') !== 'false',
    toggleSection() {
        this.sectionOpen = !this.sectionOpen;
        localStorage.setItem('bookshelfTagCloudOpen', this.sectionOpen);
    }
}">
-->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- ヘッダー -->
            <div class="px-4 sm:px-6 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 cursor-pointer"
                 @click="toggleSection()">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-tags text-gray-500 mr-2"></i>あなたのタグクラウド
                        </h2>
                    </div>
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        <i class="fas" :class="sectionOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                </div>
            </div>
            
            <!-- コンテンツ -->
            <div x-show="sectionOpen" x-collapse>
                <div class="p-4 sm:p-6">
            
            <!-- タグ切り替えボタン -->
            <div class="flex items-center justify-between mb-4" x-cloak>
                <div class="flex gap-2">
                    <button @click="setTagMode('popular')" 
                            :class="tagMode === 'popular' ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'">
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
                        <i class="fas fa-fire mr-1"></i>人気のタグ
                    </button>
                    <button @click="setTagMode('recent')" 
                            :class="tagMode === 'recent' ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'">
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
                        <i class="fas fa-clock mr-1"></i>最新のタグ
                    </button>
                </div>
                <template x-if="totalTags > 30">
                    <a href="/tag_cloud.php" class="text-sm text-green-600 hover:text-green-700 font-medium">
                        すべて見る <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </template>
            </div>
            
            <!-- ローディング表示 -->
            <div x-show="loading" x-cloak class="text-center py-8">
                <svg class="animate-spin h-8 w-8 mx-auto text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-600">タグを読み込み中...</p>
            </div>
            
            <!-- エラー表示 -->
            <div x-show="error" x-cloak class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <p x-text="error"></p>
            </div>
            
            <!-- タグクラウド表示 -->
            <div x-show="!loading && !error && tags.length > 0" x-cloak class="text-center" id="tag-cloud-container">
                <!-- タグはJavaScriptで動的に生成 -->
            </div>
            
            <!-- 空の状態 -->
            <div x-show="!loading && !error && tags.length === 0" x-cloak class="text-center text-gray-500">
                <p>まだタグが付けられていません。本の詳細ページでタグを追加してみましょう。</p>
            </div>
            
            <!-- 統計情報 -->
            <div x-show="!loading && stats && stats.total_tags !== undefined" x-cloak class="mt-4 pt-4 border-t flex justify-center gap-4 text-sm text-gray-600">
                <span><strong x-text="stats?.total_tags || 0"></strong> 個のタグ</span>
                <span><strong x-text="stats?.total_books_with_tags || 0"></strong> 冊の本</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- </section> -->
<?php endif; ?>

<!-- 本一覧セクション -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($cover_filter === 'no_cover' && !empty($books) && $is_own_bookshelf): ?>
        <!-- 表紙なしフィルタ有効時のヒント -->
        <div class="mb-6 bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-start">
                <i class="fas fa-lightbulb text-purple-600 mt-1 mr-3"></i>
                <div>
                    <h3 class="text-sm font-semibold text-purple-900 mb-1">表紙画像を設定しましょう</h3>
                    <p class="text-sm text-purple-700">
                        表紙画像が設定されていない本が表示されています。
                        各本の詳細ページから<i class="fas fa-camera mx-1"></i>ボタンをクリックして、表紙画像を設定できます。
                        Google Books、OpenLibrary、国立国会図書館の画像候補から選択するか、お好きな画像をアップロードできます。
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (empty($books)): ?>
        <!-- 空の状態 -->
        <div class="text-center py-12">
            <div class="w-24 h-24 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-book text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                <?php if ($tag_filter === 'no_tags'): ?>
                    タグのない本はありません
                <?php elseif (!empty($search_word)): ?>
                    検索条件に合う本が見つかりません
                <?php else: ?>
                    <?php echo $is_own_bookshelf ? '本棚が空です' : 'まだ本が登録されていません'; ?>
                <?php endif; ?>
            </h3>
            <p class="text-gray-600 mb-6">
                <?php if ($tag_filter === 'no_tags'): ?>
                    すべての本にタグが付けられています
                <?php elseif (!empty($search_word)): ?>
                    別のキーワードで検索してみるか、読書分析で探索してみましょう
                <?php else: ?>
                    <?php echo $is_own_bookshelf ? '最初の本を追加してみましょう！' : 'また後でチェックしてみてください。'; ?>
                <?php endif; ?>
            </p>
            <?php if ($is_own_bookshelf): ?>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <?php if (!empty($search_word)): ?>
                <a href="/reading_insights.php?mode=map" class="btn bg-readnest-primary text-white px-6 py-3 font-semibold">
                    <i class="fas fa-chart-pie mr-2"></i>読書分析で探索
                </a>
                <a href="/bookshelf.php" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 px-6 py-3 font-semibold">
                    <i class="fas fa-list mr-2"></i>すべての本を見る
                </a>
                <?php else: ?>
                <a href="/add_book.php" class="btn bg-readnest-primary text-white px-6 py-3 font-semibold">
                    <i class="fas fa-plus-circle mr-2"></i>本を追加する
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- 本一覧 -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <?php foreach ($books as $book): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 hover:shadow-md transition-shadow flex flex-col h-full">
                <a href="/book/<?php echo $book['book_id']; ?>" class="block">
                    <div class="relative w-full" style="padding-bottom: 133.33%;">
                        <img src="<?php echo html($book['image_url']); ?>" 
                             alt="<?php echo html($book['title']); ?>" 
                             class="absolute inset-0 w-full h-full object-cover rounded-t-lg"
                             loading="lazy"
                             onerror="this.src='/img/no-image-book.png'">
                        <?php if ($is_own_bookshelf): ?>
                        <!-- お気に入りボタン -->
                        <button onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(<?php echo $book['book_id']; ?>, this); return false;"
                                class="absolute top-2 right-2 w-8 h-8 bg-white dark:bg-gray-800 bg-opacity-90 dark:bg-opacity-90 rounded-full flex items-center justify-center hover:bg-opacity-100 dark:hover:bg-opacity-100 transition-all shadow-md group"
                                title="<?php echo $book['is_favorite'] ? 'お気に入りから削除' : 'お気に入りに追加'; ?>">
                            <i class="<?php echo $book['is_favorite'] ? 'fas' : 'far'; ?> fa-star text-yellow-500 group-hover:scale-110 transition-transform"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </a>
                
                <div class="p-3 flex flex-col h-full">
                    <!-- ステータス -->
                    <div class="mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo $book['status_class']; ?>">
                            <?php echo html($book['status_label']); ?>
                        </span>
                    </div>
                    
                    <!-- タイトル -->
                    <h3 class="font-medium text-sm text-gray-900 line-clamp-2 mb-1">
                        <a href="/book/<?php echo $book['book_id']; ?>" class="text-gray-900 dark:text-gray-100 hover:text-readnest-primary transition-colors">
                            <?php echo html($book['title']); ?>
                        </a>
                    </h3>
                    
                    <!-- 著者 -->
                    <?php if (!empty($book['author'])): ?>
                    <p class="text-xs line-clamp-1 mb-2">
                        <a href="/author.php?name=<?php echo urlencode($book['author']); ?>" 
                           class="text-blue-600 dark:text-blue-400 hover:text-readnest-primary hover:underline transition-colors inline-flex items-center gap-1 group"
                           title="<?php echo html($book['author']); ?>の作家情報を見る">
                            <i class="fas fa-user-edit text-[10px] text-gray-400 dark:text-gray-500 group-hover:text-readnest-primary transition-colors"></i>
                            <span><?php echo html($book['author']); ?></span>
                        </a>
                    </p>
                    <?php else: ?>
                    <p class="text-xs text-gray-500 line-clamp-1 mb-2">著者不明</p>
                    <?php endif; ?>
                    
                    <!-- 評価とレビュー -->
                    <div class="flex items-center gap-2 mb-2">
                        <?php if ($book['rating'] > 0): ?>
                        <div class="text-xs text-yellow-500">
                            <?php echo html($book['star_display']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($book['has_review']): ?>
                        <div class="text-xs text-green-600" title="レビューあり">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 中央部分（flex-growで成長） -->
                    <div class="flex-grow">
                        <!-- タグ -->
                        <?php if (!empty($book['tags'])): ?>
                        <div class="mb-2">
                            <div class="flex flex-wrap gap-1">
                                <?php foreach (array_slice($book['tags'], 0, 3) as $tag): ?>
                                <span class="inline-block px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded text-xs">
                                    <?php echo html($tag['tag_name']); ?>
                                </span>
                                <?php endforeach; ?>
                                <?php if (count($book['tags']) > 3): ?>
                                <span class="inline-block px-2 py-0.5 text-gray-500 text-xs">
                                    +<?php echo count($book['tags']) - 3; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 進捗 -->
                        <?php if ($book['status_id'] == READING_NOW && $book['total_page'] > 0): ?>
                        <div class="mt-2">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span><?php echo $book['progress']; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                                <div class="bg-readnest-primary h-1 rounded-full" style="width: <?php echo $book['progress']; ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 登録日と更新日（常に最下部・一行表示） -->
                    <div class="pt-2 text-xs text-gray-500 border-t flex justify-between items-center">
                        <span>
                            <i class="fas fa-plus-circle mr-1"></i><?php echo isset($book['create_date']) ? date('Y/n/j', strtotime($book['create_date'])) : '不明'; ?>
                        </span>
                        <span>
                            <i class="far fa-clock mr-1"></i><?php echo isset($book['update_date']) ? date('Y/n/j', strtotime($book['update_date'])) : '不明'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- もっと見るボタン（必要に応じて） -->
        <?php if (count($books) >= 20): ?>
        <div class="text-center mt-8">
            <button class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 px-6 py-3">
                さらに読み込む
            </button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php
$d_content = ob_get_clean();

// JavaScriptを追加
ob_start();
?>
<!-- Markdownパーサー -->
<script src="/js/simple-markdown-parser.js"></script>
<script>
// 検索ボックスにフォーカス（特に検索パラメータがある場合）
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($search_word)): ?>
    // 検索結果がある場合は検索ボックスをハイライト
    const searchBox = document.getElementById('bookshelf-search');
    if (searchBox) {
        searchBox.classList.add('ring-2', 'ring-readnest-primary');
        setTimeout(function() {
            searchBox.classList.remove('ring-2', 'ring-readnest-primary');
        }, 2000);
    }
    <?php endif; ?>
    
    // Ctrl+K または Cmd+K で検索ボックスにフォーカス
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchBox = document.getElementById('bookshelf-search');
            if (searchBox) {
                searchBox.focus();
                searchBox.select();
            }
        }
    });

    // action=analyze パラメータがある場合、自動的に分析を開始
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'analyze') {
        // 少し遅延させてページ読み込み完了を待つ
        setTimeout(function() {
            const analyzeBtn = document.getElementById('analyze-trends-btn');
            if (analyzeBtn) {
                analyzeBtn.click();
            }
        }, 500);
    }
});
</script>
<script>
// 読書履歴データを準備
const readingHistory = [
    <?php 
    // 読了済みの本を取得（評価とレビュー付き）
    $finished_books = array_filter($books, function($book) {
        return $book['status_id'] == READING_FINISH || $book['status_id'] == READ_BEFORE;
    });
    
    // インデックスをリセット
    $finished_books = array_values($finished_books);
    
    foreach ($finished_books as $index => $book) {
        if ($index > 0) echo ",\n    ";
        echo json_encode([
            'title' => isset($book['title']) ? $book['title'] : '',
            'author' => isset($book['author']) ? $book['author'] : '',
            'rating' => intval(isset($book['rating']) ? $book['rating'] : 0),
            'review' => mb_substr(isset($book['memo']) ? $book['memo'] : '', 0, 100)
        ]);
    }
    ?>
];

// タグクラウドコンポーネント（現在未使用）
<?php if (false): // 完全に無効化 ?>
function tagCloudComponent() {
    return {
        loading: false, // 初期状態はfalseに変更
        error: null,
        tags: [],
        stats: {},
        totalTags: 0,
        tagMode: localStorage.getItem('bookshelfTagMode') || 'popular', // 保存された状態を復元、デフォルトは人気のタグ
        currentStatus: '<?php echo $status_filter; ?>',
        
        init() {
            // セクションが開いている場合のみタグを読み込む
            if (this.sectionOpen) {
                // ページ読み込み完了後に少し遅延してタグを読み込む（UXの改善）
                setTimeout(() => {
                    this.loadTags();
                }, 100);
            }
            
            // セクションの開閉を監視
            this.$watch('sectionOpen', (value) => {
                if (value && this.tags.length === 0 && !this.loading) {
                    this.loadTags();
                }
            });
        },
        
        async loadTags() {
            this.loading = true;
            this.error = null;
            
            try {
                const params = new URLSearchParams({
                    user: '<?php echo $user_id; ?>',
                    mode: this.tagMode,
                    limit: 30
                });
                
                if (this.currentStatus) {
                    params.append('status', this.currentStatus);
                }
                
                const response = await fetch('/api/user_tags.php?' + params.toString());
                
                if (!response.ok) {
                    throw new Error('タグの読み込みに失敗しました');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    this.tags = data.tags || [];
                    this.stats = data.stats || {};
                    this.totalTags = data.stats ? data.stats.total_tags : 0;
                    this.renderTagCloud();
                } else {
                    throw new Error(data.error || 'エラーが発生しました');
                }
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
        
        renderTagCloud() {
            const container = document.getElementById('tag-cloud-container');
            if (!container || this.tags.length === 0) return;
            
            const maxCount = this.stats.max_count || 1;
            const minCount = this.stats.min_count || 0;
            
            let html = '';
            
            this.tags.forEach(tag => {
                const size = this.getTagSize(tag.tag_count, minCount, maxCount);
                const color = this.getTagColor(tag.tag_count, minCount, maxCount);
                const url = `?search_type=tag&search_word=${encodeURIComponent(tag.tag_name)}${this.currentStatus ? '&status=' + this.currentStatus : ''}<?php echo $user_id_param; ?>`;

                // 現在の検索タグかどうかチェック
                const isCurrentTag = '<?php echo $search_type === "tag" ? html($search_word) : ""; ?>' === tag.tag_name;
                const highlightClass = isCurrentTag ? 'bg-green-100 ring-2 ring-green-500' : '';
                
                html += `
                    <a href="${url}" 
                       class="inline-block px-3 py-1 m-1 rounded-full transition-all hover:bg-green-100 ${color} ${highlightClass}"
                       style="font-size: ${size}px;"
                       title="${this.escapeHtml(tag.tag_name)}（${tag.tag_count}冊）">
                        ${this.escapeHtml(tag.tag_name)}
                        <span class="text-xs opacity-70">(${tag.tag_count})</span>
                    </a>
                `;
            });
            
            container.innerHTML = html;
        },
        
        getTagSize(count, min, max) {
            if (max === min) return 16;
            const minSize = 12;
            const maxSize = 32;
            const ratio = (count - min) / (max - min);
            return Math.round(minSize + (maxSize - minSize) * ratio);
        },
        
        getTagColor(count, min, max) {
            if (max === min) return 'text-gray-600';
            const ratio = (count - min) / (max - min);
            if (ratio > 0.8) return 'text-green-700 font-bold';
            if (ratio > 0.6) return 'text-green-600 font-semibold';
            if (ratio > 0.4) return 'text-emerald-600';
            if (ratio > 0.2) return 'text-emerald-500';
            return 'text-gray-600';
        },
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        // タグモードを設定して保存
        setTagMode(mode) {
            this.tagMode = mode;
            localStorage.setItem('bookshelfTagMode', mode);
            this.loadTags();
        }
    };
}
<?php endif; // tagCloudComponent関数の無効化終了 ?>

</script>
<?php
$d_additional_scripts = ob_get_clean();

?>

<script>
// お気に入りの切り替え
function toggleFavorite(bookId, button) {
    const icon = button.querySelector('i');
    const isFavorite = icon.classList.contains('fas');
    
    // 即座にUIを更新（楽観的更新）
    if (isFavorite) {
        icon.classList.remove('fas');
        icon.classList.add('far');
    } else {
        icon.classList.remove('far');
        icon.classList.add('fas');
    }
    
    // サーバーにリクエスト
    fetch('/ajax/toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'book_id=' + bookId + '&action=toggle'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // エラーの場合は元に戻す
            if (isFavorite) {
                icon.classList.add('fas');
                icon.classList.remove('far');
            } else {
                icon.classList.add('far');
                icon.classList.remove('fas');
            }
            alert(data.message || 'エラーが発生しました');
        }
    })
    .catch(error => {
        // エラーの場合は元に戻す
        if (isFavorite) {
            icon.classList.add('fas');
            icon.classList.remove('far');
        } else {
            icon.classList.add('far');
            icon.classList.remove('fas');
        }
        console.error('Error:', error);
        alert('通信エラーが発生しました');
    });
}

// ソート順の昇順/降順を切り替える関数
function toggleSortOrder() {
    const select = document.getElementById('sort-select');
    const currentValue = select.value;
    let newValue = currentValue;
    
    // 現在の値から基本のソート項目を抽出
    const sortMap = {
        'update_date_desc': 'update_date_asc',
        'update_date_asc': 'update_date_desc',
        'finished_date_desc': 'finished_date_asc',
        'finished_date_asc': 'finished_date_desc',
        'rating_desc': 'rating_asc',
        'rating_asc': 'rating_desc',
        'title_asc': 'title_desc',
        'title_desc': 'title_asc',
        'author_asc': 'author_desc',
        'author_desc': 'author_asc',
        'pages_desc': 'pages_asc',
        'pages_asc': 'pages_desc',
        'created_date_desc': 'created_date_asc',
        'created_date_asc': 'created_date_desc'
    };
    
    if (sortMap[currentValue]) {
        newValue = sortMap[currentValue];
        select.value = newValue;
        select.dispatchEvent(new Event('change'));
    }
}
</script>

<?php
// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>