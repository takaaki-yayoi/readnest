<?php
/**
 * 著者による本の検索ページ
 * 特定の著者の本を一覧表示
 */

require_once('modern_config.php');

// パラメータ取得
$author = isset($_GET['author']) ? trim($_GET['author']) : '';

// 空の場合はホームへリダイレクト
if (empty($author)) {
    header('Location: /');
    exit;
}

// 著者名の正規化（スペースを中点に変換する場合がある）
// データベース内の形式に合わせて検索用の複数パターンを生成
$author_patterns = [
    $author,  // そのまま
    str_replace(' ', '・', $author),  // スペースを中点に
    str_replace('・', ' ', $author),  // 中点をスペースに
    str_replace(' ', '', $author),    // スペースなし
    str_replace('・', '', $author),   // 中点なし
];

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = $login_flag ? $_SESSION['AUTH_USER'] : 0;

// 著者の本を検索
$books = [];

// デバッグ情報
if (isset($_GET['debug'])) {
    error_log("search_book_by_author.php - Original author: " . $author);
    error_log("search_book_by_author.php - Author patterns: " . print_r($author_patterns, true));
}

// 1. b_book_repositoryから検索（複数パターンで検索）
$repo_sql = "
    SELECT DISTINCT
        br.asin,
        br.isbn,
        br.title,
        br.author,
        br.image_url,
        br.description,
        br.publisher,
        br.published_date,
        br.page_count,
        COUNT(DISTINCT bl.user_id) as reader_count,
        AVG(CASE WHEN bl.rating > 0 THEN bl.rating ELSE NULL END) as avg_rating,
        MAX(CASE WHEN bl.user_id = ? THEN 1 ELSE 0 END) as in_my_shelf
    FROM b_book_repository br
    LEFT JOIN b_book_list bl ON (
        br.asin = bl.amazon_id 
        OR br.isbn = bl.isbn 
        OR br.isbn = bl.isbn10 
        OR br.isbn = bl.isbn13
    )
    WHERE (br.author = ? OR br.author = ? OR br.author = ? OR br.author = ? OR br.author = ? OR br.author LIKE ?)
    GROUP BY br.asin, br.isbn, br.title, br.author, br.image_url, 
             br.description, br.publisher, br.published_date, br.page_count
    ORDER BY br.published_date DESC, br.title
";

$repo_params = array_merge(
    [$mine_user_id],
    $author_patterns,
    ['%' . $author_patterns[0] . '%']  // 部分一致も追加
);

$repo_books = $g_db->getAll($repo_sql, $repo_params, DB_FETCHMODE_ASSOC);

if (!DB::isError($repo_books)) {
    foreach ($repo_books as $book) {
        $books[] = [
            'asin' => $book['asin'],
            'isbn' => $book['isbn'],
            'title' => $book['title'],
            'author' => $book['author'],
            'image_url' => $book['image_url'],
            'description' => $book['description'],
            'publisher' => $book['publisher'],
            'published_date' => $book['published_date'],
            'page_count' => $book['page_count'],
            'reader_count' => intval($book['reader_count']),
            'avg_rating' => round(floatval($book['avg_rating']), 1),
            'in_my_shelf' => (bool)$book['in_my_shelf'],
            'source' => 'repository'
        ];
    }
}

// 2. b_book_listからも検索（repositoryにない本）
$list_sql = "
    SELECT DISTINCT
        bl.amazon_id as asin,
        bl.isbn,
        bl.name as title,
        bl.author,
        bl.image_url,
        bl.publisher,
        COUNT(DISTINCT bl.user_id) as reader_count,
        AVG(CASE WHEN bl.rating > 0 THEN bl.rating ELSE NULL END) as avg_rating,
        MAX(CASE WHEN bl.user_id = ? THEN 1 ELSE 0 END) as in_my_shelf
    FROM b_book_list bl
    INNER JOIN b_user u ON bl.user_id = u.user_id
    WHERE (bl.author = ? OR bl.author = ? OR bl.author = ? OR bl.author = ? OR bl.author = ? OR bl.author LIKE ?)
    AND u.status = 1
    AND u.diary_policy = 1
    AND NOT EXISTS (
        SELECT 1 FROM b_book_repository br 
        WHERE br.title = bl.name 
        AND (br.author = bl.author OR br.author LIKE CONCAT('%', bl.author, '%'))
    )
    GROUP BY bl.amazon_id, bl.isbn, bl.name, bl.author, bl.image_url, bl.publisher
    ORDER BY bl.name
";

$list_params = array_merge(
    [$mine_user_id],
    $author_patterns,
    ['%' . $author_patterns[0] . '%']
);

$list_books = $g_db->getAll($list_sql, $list_params, DB_FETCHMODE_ASSOC);

if (!DB::isError($list_books)) {
    foreach ($list_books as $book) {
        $book_key = $book['title'] . '_' . ($book['asin'] ?? $book['isbn'] ?? '');
        $existing = false;
        foreach ($books as $existing_book) {
            if ($existing_book['title'] == $book['title']) {
                $existing = true;
                break;
            }
        }
        
        if (!$existing) {
            $books[] = [
                'asin' => $book['asin'],
                'isbn' => $book['isbn'],
                'title' => $book['title'],
                'author' => $book['author'],
                'image_url' => $book['image_url'],
                'description' => '',
                'publisher' => $book['publisher'],
                'published_date' => '',
                'page_count' => 0,
                'reader_count' => intval($book['reader_count']),
                'avg_rating' => round(floatval($book['avg_rating']), 1),
                'in_my_shelf' => (bool)$book['in_my_shelf'],
                'source' => 'booklist'
            ];
        }
    }
}

// 統計情報を計算
$stats = [
    'total_books' => count($books),
    'total_readers' => 0,
    'avg_rating_overall' => 0
];

$total_readers = 0;
$total_rating = 0;
$rating_count = 0;

foreach ($books as $book) {
    $total_readers += $book['reader_count'];
    if ($book['avg_rating'] > 0) {
        $total_rating += $book['avg_rating'];
        $rating_count++;
    }
}

$stats['total_readers'] = $total_readers;
if ($rating_count > 0) {
    $stats['avg_rating_overall'] = round($total_rating / $rating_count, 1);
}

// ページタイトル
$d_site_title = htmlspecialchars($author) . 'の作品一覧 - ReadNest';
$d_meta_description = htmlspecialchars($author) . 'の作品一覧。本の評価やレビューを確認できます。';
$d_meta_keywords = htmlspecialchars($author) . ',作品,本,読書,ReadNest';

// テンプレートを使用
include(getTemplatePath('t_author_books.php'));
?>