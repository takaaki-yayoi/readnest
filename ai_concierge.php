<?php
require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = null;
if ($login_flag) {
    $mine_user_id = $_SESSION['AUTH_USER'];
}

// ユーザーの読書傾向を取得（ログインユーザーのみ）
$reading_preferences = [];
$recent_books = [];
$favorite_genres = [];
if ($login_flag && $mine_user_id) {
    // 最近読んだ本を取得
    $sql = "SELECT b.title, b.author, b.amazon_id, b.image_url, ub.rating
            FROM b_book b
            INNER JOIN ub_user_book ub ON b.book_id = ub.book_id
            WHERE ub.user_id = ? AND ub.status = 3
            ORDER BY ub.update_date DESC
            LIMIT 10";
    $result = $g_db->getAll($sql, [$mine_user_id]);
    if (!DB::isError($result)) {
        $recent_books = $result;
    }
    
    // よく読むジャンル（タグから推定）
    $sql = "SELECT bt.tag_name, COUNT(*) as count
            FROM b_book_tag bt
            INNER JOIN ub_user_book ub ON bt.book_id = ub.book_id
            WHERE ub.user_id = ? AND ub.status = 3
            GROUP BY bt.tag_name
            ORDER BY count DESC
            LIMIT 5";
    $result = $g_db->getAll($sql, [$mine_user_id]);
    if (!DB::isError($result)) {
        $favorite_genres = $result;
    }
}

// ページメタ情報
$d_site_title = '読書アシスタント - ReadNest';
$g_meta_description = 'ReadNestの読書アシスタントがあなたの読書をサポート。気分や状況に合わせた本の提案、読書相談、本探しのお手伝いをします。';
$g_meta_keyword = '読書アシスタント,AI,本,推薦,読書相談,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_ai_assistant.php'));
?>