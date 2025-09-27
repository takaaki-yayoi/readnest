<?php
/**
 * バックアップから著者情報を復元するスクリプト
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once('admin_auth.php');
requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    $backup_table = $_POST['backup_table'] ?? '';
    
    // テーブル名の検証
    if (!preg_match('/^[a-z0-9_]+$/i', $backup_table)) {
        $error = '無効なテーブル名です';
    } else {
        // テーブルの存在確認
        $check_sql = "SHOW TABLES LIKE ?";
        $exists = $g_db->getOne($check_sql, [$backup_table]);
        
        if (!$exists) {
            $error = 'バックアップテーブルが見つかりません';
        } else {
            // 復元処理
            $restore_sql = "
                UPDATE b_book_repository current
                INNER JOIN {$backup_table} backup ON current.asin = backup.asin
                SET current.author = backup.author
                WHERE backup.author IS NOT NULL 
                AND backup.author != ''
                AND (current.author IS NULL OR current.author = '')
            ";
            
            $result = $g_db->query($restore_sql);
            
            if (DB::isError($result)) {
                $error = '復元エラー: ' . $result->getMessage();
            } else {
                // 影響を受けた行数を取得
                $affected_sql = "
                    SELECT COUNT(*) FROM b_book_repository current
                    INNER JOIN {$backup_table} backup ON current.asin = backup.asin
                    WHERE backup.author = current.author
                    AND backup.author IS NOT NULL 
                    AND backup.author != ''
                ";
                $affected = $g_db->getOne($affected_sql);
                
                $message = "著者情報を復元しました。復元件数: " . number_format($affected) . "件";
                
                // ログを記録
                $logDir = dirname(__DIR__) . '/logs';
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0777, true);
                }
                $logFile = $logDir . '/author_restore_' . date('Y-m-d_His') . '.log';
                $logMessage = "[" . date('Y-m-d H:i:s') . "] ";
                $logMessage .= "Restored authors from {$backup_table}. ";
                $logMessage .= "Affected rows: {$affected}\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
            }
        }
    }
}

// 現在の状況を再確認
$status_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN author IS NOT NULL AND author != '' THEN 1 ELSE 0 END) as has_author,
        SUM(CASE WHEN author IS NULL OR author = '' THEN 1 ELSE 0 END) as no_author
    FROM b_book_repository
";
$status = $g_db->getRow($status_sql, null, DB_FETCHMODE_ASSOC);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>著者情報復元</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">著者情報復元結果</h1>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <p class="font-bold">成功</p>
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <p class="font-bold">エラー</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">現在のb_book_repository状況</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="p-4 bg-gray-50 rounded">
                    <div class="text-2xl font-bold"><?php echo number_format($status['total']); ?></div>
                    <div class="text-sm text-gray-600">総レコード数</div>
                </div>
                <div class="p-4 bg-green-50 rounded">
                    <div class="text-2xl font-bold text-green-600">
                        <?php echo number_format($status['has_author']); ?>
                    </div>
                    <div class="text-sm text-gray-600">著者情報あり</div>
                    <div class="text-xs text-gray-500">
                        (<?php echo round($status['has_author'] / $status['total'] * 100, 1); ?>%)
                    </div>
                </div>
                <div class="p-4 bg-red-50 rounded">
                    <div class="text-2xl font-bold text-red-600">
                        <?php echo number_format($status['no_author']); ?>
                    </div>
                    <div class="text-sm text-gray-600">著者情報なし</div>
                    <div class="text-xs text-gray-500">
                        (<?php echo round($status['no_author'] / $status['total'] * 100, 1); ?>%)
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex gap-4">
            <a href="/admin/check_author_loss.php" 
               class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                調査画面に戻る
            </a>
            <a href="/admin/sync_authors.php" 
               class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                同期処理を実行
            </a>
            <a href="/admin/missing_authors.php" 
               class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                手動編集画面へ
            </a>
        </div>
    </div>
</body>
</html>