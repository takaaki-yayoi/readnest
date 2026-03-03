<?php
/**
 * 検索結果から本棚に追加するAPI
 *
 * Google Books検索結果のデータを受け取り、
 * b_book_repository と b_book_list に追加する。
 *
 * POST /api/add_book_from_search.php
 * Body: { asin, isbn, title, author, image_url, detail_url, pages, status, categories }
 */

header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

try {
    require_once(dirname(__DIR__) . '/modern_config.php');
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

$user_id = $_SESSION['AUTH_USER'];

// POSTデータ取得
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['asin']) || empty($input['title'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '必須パラメータが不足しています']);
    exit;
}

$asin = trim($input['asin']);
$isbn = trim($input['isbn'] ?? '');
$title = trim($input['title']);
$author = trim($input['author'] ?? '');
$image_url = trim($input['image_url'] ?? '');
$detail_url = trim($input['detail_url'] ?? '');
$pages = max(0, (int)($input['pages'] ?? 0));
$status = (int)($input['status'] ?? 1);
$categories = $input['categories'] ?? null;

// ステータスのバリデーション (0-4)
if ($status < 0 || $status > 4) {
    $status = 1;
}

// AI推薦のダミーASINは拒否
if (strpos($asin, 'ai_') === 0 || strpos($asin, 'SAMPLE') === 0) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'この本は自動追加できません']);
    exit;
}

global $g_db;

// 既に本棚にあるかチェック
$existing = is_bookmarked($user_id, $asin);
if ($existing) {
    ob_end_clean();
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'この本は既に本棚にあります', 'book_id' => $existing]);
    exit;
}

// 本を追加
$book_id = createBook(
    $user_id,
    $title,
    $asin,
    $isbn,
    $author,
    '',          // memo
    $pages,
    $status,
    $detail_url,
    $image_url,
    null,        // finished_date
    $categories
);

if (!$book_id) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '本の追加に失敗しました']);
    exit;
}

ob_end_clean();

echo json_encode([
    'success' => true,
    'message' => '本を追加しました',
    'book' => [
        'book_id' => $book_id,
        'asin' => $asin,
        'title' => $title
    ]
], JSON_UNESCAPED_UNICODE);
