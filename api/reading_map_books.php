<?php
/**
 * 読書マップの特定カテゴリの本リストAPI
 */

require_once '../config.php';
require_once '../library/database.php';

// セッション確認
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$g_login_id = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : null;

if (!$g_login_id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// パラメータ取得
$user_id = isset($_GET['user']) ? $_GET['user'] : $g_login_id;
$category = isset($_GET['category']) ? $_GET['category'] : '';

// 他人のデータの場合は公開設定を確認
if ($user_id != $g_login_id) {
    $target_user = getUserInformation($user_id);
    if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
}

global $g_db;

$books = [];

if (strpos($category, '著者: ') === 0) {
    // 著者での検索
    $author = substr($category, strlen('著者: '));
    
    $sql = "SELECT 
        bl.book_id,
        bl.amazon_id,
        bl.name as title,
        bl.author,
        bl.image_url,
        bl.rating,
        bl.status,
        bl.finished_date,
        LEFT(bl.memo, 200) as review_snippet
    FROM b_book_list bl
    WHERE bl.user_id = ? AND bl.author = ?
    ORDER BY bl.update_date DESC
    LIMIT 50";
    
    $result = $g_db->getAll($sql, [$user_id, $author], DB_FETCHMODE_ASSOC);
    
} else {
    // タグでの検索
    $sql = "SELECT 
        bl.book_id,
        bl.amazon_id,
        bl.name as title,
        bl.author,
        bl.image_url,
        bl.rating,
        bl.status,
        bl.finished_date,
        LEFT(bl.memo, 200) as review_snippet
    FROM b_book_list bl
    JOIN b_book_tags bt ON bl.book_id = bt.book_id AND bl.user_id = bt.user_id
    WHERE bl.user_id = ? AND bt.tag_name = ?
    ORDER BY bl.update_date DESC
    LIMIT 50";
    
    $result = $g_db->getAll($sql, [$user_id, $category], DB_FETCHMODE_ASSOC);
}

if (!DB::isError($result)) {
    $books = $result;
}

// ステータスを日本語に変換
foreach ($books as &$book) {
    switch ($book['status']) {
        case READING_NOW:
            $book['status_text'] = '読書中';
            $book['status_color'] = 'blue';
            break;
        case READING_FINISH:
            $book['status_text'] = '読了';
            $book['status_color'] = 'green';
            break;
        case READ_BEFORE:
            $book['status_text'] = '既読';
            $book['status_color'] = 'gray';
            break;
        default:
            $book['status_text'] = '未読';
            $book['status_color'] = 'gray';
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'category' => $category,
    'count' => count($books),
    'books' => $books
], JSON_UNESCAPED_UNICODE);
?>