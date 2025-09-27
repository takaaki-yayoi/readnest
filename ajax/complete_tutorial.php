<?php
/**
 * チュートリアル完了を記録
 */

require_once('../modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// チュートリアル完了を記録（必要に応じて）
// 現在はフラグ管理をしないため、ログのみ記録
error_log("Tutorial completed for user_id: " . $user_id);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>