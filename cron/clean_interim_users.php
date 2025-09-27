<?php
/**
 * 仮登録ユーザーのクリーンアップ
 * 1時間経過した仮登録ユーザーを削除
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// ディレクトリパスの設定
$currentDir = dirname(__FILE__);
$rootDir = realpath($currentDir . '/..');

// 設定ファイルの読み込み（cron用の設定）
require_once($rootDir . '/config.php');
require_once($rootDir . '/library/database.php');

// ログディレクトリの確認・作成
$logDir = $rootDir . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// ログファイル名（日付付き）
$logFile = $logDir . '/clean_interim_users_' . date('Y-m-d') . '.log';

// ログ出力関数
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("=== 仮登録ユーザークリーンアップ開始 ===");

try {
    global $g_db;
    
    // 1時間以上前に作成された仮登録ユーザーを検索
    $select_sql = "SELECT user_id, email, nickname, create_date 
                   FROM b_user 
                   WHERE status = ? 
                   AND create_date < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                   AND regist_date IS NULL";
    
    $expired_users = $g_db->getAll($select_sql, array(USER_STATUS_INTERIM), DB_FETCHMODE_ASSOC);
    
    if (DB::isError($expired_users)) {
        writeLog("ERROR: 期限切れユーザーの検索に失敗しました: " . $expired_users->getMessage());
        exit(1);
    }
    
    $count = count($expired_users);
    writeLog("期限切れ仮登録ユーザー数: $count");
    
    if ($count > 0) {
        // 削除前に詳細をログに記録
        foreach ($expired_users as $user) {
            writeLog("削除対象: user_id={$user['user_id']}, email={$user['email']}, nickname={$user['nickname']}, create_date={$user['create_date']}");
        }
        
        // 期限切れユーザーを削除
        $delete_sql = "DELETE FROM b_user 
                       WHERE status = ? 
                       AND create_date < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                       AND regist_date IS NULL";
        
        $result = $g_db->query($delete_sql, array(USER_STATUS_INTERIM));
        
        if (DB::isError($result)) {
            writeLog("ERROR: 期限切れユーザーの削除に失敗しました: " . $result->getMessage());
            exit(1);
        }
        
        $affected_rows = $g_db->affectedRows();
        writeLog("削除完了: {$affected_rows}件の仮登録ユーザーを削除しました");
        
        // 関連データのクリーンアップ（もしあれば）
        // 例: セッションデータ、一時ファイルなど
        
    } else {
        writeLog("削除対象の仮登録ユーザーはありません");
    }
    
    // 統計情報を出力
    $stats_sql = "SELECT 
                    COUNT(CASE WHEN create_date >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) THEN 1 END) as last_10m,
                    COUNT(CASE WHEN create_date >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 1 END) as last_30m,
                    COUNT(CASE WHEN create_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as last_1h,
                    COUNT(*) as total
                  FROM b_user 
                  WHERE status = ? AND regist_date IS NULL";
    
    $stats = $g_db->getRow($stats_sql, array(USER_STATUS_INTERIM), DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($stats)) {
        writeLog("現在の仮登録ユーザー統計:");
        writeLog("  - 過去10分: {$stats['last_10m']}件");
        writeLog("  - 過去30分: {$stats['last_30m']}件");
        writeLog("  - 過去1時間: {$stats['last_1h']}件");
        writeLog("  - 合計: {$stats['total']}件");
    }
    
} catch (Exception $e) {
    writeLog("ERROR: 予期しないエラーが発生しました: " . $e->getMessage());
    exit(1);
}

writeLog("=== 仮登録ユーザークリーンアップ完了 ===\n");

// 古いログファイルの削除（30日以上前のものを削除）
$oldLogs = glob($logDir . '/clean_interim_users_*.log');
foreach ($oldLogs as $oldLog) {
    if (filemtime($oldLog) < strtotime('-30 days')) {
        unlink($oldLog);
        writeLog("古いログファイルを削除: " . basename($oldLog));
    }
}

exit(0);
?>