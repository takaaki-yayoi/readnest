<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<!-- ヘッダーセクション -->
<section class="bg-gradient-to-r from-indigo-500 to-purple-600 dark:from-gray-800 dark:to-gray-700 text-white py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    <i class="fas fa-pen-to-square mr-3"></i>
                    マイレビュー
                </h1>
                <p class="text-lg text-white opacity-90">
                    <?php if (isset($viewing_other_user) && $viewing_other_user && isset($target_user_info)): ?>
                        <span class="bg-yellow-500 text-black px-2 py-1 rounded text-sm mr-2">管理者モード</span>
                        ユーザーID: <?php echo $user_id; ?> (<?php echo html($target_user_info['nickname']); ?>) のレビュー一覧
                    <?php else: ?>
                        あなたが書いたレビューの一覧
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 統計セクション -->
<section class="py-6 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- レビュー総数 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="text-4xl mb-3">
                    <i class="fas fa-comments text-indigo-500"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">レビュー総数</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($stats['total_reviews']); ?>件</p>
            </div>
            
            <!-- 平均評価 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="text-4xl mb-3">
                    <i class="fas fa-star text-yellow-500"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">平均評価</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    <?php echo $stats['avg_rating']; ?>
                    <span class="text-sm text-gray-600 dark:text-gray-400">/ 5.0</span>
                </p>
            </div>
            
            <!-- 評価分布 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    <i class="fas fa-chart-bar text-green-500 mr-2"></i>
                    評価分布
                </h3>
                <?php 
                $total_ratings = array_sum($stats['rating_distribution']);
                if ($total_ratings > 0): 
                ?>
                <div class="space-y-2">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <?php 
                        $count = $stats['rating_distribution'][$i];
                        $percentage = ($count / $total_ratings) * 100;
                    ?>
                    <div class="flex items-center text-sm">
                        <span class="w-12 text-right mr-2"><?php echo $i; ?>★</span>
                        <div class="flex-1 bg-gray-200 rounded-full h-4 mr-2">
                            <div class="bg-yellow-400 h-4 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <span class="text-gray-600 dark:text-gray-400 w-12"><?php echo $count; ?>件</span>
                    </div>
                    <?php endfor; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-500 dark:text-gray-400 text-sm">まだ評価がありません</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- レビュー一覧セクション -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if (false && isset($is_admin) && $is_admin): ?>
        <!-- 管理者用ユーザー切り替えフォーム（非表示） -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
            <form method="get" class="flex items-center space-x-4">
                <label class="text-sm font-medium text-blue-900">ユーザーID切り替え:</label>
                <input type="number" name="user_id" value="<?php echo $user_id; ?>" 
                       class="px-3 py-1 border border-blue-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" 
                        class="px-4 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                    表示
                </button>
                <?php if ($viewing_other_user): ?>
                <a href="/my_reviews.php" 
                   class="px-4 py-1 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">
                    自分のレビューに戻る
                </a>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- ソート -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                レビュー一覧
            </h2>
            <div class="flex items-center space-x-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">並び替え:</label>
                <select onchange="location.href='?<?php echo isset($base_query) ? $base_query : ''; ?>sort=' + this.value + '&order=<?php echo $order; ?>'" 
                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800 dark:text-white">
                    <option value="update_date" <?php echo $sort === 'update_date' ? 'selected' : ''; ?>>更新日</option>
                    <option value="finished_date" <?php echo $sort === 'finished_date' ? 'selected' : ''; ?>>読了日</option>
                    <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>評価</option>
                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>タイトル</option>
                </select>
                <select onchange="location.href='?<?php echo isset($base_query) ? $base_query : ''; ?>sort=<?php echo $sort; ?>&order=' + this.value" 
                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800 dark:text-white">
                    <option value="desc" <?php echo $order === 'desc' ? 'selected' : ''; ?>>降順</option>
                    <option value="asc" <?php echo $order === 'asc' ? 'selected' : ''; ?>>昇順</option>
                </select>
            </div>
        </div>
        
        <?php if (empty($reviews)): ?>
        <!-- レビューがない場合 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
            <i class="fas fa-pen-to-square text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400 mb-4">まだレビューがありません</p>
            <a href="/bookshelf.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
                <i class="fas fa-book mr-2"></i>
                本棚へ
            </a>
        </div>
        <?php else: ?>
        <!-- レビューリスト -->
        <div class="space-y-4">
            <?php foreach ($reviews as $review): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex gap-4">
                        <!-- 本の画像 -->
                        <div class="flex-shrink-0">
                            <a href="/book/<?php echo $review['book_id']; ?>">
                                <img src="<?php echo html($review['image_url'] ?: '/img/no-image-book.png'); ?>" 
                                     alt="<?php echo html($review['name']); ?>"
                                     class="w-20 h-28 object-cover rounded shadow-sm hover:shadow-md transition-shadow">
                            </a>
                        </div>
                        
                        <!-- レビュー内容 -->
                        <div class="flex-1">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                        <a href="/book/<?php echo $review['book_id']; ?>" 
                                           class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                            <?php echo html($review['name']); ?>
                                        </a>
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <?php echo html($review['author']); ?>
                                    </p>
                                </div>
                                
                                <!-- 評価 -->
                                <?php if ($review['rating'] > 0): ?>
                                <div class="flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['rating']): ?>
                                            <i class="fas fa-star text-yellow-400"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-gray-300"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- メモ -->
                            <?php if (!empty($review['memo'])): ?>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded p-3 mb-3">
                                <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo XSS::nl2brAutoLink($review['memo']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- メタ情報 -->
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 space-x-4">
                                <?php if ($review['status'] == READING_FINISH && !empty($review['finished_date'])): ?>
                                <span>
                                    <i class="fas fa-calendar-check mr-1"></i>
                                    読了日: <?php echo date('Y年n月j日', strtotime($review['finished_date'])); ?>
                                </span>
                                <?php endif; ?>
                                
                                <span>
                                    <i class="fas fa-clock mr-1"></i>
                                    更新: <?php echo date('Y年n月j日', strtotime($review['update_date'])); ?>
                                </span>
                                
                                <?php if (!empty($review['total_page'])): ?>
                                <span>
                                    <i class="fas fa-book-open mr-1"></i>
                                    <?php echo $review['total_page']; ?>ページ
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- アクション -->
                            <div class="mt-3 flex items-center space-x-3">
                                <!-- いいね数表示（自分のレビューなのでボタンではなく表示のみ） -->
                                <?php if (isset($review['like_count']) && $review['like_count'] > 0): ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-heart text-red-500"></i>
                                    <span><?php echo number_format($review['like_count']); ?></span>
                                </span>
                                <?php endif; ?>

                                <a href="/book/<?php echo $review['book_id']; ?>"
                                   class="text-sm text-indigo-600 hover:text-indigo-800 transition-colors">
                                    <i class="fas fa-eye mr-1"></i>詳細を見る
                                </a>
                                <a href="/book/<?php echo $review['book_id']; ?>#review-section"
                                   class="text-sm text-green-600 hover:text-green-800 transition-colors">
                                    <i class="fas fa-edit mr-1"></i>レビューを編集
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- ページネーション -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex justify-center">
            <nav class="inline-flex rounded-md shadow">
                <?php if ($page > 1): ?>
                <a href="?<?php echo isset($base_query) ? $base_query : ''; ?>page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" 
                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:bg-gray-800">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if ($start > 1): ?>
                    <a href="?<?php echo isset($base_query) ? $base_query : ''; ?>page=1&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800">
                        1
                    </a>
                    <?php if ($start > 2): ?>
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300">
                        ...
                    </span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?php echo isset($base_query) ? $base_query : ''; ?>page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 <?php echo $i === $page ? 'bg-indigo-50 text-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800'; ?> text-sm font-medium">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?>
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300">
                        ...
                    </span>
                    <?php endif; ?>
                    <a href="?<?php echo isset($base_query) ? $base_query : ''; ?>page=<?php echo $total_pages; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?<?php echo isset($base_query) ? $base_query : ''; ?>page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" 
                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:bg-gray-800">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを読み込み
include(getTemplatePath('t_base.php'));
?>