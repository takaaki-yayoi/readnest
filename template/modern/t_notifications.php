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

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- ページヘッダー -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                <i class="fas fa-bell mr-2 text-readnest-primary"></i>通知
            </h1>

            <?php if ($stats['unread'] > 0): ?>
            <!-- 一括既読ボタン -->
            <form method="post" action="/notifications.php" class="inline">
                <?php echo csrfFieldTag(); ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-check-double mr-2"></i>
                    すべて既読にする
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- 未読バッジ -->
        <?php if ($stats['unread'] > 0): ?>
        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-sm text-blue-700 dark:text-blue-300">
                <i class="fas fa-info-circle mr-1"></i>
                未読の通知が <?php echo number_format($stats['unread']); ?> 件あります
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- 通知一覧 -->
    <?php if (empty($notifications)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 text-center">
        <i class="fas fa-bell-slash text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            通知はありません
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-500">
            いいねや月間レポートなどの通知がここに表示されます。
        </p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
        <?php foreach ($notifications as $notification): ?>
        <?php
        $is_unread = !$notification['is_read'];
        $bg_class = $is_unread
            ? 'bg-blue-50 dark:bg-blue-900/10 border-l-4 border-blue-500'
            : 'bg-white dark:bg-gray-800';
        ?>
        <a href="<?php echo html($notification['link_url'] ?? '#'); ?>"
           class="block <?php echo $bg_class; ?> rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow notification-item"
           data-notification-id="<?php echo $notification['notification_id']; ?>">
            <div class="flex items-start space-x-4">
                <!-- アイコン -->
                <div class="flex-shrink-0">
                    <?php if ($notification['notification_type'] === 'like'): ?>
                        <?php if (!empty($notification['actor_photo'])): ?>
                        <img src="/display_profile_photo.php?user_id=<?php echo $notification['actor_user_id']; ?>&mode=thumbnail"
                             alt="<?php echo html($notification['actor_nickname'] ?? ''); ?>"
                             class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <i class="fas fa-heart text-red-500 text-lg"></i>
                        </div>
                        <?php endif; ?>
                    <?php elseif ($notification['notification_type'] === 'monthly_report'): ?>
                        <div class="w-12 h-12 rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                            <i class="fas fa-chart-bar text-teal-500 text-lg"></i>
                        </div>
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <i class="fas fa-bell text-gray-500 text-lg"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 通知内容 -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 <?php echo $is_unread ? 'font-bold' : ''; ?>">
                        <?php echo html($notification['title']); ?>
                    </p>
                    <?php if (!empty($notification['message'])): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        <?php echo html($notification['message']); ?>
                    </p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                        <i class="fas fa-clock mr-1"></i>
                        <?php echo formatRelativeTime($notification['created_at']); ?>
                    </p>
                </div>

                <!-- 未読インジケーター -->
                <?php if ($is_unread): ?>
                <div class="flex-shrink-0">
                    <span class="inline-block w-3 h-3 bg-blue-500 rounded-full"></span>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ページネーション -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-8 flex justify-center">
        <nav class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>"
               class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-chevron-left mr-1"></i>前へ
            </a>
            <?php endif; ?>

            <span class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">
                <?php echo $page; ?> / <?php echo $total_pages; ?>
            </span>

            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>"
               class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                次へ<i class="fas fa-chevron-right ml-1"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// 通知クリック時に既読にする
document.querySelectorAll('.notification-item').forEach(function(item) {
    item.addEventListener('click', function(e) {
        const notificationId = this.dataset.notificationId;
        const notificationItem = this;

        // 既に既読の場合はスキップ（青い背景がない場合）
        const isUnread = notificationItem.classList.contains('bg-blue-50') ||
                        notificationItem.classList.contains('dark:bg-blue-900/10');

        if (notificationId && isUnread) {
            // バックグラウンドで既読リクエストを送信
            fetch('/ajax/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ notification_id: notificationId })
            }).then(function(response) {
                return response.json();
            }).then(function(data) {
                if (data.success) {
                    // UIを更新：未読スタイルを削除
                    notificationItem.classList.remove('bg-blue-50', 'dark:bg-blue-900/10', 'border-l-4', 'border-blue-500');
                    notificationItem.classList.add('bg-white', 'dark:bg-gray-800');

                    // 未読ドットを削除
                    const unreadDot = notificationItem.querySelector('.bg-blue-500.rounded-full');
                    if (unreadDot) {
                        unreadDot.remove();
                    }

                    // タイトルの太字を削除
                    const title = notificationItem.querySelector('.font-bold');
                    if (title) {
                        title.classList.remove('font-bold');
                    }

                    // ヘッダーのバッジを更新
                    updateNotificationBadge(data.unread_count);
                }
            }).catch(function(error) {
                console.log('Failed to mark notification as read:', error);
            });
        }
    });
});

// ヘッダーの通知バッジを更新
function updateNotificationBadge(count) {
    const badge = document.getElementById('notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>
