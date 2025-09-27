<?php
require_once('modern_config.php');
require_once('library/user_author_cloud.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];

// ユーザーの作家クラウドを取得（全件）
$user_cloud = new UserAuthorCloud($mine_user_id);
$author_cloud_data = $user_cloud->getUserAuthorCloud($mine_user_id, 200); // 最大200件

// ページメタ情報
$d_site_title = 'あなたの作家一覧 - ReadNest';
$g_meta_description = 'あなたが読んだ本の作家一覧';
$g_meta_keyword = '作家,著者,読書';

// テンプレートを読み込み
include(getTemplatePath('t_my_authors.php'));
?>