<?php
/**
 * OGP画像動的生成エンドポイント
 * /og-image/report/{year}/{month}/{user_id}.png
 */

// エラーを画像として返さないよう設定
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/library/database.php');
require_once(__DIR__ . '/library/monthly_report_generator.php');

// パラメータ取得
$type = $_GET['type'] ?? '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$user_id = isset($_GET['user']) ? (int)$_GET['user'] : 0;

// バリデーション
if ($type !== 'report' || $year < 2015 || $month < 1 || $month > 12 || $user_id < 1) {
    http_response_code(404);
    exit;
}

// ユーザー情報を取得
$userInfo = getUserInformation($user_id);
if (!$userInfo || $userInfo['status'] != 1) {
    http_response_code(404);
    exit;
}

// 公開設定チェック（非公開ユーザーでも自分の画像は生成可能にする）
// OGP画像はシェア用なので、シェアする意図がある=公開の意思とみなす

// キャッシュヘッダー（1時間キャッシュ）
$cacheTime = 3600;
header('Cache-Control: public, max-age=' . $cacheTime);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');

// キャッシュディレクトリ
$cacheDir = __DIR__ . '/cache/og-images';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

// キャッシュファイル名
$cacheFile = $cacheDir . "/report_{$year}_{$month}_{$user_id}.png";

// キャッシュが有効か確認（1時間以内）
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    header('Content-Type: image/png');
    header('Content-Length: ' . filesize($cacheFile));
    readfile($cacheFile);
    exit;
}

try {
    // メモリ制限を一時的に増やす
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', '30');

    // レポートデータを取得
    $generator = new MonthlyReportGenerator();
    $reportData = $generator->getReportData($user_id, $year, $month);

    if (!$reportData['has_data']) {
        // データがない場合はデフォルト画像を返す
        $defaultImage = __DIR__ . '/img/og-image.jpg';
        if (file_exists($defaultImage)) {
            header('Content-Type: image/jpeg');
            readfile($defaultImage);
        } else {
            http_response_code(404);
        }
        exit;
    }

    // 画像を生成
    $userName = $userInfo['nickname'] ?? 'ユーザー';

    // GD拡張確認
    if (!extension_loaded('gd')) {
        throw new Exception('GD拡張がロードされていません');
    }

    $tempPath = $generator->generateImage($reportData, $userName);

    if (!$tempPath || !file_exists($tempPath)) {
        throw new Exception('画像生成に失敗しました');
    }

    // キャッシュに保存
    if (is_writable($cacheDir)) {
        @copy($tempPath, $cacheFile);
    }

    // 画像を出力
    header('Content-Type: image/png');
    header('Content-Length: ' . filesize($tempPath));
    readfile($tempPath);

    // 一時ファイル削除
    @unlink($tempPath);

} catch (Exception $e) {

    // エラー時はデフォルト画像を返す
    $defaultImage = __DIR__ . '/img/og-image.jpg';
    if (file_exists($defaultImage)) {
        header('Content-Type: image/jpeg');
        readfile($defaultImage);
    } else {
        http_response_code(500);
    }
}
?>
