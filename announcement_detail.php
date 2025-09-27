<?php
/**
 * お知らせ詳細ページ
 */

require_once('modern_config.php');

// お知らせIDを取得
$announcement_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$announcement_id) {
    header('Location: /announcements.php');
    exit;
}

// お知らせを取得
$sql = sprintf(
    "SELECT * FROM b_announcements WHERE announcement_id = %d AND status = 'published'",
    $announcement_id
);

$announcement = $g_db->getRow($sql, null, DB_FETCHMODE_ASSOC);

if (DB::isError($announcement) || !$announcement) {
    // お知らせが見つからない場合はお知らせ一覧へ
    header('Location: /announcements.php');
    exit;
}

// ページメタ情報
$d_site_title = html($announcement['title']) . ' - お知らせ - ReadNest';
$g_meta_description = mb_substr(strip_tags($announcement['content']), 0, 100) . '...';
$g_meta_keyword = 'お知らせ,更新情報,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_announcement_detail.php'));
?>