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
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-6">
                <i class="fas fa-fire text-6xl opacity-80"></i>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">人気のレビュー</h1>
            <p class="text-xl text-white opacity-90">
                読書仲間のおすすめ本と感想をチェックしよう
            </p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-600 mx-2"></i>
                    <span class="text-gray-700 dark:text-gray-300">人気のレビュー</span>
                </li>
            </ol>
        </nav>

        <!-- フィルターとソート -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">フィルター</h2>
                    
                    <!-- 期間フィルター -->
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">期間:</label>
                        <select id="period-filter" class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-readnest-primary dark:focus:ring-readnest-accent">
                            <?php foreach ($period_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $period === $value ? 'selected' : ''; ?>>
                                    <?php echo html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- ソート -->
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">並び順:</label>
                        <select id="sort-filter" class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-readnest-primary dark:focus:ring-readnest-accent">
                            <?php foreach ($sort_options as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $sort === $value ? 'selected' : ''; ?>>
                                    <?php echo html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <?php if ($total_reviews > 0): ?>
                        <?php echo number_format($total_reviews); ?>件中 <?php echo $start_num; ?>〜<?php echo $end_num; ?>件目
                    <?php else: ?>
                        レビューが見つかりません
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- レビューリスト -->
        <?php if (!empty($popular_reviews)): ?>
            <div class="space-y-6">
                <?php foreach ($popular_reviews as $index => $review): ?>
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row gap-6">
                                <!-- 書籍画像 -->
                                <div class="flex-shrink-0">
                                    <a href="<?php echo html($review['detail_url']); ?>" class="block">
                                        <img src="<?php echo html($review['image_url']); ?>" 
                                             alt="<?php echo html($review['name']); ?>"
                                             class="w-32 h-40 object-cover rounded-lg shadow-sm hover:shadow-md transition-shadow mx-auto lg:mx-0"
                                             onerror="this.src='/img/no-image-book.png'">
                                    </a>
                                </div>
                                
                                <!-- コンテンツ -->
                                <div class="flex-1 min-w-0">
                                    <!-- ランキング表示 -->
                                    <div class="flex items-center mb-3">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 text-white font-bold text-sm mr-3">
                                            <?php echo $start_num + $index; ?>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-eye mr-1"></i>
                                            <?php echo number_format($review['number_of_refer']); ?>回参照
                                        </div>
                                    </div>
                                    
                                    <!-- 書籍タイトル -->
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                                        <a href="<?php echo html($review['detail_url']); ?>" 
                                           class="hover:text-readnest-primary dark:hover:text-readnest-accent transition-colors">
                                            <?php echo html($review['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <!-- ユーザー情報と評価 -->
                                    <div class="flex items-center mb-4">
                                        <div class="flex items-center mr-6">
                                            <img src="<?php echo html($review['user_photo']); ?>" 
                                                 alt="<?php echo html($review['user_nickname']); ?>"
                                                 class="w-8 h-8 rounded-full mr-2"
                                                 onerror="this.src='/img/no-image-user.png'">
                                            <div>
                                                <a href="/profile.php?user_id=<?php echo html($review['user_id']); ?>" 
                                                   class="font-medium text-gray-900 dark:text-gray-100 hover:text-readnest-primary dark:hover:text-readnest-accent transition-colors">
                                                    <?php echo html($review['user_nickname']); ?>さん
                                                </a>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    <?php echo html($review['formatted_date']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($review['rating'] > 0): ?>
                                            <div class="flex items-center">
                                                <div class="flex mr-2" title="評価: <?php echo $review['rating']; ?>/5">
                                                    <?php echo renderStars($review['rating']); ?>
                                                </div>
                                                <span class="text-sm text-gray-600 dark:text-gray-400">(<?php echo $review['rating']; ?>/5)</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- レビュー内容 -->
                                    <div class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                        <?php echo XSS::nl2brAutoLink($review['short_memo']); ?>
                                    </div>
                                    
                                    <!-- アクション -->
                                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center justify-between">
                                            <a href="<?php echo html($review['detail_url']); ?>"
                                               class="inline-flex items-center text-readnest-primary dark:text-readnest-accent hover:text-readnest-accent dark:hover:text-readnest-primary font-medium transition-colors">
                                                詳細を見る <i class="fas fa-arrow-right ml-1"></i>
                                            </a>
                                            
                                            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                                <button class="hover:text-readnest-primary dark:hover:text-readnest-accent transition-colors" 
                                                        title="いいね">
                                                    <i class="far fa-heart mr-1"></i>
                                                    いいね
                                                </button>
                                                <button class="hover:text-readnest-primary dark:hover:text-readnest-accent transition-colors" 
                                                        title="シェア">
                                                    <i class="fas fa-share mr-1"></i>
                                                    シェア
                                                </button>
                                            </div>
                                        </div>
                                    </div>
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
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo urlencode($sort); ?>&period=<?php echo urlencode($period); ?>" 
                               class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        ?>
                        
                        <?php if ($start_page > 1): ?>
                            <a href="?page=1&sort=<?php echo urlencode($sort); ?>&period=<?php echo urlencode($period); ?>" 
                               class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="px-3 py-2 text-gray-500 dark:text-gray-400">…</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="px-3 py-2 rounded-md bg-readnest-primary text-white font-medium"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&sort=<?php echo urlencode($sort); ?>&period=<?php echo urlencode($period); ?>" 
                                   class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="px-3 py-2 text-gray-500 dark:text-gray-400">…</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?>&sort=<?php echo urlencode($sort); ?>&period=<?php echo urlencode($period); ?>" 
                               class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo urlencode($sort); ?>&period=<?php echo urlencode($period); ?>" 
                               class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- レビューなし -->
            <div class="text-center py-16">
                <div class="mb-6">
                    <i class="fas fa-comment-slash text-6xl text-gray-300"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">レビューが見つかりません</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    選択した条件に一致するレビューがありません。<br>
                    フィルター条件を変更してみてください。
                </p>
                <a href="/search_review.php" 
                   class="inline-flex items-center px-6 py-3 bg-readnest-primary text-white rounded-md hover:bg-readnest-accent transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    レビューを検索
                </a>
            </div>
        <?php endif; ?>

        <!-- サイドCTA -->
        <div class="mt-16 bg-gradient-to-r from-readnest-primary to-readnest-accent rounded-lg text-white p-8 text-center">
            <h2 class="text-2xl font-bold mb-4">あなたもレビューを書いてみませんか？</h2>
            <p class="text-lg mb-6 opacity-90">
                読んだ本の感想を共有して、読書仲間とつながりましょう。
            </p>
            <?php if ($login_flag): ?>
                <a href="/add_book.php" 
                   class="inline-flex items-center px-6 py-3 bg-white text-readnest-primary rounded-md hover:bg-gray-100 transition-colors font-medium">
                    <i class="fas fa-plus mr-2"></i>
                    本を追加してレビューを書く
                </a>
            <?php else: ?>
                <a href="/register.php" 
                   class="inline-flex items-center px-6 py-3 bg-white text-readnest-primary rounded-md hover:bg-gray-100 transition-colors font-medium">
                    <i class="fas fa-user-plus mr-2"></i>
                    今すぐ始める
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- フィルター変更時の自動送信 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodFilter = document.getElementById('period-filter');
    const sortFilter = document.getElementById('sort-filter');
    
    function updateFilters() {
        const period = periodFilter.value;
        const sort = sortFilter.value;
        window.location.href = `?period=${encodeURIComponent(period)}&sort=${encodeURIComponent(sort)}`;
    }
    
    periodFilter.addEventListener('change', updateFilters);
    sortFilter.addEventListener('change', updateFilters);
});
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>