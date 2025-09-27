<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 min-h-screen">
    <!-- ヘッダーセクション -->
    <div class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-6">
                <i class="fas fa-search text-6xl opacity-80"></i>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">レビュー検索</h1>
            <p class="text-xl text-white opacity-90">
                他の読者の感想や評価を検索して参考にしよう
            </p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-700">レビュー検索</span>
                </li>
            </ol>
        </nav>

        <!-- 検索フォーム -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="get" action="/search_review.php">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <label for="keyword" class="sr-only">検索キーワード</label>
                        <input type="text" 
                               name="keyword" 
                               id="keyword"
                               value="<?php echo html($keyword); ?>"
                               placeholder="レビューや本のタイトル、著者名で検索..."
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400">
                    </div>
                    <button type="submit" 
                            class="px-8 py-3 bg-readnest-primary text-white rounded-lg hover:bg-readnest-accent transition-colors font-medium">
                        <i class="fas fa-search mr-2"></i>検索
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($keyword)): ?>
            <!-- 検索結果 -->
            <div class="mb-8">
                <?php if ($total_results > 0): ?>
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">
                            「<?php echo html($keyword); ?>」の検索結果
                        </h2>
                        <div class="text-sm text-gray-600">
                            <?php echo number_format($total_results); ?>件中 <?php echo $start_num; ?>〜<?php echo $end_num; ?>件目
                        </div>
                    </div>

                    <!-- 検索結果リスト -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <?php foreach ($search_results as $result): ?>
                            <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                                <div class="aspect-w-3 aspect-h-4 bg-gray-200">
                                    <img src="<?php echo html($result['image_url'] ?: '/img/no-image-book.png'); ?>" 
                                         alt="<?php echo html($result['name']); ?>"
                                         class="w-full h-48 object-cover"
                                         onerror="this.src='/img/no-image-book.png'">
                                </div>
                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                        <a href="<?php echo html($result['detail_url']); ?>" 
                                           class="hover:text-readnest-primary transition-colors">
                                            <?php echo html($result['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="flex items-center text-sm text-gray-600 mb-3">
                                        <i class="fas fa-user mr-1"></i>
                                        <a href="/profile.php?user_id=<?php echo html($result['user_id']); ?>" 
                                           class="hover:text-readnest-primary">
                                            <?php echo html($result['user_nickname']); ?>さん
                                        </a>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php echo html($result['formatted_date']); ?>
                                    </div>
                                    
                                    <div class="text-gray-700 text-sm line-clamp-3">
                                        <?php echo XSS::nl2brAutoLink($result['short_memo']); ?>
                                    </div>
                                    
                                    <div class="mt-4 pt-3 border-t border-gray-100">
                                        <a href="<?php echo html($result['detail_url']); ?>" 
                                           class="text-readnest-primary hover:text-readnest-accent font-medium text-sm">
                                            詳細を見る <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- ページネーション -->
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center">
                            <nav class="flex items-center space-x-2" aria-label="ページネーション">
                                <?php if ($page > 1): ?>
                                    <a href="?keyword=<?php echo urlencode($keyword); ?>&page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-2 rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                ?>
                                
                                <?php if ($start_page > 1): ?>
                                    <a href="?keyword=<?php echo urlencode($keyword); ?>&page=1" 
                                       class="px-3 py-2 rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">1</a>
                                    <?php if ($start_page > 2): ?>
                                        <span class="px-3 py-2 text-gray-500">…</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="px-3 py-2 rounded-md bg-readnest-primary text-white font-medium"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?keyword=<?php echo urlencode($keyword); ?>&page=<?php echo $i; ?>" 
                                           class="px-3 py-2 rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span class="px-3 py-2 text-gray-500">…</span>
                                    <?php endif; ?>
                                    <a href="?keyword=<?php echo urlencode($keyword); ?>&page=<?php echo $total_pages; ?>" 
                                       class="px-3 py-2 rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors"><?php echo $total_pages; ?></a>
                                <?php endif; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?keyword=<?php echo urlencode($keyword); ?>&page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-2 rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- 検索結果なし -->
                    <div class="text-center py-16">
                        <div class="mb-6">
                            <i class="fas fa-search text-6xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">検索結果が見つかりません</h3>
                        <p class="text-gray-600 mb-6">
                            「<?php echo html($keyword); ?>」に一致するレビューが見つかりませんでした。<br>
                            別のキーワードで検索してみてください。
                        </p>
                        <div class="space-y-2 text-sm text-gray-500">
                            <p>検索のコツ：</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>キーワードを短くしてみる</li>
                                <li>本のタイトルや著者名で検索する</li>
                                <li>ひらがなやカタカナで検索する</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- 初期画面（人気のレビューとタグクラウド） -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- 人気のレビュー -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-readnest-primary text-white">
                            <h2 class="text-xl font-bold flex items-center">
                                <i class="fas fa-fire mr-2"></i>
                                人気のレビュー
                            </h2>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($popular_reviews)): ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <?php foreach ($popular_reviews as $review): ?>
                                        <article class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div class="flex space-x-4">
                                                <div class="flex-shrink-0">
                                                    <img src="<?php echo html($review['image_url']); ?>" 
                                                         alt="<?php echo html($review['name']); ?>"
                                                         class="w-16 h-20 object-cover rounded"
                                                         onerror="this.src='/img/no-image-book.png'">
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">
                                                        <a href="<?php echo html($review['detail_url']); ?>" 
                                                           class="hover:text-readnest-primary transition-colors">
                                                            <?php echo html($review['name']); ?>
                                                        </a>
                                                    </h3>
                                                    <div class="text-xs text-gray-600 mb-2">
                                                        by <a href="/profile.php?user_id=<?php echo html($review['user_id']); ?>" 
                                                              class="hover:text-readnest-primary">
                                                            <?php echo html($review['user_nickname']); ?>さん
                                                        </a>
                                                        <span class="ml-2">
                                                            <i class="fas fa-eye mr-1"></i><?php echo $review['number_of_refer']; ?>回参照
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-700 text-sm line-clamp-2">
                                                        <?php echo html($review['short_memo']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-6 text-center">
                                    <a href="/popular_review.php" 
                                       class="text-readnest-primary hover:text-readnest-accent font-medium">
                                        もっと見る <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-comment-slash text-4xl mb-4"></i>
                                    <p>レビューがまだありません</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- サイドバー -->
                <div class="space-y-6">
                    <!-- タグクラウド -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-readnest-accent text-white">
                            <h2 class="text-lg font-bold flex items-center">
                                <i class="fas fa-tags mr-2"></i>
                                タグから探す
                            </h2>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($tag_cloud)): ?>
                                <div class="space-y-2">
                                    <?php foreach ($tag_cloud as $tag): ?>
                                        <a href="/search_book_by_tag.php?tag=<?php echo urlencode($tag['tag_name']); ?>" 
                                           class="inline-block px-3 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-readnest-beige dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 hover:text-readnest-primary rounded-full text-sm transition-colors mr-2 mb-2">
                                            <?php echo html($tag['tag_name']); ?>
                                            <span class="text-xs text-gray-500">(<?php echo $tag['tag_count']; ?>)</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-4 text-center">
                                    <a href="/search_book_by_tag.php" 
                                       class="text-readnest-primary hover:text-readnest-accent text-sm font-medium">
                                        すべてのタグを見る <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-gray-500">
                                    <i class="fas fa-tag text-2xl mb-2"></i>
                                    <p class="text-sm">タグがまだありません</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 検索のヒント -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 class="font-semibold text-blue-900 mb-3">
                            <i class="fas fa-lightbulb mr-2"></i>
                            検索のヒント
                        </h3>
                        <ul class="space-y-2 text-sm text-blue-800">
                            <li>• 本のタイトルや著者名で検索</li>
                            <li>• ジャンルやキーワードで検索</li>
                            <li>• レビュー内容から検索</li>
                            <li>• 気になるタグをクリック</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- カスタムCSS -->
<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>