<?php
/**
 * Google Books簡易検索API
 *
 * 本の発見ページで表紙画像の遅延読み込みと、
 * 本棚追加モーダルの検索結果取得に使用する。
 *
 * POST /api/book_search_quick.php
 * Body: { "query": "タイトル 著者名", "limit": 5 }
 */

header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

try {
    require_once(dirname(__DIR__) . '/modern_config.php');
    require_once(dirname(__DIR__) . '/library/book_search.php');
    require_once(dirname(__DIR__) . '/library/csrf.php');
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'システムエラー']);
    exit;
}

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
    exit;
}

// CSRF検証
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verifyCSRFToken($csrf_token)) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '不正なリクエストです']);
    exit;
}

// POSTデータ取得
$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? '');
$limit = min(max((int)($input['limit'] ?? 5), 1), 10);

if (empty($query)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => '検索キーワードが必要です']);
    exit;
}

// Google Books APIで検索
$search_result = searchBooks($query, 1, $limit);

$books = [];
if (!empty($search_result['books'])) {
    foreach ($search_result['books'] as $book) {
        $books[] = [
            'asin' => $book['ASIN'] ?? '',
            'isbn' => $book['ISBN'] ?? '',
            'title' => $book['Title'] ?? '',
            'author' => $book['Author'] ?? '',
            'image_url' => $book['LargeImage'] ?? '',
            'detail_url' => $book['DetailPageURL'] ?? '',
            'pages' => (int)($book['NumberOfPages'] ?? 0),
            'categories' => $book['Categories'] ?? []
        ];
    }
}

ob_end_clean();

echo json_encode([
    'success' => true,
    'books' => $books
], JSON_UNESCAPED_UNICODE);
