<?php
/**
 * Sitemap更新スクリプト
 * 
 * このスクリプトは定期的に実行され、sitemap.xmlを最新の状態に保ちます。
 * 推奨実行頻度: 1日1回
 */

declare(strict_types=1);

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

echo "[" . date('Y-m-d H:i:s') . "] Starting sitemap update...\n";

// sitemap.phpの内容を取得
$sitemap_content = file_get_contents('https://readnest.jp/sitemap.xml');

if ($sitemap_content === false) {
    error_log("[Sitemap Update] Failed to generate sitemap");
    echo "Error: Failed to generate sitemap\n";
    exit(1);
}

// 静的ファイルとして保存（パフォーマンス向上のため）
$static_file = dirname(__DIR__) . '/sitemap_static.xml';
$result = file_put_contents($static_file, $sitemap_content);

if ($result === false) {
    error_log("[Sitemap Update] Failed to write static sitemap file");
    echo "Error: Failed to write static sitemap file\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Sitemap updated successfully\n";
echo "Sitemap size: " . number_format(strlen($sitemap_content)) . " bytes\n";

// Google Search Consoleへのping送信は2023年6月に廃止されました
// 代わりにSearch Consoleでサイトマップを登録し、Googleが自動的にクロールします
// 参考: https://developers.google.com/search/blog/2023/06/sitemaps-lastmod-ping
echo "Note: Google Sitemap ping has been deprecated. Sitemap will be discovered automatically.\n";

// データベースにログを記録
try {
    $g_db = DB_Connect();
    $log_sql = "INSERT INTO b_cron_log (script_name, execution_time, status, message) VALUES (?, NOW(), ?, ?)";
    $g_db->query($log_sql, ['update_sitemap.php', 'success', 'Sitemap updated successfully']);
} catch (Exception $e) {
    error_log("[Sitemap Update] Failed to log to database: " . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Sitemap update completed\n";