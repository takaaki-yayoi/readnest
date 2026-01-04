<?php
/**
 * 通知一覧ページ
 * すべての通知（いいね、月間レポートなど）を表示
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$mine_user_id = (int)$_SESSION['AUTH_USER'];

// 通知ヘルパーを読み込み
require_once(dirname(__FILE__) . '/library/notification_helpers.php');

// ページネーション設定
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 30;
$offset = ($page - 1) * $per_page;

// すべての通知を取得
$notifications = getNotifications($mine_user_id, $per_page, $offset);
$total_count = getNotificationCount($mine_user_id);
$total_pages = ceil($total_count / $per_page);

// 一括既読処理
if (isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        markAllNotificationsAsRead($mine_user_id);
        header('Location: /notifications.php');
        exit;
    }
}

// 統計情報
$stats = [
    'total' => $total_count,
    'unread' => getUnreadNotificationCount($mine_user_id)
];

// ページタイトル
$d_site_title = '通知 - ReadNest';

// メタ情報
$g_meta_description = 'あなたへの通知一覧です。いいねや月間レポートの通知を確認できます。';
$g_meta_keyword = '通知,いいね,月間レポート,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_notifications.php'));
?>
