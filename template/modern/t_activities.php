<?php
if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
    die('reference for this file is not allowed.');
}

// t_activities.php - みんなの読書活動テンプレート
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- ページヘッダー -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">みんなの読書活動</h1>
        
        <!-- 統計情報 -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">今日の活動</p>
                <p class="text-2xl font-bold text-readnest-primary"><?php echo number_format($stats['today']); ?>件</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">過去7日間</p>
                <p class="text-2xl font-bold text-readnest-primary"><?php echo number_format($stats['week']); ?>件</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">過去30日間</p>
                <p class="text-2xl font-bold text-readnest-primary"><?php echo number_format($stats['month']); ?>件</p>
            </div>
        </div>
        
        <!-- フィルター -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <form method="get" action="/activities.php" class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">活動タイプ:</span>
                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="all" 
                               <?php echo ($activity_type === 'all') ? 'checked' : ''; ?>
                               onchange="this.form.submit()"
                               class="text-readnest-primary focus:ring-readnest-primary">
                        <span class="ml-2 text-sm dark:text-gray-300">すべて</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="progress" 
                               <?php echo ($activity_type === 'progress') ? 'checked' : ''; ?>
                               onchange="this.form.submit()"
                               class="text-readnest-primary focus:ring-readnest-primary">
                        <span class="ml-2 text-sm dark:text-gray-300">読書中</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="finished" 
                               <?php echo ($activity_type === 'finished') ? 'checked' : ''; ?>
                               onchange="this.form.submit()"
                               class="text-readnest-primary focus:ring-readnest-primary">
                        <span class="ml-2 text-sm dark:text-gray-300">読了</span>
                    </label>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 活動タイムライン -->
    <?php if (empty($formatted_activities)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 text-center">
        <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-600 dark:text-gray-400">
            まだ読書活動が記録されていません。
        </p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($formatted_activities as $activity): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start space-x-4">
                <!-- ユーザーアイコン -->
                <div class="flex-shrink-0">
                    <a href="/bookshelf.php?user_id=<?php echo $activity['user_id']; ?>">
                        <img src="<?php echo html($activity['user_photo']); ?>" 
                             alt="<?php echo html($activity['user_name']); ?>"
                             class="w-10 h-10 rounded-full object-cover">
                    </a>
                </div>
                
                <!-- 活動内容 -->
                <div class="flex-1 min-w-0">
                    <!-- ユーザー名と活動タイプ -->
                    <div class="flex items-center flex-wrap gap-2 mb-2">
                        <a href="/bookshelf.php?user_id=<?php echo $activity['user_id']; ?>" 
                           class="font-medium text-gray-900 dark:text-gray-100 hover:text-readnest-primary transition-colors">
                            <?php echo html($activity['user_name']); ?>
                        </a>
                        <?php if (isset($activity['user_level'])): ?>
                            <?php echo getLevelBadgeHtml($activity['user_level'], 'xs'); ?>
                        <?php endif; ?>
                        <span class="text-gray-600 dark:text-gray-400">さんが</span>
                        
                        <?php
                        $badge_colors = [
                            'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
                            'yellow' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
                            'green' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                            'gray' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
                        ];
                        $badge_class = isset($badge_colors[$activity['type_color']]) ? $badge_colors[$activity['type_color']] : $badge_colors['gray'];
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badge_class; ?>">
                            <?php echo html($activity['type']); ?>
                        </span>
                        
                        <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo $activity['activity_date']; ?></span>
                    </div>
                    
                    <!-- 本の情報 -->
                    <div class="flex items-start space-x-3">
                        <a href="/book/<?php echo $activity['book_id']; ?>" 
                           class="flex-shrink-0">
                            <img src="<?php echo html($activity['book_image']); ?>" 
                                 alt="<?php echo html($activity['book_title']); ?>"
                                 class="w-12 h-18 object-cover rounded shadow-sm hover:shadow-md transition-shadow">
                        </a>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium truncate dark:text-gray-100">
                                <a href="/book/<?php echo $activity['book_id']; ?>" 
                                   class="text-gray-900 dark:text-gray-100 hover:text-readnest-primary transition-colors">
                                    <?php echo html($activity['book_title']); ?>
                                </a>
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate"><?php echo html($activity['author']); ?></p>
                            
                            <?php if ($activity['page'] > 0): ?>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                <i class="fas fa-bookmark text-gray-400 mr-1"></i>
                                <?php echo number_format($activity['page']); ?>ページまで読了
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($activity['comment'])): ?>
                            <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 rounded p-2">
                                <?php echo nl2br(html($activity['comment'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- ページネーション -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-8 flex justify-center">
        <nav class="flex items-center space-x-2">
            <!-- 前へ -->
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&type=<?php echo $activity_type; ?>"
               class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 border dark:border-gray-600">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php else: ?>
            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-md border dark:border-gray-600 cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </span>
            <?php endif; ?>
            
            <!-- ページ番号 -->
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): ?>
                <a href="?page=1&type=<?php echo $activity_type; ?>"
                   class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 border dark:border-gray-600">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="px-2 text-gray-400 dark:text-gray-500">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="px-3 py-2 text-sm text-white bg-readnest-primary rounded-md"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&type=<?php echo $activity_type; ?>"
                       class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 border dark:border-gray-600"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="px-2 text-gray-400 dark:text-gray-500">...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $total_pages; ?>&type=<?php echo $activity_type; ?>"
                   class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 border dark:border-gray-600"><?php echo $total_pages; ?></a>
            <?php endif; ?>
            
            <!-- 次へ -->
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&type=<?php echo $activity_type; ?>"
               class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 border dark:border-gray-600">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php else: ?>
            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-md border dark:border-gray-600 cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </span>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- リアルタイム更新 (オプション) -->
<script>
// 5分ごとに自動更新（オプション）
// setInterval(() => {
//     window.location.reload();
// }, 300000);
</script>

<?php
$d_content = ob_get_clean();
include(getTemplatePath('t_base.php'));
?>