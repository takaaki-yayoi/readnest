<?php
/**
 * みんなの読書活動のキャッシュをクリアして再生成
 */

require_once('modern_config.php');

// 管理者権限チェック
if (!isAdmin()) {
    die('管理者権限が必要です');
}

// キャッシュライブラリを読み込み
require_once(dirname(__FILE__) . '/library/cache.php');
$cache = getCache();

// 関連するすべてのキャッシュキーをクリア
$cache_keys = [
    // みんなの読書活動
    'recent_activities_formatted_v8',
    'recent_activities_formatted_v8_backup',
    'recent_activities_formatted_v7',
    'recent_activities_formatted_v7_backup',
    'recent_activities_formatted_v6',
    'recent_activities_formatted_v5',
    'recent_activities_formatted_v4',
    'recent_activities_formatted_v3',
    'recent_activities_v6',
    
    // 人気の本
    'popular_books_v2',
    'popular_books_v2_backup',
    
    // 人気のタグ
    'popular_tags_v1',
    'popular_tags_v1_backup',
    
    // お知らせ
    'latest_announcement_v1',
    'latest_announcement_v1_backup',
    'announcement_type_column_exists',
    
    // ユーザー統計
    'site_stats_v1',
    
    // 新着レビュー
    'new_reviews_v2',
];

$cleared = 0;
foreach ($cache_keys as $key) {
    if ($cache->delete($key)) {
        $cleared++;
    }
}

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>キャッシュクリア</title></head>";
echo "<body style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>みんなの読書活動のキャッシュをクリア</h1>";
echo "<p>{$cleared}個のキャッシュキーをクリアしました。</p>";
echo "<p><strong>重要：</strong>ホームページ（/）にアクセスして新しいキャッシュを生成してください。</p>";
echo "<a href='/' style='display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>ホームページへ</a>";
echo "</body></html>";
?>