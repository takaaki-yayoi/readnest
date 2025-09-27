<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// コンテンツを生成
ob_start();

// パンくずリストを表示
if (isset($breadcrumbs)) {
    include(getTemplatePath('components/breadcrumb.php'));
}
?>

<!-- 検索結果ヘッダー -->
<section class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl sm:text-3xl font-bold mb-2">
            <i class="fas fa-search mr-2"></i>検索結果
        </h1>
        <p class="text-lg opacity-90">
            「<?php echo htmlspecialchars($search_query); ?>」の検索結果 
            <?php if ($results['total'] > 0): ?>
                （<?php echo number_format($results['total']); ?>件）
            <?php endif; ?>
        </p>
    </div>
</section>

<!-- 検索フォーム -->
<section class="bg-gray-50 dark:bg-gray-800 py-4 border-b dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="get" action="/search_results.php" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" 
                       name="q" 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       placeholder="本、著者、レビューを検索..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary">
            </div>
            <div class="flex gap-2">
                <select name="type" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary">
                    <option value="all" <?php echo $search_type === 'all' ? 'selected' : ''; ?>>すべて</option>
                    <option value="books" <?php echo $search_type === 'books' ? 'selected' : ''; ?>>本</option>
                    <option value="authors" <?php echo $search_type === 'authors' ? 'selected' : ''; ?>>著者</option>
                    <option value="reviews" <?php echo $search_type === 'reviews' ? 'selected' : ''; ?>>レビュー</option>
                </select>
                <button type="submit" class="px-6 py-2 bg-readnest-primary text-white rounded-lg hover:bg-readnest-accent transition-colors">
                    <i class="fas fa-search mr-2"></i>検索
                </button>
            </div>
        </form>
    </div>
</section>

<!-- 検索結果 -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (strlen($search_query) < 2): ?>
            <!-- 検索キーワードが短い -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-triangle text-3xl text-yellow-500 mb-3"></i>
                <p class="text-gray-700 dark:text-gray-300">検索キーワードは2文字以上入力してください。</p>
            </div>
        <?php elseif ($results['total'] === 0): ?>
            <!-- 検索結果なし -->
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">検索結果が見つかりませんでした</h2>
                <p class="text-gray-500 mb-6">「<?php echo htmlspecialchars($search_query); ?>」に一致する結果はありません。</p>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <p>検索のヒント：</p>
                    <ul class="list-disc list-inside text-left inline-block">
                        <li>キーワードのスペルを確認してください</li>
                        <li>より一般的なキーワードを試してください</li>
                        <li>キーワードの数を減らしてください</li>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <!-- 検索結果表示 -->
            <?php if ($search_type === 'all' || $search_type === 'books'): ?>
                <?php if (!empty($results['books'])): ?>
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">
                            <i class="fas fa-book text-blue-500 mr-2"></i>本
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($results['books'] as $book): ?>
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow p-4 flex space-x-4">
                                    <img src="<?php echo !empty($book['image_url']) ? htmlspecialchars($book['image_url']) : '/img/no-image-book.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                                         class="w-20 h-28 object-cover rounded shadow-sm flex-shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1 line-clamp-2">
                                            <?php 
                                            // タイトルにキーワードをハイライト
                                            $highlighted_title = preg_replace(
                                                '/(' . preg_quote($search_query, '/') . ')/i',
                                                '<mark class="bg-yellow-200">$1</mark>',
                                                htmlspecialchars($book['title'])
                                            );
                                            echo $highlighted_title;
                                            ?>
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            <?php 
                                            // 著者名にキーワードをハイライト
                                            $highlighted_author = preg_replace(
                                                '/(' . preg_quote($search_query, '/') . ')/i',
                                                '<mark class="bg-yellow-200">$1</mark>',
                                                htmlspecialchars($book['author'])
                                            );
                                            echo $highlighted_author;
                                            ?>
                                        </p>
                                        <div class="flex items-center space-x-3 text-sm">
                                            <?php if ($book['rating'] > 0): ?>
                                                <span class="text-yellow-500">
                                                    <?php for ($i = 0; $i < $book['rating']; $i++): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php endfor; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($book['status'] == READING_NOW && $book['total_page'] > 0): ?>
                                                <span class="text-gray-500">
                                                    読書中 <?php echo round(($book['current_page'] / $book['total_page']) * 100); ?>%
                                                </span>
                                            <?php elseif ($book['status'] == READING_FINISH || $book['status'] == READ_BEFORE): ?>
                                                <span class="text-green-600">
                                                    <i class="fas fa-check-circle mr-1"></i>読了
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($book['memo']) && strlen($book['memo']) > 0): ?>
                                            <p class="text-xs text-gray-500 mt-2 line-clamp-2">
                                                <?php 
                                                $excerpt = mb_substr($book['memo'], 0, 100);
                                                $highlighted_excerpt = preg_replace(
                                                    '/(' . preg_quote($search_query, '/') . ')/i',
                                                    '<mark class="bg-yellow-200">$1</mark>',
                                                    htmlspecialchars($excerpt)
                                                );
                                                echo $highlighted_excerpt;
                                                if (mb_strlen($book['memo']) > 100) echo '...';
                                                ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="flex items-center space-x-2 mt-3">
                                            <a href="/book_detail.php?book_id=<?php echo $book['book_id']; ?>" 
                                               class="inline-flex items-center px-3 py-1 bg-readnest-primary text-white text-sm rounded hover:bg-readnest-accent transition-colors">
                                                <i class="fas fa-book-open mr-1"></i>詳細を見る
                                            </a>
                                            <?php if (!empty($book['memo'])): ?>
                                                <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 text-xs rounded">
                                                    <i class="fas fa-comment mr-1"></i>レビューあり
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($search_type === 'authors'): ?>
                <?php if (!empty($results['authors'])): ?>
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">
                            <i class="fas fa-user-pen text-purple-500 mr-2"></i>著者
                        </h2>
                        <div class="space-y-3">
                            <?php foreach ($results['authors'] as $author): ?>
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-lg mb-2">
                                                <a href="/bookshelf.php?search_type=author&search_word=<?php echo urlencode($author['author']); ?>" 
                                                   class="text-readnest-primary hover:text-readnest-accent transition-colors">
                                                    <?php 
                                                    $highlighted_author = preg_replace(
                                                        '/(' . preg_quote($search_query, '/') . ')/i',
                                                        '<mark class="bg-yellow-200">$1</mark>',
                                                        htmlspecialchars($author['author'])
                                                    );
                                                    echo $highlighted_author;
                                                    ?>
                                                </a>
                                            </h3>
                                            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                <span>
                                                    <i class="fas fa-book mr-1"></i>
                                                    <?php echo $author['book_count']; ?>冊
                                                </span>
                                                <?php if ($author['avg_rating']): ?>
                                                    <span class="text-yellow-500">
                                                        <i class="fas fa-star mr-1"></i>
                                                        平均 <?php echo number_format($author['avg_rating'], 1); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($author['book_titles'])): ?>
                                                <div class="text-sm text-gray-500">
                                                    <span class="font-medium">作品：</span>
                                                    <?php echo implode('、', array_map('htmlspecialchars', $author['book_titles'])); ?>
                                                    <?php if ($author['book_count'] > 3): ?>
                                                        <span class="text-gray-400">他</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <a href="/bookshelf.php?search_type=author&search_word=<?php echo urlencode($author['author']); ?>" 
                                           class="px-3 py-1 bg-readnest-primary text-white rounded hover:bg-readnest-accent transition-colors text-sm">
                                            本棚で見る
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($search_type === 'reviews'): ?>
                <?php if (!empty($results['reviews'])): ?>
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">
                            <i class="fas fa-comment text-green-500 mr-2"></i>レビュー
                        </h2>
                        <div class="space-y-4">
                            <?php foreach ($results['reviews'] as $review): ?>
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                    <div class="flex space-x-4">
                                        <img src="<?php echo !empty($review['image_url']) ? htmlspecialchars($review['image_url']) : '/img/no-image-book.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($review['title']); ?>"
                                             class="w-16 h-20 object-cover rounded shadow-sm flex-shrink-0">
                                        <div class="flex-1">
                                            <h3 class="font-semibold mb-1">
                                                <a href="/book_detail.php?id=<?php echo $review['book_id']; ?>" 
                                                   class="text-readnest-primary hover:text-readnest-accent transition-colors">
                                                    <?php echo htmlspecialchars($review['title']); ?>
                                                </a>
                                            </h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2"><?php echo htmlspecialchars($review['author']); ?></p>
                                            <?php if ($review['rating'] > 0): ?>
                                                <div class="text-yellow-500 mb-2">
                                                    <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 p-3 rounded">
                                                <?php 
                                                $highlighted_memo = preg_replace(
                                                    '/(' . preg_quote($search_query, '/') . ')/i',
                                                    '<mark class="bg-yellow-200">$1</mark>',
                                                    nl2br(htmlspecialchars($review['memo']))
                                                );
                                                echo $highlighted_memo;
                                                ?>
                                            </div>
                                            <?php if ($review['finished_date']): ?>
                                                <p class="text-xs text-gray-500 mt-2">
                                                    読了日: <?php echo date('Y年n月j日', strtotime($review['finished_date'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- ページネーション -->
            <?php if ($total_pages > 1): ?>
                <nav class="flex justify-center mt-8">
                    <ul class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="?q=<?php echo urlencode($search_query); ?>&type=<?php echo $search_type; ?>&page=<?php echo $page - 1; ?>" 
                                   class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li>
                                <a href="?q=<?php echo urlencode($search_query); ?>&type=<?php echo $search_type; ?>&page=1" 
                                   class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 transition-colors">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="px-2 py-2">...</li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li>
                                <a href="?q=<?php echo urlencode($search_query); ?>&type=<?php echo $search_type; ?>&page=<?php echo $i; ?>" 
                                   class="px-3 py-2 <?php echo $i === $page ? 'bg-readnest-primary text-white' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600'; ?> rounded hover:bg-readnest-accent hover:text-white transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="px-2 py-2">...</li>
                            <?php endif; ?>
                            <li>
                                <a href="?q=<?php echo urlencode($search_query); ?>&type=<?php echo $search_type; ?>&page=<?php echo $total_pages; ?>" 
                                   class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 transition-colors">
                                    <?php echo $total_pages; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li>
                                <a href="?q=<?php echo urlencode($search_query); ?>&type=<?php echo $search_type; ?>&page=<?php echo $page + 1; ?>" 
                                   class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');