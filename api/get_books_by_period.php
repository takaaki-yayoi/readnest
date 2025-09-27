<?php
/**
 * 期間別の本一覧を取得するAPI
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
$period = $_GET['period'] ?? '';
$value = $_GET['value'] ?? '';

global $g_db;

$books = [];

try {
    switch ($period) {
        case 'year':
            $sql = "SELECT book_id, name, author, image_url, finished_date,
                    (SELECT image_url FROM b_book_repository WHERE asin = bl.amazon_id LIMIT 1) as repo_image
                    FROM b_book_list bl
                    WHERE user_id = ? 
                    AND status = " . READING_FINISH . "
                    AND YEAR(finished_date) = ?
                    ORDER BY finished_date DESC";
            $books = $g_db->getAll($sql, [$user_id, $value], DB_FETCHMODE_ASSOC);
            break;
            
        case 'month':
            $sql = "SELECT book_id, name, author, image_url, finished_date,
                    (SELECT image_url FROM b_book_repository WHERE asin = bl.amazon_id LIMIT 1) as repo_image
                    FROM b_book_list bl
                    WHERE user_id = ? 
                    AND status = " . READING_FINISH . "
                    AND DATE_FORMAT(finished_date, '%Y-%m') = ?
                    ORDER BY finished_date DESC";
            $books = $g_db->getAll($sql, [$user_id, $value], DB_FETCHMODE_ASSOC);
            break;
            
        case 'day':
            $sql = "SELECT book_id, name, author, image_url, finished_date,
                    (SELECT image_url FROM b_book_repository WHERE asin = bl.amazon_id LIMIT 1) as repo_image
                    FROM b_book_list bl
                    WHERE user_id = ? 
                    AND status = " . READING_FINISH . "
                    AND finished_date = ?
                    ORDER BY finished_date DESC";
            $books = $g_db->getAll($sql, [$user_id, $value], DB_FETCHMODE_ASSOC);
            break;
    }
    
    if (DB::isError($books)) {
        $books = [];
    }
    
} catch (Exception $e) {
    error_log('Error in get_books_by_period.php: ' . $e->getMessage());
    $books = [];
}

header('Content-Type: application/json');
echo json_encode($books);
?>