<?php
/**
 * グローバル検索ページ
 * 全ユーザーの公開本棚から本を検索
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// CSRF対策を読み込み
require_once(dirname(__FILE__) . '/library/csrf.php');

// ログインチェック（任意：ログインしていなくても検索可能にする場合はコメントアウト）
$login_flag = checkLogin();
$mine_user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;

// 検索パラメータの取得
$keyword = sanitizeInput($_GET['q'] ?? '');
$search_type = sanitizeInput($_GET['type'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$sort = sanitizeInput($_GET['sort'] ?? 'relevance');
$status_filter = sanitizeInput($_GET['status'] ?? '');
$rating_filter = (int)($_GET['rating'] ?? 0);
$year_filter = sanitizeInput($_GET['year'] ?? '');

// 1ページあたりの表示件数
$per_page = 20;

// 検索オプションを設定
$options = [
    'sort' => $sort
];

if ($status_filter !== '') {
    $options['status'] = $status_filter;
}

if ($rating_filter > 0) {
    $options['rating_min'] = $rating_filter;
}

if ($year_filter !== '') {
    $options['year'] = $year_filter;
}

// 検索実行フラグ
$search_performed = false;
$search_results = null;
$popular_books = null;

// 検索実行
if (!empty($keyword) || !empty($_GET['search'])) {
    $search_performed = true;
    $search_results = globalSearchBooks($keyword, $search_type, $page, $per_page, $options);
} else {
    // キーワードがない場合は人気の本を表示
    $popular_books = getPopularBooksGlobal(20, 'month');
}

// ページタイトル設定
$d_site_title = !empty($keyword) 
    ? "「{$keyword}」の検索結果 - ReadNest グローバル検索" 
    : "グローバル検索 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestのグローバル検索。全ユーザーの公開本棚から本を検索できます。";
$g_meta_keyword = "グローバル検索,本検索,読書記録,ReadNest";

// CSRFトークン生成
$csrf_token = generateCSRFToken();

// テンプレートを読み込み
include(getTemplatePath('t_global_search.php'));
?>