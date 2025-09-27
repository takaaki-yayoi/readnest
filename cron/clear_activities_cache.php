<?php
/**
 * 活動キャッシュの定期クリア
 * ニックネーム表示問題を防ぐため、問題のあるキャッシュを検出してクリア
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/cache.php');
require_once(dirname(__DIR__) . '/library/nickname_helpers.php');
require_once(dirname(__DIR__) . '/library/logger.php');

// データベース接続
$g_db = DB_Connect();

// キャッシュ取得
$cache = getCache();

// 実行ログ
$log_entries = [];
$logFile = dirname(__DIR__) . '/logs/activities_cache_' . date('Y-m-d') . '.log';

function addLog($message) {
    global $log_entries, $logFile;
    $log_entries[] = $message;
    logInfo($message, $logFile);
}

addLog("活動キャッシュクリア処理開始");

try {
    // 活動キャッシュを確認
    $cacheKey = 'recent_activities_formatted_v3';
    $activitiesCache = $cache->get($cacheKey);
    
    if ($activitiesCache !== false && is_array($activitiesCache)) {
        $totalCount = count($activitiesCache);
        $invalidCount = 0;
        $problematicUsers = [];
        
        // 無効なニックネームをチェック
        foreach ($activitiesCache as $activity) {
            if (!isset($activity['user_name']) || !isValidNickname($activity['user_name'])) {
                $invalidCount++;
                $problematicUsers[] = $activity['user_id'] ?? 'unknown';
            }
        }
        
        addLog("キャッシュ確認: 総数={$totalCount}, 無効={$invalidCount}");
        
        // 20%以上が無効、または5件以上の無効なエントリーがある場合はクリア
        $invalidRate = $totalCount > 0 ? ($invalidCount / $totalCount) * 100 : 0;
        
        if ($invalidRate >= 20 || $invalidCount >= 5) {
            addLog("無効率が高いためキャッシュをクリア (無効率: " . round($invalidRate, 2) . "%)");
            addLog("問題のあるユーザーID: " . implode(', ', array_unique($problematicUsers)));
            
            // キャッシュクリア
            $cache->delete($cacheKey);
            $cache->delete($cacheKey . '_backup');
            
            addLog("キャッシュをクリアしました");
            
            // 問題のあるユーザーのニックネームを確認
            foreach (array_unique($problematicUsers) as $userId) {
                if ($userId !== 'unknown') {
                    $userInfo = getUserInformation($userId);
                    if ($userInfo) {
                        $nickname = $userInfo['nickname'] ?? 'NULL';
                        addLog("ユーザー {$userId} のニックネーム: '{$nickname}'");
                    }
                }
            }
        } else {
            addLog("キャッシュは正常です (無効率: " . round($invalidRate, 2) . "%)");
        }
    } else {
        addLog("活動キャッシュが存在しません");
    }
    
    // 古いバージョンのキャッシュも削除
    $oldCacheKeys = [
        'recent_activities_formatted_v2',
        'recent_activities_formatted_v2_backup',
        'recent_activities_formatted_v1',
        'recent_activities_formatted_v1_backup'
    ];
    
    foreach ($oldCacheKeys as $oldKey) {
        if ($cache->delete($oldKey)) {
            addLog("古いキャッシュを削除: {$oldKey}");
        }
    }
    
} catch (Exception $e) {
    addLog("エラー: " . $e->getMessage());
}

addLog("活動キャッシュクリア処理完了");

// ログファイルのローテーション
rotateLogs(dirname(__DIR__) . '/logs', 30);

// 実行結果を返す（cronで使用する場合）
exit(0);