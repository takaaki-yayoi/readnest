<?php
/**
 * 利用規約ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ページタイトル設定
$d_site_title = "利用規約・プライバシーポリシー - ReadNest";

// メタ情報
$g_meta_description = "ReadNestの利用規約とプライバシーポリシーをご確認いただけます。個人情報の取り扱いやサービス利用に関する重要事項を記載しています。";
$g_meta_keyword = "利用規約,プライバシーポリシー,個人情報保護,ReadNest";

// ログイン状態を確認
$login_flag = checkLogin();
$user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;
$d_nickname = $login_flag ? getNickname($user_id) : '';

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_terms.php'));
?>