<?php
/**
 * 復元可能な著者情報の簡易チェック
 * すべてのバックアップテーブルから復元可能な件数を一覧表示
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once('admin_auth.php');
requireAdmin();

// すべてのbook_repositoryテーブルを取得
$sql = "SHOW TABLES LIKE '%book_repository%'";
$tables_result = $g_db->getAll($sql, null, DB_FETCHMODE_ORDERED);

$backup_tables = [];
if (!DB::isError($tables_result)) {
    foreach ($tables_result as $row) {
        $table_name = $row[0];
        if ($table_name !== 'b_book_repository') {
            $backup_tables[] = $table_name;
        }
    }
}

// 各バックアップから復元可能な件数を取得
$restore_potential = [];
foreach ($backup_tables as $backup_table) {
    // テーブル名の検証
    if (!preg_match('/^[a-z0-9_]+$/i', $backup_table)) {
        continue;
    }
    
    // 復元可能な件数を確認
    $check_sql = "
        SELECT 
            COUNT(*) as restorable,
            COUNT(DISTINCT b.author) as unique_authors,
            MIN(b.author) as sample_author
        FROM b_book_repository r
        INNER JOIN `{$backup_table}` b ON r.asin = b.asin
        WHERE b.author IS NOT NULL 
        AND b.author != ''
        AND (r.author IS NULL OR r.author = '')
    ";
    
    $result = $g_db->getRow($check_sql, null, DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($result) && $result['restorable'] > 0) {
        $restore_potential[] = [
            'table' => $backup_table,
            'restorable' => $result['restorable'],
            'unique_authors' => $result['unique_authors'],
            'sample_author' => $result['sample_author']
        ];
    }
}

// 復元可能数で降順ソート
usort($restore_potential, function($a, $b) {
    return $b['restorable'] - $a['restorable'];
});

// 現在の状況
$current_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN author IS NULL OR author = '' THEN 1 ELSE 0 END) as missing
    FROM b_book_repository
";
$current = $g_db->getRow($current_sql, null, DB_FETCHMODE_ASSOC);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>復元可能性クイックチェック</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">
            <i class="fas fa-search mr-2"></i>復元可能性クイックチェック
        </h1>
        
        <!-- 現在の状況 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">現在のb_book_repository</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 rounded">
                    <div class="text-2xl font-bold"><?php echo number_format($current['total']); ?></div>
                    <div class="text-sm text-gray-600">総レコード数</div>
                </div>
                <div class="p-4 bg-red-50 rounded">
                    <div class="text-2xl font-bold text-red-600">
                        <?php echo number_format($current['missing']); ?>
                    </div>
                    <div class="text-sm text-gray-600">著者情報なし</div>
                </div>
            </div>
        </div>
        
        <!-- 復元可能性 -->
        <?php if (empty($restore_potential)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                <p class="font-bold">バックアップから復元可能な著者情報は見つかりませんでした。</p>
                <p class="text-sm mt-2">Google Books APIを使用して著者情報を取得する必要があります。</p>
            </div>
        <?php else: ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <p class="font-bold">
                    <i class="fas fa-check-circle mr-2"></i>
                    バックアップから著者情報を復元できます！
                </p>
                <p class="text-sm mt-2">
                    最大で <?php echo number_format(array_sum(array_column($restore_potential, 'restorable'))); ?> 件の著者情報を復元可能です。
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h2 class="text-lg font-semibold">バックアップテーブル別復元可能数</h2>
                </div>
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">順位</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">バックアップテーブル</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">復元可能数</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">著者数</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者例</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">アクション</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($restore_potential as $index => $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php if ($index === 0): ?>
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                                        <i class="fas fa-crown mr-1"></i><?php echo $index + 1; ?>
                                    </span>
                                <?php else: ?>
                                    <?php echo $index + 1; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-900">
                                <?php echo htmlspecialchars($item['table']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <span class="font-bold text-green-600">
                                    <?php echo number_format($item['restorable']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <?php echo number_format($item['unique_authors']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo htmlspecialchars($item['sample_author']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <a href="/admin/preview_restore.php?table=<?php echo urlencode($item['table']); ?>" 
                                   class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    <i class="fas fa-eye mr-1"></i>詳細を見る
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($restore_potential) > 1): ?>
            <div class="mt-6 p-4 bg-blue-50 border-l-4 border-blue-400">
                <p class="text-sm text-blue-800">
                    <strong>ヒント:</strong> 
                    複数のバックアップから復元可能です。最も復元可能数が多いテーブルから始めることをお勧めします。
                    同じ本の著者情報は上書きされないため、複数のバックアップから順番に復元しても安全です。
                </p>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="mt-6 flex gap-4">
            <a href="/admin/check_backup_tables.php" 
               class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-database mr-2"></i>バックアップテーブル一覧
            </a>
            <a href="/admin/check_missing_authors.php" 
               class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-chart-bar mr-2"></i>著者情報欠落状況
            </a>
        </div>
    </div>
</body>
</html>