<?php
/**
 * セッション延長用API
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once(dirname(__DIR__) . '/library/session_helper.php');

// セッションチェック
if (!checkLogin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// セッションをリフレッシュ
refreshSession();

// 成功レスポンス
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'remaining_time' => getSessionRemainingTime()
]);
?>