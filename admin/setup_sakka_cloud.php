<?php
/**
 * 作家クラウドテーブルセットアップ
 */

require_once('../modern_config.php');

// 管理者権限チェック
if (!isAdmin()) {
    die('管理者権限が必要です');
}

$messages = [];
$errors = [];

// テーブル作成SQL
$create_tables = [
    'b_sakka_cloud' => "
        CREATE TABLE IF NOT EXISTS b_sakka_cloud (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author VARCHAR(255) NOT NULL,
            author_count INT NOT NULL DEFAULT 0,
            updated DATETIME NOT NULL,
            INDEX idx_author (author),
            INDEX idx_count (author_count),
            INDEX idx_updated (updated)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'b_author_stats_cache' => "
        CREATE TABLE IF NOT EXISTS b_author_stats_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author VARCHAR(255) NOT NULL,
            book_count INT NOT NULL DEFAULT 0,
            reader_count INT NOT NULL DEFAULT 0,
            review_count INT NOT NULL DEFAULT 0,
            average_rating DECIMAL(3,2) DEFAULT NULL,
            last_read_date DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_author_unique (author),
            INDEX idx_book_count (book_count),
            INDEX idx_reader_count (reader_count),
            INDEX idx_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

// テーブル作成実行
foreach ($create_tables as $table_name => $sql) {
    $result = $g_db->query($sql);
    if (DB::isError($result)) {
        $errors[] = "$table_name の作成に失敗: " . $result->getMessage();
    } else {
        $messages[] = "$table_name を作成しました";
    }
}

// データ生成・更新を実行するかチェック
if (isset($_GET['action'])) {
    require_once('../library/sakka_cloud_generator.php');
    $generator = new SakkaCloudGenerator();
    
    if ($_GET['action'] == 'generate') {
        // 初期データ生成
        $result = $generator->generate();
        if ($result['success']) {
            $messages[] = "b_author_stats_cache を生成しました（" . $result['count'] . "件）";
        } else {
            $errors[] = "b_author_stats_cache の生成に失敗: " . $result['error'];
        }
        
        $simple_result = $generator->generateSimple();
        if ($simple_result['success']) {
            $messages[] = "b_sakka_cloud を生成しました（" . $simple_result['count'] . "件）";
        } else {
            $errors[] = "b_sakka_cloud の生成に失敗: " . $simple_result['error'];
        }
        
        // キャッシュをクリア
        $generator->clearCache();
        $messages[] = "キャッシュをクリアしました";
        
    } elseif ($_GET['action'] == 'update') {
        // 手動更新（cronと同じ処理）
        try {
            // データ生成
            $result = $generator->generate();
            if ($result['success']) {
                $messages[] = "b_author_stats_cache を更新しました（" . $result['count'] . "件）";
            } else {
                $errors[] = "更新エラー: " . $result['error'];
            }
            
            // 簡易版も生成
            $simple_result = $generator->generateSimple();
            if ($simple_result['success']) {
                $messages[] = "b_sakka_cloud を更新しました（" . $simple_result['count'] . "件）";
            }
            
            // キャッシュをクリア
            $generator->clearCache();
            $messages[] = "キャッシュをクリアしました";
            
        } catch (Exception $e) {
            $errors[] = "エラー: " . $e->getMessage();
        }
        
    } elseif ($_GET['action'] == 'clear_cache') {
        // キャッシュのみクリア
        $generator->clearCache();
        $messages[] = "キャッシュをクリアしました";
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>作家クラウドセットアップ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">作家クラウドセットアップ</h1>
        
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
            <h2 class="text-lg font-bold mb-4">テーブル状態</h2>
            <?php
            // テーブル存在チェック
            $tables_to_check = ['b_sakka_cloud', 'b_author_stats_cache'];
            foreach ($tables_to_check as $table) {
                $check = $g_db->getOne("SHOW TABLES LIKE '$table'");
                if ($check) {
                    $count = $g_db->getOne("SELECT COUNT(*) FROM $table");
                    echo '<p class="mb-2"><span class="text-green-600">✓</span> ' . $table . ' (レコード数: ' . $count . ')</p>';
                } else {
                    echo '<p class="mb-2"><span class="text-red-600">✗</span> ' . $table . ' (未作成)</p>';
                }
            }
            ?>
            </div>
            
            <div class="mt-6">
                <h2 class="text-lg font-bold mb-4">データ管理</h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <a href="?action=generate" 
                       class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 text-center"
                       onclick="return confirm('初期データを生成します。既存のデータは上書きされます。よろしいですか？')">
                        <i class="fas fa-plus-circle mr-1"></i>初期生成
                    </a>
                    
                    <a href="?action=update" 
                       class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-center">
                        <i class="fas fa-sync-alt mr-1"></i>手動更新
                    </a>
                    
                    <a href="?action=clear_cache" 
                       class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 text-center">
                        <i class="fas fa-broom mr-1"></i>キャッシュクリア
                    </a>
                    
                    <a href="/sakka_cloud.php" target="_blank"
                       class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 text-center">
                        <i class="fas fa-cloud mr-1"></i>表示確認
                    </a>
                </div>
                
                <!-- 最終更新情報 -->
                <?php
                $last_update = $g_db->getOne("SELECT MAX(updated_at) FROM b_author_stats_cache");
                if ($last_update) {
                    echo '<div class="mt-4 p-3 bg-blue-50 rounded">';
                    echo '<i class="fas fa-info-circle text-blue-600 mr-2"></i>';
                    echo '最終更新: ' . date('Y年m月d日 H:i:s', strtotime($last_update));
                    echo '</div>';
                }
                ?>
                
                <!-- 統計情報 -->
                <?php
                $stats = $g_db->getRow("
                    SELECT 
                        COUNT(*) as total_authors,
                        SUM(book_count) as total_books,
                        SUM(reader_count) as total_readers
                    FROM b_author_stats_cache
                ", null, DB_FETCHMODE_ASSOC);
                
                if (!DB::isError($stats) && $stats['total_authors'] > 0) {
                    echo '<div class="mt-4 grid grid-cols-3 gap-3">';
                    echo '<div class="bg-gray-50 p-3 rounded text-center">';
                    echo '<div class="text-2xl font-bold text-gray-700">' . number_format($stats['total_authors']) . '</div>';
                    echo '<div class="text-xs text-gray-500">作家数</div>';
                    echo '</div>';
                    echo '<div class="bg-gray-50 p-3 rounded text-center">';
                    echo '<div class="text-2xl font-bold text-gray-700">' . number_format($stats['total_books']) . '</div>';
                    echo '<div class="text-xs text-gray-500">作品数</div>';
                    echo '</div>';
                    echo '<div class="bg-gray-50 p-3 rounded text-center">';
                    echo '<div class="text-2xl font-bold text-gray-700">' . number_format($stats['total_readers']) . '</div>';
                    echo '<div class="text-xs text-gray-500">のべ読者数</div>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
                
                <div class="mt-6 p-4 bg-gray-100 rounded">
                    <h3 class="font-semibold mb-2">cronジョブ設定（推奨）</h3>
                    <pre class="bg-white p-3 rounded text-xs overflow-x-auto">
# 毎日午前3時に作家クラウドを更新
0 3 * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/update_sakka_cloud.php > /dev/null 2>&1

# 実行コマンド（手動テスト用）
php <?php echo dirname(__DIR__); ?>/cron/update_sakka_cloud.php</pre>
                </div>
            </div>
        </div>
    </div>
</body>
</html>