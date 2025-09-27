<?php
/**
 * 軽量版トップページ（ログアウトユーザー用）
 * index.phpから内部的にインクルードされる
 */

// 静的な統計情報を読み込み
$static_stats_file = __DIR__ . '/data/static_stats.php';
if (file_exists($static_stats_file)) {
    include($static_stats_file);
    $total_users = $static_stats['total_users'] ?? 15234;
    $total_books = $static_stats['total_books'] ?? 48567;
    $total_reviews = $static_stats['total_reviews'] ?? 9234;
    $total_pages_read = $static_stats['total_pages_read'] ?? 2456789;
} else {
    // フォールバック値
    $total_users = 15234;
    $total_books = 48567;
    $total_reviews = 9234;
    $total_pages_read = 2456789;
}

// ページタイトル設定
$d_site_title = "ReadNest - あなたの読書の巣";

// メタ情報
$g_meta_description = "ReadNest - 読書の進捉を記録し、レビューを書き、本を整理するための居心地のよい空間です。読書仲間とのつながりを楽しみましょう。";
$g_meta_keyword = "読書,本,書評,レビュー,本棚,読書記録,ReadNest";

// モダンテンプレートフラグをセット
$_SESSION['use_modern_template'] = true;

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// 静的コンテンツ用のテンプレートを使用
include(getTemplatePath('t_index_light.php'));
?>