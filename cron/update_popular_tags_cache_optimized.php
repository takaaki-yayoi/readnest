<?php
/**
 * 人気のタグキャッシュ更新（最適化版）
 * 実証済みの方法でタイムアウトを回避
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

$g_db = DB_Connect();

// CLIチェック
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

// ログ関数
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
    
    if ($level === 'ERROR') {
        error_log("[$timestamp] update_popular_tags_cache: $message");
    }
}

try {
    logMessage("人気のタグキャッシュ更新開始");
    
    // タイムアウトを設定
    set_time_limit(600); // 10分
    
    // キャッシュテーブルが存在しない場合は作成
    $create_table = "
        CREATE TABLE IF NOT EXISTS b_popular_tags_cache (
            tag_name VARCHAR(255) NOT NULL,
            user_count INT NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (tag_name),
            INDEX idx_user_count (user_count DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $g_db->query($create_table);
    
    // 1. 使用頻度の高いタグを取得（上位500個）
    logMessage("人気タグを取得中...");
    $popular_tags = $g_db->getAll("
        SELECT tag_name, COUNT(*) as total_usage
        FROM b_book_tags
        WHERE tag_name IS NOT NULL AND tag_name != ''
        GROUP BY tag_name
        ORDER BY total_usage DESC
        LIMIT 500
    ");
    
    logMessage(count($popular_tags) . "個のタグを取得しました");
    
    if (empty($popular_tags)) {
        logMessage("タグが見つかりません", 'WARNING');
        exit(0);
    }
    
    // 2. キャッシュテーブルをクリア
    logMessage("キャッシュテーブルをクリア中...");
    $g_db->query("TRUNCATE TABLE b_popular_tags_cache");
    
    // 3. 各タグについて公開ユーザー数を計算
    logMessage("各タグの公開ユーザー数を計算中...");
    $processed = 0;
    $inserted = 0;
    $start_time = microtime(true);
    
    foreach ($popular_tags as $tag) {
        $processed++;
        
        // このタグを使用している公開ユーザー数を取得
        $public_user_count = $g_db->getOne("
            SELECT COUNT(DISTINCT bt.user_id)
            FROM b_book_tags bt
            WHERE bt.tag_name = ?
            AND EXISTS (
                SELECT 1 
                FROM b_user u 
                WHERE u.user_id = bt.user_id 
                AND u.diary_policy = 1 
                AND u.status = 1
            )
        ", [$tag['tag_name']]);
        
        if ($public_user_count > 0) {
            // キャッシュに挿入
            $result = $g_db->query("
                INSERT INTO b_popular_tags_cache (tag_name, user_count)
                VALUES (?, ?)
            ", [$tag['tag_name'], $public_user_count]);
            
            if (!DB::isError($result)) {
                $inserted++;
            } else {
                logMessage("挿入エラー: " . $result->getMessage() . " (tag: {$tag['tag_name']})", 'ERROR');
            }
        }
        
        // 進捗表示（50件ごと）
        if ($processed % 50 == 0) {
            $elapsed = microtime(true) - $start_time;
            $rate = $processed / $elapsed;
            $eta = ($count($popular_tags) - $processed) / $rate;
            
            logMessage(sprintf(
                "処理済み: %d / %d (挿入: %d) - %.1f件/秒 - 残り約%.0f秒",
                $processed,
                count($popular_tags),
                $inserted,
                $rate,
                $eta
            ));
        }
    }
    
    $total_time = microtime(true) - $start_time;
    
    logMessage("処理完了！");
    logMessage("処理時間: " . round($total_time, 2) . "秒");
    logMessage("処理したタグ数: $processed");
    logMessage("キャッシュに挿入したタグ数: $inserted");
    
    // 結果確認
    $top_cached = $g_db->getAll("
        SELECT tag_name, user_count
        FROM b_popular_tags_cache
        ORDER BY user_count DESC
        LIMIT 10
    ");
    
    logMessage("キャッシュ内容（上位10件）:");
    foreach ($top_cached as $idx => $tag) {
        logMessage(sprintf("  %2d. %-20s: %d人", 
            $idx + 1, 
            $tag['tag_name'], 
            $tag['user_count']
        ));
    }
    
    // cronログに記録
    $table_check = $g_db->getOne("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'b_cron_log'
    ");
    
    if ($table_check > 0) {
        $log_sql = "INSERT INTO b_cron_log (
            cron_type, status, message, execution_time, created_at
        ) VALUES (?, ?, ?, ?, ?)";
        
        $g_db->query($log_sql, [
            'update_popular_tags_cache',
            'success',
            "Processed $processed tags, inserted $inserted into cache",
            intval($total_time * 1000),
            time()
        ]);
    }
    
    logMessage("終了時刻: " . date('Y-m-d H:i:s'));
    
} catch (Exception $e) {
    logMessage("致命的エラー: " . $e->getMessage(), 'ERROR');
    
    // cronログにエラーを記録
    if (isset($g_db) && !DB::isError($g_db)) {
        $table_check = $g_db->getOne("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'b_cron_log'
        ");
        
        if ($table_check > 0) {
            $g_db->query(
                "INSERT INTO b_cron_log (cron_type, status, message, execution_time, created_at) 
                 VALUES (?, ?, ?, ?, ?)",
                [
                    'update_popular_tags_cache',
                    'error',
                    substr($e->getMessage(), 0, 500),
                    0,
                    time()
                ]
            );
        }
    }
    
    exit(1);
}
?>