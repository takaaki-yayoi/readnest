<?php
/**
 * 通知設定の更新API（現状はストリークリマインダーのON/OFFのみ）
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

$enabled = !empty($data['streak_reminder_enabled']) ? 1 : 0;

try {
    global $g_db;
    $result = $g_db->query(
        "UPDATE b_user SET streak_reminder_enabled = ? WHERE user_id = ?",
        [$enabled, $user_id]
    );
    if (DB::isError($result)) {
        sendResponse(false, '設定の保存に失敗しました');
    }
    sendResponse(true, '設定を保存しました', ['streak_reminder_enabled' => $enabled]);
} catch (\Throwable $e) {
    error_log('[notification_settings] exception: ' . $e->getMessage());
    sendResponse(false, '設定の保存に失敗しました');
}
