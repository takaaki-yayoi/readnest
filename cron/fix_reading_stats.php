<?php
/**
 * 全ユーザーの読書統計不整合を修正するCronジョブ
 * 
 * このスクリプトは以下の処理を行います：
 * 1. 読書統計の不整合を検出
 * 2. 不整合があるユーザーの統計を修正
 * 3. 結果をログに記録
 */

// Load configuration
require_once(dirname(__FILE__) . '/../modern_config.php');
require_once(dirname(__FILE__) . '/../library/logger.php');

// Start logging
$log_file = 'fix_reading_stats_' . date('Y-m-d') . '.log';
logMessage("Starting reading stats fix", 'INFO', $log_file);

try {
    global $g_db;
    
    // 問題のあるユーザーをチェック
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');
    
    // 今月の読了イベントがあるのにread_books_monthが正しくないユーザーを検出
    $check_sql = "
        SELECT 
            u.user_id,
            u.nickname,
            u.read_books_month as db_month_count,
            COUNT(DISTINCT be.book_id) as actual_month_count,
            u.read_books_total as db_total_count,
            (SELECT COUNT(*) FROM b_book_list bl 
             WHERE bl.user_id = u.user_id 
             AND bl.status IN (" . READING_FINISH . ", " . READ_BEFORE . ")) as actual_total_count
        FROM b_user u
        LEFT JOIN b_book_event be ON u.user_id = be.user_id 
            AND be.event = " . READING_FINISH . "
            AND be.event_date BETWEEN ? AND ?
        WHERE u.status = 1
        GROUP BY u.user_id
        HAVING db_month_count != actual_month_count 
            OR db_total_count != actual_total_count
        ORDER BY actual_month_count DESC
    ";
    
    $issues = $g_db->getAll($check_sql, array($month_start, $month_end), DB_FETCHMODE_ASSOC);
    
    if (DB::isError($issues)) {
        logMessage("Error detecting issues: " . $issues->getMessage(), 'ERROR', $log_file);
        exit(1);
    }
    
    $total_issues = count($issues);
    logMessage("Found {$total_issues} users with inconsistent reading stats", 'INFO', $log_file);
    
    if ($total_issues > 0) {
        $fixed_count = 0;
        $error_count = 0;
        
        // 問題のあるユーザーのみ統計を更新
        foreach ($issues as $issue) {
            $result = updateUserReadingStat($issue['user_id']);
            if ($result === DB_OPERATE_SUCCESS) {
                $fixed_count++;
                logMessage(
                    sprintf(
                        "Fixed user_id=%d (%s): month %d→%d, total %d→%d",
                        $issue['user_id'],
                        $issue['nickname'],
                        $issue['db_month_count'],
                        $issue['actual_month_count'],
                        $issue['db_total_count'],
                        $issue['actual_total_count']
                    ),
                    'INFO',
                    $log_file
                );
            } else {
                $error_count++;
                logMessage("Failed to fix user_id: " . $issue['user_id'], 'ERROR', $log_file);
            }
        }
        
        logMessage("Fix complete. Fixed: {$fixed_count}, Errors: {$error_count}", 'INFO', $log_file);
        
        // Clear ranking cache to ensure fresh data
        if (isset($memcache)) {
            $cache_key = 'user_ranking_cache_read_books_month';
            $memcache->delete($cache_key);
            $cache_key = 'user_ranking_cache_read_books_total';
            $memcache->delete($cache_key);
            
            logMessage("Cleared ranking caches", 'INFO', $log_file);
        }
        
        // Cron実行ログを記録
        if (function_exists('recordCronExecution')) {
            $message = "Fixed {$fixed_count} users' reading stats";
            if ($error_count > 0) {
                $message .= " ({$error_count} errors)";
                recordCronExecution('fix_reading_stats', 'partial', $message, 0);
            } else {
                recordCronExecution('fix_reading_stats', 'success', $message, 0);
            }
        }
    } else {
        logMessage("No inconsistencies found. All reading stats are correct.", 'INFO', $log_file);
        
        if (function_exists('recordCronExecution')) {
            recordCronExecution('fix_reading_stats', 'success', 'No inconsistencies found', 0);
        }
    }
    
} catch (Exception $e) {
    logMessage("Fatal error: " . $e->getMessage(), 'ERROR', $log_file);
    
    if (function_exists('recordCronExecution')) {
        recordCronExecution('fix_reading_stats', 'error', $e->getMessage(), 0);
    }
    
    exit(1);
}

logMessage("Reading stats fix completed successfully", 'INFO', $log_file);
?>