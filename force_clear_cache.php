<?php
/**
 * 強制キャッシュクリア（管理者用）
 * すべてのキャッシュファイルを物理的に削除
 */

require_once('modern_config.php');

// 管理者権限チェック
if (!isAdmin()) {
    die('管理者権限が必要です');
}

$messages = [];
$errors = [];

// キャッシュディレクトリ
$cache_dir = dirname(__FILE__) . '/cache/';

// キャッシュファイルを全削除
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '*');
    $deleted_count = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if (unlink($file)) {
                $deleted_count++;
            } else {
                $errors[] = "削除失敗: " . basename($file);
            }
        }
    }
    
    $messages[] = "$deleted_count 個のキャッシュファイルを削除しました";
} else {
    $errors[] = "キャッシュディレクトリが見つかりません: $cache_dir";
}

// キャッシュライブラリ経由でもクリア
require_once('library/cache.php');
$cache = getCache();

// 既知のキャッシュキーをすべてクリア
$cache_keys = [
    // みんなの読書活動
    'recent_activities_formatted_v8',
    'recent_activities_formatted_v8_backup',
    'recent_activities_formatted_v7',
    'recent_activities_formatted_v7_backup',
    
    // 人気の本
    'popular_books_v2',
    'popular_books_v2_backup',
    'popular_books_v1',
    'popular_books_v1_backup',
    
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
    'new_reviews_v1',
    
    // 作家クラウド
    'sakka_cloud_data_100',
    'sakka_cloud_data_150',
    'sakka_cloud_data_200',
    'sakka_cloud_html',
    'author_stats_cache',
    'sakka_cloud_update_check',
];

$cleared_keys = 0;
foreach ($cache_keys as $key) {
    if ($cache->delete($key)) {
        $cleared_keys++;
    }
}

$messages[] = "$cleared_keys 個のキャッシュキーをクリアしました";

// 全キャッシュをクリア（SimpleCache::clear()メソッドを使用）
if (method_exists($cache, 'clear')) {
    $cache->clear();
    $messages[] = "すべてのキャッシュをクリアしました（clear()メソッド）";
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>強制キャッシュクリア</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">強制キャッシュクリア</h1>
        
        <?php if (!empty($messages)): ?>
        <div class="bg-green-50 border border-green-200 rounded p-4 mb-4">
            <?php foreach ($messages as $msg): ?>
            <p class="text-green-700">✓ <?php echo htmlspecialchars($msg); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded p-4 mb-4">
            <?php foreach ($errors as $err): ?>
            <p class="text-red-700">✗ <?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold mb-4">キャッシュの状態</h2>
            <p class="text-sm text-gray-600 mb-4">
                すべてのキャッシュファイルとキャッシュキーをクリアしました。<br>
                次回アクセス時に新しいデータが生成されます。
            </p>
            
            <div class="flex gap-3">
                <a href="/" class="inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    ホームページで確認
                </a>
                <a href="/clear_activities_cache.php" class="inline-block px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                    通常のキャッシュクリア
                </a>
            </div>
        </div>
    </div>
</body>
</html>