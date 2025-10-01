<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// メインコンテンツを生成
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- ページヘッダー -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">いいね</h1>

        <!-- タブ切り替え -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex gap-4">
                <a href="?tab=gave&type=<?php echo urlencode($filter_type); ?>"
                   class="pb-3 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'gave' ? 'border-readnest-primary text-readnest-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'; ?>">
                    <i class="fas fa-heart mr-1"></i>いいねした投稿
                </a>
                <a href="?tab=received&type=<?php echo urlencode($filter_type); ?>"
                   class="pb-3 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'received' ? 'border-readnest-primary text-readnest-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'; ?>">
                    <i class="fas fa-bell mr-1"></i>いいねされた投稿
                </a>
            </nav>
        </div>

        <!-- 統計情報 -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">すべて</p>
                <p class="text-2xl font-bold text-readnest-primary"><?php echo number_format($stats['total']); ?>件</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">読書活動</p>
                <p class="text-2xl font-bold text-readnest-primary"><?php echo number_format($stats['activity']); ?>件</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">レビュー</p>
                <p class="text-2xl font-bold text-readnest-primary"><?php echo number_format($stats['review']); ?>件</p>
            </div>
        </div>

        <!-- フィルター -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <form method="get" action="/my_likes.php" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="<?php echo html($tab); ?>">
                <span class="text-sm text-gray-600 dark:text-gray-400">表示:</span>
                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="all"
                               <?php echo ($filter_type === 'all') ? 'checked' : ''; ?>
                               onchange="this.form.submit()"
                               class="text-readnest-primary focus:ring-readnest-primary">
                        <span class="ml-2 text-sm dark:text-gray-300">すべて</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="activity"
                               <?php echo ($filter_type === 'activity') ? 'checked' : ''; ?>
                               onchange="this.form.submit()"
                               class="text-readnest-primary focus:ring-readnest-primary">
                        <span class="ml-2 text-sm dark:text-gray-300">読書活動</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="review"
                               <?php echo ($filter_type === 'review') ? 'checked' : ''; ?>
                               onchange="this.form.submit()"
                               class="text-readnest-primary focus:ring-readnest-primary">
                        <span class="ml-2 text-sm dark:text-gray-300">レビュー</span>
                    </label>
                </div>
            </form>
        </div>
    </div>

    <!-- ヘルプリンク -->
    <div class="mb-4 text-right">
        <a href="/help.php#likes" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center">
            <i class="fas fa-question-circle mr-1"></i>
            いいね機能の使い方を見る
        </a>
    </div>

    <!-- いいね一覧 -->
    <?php if (empty($formatted_likes)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 text-center">
        <i class="fas fa-heart text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            <?php if ($tab === 'received'): ?>
                まだいいねされた投稿がありません。
            <?php else: ?>
                まだいいねした投稿がありません。
            <?php endif; ?>
        </p>
        <a href="/help.php#likes" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
            いいね機能について詳しく見る
        </a>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($formatted_likes as $item): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
            <?php if ($item['target_type'] === 'activity' && isset($item['activity'])): ?>
                <?php $activity = $item['activity']; ?>
                <!-- 読書活動 -->
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
                        <!-- ヘッダー -->
                        <div class="flex items-center flex-wrap gap-2 mb-2">
                            <a href="/bookshelf.php?user_id=<?php echo $activity['user_id']; ?>"
                               class="font-medium text-gray-900 dark:text-gray-100 hover:text-readnest-primary transition-colors">
                                <?php echo html($activity['user_name']); ?>
                            </a>
                            <span class="text-gray-600 dark:text-gray-400">さんが</span>

                            <?php
                            $badge_colors = [
                                'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
                                'yellow' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
                                'green' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                                'gray' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
                            ];
                            $badge_class = $badge_colors[$activity['type_color']] ?? $badge_colors['gray'];
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

                                <?php if (!empty($activity['memo'])): ?>
                                <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 rounded p-2">
                                    <?php echo nl2br(html($activity['memo'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- いいね情報 -->
                        <div class="mt-2 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span><i class="fas fa-heart text-red-500"></i> <?php echo number_format($item['like_count']); ?></span>
                            <span class="text-xs">
                                <?php if ($tab === 'received' && isset($item['liker_nickname'])): ?>
                                    <a href="/bookshelf.php?user_id=<?php echo $item['liker_user_id']; ?>" class="hover:text-readnest-primary">
                                        <?php echo html($item['liker_nickname']); ?>さん
                                    </a>がいいねしました・
                                <?php else: ?>
                                    いいねした日時:
                                <?php endif; ?>
                                <?php echo formatRelativeTime($item['created_at']); ?>
                            </span>
                        </div>
                    </div>
                </div>

            <?php elseif ($item['target_type'] === 'review' && isset($item['review'])): ?>
                <?php $review = $item['review']; ?>
                <!-- レビュー -->
                <div class="flex items-start space-x-4">
                    <!-- ユーザーアイコン -->
                    <div class="flex-shrink-0">
                        <a href="/bookshelf.php?user_id=<?php echo $review['user_id']; ?>">
                            <img src="<?php echo html($review['user_photo']); ?>"
                                 alt="<?php echo html($review['user_name']); ?>"
                                 class="w-10 h-10 rounded-full object-cover">
                        </a>
                    </div>

                    <!-- レビュー内容 -->
                    <div class="flex-1 min-w-0">
                        <!-- ヘッダー -->
                        <div class="flex items-center flex-wrap gap-2 mb-2">
                            <a href="/bookshelf.php?user_id=<?php echo $review['user_id']; ?>"
                               class="font-medium text-gray-900 dark:text-gray-100 hover:text-readnest-primary transition-colors">
                                <?php echo html($review['user_name']); ?>
                            </a>
                            <span class="text-gray-600 dark:text-gray-400">さんのレビュー</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo $review['update_date']; ?></span>
                        </div>

                        <!-- 本の情報 -->
                        <div class="flex items-start space-x-3">
                            <a href="/book/<?php echo $review['book_id']; ?>"
                               class="flex-shrink-0">
                                <img src="<?php echo html($review['book_image']); ?>"
                                     alt="<?php echo html($review['book_title']); ?>"
                                     class="w-12 h-18 object-cover rounded shadow-sm hover:shadow-md transition-shadow">
                            </a>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium truncate dark:text-gray-100">
                                    <a href="/book/<?php echo $review['book_id']; ?>"
                                       class="text-gray-900 dark:text-gray-100 hover:text-readnest-primary transition-colors">
                                        <?php echo html($review['book_title']); ?>
                                    </a>
                                </h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 truncate"><?php echo html($review['author']); ?></p>

                                <!-- 評価 -->
                                <?php if ($review['rating'] > 0): ?>
                                <div class="flex items-center mt-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php endif; ?>

                                <!-- レビュー本文 -->
                                <?php if (!empty($review['memo'])): ?>
                                <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 rounded p-2">
                                    <?php echo nl2br(html($review['memo'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- いいね情報 -->
                        <div class="mt-2 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span><i class="fas fa-heart text-red-500"></i> <?php echo number_format($item['like_count']); ?></span>
                            <span class="text-xs">
                                <?php if ($tab === 'received' && isset($item['liker_nickname'])): ?>
                                    <a href="/bookshelf.php?user_id=<?php echo $item['liker_user_id']; ?>" class="hover:text-readnest-primary">
                                        <?php echo html($item['liker_nickname']); ?>さん
                                    </a>がいいねしました・
                                <?php else: ?>
                                    いいねした日時:
                                <?php endif; ?>
                                <?php echo formatRelativeTime($item['created_at']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ページネーション -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-8 flex justify-center">
        <nav class="flex items-center gap-2">
            <?php if ($page > 1): ?>
            <a href="?tab=<?php echo urlencode($tab); ?>&type=<?php echo urlencode($filter_type); ?>&page=<?php echo $page - 1; ?>"
               class="px-3 py-2 rounded bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                前へ
            </a>
            <?php endif; ?>

            <span class="px-3 py-2 text-gray-700 dark:text-gray-300">
                <?php echo $page; ?> / <?php echo $total_pages; ?>
            </span>

            <?php if ($page < $total_pages): ?>
            <a href="?tab=<?php echo urlencode($tab); ?>&type=<?php echo urlencode($filter_type); ?>&page=<?php echo $page + 1; ?>"
               class="px-3 py-2 rounded bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                次へ
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>