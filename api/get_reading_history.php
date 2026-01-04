<?php
/**
 * 読書履歴を取得するAPI（AI傾向診断用）
 */

declare(strict_types=1);

require_once(dirname(__DIR__) . '/modern_config.php');

header('Content-Type: application/json');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

$user_id = $_SESSION['AUTH_USER'];
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;

global $g_db;

try {
    // 読了済みの本を取得（評価とレビュー付き）
    $sql = "SELECT
                bl.name as title,
                bl.author,
                bl.rating,
                SUBSTRING(bl.memo, 1, 200) as review,
                bl.finished_date
            FROM b_book_list bl
            WHERE bl.user_id = ?
            AND bl.status IN (" . READING_FINISH . ", " . READ_BEFORE . ")
            ORDER BY bl.finished_date DESC, bl.book_id DESC
            LIMIT ?";

    $books = $g_db->getAll($sql, [$user_id, $limit], DB_FETCHMODE_ASSOC);

    if (DB::isError($books)) {
        throw new Exception('Database error');
    }

    // 整形
    $result = [];
    foreach ($books as $book) {
        $result[] = [
            'title' => $book['title'] ?? '',
            'author' => $book['author'] ?? '',
            'rating' => (int)($book['rating'] ?? 0),
            'review' => $book['review'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'books' => $result,
        'count' => count($result)
    ]);

} catch (Exception $e) {
    error_log('Error in get_reading_history.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>
