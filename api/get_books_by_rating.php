<?php
/**
 * 評価別の本一覧を取得するAPI
 */

declare(strict_types=1);

require_once(dirname(__DIR__) . '/modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['AUTH_USER'];
$rating = intval($_GET['rating'] ?? 0);

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid rating']);
    exit;
}

global $g_db;

$books = [];

try {
    $sql = "SELECT book_id, name, author, image_url, finished_date, rating,
            (SELECT image_url FROM b_book_repository WHERE asin = bl.amazon_id LIMIT 1) as repo_image
            FROM b_book_list bl
            WHERE user_id = ? 
            AND rating = ?
            ORDER BY finished_date DESC";
    
    $books = $g_db->getAll($sql, [$user_id, $rating], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($books)) {
        $books = [];
    }
    
} catch (Exception $e) {
    error_log('Error in get_books_by_rating.php: ' . $e->getMessage());
    $books = [];
}

header('Content-Type: application/json');
echo json_encode($books);
?>