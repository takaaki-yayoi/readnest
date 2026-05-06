<?php
/**
 * Push通知 購読登録API
 *
 * フロントの PushManager.subscribe() で取得した購読情報を保存する。
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
$p256dh = isset($data['p256dh']) ? trim((string)$data['p256dh']) : '';
$auth = isset($data['auth']) ? trim((string)$data['auth']) : '';

if ($endpoint === '' || $p256dh === '' || $auth === '') {
    sendResponse(false, '購読情報が不足しています');
}

$user_agent = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

try {
    global $g_db;

    // UPSERT: 同一endpointなら更新（同じデバイスから再購読）
    $sql = "INSERT INTO b_push_subscriptions (user_id, endpoint, p256dh, auth, user_agent, last_used_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                p256dh = VALUES(p256dh),
                auth = VALUES(auth),
                user_agent = VALUES(user_agent),
                last_used_at = NOW()";
    $result = $g_db->query($sql, [$user_id, $endpoint, $p256dh, $auth, $user_agent]);

    if (DB::isError($result)) {
        error_log('[push_subscribe] DB error: ' . $result->getMessage());
        sendResponse(false, '購読の保存に失敗しました');
    }

    sendResponse(true, '通知を有効にしました');
} catch (\Throwable $e) {
    error_log('[push_subscribe] exception: ' . $e->getMessage());
    sendResponse(false, '購読の保存に失敗しました');
}
