<?php
/**
 * MCP API: 読書統計情報
 *
 * GET /api/mcp/stats.php
 *
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "total_books": 150,
 *     "by_status": {
 *       "tsundoku": 20,
 *       "reading": 5,
 *       "finished": 100,
 *       "read": 25
 *     },
 *     "this_year": {
 *       "finished": 45,
 *       "pages": 12500
 *     },
 *     "this_month": {
 *       "finished": 8,
 *       "pages": 2100
 *     },
 *     "average_rating": 4.2,
 *     "total_pages_read": 45000
 *   }
 * }
 */

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once(dirname(dirname(__DIR__)) . '/library/api_auth.php');
require_once(dirname(dirname(__DIR__)) . '/library/database.php');

// 認証チェック
$user_id = requireApiAuth();

// 統計情報を取得
$stats = [];

// 1. 総書籍数
$total_sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ?";
$stats['total_books'] = (int)$g_db->getOne($total_sql, [$user_id]);

// 2. ステータス別の冊数
$status_sql = "SELECT status, COUNT(*) as count
               FROM b_book_list
               WHERE user_id = ?
               GROUP BY status";
$status_results = $g_db->getAll($status_sql, [$user_id], DB_FETCHMODE_ASSOC);

$status_map = [
    1 => 'tsundoku',
    2 => 'reading',
    3 => 'finished',
    4 => 'read'
];

$by_status = [
    'tsundoku' => 0,
    'reading' => 0,
    'finished' => 0,
    'read' => 0
];

if (!DB::isError($status_results)) {
    foreach ($status_results as $row) {
        $status_key = $status_map[(int)$row['status']] ?? null;
        if ($status_key) {
            $by_status[$status_key] = (int)$row['count'];
        }
    }
}

$stats['by_status'] = $by_status;

// 3. 今年の読了冊数とページ数
$this_year_sql = "SELECT COUNT(*) as count, SUM(total_page) as pages
                  FROM b_book_list
                  WHERE user_id = ?
                  AND status = 3
                  AND YEAR(finished_date) = YEAR(NOW())";
$this_year = $g_db->getRow($this_year_sql, [$user_id], DB_FETCHMODE_ASSOC);

$stats['this_year'] = [
    'finished' => !DB::isError($this_year) ? (int)$this_year['count'] : 0,
    'pages' => !DB::isError($this_year) ? (int)$this_year['pages'] : 0
];

// 4. 今月の読了冊数とページ数
$this_month_sql = "SELECT COUNT(*) as count, SUM(total_page) as pages
                   FROM b_book_list
                   WHERE user_id = ?
                   AND status = 3
                   AND YEAR(finished_date) = YEAR(NOW())
                   AND MONTH(finished_date) = MONTH(NOW())";
$this_month = $g_db->getRow($this_month_sql, [$user_id], DB_FETCHMODE_ASSOC);

$stats['this_month'] = [
    'finished' => !DB::isError($this_month) ? (int)$this_month['count'] : 0,
    'pages' => !DB::isError($this_month) ? (int)$this_month['pages'] : 0
];

// 5. 平均評価
$rating_sql = "SELECT AVG(rating) as avg_rating
               FROM b_book_list
               WHERE user_id = ?
               AND rating IS NOT NULL";
$avg_rating = $g_db->getOne($rating_sql, [$user_id]);

$stats['average_rating'] = !DB::isError($avg_rating) && $avg_rating ? round((float)$avg_rating, 2) : null;

// 6. 読了した総ページ数
$total_pages_sql = "SELECT SUM(total_page) as total_pages
                    FROM b_book_list
                    WHERE user_id = ?
                    AND status = 3";
$total_pages = $g_db->getOne($total_pages_sql, [$user_id]);

$stats['total_pages_read'] = !DB::isError($total_pages) ? (int)$total_pages : 0;

// レスポンス
sendJsonResponse([
    'success' => true,
    'data' => $stats
]);
?>
