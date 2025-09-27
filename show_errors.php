<?php
/**
 * エラーログ確認用の一時ファイル
 * ※セキュリティのため、確認後は必ず削除してください
 */

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// PHPのエラーログの場所を表示
echo "<h2>PHPエラーログの設定</h2>";
echo "<pre>";
echo "error_log = " . ini_get('error_log') . "\n";
echo "log_errors = " . ini_get('log_errors') . "\n";
echo "</pre>";

// 最近のエラーログを表示（もしアクセス可能なら）
$error_log_file = ini_get('error_log');
if ($error_log_file && file_exists($error_log_file) && is_readable($error_log_file)) {
    echo "<h2>最近のエラーログ（最後の50行）</h2>";
    echo "<pre>";
    $lines = file($error_log_file);
    $recent_lines = array_slice($lines, -50);
    foreach ($recent_lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>エラーログファイルにアクセスできません。</p>";
}

// システム情報
echo "<h2>システム情報</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";

// 重要: このファイルは確認後、必ず削除してください！
echo "<p style='color: red; font-weight: bold;'>⚠️ セキュリティ警告: このファイルは確認後、必ず削除してください！</p>";
?>