<?php
/**
 * 読書傾向分析を画像に変換するAPIエンドポイント
 */

// Start output buffering
ob_start();

// エラーハンドラー設定
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => 'システムエラーが発生しました'
        ]);
    }
});

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

if (!isset($input['analysis_id'])) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '分析IDが指定されていません']);
    exit;
}

// Include config and required files
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/analysis_image_generator.php');
require_once(dirname(__DIR__) . '/library/author_corrections.php');

// Clear any output before processing
ob_clean();

try {
    // メモリ制限を一時的に増やす
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', '60');
    
    // Use global database connection from config.php
    global $g_db;
    if (!$g_db) {
        throw new Exception('データベース接続エラー');
    }
    
    // 分析データを取得
    $sql = "SELECT * FROM b_reading_analysis 
            WHERE analysis_id = ? AND user_id = ? 
            LIMIT 1";
    $analysis = $g_db->getRow($sql, array($input['analysis_id'], $user_id), DB_FETCHMODE_ASSOC);
    
    if (!$analysis || DB::isError($analysis)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => '分析データが見つかりません'
        ]);
        exit;
    }
    
    // ユーザー情報を取得
    $userInfo = getUserInformation($user_id);
    if (!$userInfo) {
        throw new Exception('ユーザー情報の取得に失敗しました');
    }
    
    // 分析内容の著者名を修正
    $corrected_content = AuthorCorrections::correctInText($analysis['analysis_content']);
    
    // 画像を生成
    $generator = new AnalysisImageGenerator();
    
    // エラーを捕捉
    $imagePath = @$generator->generateImage(
        $corrected_content,
        $userInfo['nickname'] ?? $user_id,
        date('Y年n月j日', strtotime($analysis['created_at']))
    );
    
    if (!$imagePath || !file_exists($imagePath)) {
        $lastError = error_get_last();
        $errorMsg = $lastError ? $lastError['message'] : '不明なエラー';
        throw new Exception('画像の生成に失敗しました: ' . $errorMsg);
    }
    
    // ファイルサイズをチェック
    $fileSize = filesize($imagePath);
    if ($fileSize > 10 * 1024 * 1024) { // 10MB以上
        unlink($imagePath);
        throw new Exception('生成された画像が大きすぎます');
    }
    
    // 画像をBase64エンコード
    $imageContent = @file_get_contents($imagePath);
    if ($imageContent === false) {
        unlink($imagePath);
        throw new Exception('画像ファイルの読み込みに失敗しました');
    }
    
    $imageData = base64_encode($imageContent);
    $imageUrl = 'data:image/png;base64,' . $imageData;
    
    // 一時ファイルを削除
    @unlink($imagePath);
    
    // 成功レスポンス
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'image_url' => $imageUrl,
        'message' => '画像を生成しました'
    ]);
    
} catch (Exception $e) {
    error_log('Generate Analysis Image Error: ' . $e->getMessage());
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'error' => 'エラー: ' . $e->getMessage()
    ]);
}
?>