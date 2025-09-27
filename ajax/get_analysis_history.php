<?php
/**
 * 読書傾向分析の履歴を取得するAPIエンドポイント
 */

// Start output buffering to catch any warnings
ob_start();

// Simple endpoint that avoids session class issues
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

// Get request parameters
$target_user_id = $_GET['user_id'] ?? $user_id;
$analysis_id = $_GET['analysis_id'] ?? null;

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
    
    // Check if user can view the target user's analyses
    $can_view = false;
    
    if ($target_user_id === $user_id) {
        // 自分の分析は常に見れる
        $can_view = true;
    } else {
        // 他人の分析は公開設定を確認
        $user_info = getUserInformation($target_user_id);
        if ($user_info && $user_info['diary_policy'] == 1) {
            $can_view = true;
        }
    }
    
    if (!$can_view) {
        echo json_encode([
            'success' => false,
            'error' => 'この分析を表示する権限がありません'
        ]);
        exit;
    }
    
    if ($analysis_id) {
        // 特定の分析を取得
        $sql = "SELECT * FROM b_reading_analysis 
                WHERE analysis_id = ? AND user_id = ? AND analysis_type = 'trend'";
        
        // 他人の分析の場合は公開のみ
        if ($target_user_id !== $user_id) {
            $sql .= " AND is_public = 1";
        }
        
        $analysis = $g_db->getRow($sql, array($analysis_id, $target_user_id), DB_FETCHMODE_ASSOC);
        
        if ($analysis) {
            // 著者名を修正
            $analysis['analysis_content'] = AuthorCorrections::correctInText($analysis['analysis_content']);
            
            echo json_encode([
                'success' => true,
                'analysis' => $analysis
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => '分析が見つかりません'
            ]);
        }
    } else {
        // 分析履歴一覧を取得
        $sql = "SELECT analysis_id, created_at, is_public,
                SUBSTRING(analysis_content, 1, 200) as preview
                FROM b_reading_analysis 
                WHERE user_id = ? AND analysis_type = 'trend'";
        
        // 他人の分析の場合は公開のみ
        if ($target_user_id !== $user_id) {
            $sql .= " AND is_public = 1";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 20";
        
        $history = $g_db->getAll($sql, array($target_user_id), DB_FETCHMODE_ASSOC);
        
        if (!$history) {
            $history = [];
        }
        
        // プレビューの著者名も修正
        foreach ($history as &$item) {
            $item['preview'] = AuthorCorrections::correctInText($item['preview']);
            // 改行を削除してプレビューを整形
            $item['preview'] = str_replace(["\r\n", "\r", "\n"], ' ', $item['preview']);
            $item['preview'] = trim($item['preview']) . '...';
        }
        
        echo json_encode([
            'success' => true,
            'history' => $history,
            'is_own_profile' => ($target_user_id === $user_id)
        ]);
    }
    
} catch (Exception $e) {
    error_log('Get Analysis History Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'エラー: ' . $e->getMessage()
    ]);
}
?>