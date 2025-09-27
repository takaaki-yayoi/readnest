<?php
/**
 * 読書履歴のメモを更新するAPI
 */

declare(strict_types=1);

// エラー表示を抑制（JSON出力のため）
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/library/database.php';
require_once dirname(__DIR__) . '/library/csrf.php';

header('Content-Type: application/json; charset=UTF-8');

// レスポンスを返す関数
function sendResponse($success, $message = '', $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ログインチェック
if (!checkLogin()) {
    sendResponse(false, 'ログインが必要です');
}

$user_id = $_SESSION['AUTH_USER'];

// POSTデータの取得
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    sendResponse(false, 'リクエストデータが不正です');
}

// 必須パラメータのチェック
if (!isset($data['event_id']) || !isset($data['memo'])) {
    sendResponse(false, '必須パラメータが不足しています');
}

$event_id = (int)$data['event_id'];
$memo = trim($data['memo']);

// CSRFトークンの検証
if (!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
    sendResponse(false, '不正なリクエストです');
}

try {
    global $g_db;
    
    // イベントの所有者確認
    $sql = "SELECT user_id, book_id FROM b_book_event WHERE event_id = ?";
    $event = $g_db->getRow($sql, [$event_id]);
    
    if (!$event) {
        sendResponse(false, 'イベントが見つかりません');
    }
    
    if ($event['user_id'] != $user_id) {
        sendResponse(false, '権限がありません');
    }
    
    // メモの更新
    $sql = "UPDATE b_book_event SET memo = ? WHERE event_id = ?";
    $result = $g_db->query($sql, [$memo, $event_id]);
    
    if (DB::isError($result)) {
        error_log('Failed to update progress memo: ' . $result->getMessage());
        sendResponse(false, 'メモの更新に失敗しました');
    }
    
    sendResponse(true, 'メモを更新しました', [
        'event_id' => $event_id,
        'memo' => $memo
    ]);
    
} catch (Exception $e) {
    error_log('Error in update_progress_memo.php: ' . $e->getMessage());
    sendResponse(false, 'エラーが発生しました');
}