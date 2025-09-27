<?php
/**
 * 読書傾向分析の公開設定を更新するAPIエンドポイント
 */

// Start output buffering to catch any warnings
ob_start();

// Error reporting off
error_reporting(0);
ini_set('display_errors', 0);

// Start session manually
session_name('DOKUSHO');
session_start();

// Check login
if (!isset($_SESSION['AUTH_USER'])) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['analysis_id']) || !isset($input['is_public'])) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '必要なパラメータが不足しています']);
    exit;
}

// Include config and required files
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// Clear any output before sending JSON
ob_clean();
header('Content-Type: application/json; charset=UTF-8');

try {
    // Use global database connection from config.php
    global $g_db;
    if (!$g_db) {
        throw new Exception('データベース接続エラー');
    }
    
    // Update visibility
    $result = updateReadingAnalysisVisibility(
        $input['analysis_id'],
        $user_id,
        $input['is_public'] ? 1 : 0
    );
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $input['is_public'] ? '公開設定に変更しました' : '非公開設定に変更しました'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => '更新に失敗しました'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Update Reading Analysis Visibility Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'エラー: ' . $e->getMessage()
    ]);
}
?>