<?php
if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
    die('reference for this file is not allowed.');
}

// t_reviews.php - みんなのレビュー一覧テンプレート
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- ページヘッダー -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">みんなのレビュー</h1>
        
        <!-- 検索とソート -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="get" action="/reviews.php" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-4">
                <!-- 検索 -->
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" 
                               name="q" 
                               value="<?php echo html(isset($search_keyword) ? $search_keyword : ''); ?>"
                               placeholder="本のタイトル、著者、レビューを検索..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- ソート -->
                <div class="sm:w-48">
                    <select name="sort" 
                            onchange="this.form.submit()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                        <option value="recent" <?php echo ($sort_by === 'recent') ? 'selected' : ''; ?>>新着順</option>
                        <option value="rating_high" <?php echo ($sort_by === 'rating_high') ? 'selected' : ''; ?>>評価が高い順</option>
                        <option value="rating_low" <?php echo ($sort_by === 'rating_low') ? 'selected' : ''; ?>>評価が低い順</option>
                    </select>
                </div>
                
                <?php if (!empty($search_keyword)): ?>
                <button type="submit" name="q" value="" class="btn-outline text-sm">
                    <i class="fas fa-times mr-1"></i>クリア
                </button>
                <?php else: ?>
                <button type="submit" class="btn-primary text-sm">
                    <i class="fas fa-search mr-1"></i>検索
                </button>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- 検索結果情報 -->
        <?php if (!empty($search_keyword)): ?>
        <div class="text-sm text-gray-600 mb-4">
            「<?php echo html($search_keyword); ?>」の検索結果: <?php echo number_format($total_count); ?>件
        </div>
        <?php else: ?>
        <div class="text-sm text-gray-600 mb-4">
            全<?php echo number_format($total_count); ?>件のレビュー
        </div>
        <?php endif; ?>
    </div>
    
    <!-- レビュー一覧 -->
    <?php if (empty($formatted_reviews)): ?>
    <div class="bg-white rounded-lg shadow-sm p-8 text-center">
        <i class="fas fa-book-open text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-600">
            <?php if (!empty($search_keyword)): ?>
                検索条件に一致するレビューが見つかりませんでした。
            <?php else: ?>
                まだレビューが投稿されていません。
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="space-y-6">
        <?php foreach ($formatted_reviews as $review): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex flex-col sm:flex-row gap-4">
                <!-- 本の画像 -->
                <div class="sm:w-24 flex-shrink-0">
                    <a href="/book/<?php echo $review['book_id']; ?>">
                        <img src="<?php echo html($review['image_url']); ?>" 
                             alt="<?php echo html($review['book_title']); ?>"
                             class="w-full sm:w-24 h-auto sm:h-36 object-cover rounded shadow-sm hover:shadow-md transition-shadow">
                    </a>
                </div>
                
                <!-- レビュー内容 -->
                <div class="flex-1">
                    <!-- 本の情報 -->
                    <div class="mb-3">
                        <h3 class="text-lg font-semibold mb-1">
                            <a href="/book/<?php echo $review['book_id']; ?>" 
                               class="text-gray-900 hover:text-readnest-primary transition-colors">
                                <?php echo html($review['book_title']); ?>
                            </a>
                        </h3>
                        <p class="text-sm text-gray-600"><?php echo html($review['author']); ?></p>
                    </div>
                    
                    <!-- 評価 -->
                    <?php if ($review['rating'] > 0): ?>
                    <div class="flex items-center mb-3">
                        <div class="flex text-yellow-400">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $review['rating']): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="ml-2 text-sm text-gray-600"><?php echo $review['rating']; ?>/5</span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- レビュー本文 -->
                    <div class="text-gray-700 mb-3 line-clamp-3">
                        <?php echo XSS::nl2brAutoLink($review['comment']); ?>
                    </div>
                    
                    <!-- レビュアー情報 -->
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center">
                            <img src="<?php echo html($review['user_photo']); ?>" 
                                 alt="<?php echo html($review['nickname']); ?>"
                                 class="w-6 h-6 rounded-full mr-2">
                            <a href="/bookshelf.php?user_id=<?php echo $review['user_id']; ?>" 
                               class="text-gray-600 hover:text-readnest-primary transition-colors">
                                <?php echo html($review['nickname']); ?>
                            </a>
                            <?php if (isset($review['user_level'])): ?>
                                <?php echo getLevelBadgeHtml($review['user_level'], 'xs'); ?>
                            <?php endif; ?>
                        </div>
                        <span class="text-gray-500"><?php echo $review['update_date']; ?></span>
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
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search_keyword) ? '&q=' . urlencode($search_keyword) : ''; ?>&sort=<?php echo $sort_by; ?>" 
               class="px-3 py-2 text-sm text-gray-700 bg-white rounded-md hover:bg-gray-50 border">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php else: ?>
            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded-md border cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </span>
            <?php endif; ?>
            
            <!-- ページ番号 -->
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): ?>
                <a href="?page=1<?php echo !empty($search_keyword) ? '&q=' . urlencode($search_keyword) : ''; ?>&sort=<?php echo $sort_by; ?>" 
                   class="px-3 py-2 text-sm text-gray-700 bg-white rounded-md hover:bg-gray-50 border">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="px-2 text-gray-400">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="px-3 py-2 text-sm text-white bg-readnest-primary rounded-md"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search_keyword) ? '&q=' . urlencode($search_keyword) : ''; ?>&sort=<?php echo $sort_by; ?>" 
                       class="px-3 py-2 text-sm text-gray-700 bg-white rounded-md hover:bg-gray-50 border"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="px-2 text-gray-400">...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search_keyword) ? '&q=' . urlencode($search_keyword) : ''; ?>&sort=<?php echo $sort_by; ?>" 
                   class="px-3 py-2 text-sm text-gray-700 bg-white rounded-md hover:bg-gray-50 border"><?php echo $total_pages; ?></a>
            <?php endif; ?>
            
            <!-- 次へ -->
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search_keyword) ? '&q=' . urlencode($search_keyword) : ''; ?>&sort=<?php echo $sort_by; ?>" 
               class="px-3 py-2 text-sm text-gray-700 bg-white rounded-md hover:bg-gray-50 border">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php else: ?>
            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded-md border cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </span>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- スタイル調整 -->
<style>
.line-clamp-3 {
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}
</style>

<?php
$d_content = ob_get_clean();
include(getTemplatePath('t_base.php'));
?>