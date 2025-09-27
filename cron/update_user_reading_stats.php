<?php
/**
 * Cron job to update user reading statistics
 * This ensures read_books_month and read_books_total are accurate
 */

// Load configuration
require_once(dirname(__FILE__) . '/../modern_config.php');
require_once(dirname(__FILE__) . '/../library/logger.php');

// Start logging
$log_file = 'update_user_reading_stats_' . date('Y-m-d') . '.log';
logMessage("Starting user reading stats update", 'INFO', $log_file);

try {
    global $g_db;
    
    // Get all active users
    $users_sql = "SELECT user_id FROM b_user WHERE status = 1";
    $users = $g_db->getAll($users_sql);
    
    if (DB::isError($users)) {
        logMessage("Error getting users: " . $users->getMessage(), 'ERROR', $log_file);
        exit(1);
    }
    
    $updated_count = 0;
    $error_count = 0;
    
    logMessage("Found " . count($users) . " active users to update", 'INFO', $log_file);
    
    // Update each user's statistics
    foreach ($users as $user) {
        $user_id = $user['user_id'];
        
        try {
            // Call the updateUserReadingStat function
            $result = updateUserReadingStat($user_id);
            
            if ($result === DB_OPERATE_SUCCESS) {
                $updated_count++;
            } else {
                $error_count++;
                logMessage("Failed to update user_id: $user_id", 'ERROR', $log_file);
            }
            
            // Add a small delay to avoid overloading the server
            if ($updated_count % 100 == 0) {
                logMessage("Progress: $updated_count users updated", 'INFO', $log_file);
                sleep(1);
            }
            
        } catch (Exception $e) {
            $error_count++;
            logMessage("Exception updating user_id $user_id: " . $e->getMessage(), 'ERROR', $log_file);
        }
    }
    
    logMessage("Update complete. Updated: $updated_count, Errors: $error_count", 'INFO', $log_file);
    
    // Clear ranking cache to ensure fresh data
    if (isset($memcache)) {
        $cache_key = 'user_ranking_cache_read_books_month';
        $memcache->delete($cache_key);
        $cache_key = 'user_ranking_cache_read_books_total';
        $memcache->delete($cache_key);
        
        logMessage("Cleared ranking caches", 'INFO', $log_file);
    }
    
} catch (Exception $e) {
    logMessage("Fatal error: " . $e->getMessage(), 'ERROR', $log_file);
    exit(1);
}

logMessage("User reading stats update completed successfully", 'INFO', $log_file);
?>