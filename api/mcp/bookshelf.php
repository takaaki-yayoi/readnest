<?php
/**
 * MCP API: 本棚データ取得
 *
 * GET /api/mcp/bookshelf.php
 *
 * Query Parameters:
 * - status: int (optional) 1=積読, 2=読書中, 3=読了, 4=既読
 * - limit: int (optional) 取得件数（デフォルト100、最大1000）
 * - offset: int (optional) オフセット（デフォルト0）
 *
 * Response:
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "book_id": 123,
 *       "title": "書籍名",
 *       "author": "著者名",
 *       "status": 3,
 *       "rating": 5,
 *       "current_page": 100,
 *       "total_page": 300,
 *       "finished_date": "2025-01-15",
 *       "amazon_id": "B0XXXXXX",
 *       "isbn": "978XXXXXXXXXX",
 *       "image_url": "https://...",
 *       "updated_at": "2025-01-15 10:30:00"
 *     }
 *   ],
 *   "total": 150,
 *   "limit": 100,
 *   "offset": 0
 * }
 */

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once(dirname(dirname(__DIR__)) . '/library/api_auth.php');
require_once(dirname(dirname(__DIR__)) . '/library/database.php');

// 認証チェック
$user_id = requireApiAuth();

// パラメータ取得
$status = isset($_GET['status']) ? (int)$_GET['status'] : null;
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 1000) : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// ステータス条件
$status_where = '';
$params = [$user_id];

if ($status !== null && in_array($status, [1, 2, 3, 4])) {
    $status_where = ' AND bl.status = ?';
    $params[] = $status;
}

// 総件数を取得
$count_sql = "SELECT COUNT(*) FROM b_book_list bl WHERE bl.user_id = ? $status_where";
$total = $g_db->getOne($count_sql, $params);

if (DB::isError($total)) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Database error'
    ], 500);
}

// 本棚データを取得
$sql = "SELECT bl.book_id, bl.user_id, bl.amazon_id, bl.isbn, bl.name,
        bl.image_url, bl.detail_url, bl.status, bl.rating, bl.memo,
        bl.total_page, bl.current_page, bl.finished_date, bl.update_date,
        COALESCE(bl.author, br.author, '') as author
        FROM b_book_list bl
        LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
        WHERE bl.user_id = ? $status_where
        ORDER BY bl.update_date DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$results = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);

if (DB::isError($results)) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Database error'
    ], 500);
}

// レスポンスデータを整形
$books = [];
foreach ($results as $row) {
    $books[] = [
        'book_id' => (int)$row['book_id'],
        'title' => $row['name'],
        'author' => $row['author'],
        'status' => (int)$row['status'],
        'rating' => $row['rating'] ? (int)$row['rating'] : null,
        'current_page' => $row['current_page'] ? (int)$row['current_page'] : null,
        'total_page' => $row['total_page'] ? (int)$row['total_page'] : null,
        'finished_date' => $row['finished_date'],
        'amazon_id' => $row['amazon_id'],
        'isbn' => $row['isbn'],
        'image_url' => $row['image_url'],
        'updated_at' => $row['update_date']
    ];
}

// レスポンス
sendJsonResponse([
    'success' => true,
    'data' => $books,
    'total' => (int)$total,
    'limit' => $limit,
    'offset' => $offset
]);
?>
