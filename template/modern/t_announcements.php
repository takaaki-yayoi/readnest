<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- ヘッダーセクション -->
    <div class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-6">
                <i class="fas fa-bullhorn text-6xl opacity-80"></i>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">お知らせ</h1>
            <p class="text-xl text-white opacity-90">
                ReadNestの最新情報をお届けします
            </p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 mx-2"></i>
                    <span class="text-gray-700 dark:text-gray-300">お知らせ</span>
                </li>
            </ol>
        </nav>

        <!-- タイプフィルター -->
        <div class="mb-8 flex flex-wrap gap-2">
            <?php
            $type_filters = [
                'all' => ['label' => 'すべて', 'icon' => 'list', 'color' => 'gray'],
                'new_feature' => ['label' => '新機能', 'icon' => 'sparkles', 'color' => 'green'],
                'bug_fix' => ['label' => '不具合修正', 'icon' => 'bug', 'color' => 'red'],
                'maintenance' => ['label' => 'メンテナンス', 'icon' => 'wrench', 'color' => 'yellow'],
                'general' => ['label' => '一般', 'icon' => 'info-circle', 'color' => 'blue']
            ];
            
            foreach ($type_filters as $type => $filter): ?>
                <a href="?type=<?php echo $type; ?>" 
                   class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors
                          <?php if ($filter_type === $type || ($filter_type === 'all' && $type === 'all')): ?>
                              bg-<?php echo $filter['color']; ?>-100 text-<?php echo $filter['color']; ?>-700 ring-2 ring-<?php echo $filter['color']; ?>-500
                          <?php else: ?>
                              bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600
                          <?php endif; ?>">
                    <i class="fas fa-<?php echo $filter['icon']; ?> mr-2"></i>
                    <?php echo $filter['label']; ?>
                    <?php if ($type !== 'all' && !empty($announcements_by_type[$type])): ?>
                        <span class="ml-2 bg-<?php echo $filter['color']; ?>-200 text-<?php echo $filter['color']; ?>-700 px-2 py-0.5 rounded-full text-xs">
                            <?php echo count($announcements_by_type[$type]); ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- お知らせリスト -->
        <?php if (empty($announcements_page)): ?>
            <div class="text-center py-16">
                <div class="mb-6">
                    <i class="fas fa-info-circle text-6xl text-gray-300 dark:text-gray-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">お知らせはありません</h3>
                <p class="text-gray-600 dark:text-gray-400">現在表示できるお知らせはありません。</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($announcements_page as $announcement): ?>
                    <?php
                    $created_date = date('Y年n月j日', strtotime($announcement['created']));
                    $is_important = (isset($announcement['important']) ? $announcement['important'] : 0) == 1;
                    ?>
                    
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <!-- ヘッダー部分 -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <time class="text-sm text-gray-500 dark:text-gray-400" datetime="<?php echo $announcement['created']; ?>">
                                        <?php echo html($created_date); ?>
                                    </time>
                                    <?php
                                    // タイプバッジ
                                    $type = $announcement['type'] ?? 'general';
                                    $type_badges = [
                                        'general' => ['class' => 'bg-blue-100 text-blue-700', 'icon' => 'info-circle', 'label' => '一般'],
                                        'new_feature' => ['class' => 'bg-green-100 text-green-700', 'icon' => 'sparkles', 'label' => '新機能'],
                                        'bug_fix' => ['class' => 'bg-red-100 text-red-700', 'icon' => 'bug', 'label' => '不具合修正'],
                                        'maintenance' => ['class' => 'bg-yellow-100 text-yellow-700', 'icon' => 'wrench', 'label' => 'メンテナンス']
                                    ];
                                    $badge = $type_badges[$type] ?? $type_badges['general'];
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo $badge['class']; ?>">
                                        <i class="fas fa-<?php echo $badge['icon']; ?> mr-1"></i>
                                        <?php echo $badge['label']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- タイトル -->
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                                <?php echo html($announcement['title']); ?>
                            </h2>
                            
                            <!-- 内容 -->
                            <?php if (!empty($announcement['content'])): ?>
                                <div class="prose prose-gray max-w-none">
                                    <?php echo nl2br(html($announcement['content'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- フッター -->
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                    <div>
                                        投稿者: <?php echo html(isset($announcement['author']) ? $announcement['author'] : 'ReadNest運営'); ?>
                                    </div>
                                    <?php if (!empty($announcement['updated']) && $announcement['updated'] !== $announcement['created']): ?>
                                        <div>
                                            更新: <?php echo date('Y年n月j日', strtotime($announcement['updated'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- ページネーション -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-12 flex justify-center">
                    <nav class="flex items-center space-x-2" aria-label="ページネーション">
                        <?php 
                        $type_param = ($filter_type !== 'all') ? '&type=' . urlencode($filter_type) : '';
                        ?>
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $type_param; ?>" 
                               class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        ?>
                        
                        <?php if ($start_page > 1): ?>
                            <a href="?page=1<?php echo $type_param; ?>" 
                               class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="px-3 py-2 text-gray-500 dark:text-gray-400">…</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="px-3 py-2 rounded-md bg-readnest-primary text-white font-medium"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo $type_param; ?>" 
                                   class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="px-3 py-2 text-gray-500 dark:text-gray-400">…</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?><?php echo $type_param; ?>" 
                               class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $type_param; ?>" 
                               class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>