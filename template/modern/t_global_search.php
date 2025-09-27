<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// ブレッドクラムの設定
$breadcrumbs = [
    ['label' => 'ホーム', 'url' => '/']
];

if (!empty($keyword)) {
    $breadcrumbs[] = ['label' => 'グローバル検索', 'url' => '/global_search.php'];
    $breadcrumbs[] = ['label' => '「' . htmlspecialchars($keyword) . '」の検索結果', 'url' => null];
} else {
    $breadcrumbs[] = ['label' => 'グローバル検索', 'url' => null];
}

// メインコンテンツを生成
ob_start();

// ブレッドクラムを表示
include(getTemplatePath('components/breadcrumb.php'));
?>

<!-- ヘッダーセクション -->
<section class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-4 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-1 sm:mb-2">
                    <i class="fas fa-globe mr-2 sm:mr-3 text-lg sm:text-2xl"></i>
                    グローバル検索
                    <a href="/help.php#global-search" class="ml-3 text-base text-white opacity-75 hover:opacity-100 transition-opacity" title="グローバル検索のヘルプ">
                        <i class="fas fa-question-circle"></i>
                    </a>
                </h1>
                <p class="text-sm sm:text-lg md:text-xl text-white opacity-90 hidden sm:block">
                    ReadNest全体から書籍・レビューを探す
                </p>
            </div>
        </div>
    </div>
</section>

<!-- フィルター・ソートセクション -->
<section class="bg-gray-50 dark:bg-gray-800 py-4 sm:py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 検索ボックス -->
        <div class="mb-4">
            <form method="get" action="/global_search.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <!-- モバイル：縦並びレイアウト -->
                <div class="sm:hidden space-y-3">
                    <!-- 検索ボックス -->
                    <div class="relative">
                        <input type="text" 
                               id="global-search"
                               name="q" 
                               value="<?php echo htmlspecialchars($keyword ?? ''); ?>" 
                               placeholder="タイトル、著者名、レビューで検索..."
                               class="w-full px-4 py-3 pl-11 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent dark:bg-gray-800 dark:text-white">
                        <i class="fas fa-search absolute left-3 top-3.5 text-gray-400 dark:text-gray-500"></i>
                    </div>
                    
                    <!-- 検索タイプとボタン -->
                    <div class="flex gap-2">
                        <select name="type" class="flex-1 px-3 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary bg-white dark:bg-gray-800">
                            <option value="">すべて</option>
                            <option value="title" <?php echo $search_type === 'title' ? 'selected' : ''; ?>>タイトル</option>
                            <option value="author" <?php echo $search_type === 'author' ? 'selected' : ''; ?>>著者</option>
                            <option value="isbn" <?php echo $search_type === 'isbn' ? 'selected' : ''; ?>>ISBN</option>
                            <option value="review" <?php echo $search_type === 'review' ? 'selected' : ''; ?>>レビュー</option>
                        </select>
                        <button type="submit" class="px-6 py-3 text-base bg-readnest-primary text-white rounded-lg hover:bg-readnest-accent transition-colors whitespace-nowrap font-semibold">
                            <i class="fas fa-search mr-2"></i>検索
                        </button>
                    </div>
                </div>
                
                <!-- デスクトップ：横並びレイアウト -->
                <div class="hidden sm:flex sm:gap-2">
                    <div class="flex-1 relative">
                        <input type="text" 
                               id="global-search-desktop"
                               name="q" 
                               value="<?php echo htmlspecialchars($keyword ?? ''); ?>" 
                               placeholder="タイトル、著者名、レビューで検索..."
                               class="w-full px-4 py-2 pl-10 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent dark:bg-gray-800 dark:text-white">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 dark:text-gray-500"></i>
                    </div>
                    <select name="type" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary dark:bg-gray-800 dark:text-white">
                        <option value="">すべて</option>
                        <option value="title" <?php echo $search_type === 'title' ? 'selected' : ''; ?>>タイトル</option>
                        <option value="author" <?php echo $search_type === 'author' ? 'selected' : ''; ?>>著者</option>
                        <option value="isbn" <?php echo $search_type === 'isbn' ? 'selected' : ''; ?>>ISBN</option>
                        <option value="review" <?php echo $search_type === 'review' ? 'selected' : ''; ?>>レビュー</option>
                    </select>
                    <button type="submit" class="px-4 py-2 text-sm bg-readnest-primary text-white rounded-lg hover:bg-readnest-accent transition-colors whitespace-nowrap">
                        <i class="fas fa-search mr-1"></i>検索
                    </button>
                </div>
                
                <?php if (!empty($sort) && $sort !== 'relevance'): ?>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <?php endif; ?>
            </form>
        </div>

        <!-- 検索結果ヘッダーとソート -->
        <?php if ($search_performed && !empty($search_results['books'])): ?>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-700 dark:text-gray-300">
                    検索結果: <span class="text-readnest-primary"><?php echo number_format($search_results['total'] ?? 0); ?></span>件
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-500 sm:hidden">並び替え:</span>
                <div class="relative flex-1 sm:flex-initial">
                    <select id="sort-select" 
                            onchange="location.href='?q=<?php echo urlencode($keyword); ?>&type=<?php echo urlencode($search_type); ?>&sort=' + this.value"
                            class="appearance-none w-full sm:w-auto px-3 sm:px-4 py-2 sm:py-2.5 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent transition-all duration-200 shadow-sm cursor-pointer">
                        <optgroup label="🔍 関連度">
                            <option value="relevance" <?php echo ($sort === 'relevance' || empty($sort)) ? 'selected' : ''; ?>>関連度順</option>
                        </optgroup>
                        <optgroup label="👥 人気度">
                            <option value="readers_desc" <?php echo $sort === 'readers_desc' ? 'selected' : ''; ?>>読者数（多い→少ない）</option>
                            <option value="readers_asc" <?php echo $sort === 'readers_asc' ? 'selected' : ''; ?>>読者数（少ない→多い）</option>
                        </optgroup>
                        <optgroup label="⭐ 評価で並べ替え">
                            <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>評価（高い→低い）</option>
                            <option value="rating_asc" <?php echo $sort === 'rating_asc' ? 'selected' : ''; ?>>評価（低い→高い）</option>
                        </optgroup>
                        <optgroup label="📖 タイトル・著者で並べ替え">
                            <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>タイトル（あ→ん）</option>
                            <option value="title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>タイトル（ん→あ）</option>
                            <option value="author_asc" <?php echo $sort === 'author_asc' ? 'selected' : ''; ?>>著者名（あ→ん）</option>
                            <option value="author_desc" <?php echo $sort === 'author_desc' ? 'selected' : ''; ?>>著者名（ん→あ）</option>
                        </optgroup>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- 検索結果 -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!$search_performed || empty($keyword)): ?>
            <!-- 初期状態 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                <div class="text-center py-12">
                    <i class="fas fa-book-open text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400 dark:text-gray-500 text-xl mb-2">検索キーワードを入力してください</p>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-500">タイトル、著者名、ISBNで書籍を検索できます</p>
                </div>
            </div>
        <?php elseif (empty($search_results['books'])): ?>
            <!-- 検索結果なし -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                <div class="text-center py-12">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400 dark:text-gray-500 text-xl mb-2">「<?php echo htmlspecialchars($keyword); ?>」に一致する書籍が見つかりませんでした</p>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-500">別のキーワードで検索してみてください</p>
                </div>
            </div>
        <?php else: ?>
            <!-- レビュー検索結果 -->
            <?php if (!empty($search_results['reviews'])): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center">
                        <i class="fas fa-comment-dots mr-2 text-orange-500"></i>
                        レビュー検索結果 (<?php echo count($search_results['reviews']); ?>件)
                    </h3>
                    <div class="grid gap-4">
                        <?php foreach ($search_results['reviews'] as $review): ?>
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700 p-3 sm:p-4">
                                <div class="flex items-start gap-3 sm:gap-4">
                                    <?php if (!empty($review['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($review['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($review['title']); ?>"
                                             class="w-12 sm:w-16 h-18 sm:h-24 object-cover rounded shadow-sm flex-shrink-0">
                                    <?php else: ?>
                                        <div class="w-12 sm:w-16 h-18 sm:h-24 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-book text-gray-400 dark:text-gray-500 text-base sm:text-xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="mb-2">
                                            <h4 class="text-sm sm:text-base font-semibold text-gray-800 dark:text-gray-200 line-clamp-1">
                                                <?php if (!empty($review['isbn'])): ?>
                                                    <a href="/book_entity.php?isbn=<?php echo urlencode($review['isbn']); ?>" 
                                                       class="hover:text-readnest-primary transition-colors">
                                                        <?php echo htmlspecialchars($review['title']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($review['title']); ?>
                                                <?php endif; ?>
                                            </h4>
                                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 dark:text-gray-500 line-clamp-1"><?php echo htmlspecialchars($review['author']); ?></p>
                                        </div>
                                        
                                        <?php if ($review['rating'] > 0): ?>
                                            <div class="flex items-center mb-1 sm:mb-2">
                                                <span class="text-yellow-400 text-xs sm:text-sm">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $review['rating']): ?>
                                                            <i class="fas fa-star"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <p class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 mb-2 line-clamp-2 sm:line-clamp-3">
                                            <?php 
                                                $review_excerpt = mb_substr($review['review_text'], 0, 200);
                                                if (mb_strlen($review['review_text']) > 200) {
                                                    $review_excerpt .= '...';
                                                }
                                                // キーワードをハイライト
                                                $review_excerpt = str_replace(
                                                    $keyword, 
                                                    '<mark class="bg-yellow-200">' . htmlspecialchars($keyword) . '</mark>', 
                                                    htmlspecialchars($review_excerpt)
                                                );
                                                echo $review_excerpt;
                                            ?>
                                        </p>
                                        
                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                            <span>
                                                <i class="fas fa-user mr-1"></i>
                                                <?php echo htmlspecialchars($review['reviewer_name']); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?php echo date('Y年m月d日', strtotime($review['update_date'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if (!empty($search_results['books'])): ?>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center">
                        <i class="fas fa-book mr-2 text-blue-500"></i>
                        書籍検索結果 (<?php echo $search_results['total']; ?>件)
                    </h3>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- 書籍検索結果 -->
            <div class="grid gap-4">
                <?php foreach ($search_results['books'] as $book): ?>
                    <?php 
                        // GROUP_CONCATの結果から最初の値を取得
                        $first_isbn = !empty($book['isbn']) ? explode(',', $book['isbn'])[0] : '';
                        $first_asin = !empty($book['asin']) ? explode(',', $book['asin'])[0] : '';
                    ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
                        <div class="p-4 sm:p-6">
                            <div class="flex">
                                <!-- 本の画像 -->
                                <div class="mr-3 sm:mr-4 flex-shrink-0">
                                    <?php if (!empty($book['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($book['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                                             class="w-16 sm:w-24 h-auto max-h-24 sm:max-h-36 object-cover rounded shadow-sm"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="w-16 sm:w-24 h-24 sm:h-36 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center" style="display:none;">
                                            <i class="fas fa-book text-gray-400 dark:text-gray-500 text-xl sm:text-3xl"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-16 sm:w-24 h-24 sm:h-36 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                            <i class="fas fa-book text-gray-400 dark:text-gray-500 text-xl sm:text-3xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- 本の情報 -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1 line-clamp-2">
                                        <?php if (!empty($first_isbn)): ?>
                                            <a href="/book_entity.php?isbn=<?php echo urlencode($first_isbn); ?>" 
                                               class="hover:text-readnest-primary transition-colors">
                                                <?php echo htmlspecialchars($book['title']); ?>
                                            </a>
                                        <?php elseif (!empty($first_asin)): ?>
                                            <a href="/book_entity.php?asin=<?php echo urlencode($first_asin); ?>" 
                                               class="hover:text-readnest-primary transition-colors">
                                                <?php echo htmlspecialchars($book['title']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span><?php echo htmlspecialchars($book['title']); ?></span>
                                        <?php endif; ?>
                                    </h3>
                                    
                                    <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 dark:text-gray-500 mb-2 sm:mb-3 line-clamp-1">
                                        <?php echo htmlspecialchars($book['author']); ?>
                                    </p>
                                    
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-4 mb-2 sm:mb-3">
                                        <!-- 評価 -->
                                        <?php if ($book['avg_rating'] > 0): ?>
                                            <div class="flex items-center">
                                                <span class="text-yellow-400 text-sm">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= round($book['avg_rating'])): ?>
                                                            <i class="fas fa-star"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </span>
                                                <span class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 dark:text-gray-500 ml-1">
                                                    (<?php echo number_format($book['avg_rating'], 1); ?>)
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- 読者情報 -->
                                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 dark:text-gray-500">
                                            <i class="fas fa-users mr-1 text-xs"></i>
                                            <?php echo $book['reader_count']; ?>人
                                        </p>
                                    </div>
                                    
                                    <!-- ステータス集計 -->
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <?php if ($book['finished_count'] > 0): ?>
                                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                                読了: <?php echo $book['finished_count']; ?>人
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($book['reading_count'] > 0): ?>
                                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                                読んでる: <?php echo $book['reading_count']; ?>人
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($book['want_count'] > 0): ?>
                                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                                読みたい: <?php echo $book['want_count']; ?>人
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- レビュー抜粋 -->
                                    <?php if (!empty($book['review_excerpts'])): ?>
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-500 mb-1">
                                                <i class="fas fa-comment-dots mr-1"></i>レビュー抜粋:
                                            </p>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 italic line-clamp-2">
                                                <?php 
                                                    $excerpt = mb_substr($book['review_excerpts'], 0, 150);
                                                    if (mb_strlen($book['review_excerpts']) > 150) {
                                                        $excerpt .= '...';
                                                    }
                                                    echo htmlspecialchars($excerpt); 
                                                ?>
                                            </p>
                                            <?php if ($book['review_count'] > 0): ?>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 mt-1">
                                                    （<?php echo $book['review_count']; ?>件のレビュー）
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- アクション -->
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                            <?php if (!empty($first_isbn)): ?>
                                                ISBN: <?php echo htmlspecialchars($first_isbn); ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($login_flag): ?>
                                            <button class="px-4 py-2 text-sm bg-readnest-primary text-white rounded-lg hover:bg-readnest-accent transition-colors add-to-bookshelf"
                                                    data-isbn="<?php echo htmlspecialchars($first_isbn); ?>"
                                                    data-asin="<?php echo htmlspecialchars($first_asin); ?>"
                                                    data-title="<?php echo htmlspecialchars($book['title']); ?>"
                                                    data-author="<?php echo htmlspecialchars($book['author']); ?>"
                                                    data-image="<?php echo htmlspecialchars($book['image_url'] ?? ''); ?>"
                                                    data-pages="<?php echo htmlspecialchars($book['total_page'] ?? ''); ?>">
                                                <i class="fas fa-plus mr-1"></i>本棚に追加
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ページネーション -->
            <?php if ($search_results['total_pages'] > 1): ?>
                <nav class="mt-8">
                    <ul class="flex justify-center space-x-2">
                        <!-- 前へ -->
                        <li>
                            <a class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded <?php echo $page <= 1 ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>" 
                               <?php if ($page > 1): ?>
                               href="?q=<?php echo urlencode($keyword); ?>&type=<?php echo urlencode($search_type); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page - 1; ?>"
                               <?php endif; ?>>
                                前へ
                            </a>
                        </li>
                        
                        <!-- ページ番号 -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($search_results['total_pages'], $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li>
                                <a class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700" 
                                   href="?q=<?php echo urlencode($keyword); ?>&type=<?php echo urlencode($search_type); ?>&sort=<?php echo urlencode($sort); ?>&page=1">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li><span class="px-3 py-2">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                            <li>
                                <a class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded <?php echo $p == $page ? 'bg-readnest-primary text-white' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>" 
                                   href="?q=<?php echo urlencode($keyword); ?>&type=<?php echo urlencode($search_type); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $p; ?>">
                                    <?php echo $p; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $search_results['total_pages']): ?>
                            <?php if ($end_page < $search_results['total_pages'] - 1): ?>
                                <li><span class="px-3 py-2">...</span></li>
                            <?php endif; ?>
                            <li>
                                <a class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700" 
                                   href="?q=<?php echo urlencode($keyword); ?>&type=<?php echo urlencode($search_type); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $search_results['total_pages']; ?>">
                                    <?php echo $search_results['total_pages']; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- 次へ -->
                        <li>
                            <a class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded <?php echo $page >= $search_results['total_pages'] ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>" 
                               <?php if ($page < $search_results['total_pages']): ?>
                               href="?q=<?php echo urlencode($keyword); ?>&type=<?php echo urlencode($search_type); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page + 1; ?>"
                               <?php endif; ?>>
                                次へ
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 本棚に追加ボタンの処理
    document.querySelectorAll('.add-to-bookshelf').forEach(button => {
        button.addEventListener('click', function() {
            const isbn = this.dataset.isbn;
            const asin = this.dataset.asin;
            const title = this.dataset.title;
            const author = this.dataset.author;
            const image = this.dataset.image;
            const pages = this.dataset.pages;
            
            // 本棚追加ページへ遷移（パラメータ付き）
            const params = new URLSearchParams({
                isbn: isbn || '',
                asin: asin || '',
                title: title || '',
                author: author || '',
                image_url: image || '',
                total_page: pages || '',
                from: 'global_search'
            });
            
            window.location.href = '/add_book.php?' + params.toString();
        });
    });
});
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>