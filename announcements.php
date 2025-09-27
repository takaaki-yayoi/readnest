<?php
/**
 * モダンお知らせページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ページタイトル設定
$d_site_title = "お知らせ - ReadNest";

// メタ情報
$g_meta_description = "ReadNestからの最新のお知らせ、機能追加、メンテナンス情報などをご確認いただけます。";
$g_meta_keyword = "お知らせ,ReadNest,機能追加,メンテナンス,アップデート";

// ログイン状態を確認
$login_flag = checkLogin();
$user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;
$d_nickname = $login_flag ? getNickname($user_id) : '';

// お知らせデータを取得
$announcements = [];
$announcements_by_type = [
    'new_feature' => [],
    'bug_fix' => [],
    'maintenance' => [],
    'general' => []
];

try {
    $announcements_data = getAnnouncement('');
    if ($announcements_data && !DB::isError($announcements_data)) {
        $announcements = $announcements_data;
        
        // タイプ別に分類
        foreach ($announcements as $announcement) {
            $type = $announcement['type'] ?? 'general';
            if (!isset($announcements_by_type[$type])) {
                $type = 'general';
            }
            $announcements_by_type[$type][] = $announcement;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching announcements: " . $e->getMessage());
    $announcements = [];
}

// フィルタリング
$filter_type = $_GET['type'] ?? 'all';
if ($filter_type !== 'all' && isset($announcements_by_type[$filter_type])) {
    $filtered_announcements = $announcements_by_type[$filter_type];
} else {
    $filtered_announcements = $announcements;
}

// ページング設定
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$total_announcements = count($filtered_announcements ?? []);
$total_pages = max(1, (int)ceil($total_announcements / $per_page));
$page = max(1, min($page, $total_pages));

$start_index = ($page - 1) * $per_page;
$announcements_page = array_slice($filtered_announcements, $start_index, $per_page);

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_announcements.php'));
?>