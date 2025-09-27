<?php
/**
 * 著者情報の消失を調査するスクリプト
 * バックアップテーブルと現在のテーブルを比較
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once('admin_auth.php');
requireAdmin();

// バックアップテーブルが存在するか確認
$tables_sql = "SHOW TABLES LIKE '%book_repository%'";
$tables_result = $g_db->getAll($tables_sql, null, DB_FETCHMODE_ORDERED);

$tables = [];
if (!DB::isError($tables_result)) {
    foreach ($tables_result as $row) {
        $tables[] = $row[0];
    }
}

$backup_tables = array_filter($tables, function($table) {
    return $table !== 'b_book_repository' && strpos($table, 'book_repository') !== false;
});

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>著者情報消失調査</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">著者情報消失の調査</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">利用可能なテーブル</h2>
            <div class="space-y-2">
                <p class="text-gray-700">
                    <strong>現在のテーブル:</strong> b_book_repository
                </p>
                <?php if (!empty($backup_tables)): ?>
                    <p class="text-gray-700">
                        <strong>バックアップテーブル:</strong>
                    </p>
                    <ul class="list-disc list-inside ml-4">
                        <?php foreach ($backup_tables as $table): ?>
                            <li><?php echo htmlspecialchars($table); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-red-600">
                        バックアップテーブルが見つかりません
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($backup_tables)): ?>
            <?php foreach ($backup_tables as $backup_table): ?>
                <?php
                // テーブル名の検証（SQLインジェクション対策）
                if (!preg_match('/^[a-z0-9_]+$/i', $backup_table)) {
                    continue;
                }
                
                // バックアップと現在のテーブルを比較
                $comparison_sql = "
                    SELECT 
                        COUNT(*) as total_in_backup,
                        SUM(CASE WHEN backup.author IS NOT NULL AND backup.author != '' THEN 1 ELSE 0 END) as has_author_in_backup,
                        SUM(CASE WHEN current.asin IS NOT NULL THEN 1 ELSE 0 END) as exists_in_current,
                        SUM(CASE 
                            WHEN backup.author IS NOT NULL AND backup.author != ''
                            AND current.asin IS NOT NULL
                            AND (current.author IS NULL OR current.author = '')
                            THEN 1 ELSE 0 
                        END) as lost_author,
                        SUM(CASE 
                            WHEN backup.author IS NOT NULL AND backup.author != ''
                            AND current.asin IS NULL
                            THEN 1 ELSE 0 
                        END) as deleted_with_author
                    FROM {$backup_table} backup
                    LEFT JOIN b_book_repository current ON backup.asin = current.asin
                ";
                
                $stats = $g_db->getRow($comparison_sql, null, DB_FETCHMODE_ASSOC);
                if (DB::isError($stats)) {
                    echo '<div class="bg-red-100 p-4 rounded">'
                        . 'エラー: ' . htmlspecialchars($stats->getMessage())
                        . '</div>';
                    continue;
                }
                
                // 失われた著者情報のサンプル
                $lost_sample_sql = "
                    SELECT 
                        backup.asin,
                        backup.title,
                        backup.author as backup_author,
                        current.author as current_author
                    FROM {$backup_table} backup
                    INNER JOIN b_book_repository current ON backup.asin = current.asin
                    WHERE backup.author IS NOT NULL AND backup.author != ''
                    AND (current.author IS NULL OR current.author = '')
                    LIMIT 10
                ";
                
                $lost_samples = $g_db->getAll($lost_sample_sql, null, DB_FETCHMODE_ASSOC);
                if (DB::isError($lost_samples)) {
                    $lost_samples = [];
                }
                ?>
                
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">
                        比較結果: <?php echo htmlspecialchars($backup_table); ?>
                    </h2>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="p-4 bg-gray-50 rounded">
                            <div class="text-2xl font-bold"><?php echo number_format($stats['total_in_backup']); ?></div>
                            <div class="text-sm text-gray-600">バックアップ総数</div>
                        </div>
                        <div class="p-4 bg-green-50 rounded">
                            <div class="text-2xl font-bold text-green-600">
                                <?php echo number_format($stats['has_author_in_backup']); ?>
                            </div>
                            <div class="text-sm text-gray-600">バックアップ著者あり</div>
                        </div>
                        <div class="p-4 bg-red-50 rounded">
                            <div class="text-2xl font-bold text-red-600">
                                <?php echo number_format($stats['lost_author']); ?>
                            </div>
                            <div class="text-sm text-gray-600">著者情報消失</div>
                        </div>
                        <div class="p-4 bg-orange-50 rounded">
                            <div class="text-2xl font-bold text-orange-600">
                                <?php echo number_format($stats['deleted_with_author']); ?>
                            </div>
                            <div class="text-sm text-gray-600">削除された本</div>
                        </div>
                    </div>
                    
                    <?php if ($stats['lost_author'] > 0): ?>
                        <div class="p-4 bg-red-50 border-l-4 border-red-400 mb-4">
                            <p class="text-red-800">
                                <strong>警告:</strong> 
                                <?php echo number_format($stats['lost_author']); ?>件の著者情報が失われています！
                                バックアップから復元可能です。
                            </p>
                        </div>
                        
                        <h3 class="text-lg font-semibold mb-2">失われた著者情報のサンプル</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ASIN</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">バックアップの著者</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">現在の著者</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($lost_samples as $book): ?>
                                    <tr>
                                        <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($book['asin']); ?></td>
                                        <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="text-green-600 font-semibold">
                                                <?php echo htmlspecialchars($book['backup_author']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="text-red-600">
                                                <?php echo $book['current_author'] ? htmlspecialchars($book['current_author']) : '(空)'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            <form method="post" action="restore_authors.php" 
                                  onsubmit="return confirm('本当に著者情報を復元しますか？')">
                                <input type="hidden" name="backup_table" value="<?php echo htmlspecialchars($backup_table); ?>">
                                <button type="submit" name="restore" value="1" 
                                        class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                    <i class="fas fa-undo mr-2"></i>
                                    バックアップから著者情報を復元
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="p-4 bg-green-50 border-l-4 border-green-400">
                            <p class="text-green-800">
                                このバックアップとの比較では、著者情報の消失は見つかりませんでした。
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">調査SQL（手動実行用）</h2>
            <pre class="bg-gray-900 text-green-400 p-4 rounded overflow-x-auto text-sm">
-- 現在の著者なしレコード数
SELECT COUNT(*) FROM b_book_repository 
WHERE author IS NULL OR author = '';

-- 重複ASINの確認
SELECT asin, COUNT(*) as cnt 
FROM b_book_repository 
GROUP BY asin 
HAVING cnt > 1;

-- b_book_listとの不整合確認
SELECT COUNT(*) 
FROM b_book_list bl
LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
WHERE bl.author IS NOT NULL AND bl.author != ''
AND (br.author IS NULL OR br.author = '');
            </pre>
        </div>

        <div class="mt-6 flex gap-4">
            <a href="/admin/check_missing_authors.php" 
               class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                著者情報欠落状況確認
            </a>
            <a href="/admin/sync_authors_progress.php" 
               class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                進捗確認画面へ
            </a>
        </div>
    </div>
</body>
</html>