<?php
/**
 * 人気の本の集計テーブルを更新するcronスクリプト
 * 1時間ごとに実行することを推奨
 */

// CLIでの実行を確認
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/database_optimized_v2.php');

// データベース接続
$g_db = DB_Connect();
if (!$g_db || DB::isError($g_db)) {
    error_log('[Popular Books Update] Database connection failed');
    exit(1);
}

// 更新開始をログに記録
$start_time = microtime(true);
error_log('[Popular Books Update] Starting update at ' . date('Y-m-d H:i:s'));

try {
    // 集計テーブルを更新
    if (preCalculatePopularBooks()) {
        $execution_time = round(microtime(true) - $start_time, 2);
        error_log('[Popular Books Update] Successfully updated popular books cache in ' . $execution_time . ' seconds');
        
        // 実行ログをデータベースに記録
        $log_sql = "INSERT INTO b_cron_log (
            cron_type, 
            status, 
            message, 
            execution_time, 
            created_at
        ) VALUES (?, ?, ?, ?, ?)";
        
        $g_db->query($log_sql, [
            'update_popular_books',
            'success',
            'Popular books cache updated successfully',
            $execution_time * 1000, // ミリ秒に変換
            time()
        ]);
        
        exit(0);
    } else {
        error_log('[Popular Books Update] Failed to update popular books cache');
        
        // エラーログを記録
        $g_db->query($log_sql, [
            'update_popular_books',
            'error',
            'Failed to update popular books cache',
            0,
            time()
        ]);
        
        exit(1);
    }
} catch (Exception $e) {
    error_log('[Popular Books Update] Error: ' . $e->getMessage());
    exit(1);
}
?>