<?php
/**
 * モダンヘルプページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ページタイトル設定
$d_site_title = "ヘルプ・使い方 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestの使い方やよくある質問をご案内します。本の追加方法、読書記録の付け方、プロフィール設定など詳しく説明しています。";
$g_meta_keyword = "ヘルプ,使い方,FAQ,ReadNest,読書記録,本棚";

// ログイン状態を確認
$login_flag = checkLogin();
$user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;
$d_nickname = $login_flag ? getNickname($user_id) : '';
$user_email = '';

if ($login_flag) {
    $user_info = getUserInformation($user_id);
    $user_email = $user_info['email'] ?? '';
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_help.php'));
?>