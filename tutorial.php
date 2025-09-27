<?php
/**
 * チュートリアルページ（手動で再生可能）
 */

require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];

// ページメタ情報
$d_site_title = 'チュートリアル - ReadNest';
$g_meta_description = 'ReadNestの使い方をインタラクティブに学習';
$g_meta_keyword = 'チュートリアル,使い方,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_tutorial.php'));
?>