<?php
/**
 * Push通知 購読解除API
 */

declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/library/database.php';
require_once dirname(__DIR__) . '/library/csrf.php';

header('Content-Type: application/json; charset=UTF-8');

function sendResponse(bool $success, string $message = '', $data = null): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!checkLogin()) {
    sendResponse(false, 'ログインが必要です');
}
$user_id = (int)$_SESSION['AUTH_USER'];

$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data) {
    sendResponse(false, 'リクエストデータが不正です');
}

if (!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
    sendResponse(false, '不正なリクエストです');
}

$endpoint = isset($data['endpoint']) ? trim((string)$data['endpoint']) : '';
if ($endpoint === '') {
    sendResponse(false, 'endpointが必要です');
}

try {
    global $g_db;
    $result = $g_db->query(
        "DELETE FROM b_push_subscriptions WHERE user_id = ? AND endpoint = ?",
        [$user_id, $endpoint]
    );
    if (DB::isError($result)) {
        sendResponse(false, '購読解除に失敗しました');
    }
    sendResponse(true, '通知を無効にしました');
} catch (\Throwable $e) {
    error_log('[push_unsubscribe] exception: ' . $e->getMessage());
    sendResponse(false, '購読解除に失敗しました');
}
