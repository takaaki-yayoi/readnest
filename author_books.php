<?php
/**
 * 作家の本一覧（公開ページ・ログイン不要）
 */

require_once('modern_config.php');

// 作家名を取得
$author = isset($_GET['author']) ? trim($_GET['author']) : '';

if (empty($author)) {
    header('Location: /sakka_cloud.php');
    exit;
}

// SEO用メタ情報
$d_site_title = htmlspecialchars($author) . 'の作品一覧 - ReadNest';
$g_meta_description = htmlspecialchars($author) . 'の作品を読んでいるユーザーの読書記録。みんなの評価やレビューを参考にしよう。';
$g_meta_keyword = htmlspecialchars($author) . ',作品一覧,読書記録,書評';

// 作家の本を取得（公開されている読書記録のみ）
$sql = "
    SELECT 
        br.asin,
        br.title,
        br.author,
        br.image_url,
        COUNT(DISTINCT bl.user_id) as reader_count,
        COUNT(DISTINCT CASE WHEN bl.status = 3 THEN bl.user_id END) as completed_count,
        MAX(bl.update_date) as last_read
    FROM b_book_repository br
    INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
    INNER JOIN b_user bu ON bl.user_id = bu.user_id
    WHERE 
        br.author = ?
        AND bu.diary_policy = 1
        AND bu.status = 1
    GROUP BY br.asin, br.title, br.author, br.image_url
    ORDER BY reader_count DESC
    LIMIT 100
";

$books = $g_db->getAll($sql, [$author], DB_FETCHMODE_ASSOC);

if (DB::isError($books)) {
    $books = [];
}

// 作家の統計情報を取得
$stats_sql = "
    SELECT 
        COUNT(DISTINCT br.asin) as total_books,
        COUNT(DISTINCT bl.user_id) as total_readers
    FROM b_book_repository br
    INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
    INNER JOIN b_user bu ON bl.user_id = bu.user_id
    WHERE 
        br.author = ?
        AND bu.diary_policy = 1
        AND bu.status = 1
";

$stats = $g_db->getRow($stats_sql, [$author], DB_FETCHMODE_ASSOC);

// テンプレートを読み込み
include(getTemplatePath('t_author_books.php'));
?>