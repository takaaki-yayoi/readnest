<?php
/**
 * 読書傾向分析を保存するAPIエンドポイント
 */

// Start output buffering to catch any warnings
ob_start();

// Simple save endpoint that avoids session class issues
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

if (!isset($input['analysis_content'])) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '分析結果がありません']);
    exit;
}

// Include config and required files
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/author_corrections.php');

// Clear any output before sending JSON
ob_clean();
header('Content-Type: application/json; charset=UTF-8');

try {
    // Use global database connection from config.php
    global $g_db;
    if (!$g_db) {
        throw new Exception('データベース接続エラー');
    }
    
    // Check if table exists
    $table_exists = false;
    try {
        $table_exists = $g_db->getOne("SHOW TABLES LIKE 'b_reading_analysis'");
    } catch (Exception $e) {
        throw new Exception('テーブル確認エラー: ' . $e->getMessage());
    }
    
    if (!$table_exists) {
        echo json_encode([
            'success' => false,
            'error' => 'データベーステーブルが存在しません。管理者に連絡してください。',
            'admin_url' => '/admin/add_reading_analysis_table.php'
        ]);
        exit;
    }
    
    // 分析タイプ（デフォルトはtrend）
    $analysis_type = isset($input['analysis_type']) ? $input['analysis_type'] : 'trend';

    // 許可されたタイプのみ
    if (!in_array($analysis_type, ['trend', 'monthly_report', 'yearly_report'])) {
        $analysis_type = 'trend';
    }

    // 分析内容の著者名を修正してから保存
    $corrected_content = AuthorCorrections::correctInText($input['analysis_content']);

    // 公開設定（パラメータがあれば使用、なければ非公開）
    $is_public = isset($input['is_public']) ? (int)$input['is_public'] : 0;

    // Save the analysis
    $analysis_id = saveReadingAnalysis(
        $user_id,
        $analysis_type,
        $corrected_content,
        $is_public
    );
    
    if ($analysis_id) {
        echo json_encode([
            'success' => true,
            'analysis_id' => $analysis_id,
            'message' => '読書傾向分析を保存しました'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => '保存に失敗しました'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Save Reading Analysis Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'エラー: ' . $e->getMessage()
    ]);
}
?>