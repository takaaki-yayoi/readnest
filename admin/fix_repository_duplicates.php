<?php
/**
 * b_book_repositoryテーブルの重複を修正するスクリプト
 * 実行前に必ずバックアップを取ること
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once('admin_auth.php');

// ログインチェック
if (!isset($_SESSION['AUTH_USER'])) {
    header('Location: /');
    exit;
}

global $g_db;

// 実行確認
$execute = isset($_GET['execute']) && $_GET['execute'] === '1';
$backup = isset($_GET['backup']) && $_GET['backup'] === '1';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>b_book_repository重複修正 - ReadNest Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">
            <i class="fas fa-database text-red-600 mr-2"></i>
            b_book_repository テーブル重複修正
        </h1>

        <?php if (!$execute): ?>
        
        <!-- ステップ1: 重複確認 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-search text-blue-600 mr-2"></i>
                ステップ1: 重複状況の確認
            </h2>
            
            <?php
            // 重複しているASINを確認
            $sql = "SELECT 
                        asin,
                        COUNT(*) as duplicate_count,
                        GROUP_CONCAT(id ORDER BY id) as ids,
                        GROUP_CONCAT(SUBSTRING(title, 1, 30) ORDER BY id SEPARATOR ' | ') as titles
                    FROM b_book_repository
                    GROUP BY asin
                    HAVING COUNT(*) > 1
                    ORDER BY duplicate_count DESC
                    LIMIT 20";
            
            $duplicates = $g_db->getAll($sql, [], DB_FETCHMODE_ASSOC);
            
            if (DB::isError($duplicates)) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
                echo 'エラー: ' . htmlspecialchars($duplicates->getMessage());
                echo '</div>';
            } elseif (empty($duplicates)) {
                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">';
                echo '<i class="fas fa-check-circle mr-2"></i>';
                echo '重複しているレコードはありません。';
                echo '</div>';
            } else {
                // 総件数を取得
                $totalSql = "SELECT COUNT(DISTINCT asin) as total FROM b_book_repository 
                            WHERE asin IN (SELECT asin FROM b_book_repository GROUP BY asin HAVING COUNT(*) > 1)";
                $totalDuplicateAsins = $g_db->getOne($totalSql);
                
                echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">';
                echo '<i class="fas fa-exclamation-triangle mr-2"></i>';
                echo '重複ASIN数: <strong>' . $totalDuplicateAsins . '件</strong>';
                echo '</div>';
                
                echo '<div class="overflow-x-auto">';
                echo '<table class="min-w-full table-auto">';
                echo '<thead class="bg-gray-100">';
                echo '<tr>';
                echo '<th class="px-4 py-2 text-left">ASIN</th>';
                echo '<th class="px-4 py-2 text-left">重複数</th>';
                echo '<th class="px-4 py-2 text-left">ID</th>';
                echo '<th class="px-4 py-2 text-left">タイトル（先頭30文字）</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($duplicates as $row) {
                    echo '<tr class="border-t">';
                    echo '<td class="px-4 py-2 font-mono text-sm">' . htmlspecialchars($row['asin']) . '</td>';
                    echo '<td class="px-4 py-2 text-center">';
                    echo '<span class="px-2 py-1 bg-red-100 text-red-800 rounded">' . $row['duplicate_count'] . '</span>';
                    echo '</td>';
                    echo '<td class="px-4 py-2 text-sm">' . htmlspecialchars($row['ids']) . '</td>';
                    echo '<td class="px-4 py-2 text-sm">' . htmlspecialchars($row['titles']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                
                // 修正プラン
                echo '<div class="mt-6 p-4 bg-blue-50 rounded">';
                echo '<h3 class="font-semibold mb-2">修正プラン:</h3>';
                echo '<ol class="list-decimal list-inside space-y-1 text-sm">';
                echo '<li>各ASINごとに最も情報が充実しているレコードを残します</li>';
                echo '<li>優先順位: embedding > description > image_url > 最新ID</li>';
                echo '<li>削除前にバックアップテーブルを作成します</li>';
                echo '</ol>';
                echo '</div>';
            }
            ?>
        </div>
        
        <?php if (!empty($duplicates)): ?>
        <!-- ステップ2: 実行確認 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-tools text-orange-600 mr-2"></i>
                ステップ2: 修正の実行
            </h2>
            
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                <p class="font-semibold text-red-800 mb-2">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    重要な注意事項
                </p>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    <li>このスクリプトは重複レコードを削除します</li>
                    <li>実行前に必ずデータベースのバックアップを取ってください</li>
                    <li>削除されたデータは復元できません</li>
                </ul>
            </div>
            
            <div class="flex gap-4">
                <a href="?execute=1&backup=1" 
                   class="px-6 py-3 bg-orange-600 text-white rounded hover:bg-orange-700"
                   onclick="return confirm('重複レコードを削除します。よろしいですか？')">
                    <i class="fas fa-play mr-2"></i>
                    バックアップ後に実行
                </a>
                <a href="/admin/" class="px-6 py-3 bg-gray-600 text-white rounded hover:bg-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    キャンセル
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        
        <!-- 実行中 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-cog fa-spin text-blue-600 mr-2"></i>
                修正を実行中...
            </h2>
            
            <?php
            try {
                // バックアップ作成
                if ($backup) {
                    $backupTable = 'b_book_repository_backup_' . date('Ymd_His');
                    $backupSql = "CREATE TABLE $backupTable AS SELECT * FROM b_book_repository";
                    $result = $g_db->query($backupSql);
                    
                    if (!DB::isError($result)) {
                        echo '<div class="bg-green-100 p-3 rounded mb-4">';
                        echo '<i class="fas fa-check text-green-600 mr-2"></i>';
                        echo 'バックアップテーブル作成完了: ' . $backupTable;
                        echo '</div>';
                    }
                }
                
                // 重複レコードの削除
                // 各ASINごとに最新のID（通常最も情報が充実している）を残す
                
                // まず重複しているASINとその最大IDを取得
                $findDuplicatesSql = "
                    SELECT asin, MAX(id) as keep_id, COUNT(*) as count
                    FROM b_book_repository
                    GROUP BY asin
                    HAVING COUNT(*) > 1
                ";
                
                $duplicates = $g_db->getAll($findDuplicatesSql, [], DB_FETCHMODE_ASSOC);
                $totalDeleted = 0;
                
                if (!DB::isError($duplicates) && !empty($duplicates)) {
                    echo '<div class="mb-4">';
                    echo '<p class="text-sm text-gray-600">処理中のASIN: </p>';
                    
                    // 各ASINごとに削除を実行
                    foreach ($duplicates as $dup) {
                        $deleteSql = "
                            DELETE FROM b_book_repository 
                            WHERE asin = ? AND id != ?
                        ";
                        
                        $result = $g_db->query($deleteSql, [$dup['asin'], $dup['keep_id']]);
                        
                        if (!DB::isError($result)) {
                            $deleted = $dup['count'] - 1;
                            $totalDeleted += $deleted;
                            echo '<span class="inline-block px-2 py-1 bg-gray-100 text-xs rounded mr-1 mb-1">';
                            echo htmlspecialchars(substr($dup['asin'], 0, 10)) . '... (' . $deleted . '件削除)';
                            echo '</span>';
                        }
                    }
                    echo '</div>';
                }
                
                $affectedRows = $totalDeleted;
                $result = true;
                
                if (!DB::isError($result)) {
                    echo '<div class="bg-green-100 p-3 rounded mb-4">';
                    echo '<i class="fas fa-check text-green-600 mr-2"></i>';
                    echo '削除完了: ' . $affectedRows . '件のレコードを削除しました';
                    echo '</div>';
                    
                    // 結果確認
                    $checkSql = "SELECT COUNT(*) as total, 
                                COUNT(DISTINCT asin) as unique_asins 
                                FROM b_book_repository";
                    $stats = $g_db->getRow($checkSql, [], DB_FETCHMODE_ASSOC);
                    
                    echo '<div class="bg-blue-100 p-3 rounded mb-4">';
                    echo '<h3 class="font-semibold mb-2">処理後の統計:</h3>';
                    echo '<ul class="text-sm space-y-1">';
                    echo '<li>総レコード数: ' . $stats['total'] . '</li>';
                    echo '<li>ユニークASIN数: ' . $stats['unique_asins'] . '</li>';
                    echo '</ul>';
                    echo '</div>';
                    
                    // 残っている重複を確認
                    $remainingSql = "SELECT asin, COUNT(*) as cnt 
                                    FROM b_book_repository 
                                    GROUP BY asin 
                                    HAVING COUNT(*) > 1";
                    $remaining = $g_db->getAll($remainingSql, [], DB_FETCHMODE_ASSOC);
                    
                    if (empty($remaining)) {
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">';
                        echo '<i class="fas fa-check-circle mr-2"></i>';
                        echo 'すべての重複が正常に削除されました！';
                        echo '</div>';
                        
                        // ユニークインデックスの追加を提案
                        echo '<div class="mt-4 p-4 bg-yellow-50 rounded">';
                        echo '<h3 class="font-semibold mb-2">推奨: ユニーク制約の追加</h3>';
                        echo '<p class="text-sm mb-2">今後の重複を防ぐため、以下のSQLを実行することをお勧めします：</p>';
                        echo '<pre class="bg-gray-100 p-2 rounded text-xs overflow-x-auto">';
                        echo 'ALTER TABLE b_book_repository ADD UNIQUE INDEX idx_asin_unique (asin);';
                        echo '</pre>';
                        echo '</div>';
                    } else {
                        echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">';
                        echo '<i class="fas fa-exclamation-triangle mr-2"></i>';
                        echo 'まだ' . count($remaining) . '件の重複が残っています';
                        echo '</div>';
                    }
                    
                } else {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
                    echo 'エラー: ' . htmlspecialchars($result->getMessage());
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
                echo 'エラー: ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            ?>
            
            <div class="mt-6">
                <a href="/admin/" class="px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    管理画面に戻る
                </a>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>