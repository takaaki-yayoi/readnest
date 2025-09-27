<?php
/**
 * ユーザー一覧ページ
 * ReadNestのユーザーを一覧表示
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ログインチェック（任意：ログインしていなくても閲覧可能）
$login_flag = checkLogin();
$mine_user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;

// ページパラメータ
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// ソート条件
$sort = $_GET['sort'] ?? 'recent';
$sort_options = [
    'recent' => 'ORDER BY u.regist_date DESC',
    'books' => 'ORDER BY book_count DESC',
    'reviews' => 'ORDER BY review_count DESC',
    'active' => 'ORDER BY last_update DESC'
];
$order_by = $sort_options[$sort] ?? $sort_options['recent'];

// ユーザー一覧を取得
$sql = "
    SELECT 
        u.user_id,
        u.nickname,
        u.introduction,
        u.regist_date,
        COUNT(DISTINCT bl.book_id) as book_count,
        COUNT(DISTINCT CASE WHEN bl.memo != '' THEN bl.book_id END) as review_count,
        MAX(bl.update_date) as last_update,
        AVG(CASE WHEN bl.rating > 0 THEN bl.rating END) as avg_rating
    FROM b_user u
    LEFT JOIN b_book_list bl ON u.user_id = bl.user_id
    WHERE u.diary_policy = 1
        AND u.status = 1
    GROUP BY u.user_id
    $order_by
    LIMIT ? OFFSET ?
";

$users = [];
try {
    $result = $g_db->getAll($sql, [$per_page, $offset], DB_FETCHMODE_ASSOC);
    if (!DB::isError($result)) {
        $users = $result;
    }
} catch (Exception $e) {
    error_log("User list error: " . $e->getMessage());
}

// 総ユーザー数を取得
$count_sql = "SELECT COUNT(*) FROM b_user WHERE diary_policy = 1 AND status = 1";
$total_users = $g_db->getOne($count_sql) ?? 0;
$total_pages = ceil($total_users / $per_page);

// ページタイトル設定
$d_site_title = "ユーザー一覧 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestのユーザー一覧。読書仲間を見つけて、読書の楽しみを共有しましょう。";
$g_meta_keyword = "ユーザー一覧,読書仲間,ReadNest";

// テンプレートを読み込み
include(getTemplatePath('t_user_list.php'));
?>