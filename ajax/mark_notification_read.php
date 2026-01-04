<?php
/**
 * 通知既読APIエンドポイント
 */

header('Content-Type: application/json');

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/csrf.php');
require_once(dirname(__DIR__) . '/library/notification_helpers.php');

// ログインチェック
if (!isset($_SESSION['AUTH_USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['AUTH_USER'];

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// JSON入力を取得
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// CSRFトークン検証（ヘッダーから取得）
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// 通知IDを取得
$notification_id = isset($input['notification_id']) ? (int)$input['notification_id'] : 0;

if ($notification_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    exit;
}

// 既読にする
$result = markNotificationAsRead($notification_id, $user_id);

if ($result) {
    echo json_encode([
        'success' => true,
        'unread_count' => getUnreadNotificationCount($user_id)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to mark notification as read'
    ]);
}
