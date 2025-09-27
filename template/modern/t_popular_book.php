<?php
/**
 * 人気の本一覧テンプレート
 * モダンデザイン版
 */

if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
    die('reference for this file is not allowed.');
}

// 追加のヘッド要素
ob_start();
?>
<style>
    .book-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1.5rem;
    }
    
    @media (min-width: 640px) {
        .book-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
    }
</style>
<?php
$d_additional_head = ob_get_clean();

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-800 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-700 dark:text-gray-300">人気の本</span>
                </li>
            </ol>
        </nav>

        <!-- ヘッダー -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        <i class="fas fa-fire text-orange-500 mr-2"></i>人気の本
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        多くのユーザーが読んでいる本をチェックしよう
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <p class="text-sm text-gray-500">
                        全<?php echo number_format($stats['total_books']); ?>冊中
                        <?php echo number_format($stats['showing_from']); ?>〜<?php echo number_format($stats['showing_to']); ?>冊を表示
                    </p>
                </div>
            </div>
        </div>

        <!-- フィルター -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">期間:</span>
                <div class="flex gap-2">
                    <a href="?period=all" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $period === 'all' ? 'bg-readnest-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                        すべて
                    </a>
                    <a href="?period=month" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $period === 'month' ? 'bg-readnest-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                        今月
                    </a>
                    <a href="?period=year" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $period === 'year' ? 'bg-readnest-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                        今年
                    </a>
                </div>
            </div>
        </div>

        <!-- 本の一覧 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <?php if (!empty($books)): ?>
                <div class="book-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="group">
                            <div class="relative">
                                <?php 
                                // amazon_idがあればbook_entityへ、なければbook_detailへリンク
                                $book_url = !empty($book['amazon_id']) ? '/book_entity/' . urlencode($book['amazon_id']) : '/book/' . $book['book_id'];
                                ?>
                                <a href="<?php echo $book_url; ?>" class="block">
                                    <div class="aspect-[3/4] bg-gray-100 rounded-lg overflow-hidden shadow-sm group-hover:shadow-md transition-shadow">
                                        <img src="<?php echo html(isset($book['image_url']) ? $book['image_url'] : '/img/no-image-book.png'); ?>" 
                                             alt="<?php echo html($book['title']); ?>" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                             onerror="this.src='/img/no-image-book.png'">
                                    </div>
                                    <div class="absolute top-2 right-2 bg-black bg-opacity-70 text-white px-2 py-1 rounded text-xs font-medium">
                                        <i class="fas fa-bookmark mr-1"></i><?php echo $book['bookmark_count']; ?>
                                    </div>
                                </a>
                            </div>
                            <div class="mt-3">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2 group-hover:text-readnest-primary transition-colors">
                                    <a href="<?php echo $book_url; ?>">
                                        <?php echo html($book['title']); ?>
                                    </a>
                                </h3>
                                <?php if (!empty($book['author'])): ?>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-1"><?php echo html($book['author']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($book['avg_rating'])): ?>
                                    <div class="flex items-center mt-2">
                                        <div class="flex">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= round($book['avg_rating'])): ?>
                                                    <i class="fas fa-star text-yellow-400 text-xs"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-gray-300 text-xs"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-xs text-gray-600 dark:text-gray-400 ml-1">(<?php echo number_format($book['avg_rating'], 1); ?>)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ページネーション -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&period=<?php echo urlencode($period); ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-800">
                                    <i class="fas fa-chevron-left mr-1"></i>前へ
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1):
                            ?>
                                <a href="?page=1&period=<?php echo urlencode($period); ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-800">
                                    1
                                </a>
                                <?php if ($start_page > 2): ?>
                                    <span class="px-2 text-gray-500">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="px-3 py-2 text-sm font-medium text-white bg-readnest-primary border border-readnest-primary rounded-md">
                                        <?php echo $i; ?>
                                    </span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&period=<?php echo urlencode($period); ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-800">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="px-2 text-gray-500">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $total_pages; ?>&period=<?php echo urlencode($period); ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-800">
                                    <?php echo $total_pages; ?>
                                </a>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&period=<?php echo urlencode($period); ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-800">
                                    次へ<i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-book text-6xl text-gray-300 mb-4"></i>
                    <p class="text-xl text-gray-600 dark:text-gray-400 mb-2">人気の本が見つかりませんでした</p>
                    <p class="text-gray-500">期間を変更して再度お試しください</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを読み込み
require_once(getTemplatePath('t_base.php'));
?>