<?php
/**
 * シンプルな静的トップページ（ログアウト時用）
 * データベースクエリを最小限に
 */

declare(strict_types=1);

// セッション開始（シンプル版）
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// シンプルなログインチェック
$login_flag = isset($_SESSION['AUTH_USER']) && !empty($_SESSION['AUTH_USER']);

// ログイン済みの場合は通常のindex.phpにリダイレクト
if ($login_flag) {
    header('Location: /index.php');
    exit;
}

// 最小限の設定読み込み
define('CONFIG', true);

// ページタイトル設定
$d_site_title = "ReadNest - あなたの読書の巣";

// メタ情報
$g_meta_description = "ReadNest - 読書の進捉を記録し、レビューを書き、本を整理するための居心地のよい空間です。読書仲間とのつながりを楽しみましょう。";
$g_meta_keyword = "読書,本,書評,レビュー,本棚,読書記録,ReadNest";

// 静的な統計情報を読み込み
$static_stats_file = __DIR__ . '/data/static_stats.php';
if (file_exists($static_stats_file)) {
    include($static_stats_file);
    $total_users = $static_stats['total_users'];
    $total_books = $static_stats['total_books'];
    $total_reviews = $static_stats['total_reviews'];
    $total_pages_read = $static_stats['total_pages_read'];
} else {
    // フォールバック値
    $total_users = 12000;
    $total_books = 45000;
    $total_reviews = 8900;
    $total_pages_read = 2300000;
}

// モダンテンプレートを使用
if (!isset($_SESSION)) {
    $_SESSION = [];
}
$_SESSION['use_modern_template'] = true;
include('template/modern/t_index_simple.php');