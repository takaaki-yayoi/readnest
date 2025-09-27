<?php
// 読書マップから読書インサイトへリダイレクト
header('Location: /reading_insights.php?mode=map');
exit;

// ユーザー情報取得
$g_my_user = getUserInformation($g_login_id);
$g_display_nickname = getNickname($g_login_id);

// ページタイトル
$d_site_title = '読書マップ - ReadNest';
$g_meta_description = 'あなたの読書履歴をマッピング。ジャンルの偏りや成長を可視化し、次に探索すべき領域を発見しましょう。';

// 表示するユーザーID（プロフィール表示の場合）
$user_id = isset($_GET['user']) ? $_GET['user'] : $g_login_id;
$is_my_map = ($user_id == $g_login_id);

// ユーザー存在確認
if ($user_id != $g_login_id) {
    $target_user = getUserInformation($user_id);
    if (!$target_user) {
        header("Location: /");
        exit;
    }
    // 公開設定確認
    if ($target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
        header("Location: /");
        exit;
    }
    $display_nickname = getNickname($user_id);
    $d_site_title = $display_nickname . 'さんの読書マップ - ReadNest';
} else {
    $display_nickname = $g_display_nickname;
}

// ビューを読み込む
include 'template/modern/t_reading_map.php';
?>