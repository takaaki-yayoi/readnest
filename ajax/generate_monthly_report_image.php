<?php
/**
 * 月間読書レポート画像生成APIエンドポイント
 */

// Start output buffering
ob_start();

// エラーハンドラー設定
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json; charset=UTF-8');
        $errorMsg = 'システムエラー';
        if (isset($error['message'])) {
            // 詳細なエラーメッセージ（開発時のみ）
            $errorMsg .= ': ' . $error['message'];
            if (isset($error['file'])) {
                $errorMsg .= ' in ' . basename($error['file']) . ':' . ($error['line'] ?? '?');
            }
        }
        error_log('Monthly Report Image Fatal Error: ' . json_encode($error));
        echo json_encode([
            'success' => false,
            'error' => $errorMsg
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

$current_year = (int)date('Y');
$current_month = (int)date('n');

$year = isset($input['year']) ? (int)$input['year'] : $current_year;
$month = isset($input['month']) ? (int)$input['month'] : $current_month;

// バリデーション
if ($month < 1 || $month > 12) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '無効な月です']);
    exit;
}

if ($year < 2015 || $year > $current_year) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '無効な年です']);
    exit;
}

// 未来の月はエラー
if ($year > $current_year || ($year == $current_year && $month > $current_month)) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '未来の月は指定できません']);
    exit;
}

// Include config and required files
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/monthly_report_generator.php');

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

    // ユーザー情報を取得
    $userInfo = getUserInformation($user_id);
    if (!$userInfo) {
        throw new Exception('ユーザー情報の取得に失敗しました');
    }

    // レポートデータを取得
    $generator = new MonthlyReportGenerator();
    $reportData = $generator->getReportData($user_id, $year, $month);

    if (!$reportData['has_data']) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => 'この月の読書記録がありません'
        ]);
        exit;
    }

    // GD拡張の確認
    if (!extension_loaded('gd')) {
        throw new Exception('GD拡張がインストールされていません');
    }

    // 画像を生成
    $imagePath = $generator->generateImage(
        $reportData,
        $userInfo['nickname'] ?? (string)$user_id
    );

    if (!$imagePath) {
        throw new Exception('画像の生成に失敗しました（パスが返されませんでした）');
    }

    if (!file_exists($imagePath)) {
        throw new Exception('画像ファイルが見つかりません: ' . basename($imagePath));
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
    error_log('Generate Monthly Report Image Error: ' . $e->getMessage());
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'error' => 'エラー: ' . $e->getMessage()
    ]);
}
?>
