<?php
/**
 * 作家クラウドデータ再生成
 */

require_once('../modern_config.php');
require_once('../library/sakka_cloud_generator.php');

// 管理者権限チェック
if (!isAdmin()) {
    die('管理者権限が必要です');
}

$messages = [];
$errors = [];

// キャッシュをクリア
require_once('../library/cache.php');
$cache = getCache();
$cache->clear(); // すべてのキャッシュをクリア

$messages[] = 'キャッシュをクリアしました';

// ジェネレータを初期化
$generator = new SakkaCloudGenerator();

// データ生成
try {
    // b_author_stats_cacheを生成
    $result = $generator->generate();
    if ($result['success']) {
        $messages[] = 'b_author_stats_cache を生成しました（' . $result['count'] . '件）';
    } else {
        $errors[] = 'b_author_stats_cache の生成に失敗: ' . $result['error'];
    }
    
    // b_sakka_cloudを生成
    $simple_result = $generator->generateSimple();
    if ($simple_result['success']) {
        $messages[] = 'b_sakka_cloud を生成しました（' . $simple_result['count'] . '件）';
    } else {
        $errors[] = 'b_sakka_cloud の生成に失敗: ' . $simple_result['error'];
    }
    
} catch (Exception $e) {
    $errors[] = 'エラー: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>作家クラウド再生成</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">作家クラウドデータ再生成</h1>
        
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
            <h2 class="text-lg font-bold mb-4">生成結果</h2>
            <?php
            // 結果を確認
            $stats_count = $g_db->getOne("SELECT COUNT(*) FROM b_author_stats_cache");
            $cloud_count = $g_db->getOne("SELECT COUNT(*) FROM b_sakka_cloud");
            
            echo '<p class="mb-2">b_author_stats_cache: ' . number_format($stats_count) . '件</p>';
            echo '<p class="mb-4">b_sakka_cloud: ' . number_format($cloud_count) . '件</p>';
            
            // サンプルデータ
            $sample = $g_db->getAll("
                SELECT author, reader_count, book_count 
                FROM b_author_stats_cache 
                ORDER BY reader_count DESC 
                LIMIT 10
            ", null, DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($sample) && !empty($sample)) {
                echo '<h3 class="font-bold mb-2">上位10名の作家:</h3>';
                echo '<table class="w-full text-sm">';
                echo '<thead><tr class="bg-gray-100">';
                echo '<th class="p-2 text-left">作家名</th>';
                echo '<th class="p-2 text-right">読者数</th>';
                echo '<th class="p-2 text-right">作品数</th>';
                echo '</tr></thead><tbody>';
                
                foreach ($sample as $row) {
                    echo '<tr class="border-b">';
                    echo '<td class="p-2">' . htmlspecialchars($row['author']) . '</td>';
                    echo '<td class="p-2 text-right">' . number_format($row['reader_count']) . '</td>';
                    echo '<td class="p-2 text-right">' . number_format($row['book_count']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            }
            ?>
            
            <div class="mt-6">
                <a href="/sakka_cloud.php" class="inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    作家クラウドを表示
                </a>
            </div>
        </div>
    </div>
</body>
</html>