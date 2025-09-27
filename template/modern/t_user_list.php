<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// ブレッドクラムの設定
$breadcrumbs = [
    ['label' => 'ホーム', 'url' => '/'],
    ['label' => 'ユーザー一覧', 'url' => null]
];

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
                    <i class="fas fa-user-group mr-2 sm:mr-3 text-lg sm:text-2xl"></i>
                    ユーザー一覧
                </h1>
                <p class="text-sm sm:text-lg md:text-xl text-white opacity-90 hidden sm:block">
                    読書仲間を見つけよう
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <p class="text-white text-opacity-90">
                    <span class="text-2xl font-bold"><?php echo number_format($total_users); ?></span>
                    <span class="text-sm">人のユーザー</span>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ソートセクション -->
<section class="bg-gray-50 dark:bg-gray-800 py-4 border-b dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                ユーザー一覧
            </h2>
            <select onchange="location.href='?sort=' + this.value"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-readnest-primary">
                <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>新着順</option>
                <option value="books" <?php echo $sort === 'books' ? 'selected' : ''; ?>>登録数順</option>
                <option value="reviews" <?php echo $sort === 'reviews' ? 'selected' : ''; ?>>レビュー数順</option>
                <option value="active" <?php echo $sort === 'active' ? 'selected' : ''; ?>>最終更新順</option>
            </select>
        </div>
    </div>
</section>

<!-- ユーザーリスト -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (empty($users)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                <div class="text-center py-12">
                    <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400 text-xl">ユーザーが見つかりませんでした</p>
                </div>
            </div>
        <?php else: ?>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($users as $user): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 bg-gradient-to-br from-readnest-primary to-readnest-accent rounded-full flex items-center justify-center text-white font-bold text-xl">
                                    <?php echo mb_substr($user['nickname'], 0, 1); ?>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    <a href="/user_profile.php?id=<?php echo $user['user_id']; ?>" 
                                       class="hover:text-readnest-primary transition-colors">
                                        <?php echo htmlspecialchars($user['nickname']); ?>
                                    </a>
                                </h3>
                                <?php if (!empty($user['introduction'])): ?>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                        <?php echo htmlspecialchars(mb_substr($user['introduction'], 0, 100)); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="mt-3 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span>
                                        <i class="fas fa-book mr-1"></i>
                                        <?php echo number_format($user['book_count']); ?>冊
                                    </span>
                                    <span>
                                        <i class="fas fa-comment mr-1"></i>
                                        <?php echo number_format($user['review_count']); ?>件
                                    </span>
                                    <?php if ($user['avg_rating'] > 0): ?>
                                        <span>
                                            <i class="fas fa-star text-yellow-400 mr-1"></i>
                                            <?php echo number_format($user['avg_rating'], 1); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                    登録: <?php echo date('Y年m月', strtotime($user['regist_date'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- ページネーション -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-8">
                    <ul class="flex justify-center space-x-2">
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="?sort=<?php echo urlencode($sort); ?>&page=<?php echo $page - 1; ?>"
                                   class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700">
                                    前へ
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($p = $start_page; $p <= $end_page; $p++): ?>
                            <li>
                                <a href="?sort=<?php echo urlencode($sort); ?>&page=<?php echo $p; ?>"
                                   class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded <?php echo $p == $page ? 'bg-readnest-primary text-white' : 'bg-white dark:bg-gray-800 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                                    <?php echo $p; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li>
                                <a href="?sort=<?php echo urlencode($sort); ?>&page=<?php echo $page + 1; ?>"
                                   class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700">
                                    次へ
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
?>