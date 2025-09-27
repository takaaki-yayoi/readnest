<?php
/**
 * バックアップテーブルの存在確認と簡易復元
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once('admin_auth.php');
requireAdmin();

$message = '';
$error = '';

// 復元処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'restore_from_backup') {
        $backup_table = $_POST['backup_table'] ?? '';
        
        // テーブル名の検証
        if (!preg_match('/^[a-z0-9_]+$/i', $backup_table)) {
            $error = '無効なテーブル名です';
        } else {
            // 復元SQLを実行
            $restore_sql = "
                UPDATE b_book_repository r
                INNER JOIN `{$backup_table}` b ON r.asin = b.asin
                SET r.author = b.author
                WHERE b.author IS NOT NULL 
                AND b.author != ''
                AND (r.author IS NULL OR r.author = '')
            ";
            
            $result = $g_db->query($restore_sql);
            
            if (DB::isError($result)) {
                $error = 'エラー: ' . $result->getMessage();
            } else {
                $message = "バックアップから著者情報を復元しました";
            }
        }
    }
}

// テーブル一覧を取得
$sql = "SHOW TABLES";
$all_tables = $g_db->getAll($sql, null, DB_FETCHMODE_ORDERED);
$repository_tables = [];

if (!DB::isError($all_tables)) {
    foreach ($all_tables as $row) {
        $table_name = $row[0];
        if (strpos($table_name, 'book_repository') !== false) {
            // テーブルの詳細情報を取得
            $count_sql = "SELECT COUNT(*) FROM `{$table_name}`";
            $count = $g_db->getOne($count_sql);
            
            $author_sql = "SELECT COUNT(*) FROM `{$table_name}` WHERE author IS NOT NULL AND author != ''";
            $author_count = $g_db->getOne($author_sql);
            
            $repository_tables[] = [
                'name' => $table_name,
                'total' => DB::isError($count) ? 0 : $count,
                'with_author' => DB::isError($author_count) ? 0 : $author_count,
                'is_current' => ($table_name === 'b_book_repository')
            ];
        }
    }
}

// 現在のb_book_repositoryの状況
$current_stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN author IS NOT NULL AND author != '' THEN 1 ELSE 0 END) as with_author,
        SUM(CASE WHEN author IS NULL OR author = '' THEN 1 ELSE 0 END) as without_author
    FROM b_book_repository
";
$current_stats = $g_db->getRow($current_stats_sql, null, DB_FETCHMODE_ASSOC);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>バックアップテーブル確認</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">バックアップテーブル確認</h1>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">現在のb_book_repository</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="p-4 bg-gray-50 rounded">
                    <div class="text-2xl font-bold"><?php echo number_format($current_stats['total']); ?></div>
                    <div class="text-sm text-gray-600">総レコード数</div>
                </div>
                <div class="p-4 bg-green-50 rounded">
                    <div class="text-2xl font-bold text-green-600">
                        <?php echo number_format($current_stats['with_author']); ?>
                    </div>
                    <div class="text-sm text-gray-600">著者情報あり</div>
                </div>
                <div class="p-4 bg-red-50 rounded">
                    <div class="text-2xl font-bold text-red-600">
                        <?php echo number_format($current_stats['without_author']); ?>
                    </div>
                    <div class="text-sm text-gray-600">著者情報なし</div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">book_repositoryテーブル一覧</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">テーブル名</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">総レコード数</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">著者情報あり</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">著者情報率</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($repository_tables as $table): ?>
                        <tr class="<?php echo $table['is_current'] ? 'bg-blue-50' : ''; ?>">
                            <td class="px-4 py-2 text-sm">
                                <?php echo htmlspecialchars($table['name']); ?>
                                <?php if ($table['is_current']): ?>
                                    <span class="ml-2 px-2 py-1 bg-blue-600 text-white text-xs rounded">現在</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-sm"><?php echo number_format($table['total']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo number_format($table['with_author']); ?></td>
                            <td class="px-4 py-2 text-sm">
                                <?php 
                                $rate = $table['total'] > 0 ? round($table['with_author'] / $table['total'] * 100, 1) : 0;
                                $color = $rate >= 80 ? 'text-green-600' : ($rate >= 50 ? 'text-yellow-600' : 'text-red-600');
                                ?>
                                <span class="<?php echo $color; ?> font-semibold">
                                    <?php echo $rate; ?>%
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <?php if (!$table['is_current'] && $table['with_author'] > 0): ?>
                                    <div class="flex gap-2">
                                        <a href="/admin/preview_restore.php?table=<?php echo urlencode($table['name']); ?>" 
                                           class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">
                                            プレビュー
                                        </a>
                                        <form method="post" class="inline" 
                                              onsubmit="return confirm('このバックアップから著者情報を復元しますか？')">
                                            <input type="hidden" name="action" value="restore_from_backup">
                                            <input type="hidden" name="backup_table" value="<?php echo htmlspecialchars($table['name']); ?>">
                                            <button type="submit" 
                                                    class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs">
                                                直接復元
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">手動復元用SQL</h2>
            <pre class="bg-gray-900 text-green-400 p-4 rounded overflow-x-auto text-sm">
-- バックアップテーブルから著者情報を復元
-- [backup_table]を実際のテーブル名に置き換えてください
UPDATE b_book_repository r
INNER JOIN [backup_table] b ON r.asin = b.asin
SET r.author = b.author
WHERE b.author IS NOT NULL 
AND b.author != ''
AND (r.author IS NULL OR r.author = '');

-- 復元前の確認用
SELECT COUNT(*) 
FROM b_book_repository r
INNER JOIN [backup_table] b ON r.asin = b.asin
WHERE b.author IS NOT NULL 
AND b.author != ''
AND (r.author IS NULL OR r.author = '');
            </pre>
        </div>
        
        <div class="mt-6 flex gap-4">
            <a href="/admin/check_missing_authors.php" 
               class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                著者情報欠落状況
            </a>
            <a href="/admin/sync_authors.php" 
               class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                同期処理
            </a>
        </div>
    </div>
</body>
</html>