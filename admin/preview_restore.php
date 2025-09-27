<?php
/**
 * 著者情報復元のプレビュー
 * 実際に復元する前に、どれだけ復旧できるか確認
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once('admin_auth.php');
requireAdmin();

$backup_table = $_GET['table'] ?? '';
$preview_data = null;
$error = '';

// テーブル名の検証
if ($backup_table && !preg_match('/^[a-z0-9_]+$/i', $backup_table)) {
    $error = '無効なテーブル名です';
    $backup_table = '';
}

if ($backup_table) {
    // テーブルの存在確認
    $check_sql = "SHOW TABLES LIKE ?";
    $exists = $g_db->getOne($check_sql, [$backup_table]);
    
    if (!$exists) {
        $error = 'テーブルが見つかりません';
    } else {
        // 復元可能な件数を取得
        $preview_sql = "
            SELECT 
                COUNT(*) as restorable_count,
                COUNT(DISTINCT b.author) as unique_authors
            FROM b_book_repository r
            INNER JOIN `{$backup_table}` b ON r.asin = b.asin
            WHERE b.author IS NOT NULL 
            AND b.author != ''
            AND (r.author IS NULL OR r.author = '')
        ";
        
        $preview_stats = $g_db->getRow($preview_sql, null, DB_FETCHMODE_ASSOC);
        
        // 著者別の復元可能件数
        $author_stats_sql = "
            SELECT 
                b.author,
                COUNT(*) as book_count
            FROM b_book_repository r
            INNER JOIN `{$backup_table}` b ON r.asin = b.asin
            WHERE b.author IS NOT NULL 
            AND b.author != ''
            AND (r.author IS NULL OR r.author = '')
            GROUP BY b.author
            ORDER BY COUNT(*) DESC
            LIMIT 20
        ";
        
        $author_stats = $g_db->getAll($author_stats_sql, null, DB_FETCHMODE_ASSOC);
        
        // 復元対象のサンプル（最大50件）
        $sample_sql = "
            SELECT 
                r.asin,
                COALESCE(r.title, b.title) as title,
                b.author as new_author,
                r.author as current_author
            FROM b_book_repository r
            INNER JOIN `{$backup_table}` b ON r.asin = b.asin
            WHERE b.author IS NOT NULL 
            AND b.author != ''
            AND (r.author IS NULL OR r.author = '')
            ORDER BY b.title
            LIMIT 50
        ";
        
        $samples = $g_db->getAll($sample_sql, null, DB_FETCHMODE_ASSOC);
        
        // 出版社やジャンル別の統計も取得
        $publisher_stats_sql = "
            SELECT 
                CASE 
                    WHEN b.publisher IS NOT NULL THEN b.publisher
                    ELSE '(不明)'
                END as publisher,
                COUNT(*) as book_count
            FROM b_book_repository r
            INNER JOIN `{$backup_table}` b ON r.asin = b.asin
            WHERE b.author IS NOT NULL 
            AND b.author != ''
            AND (r.author IS NULL OR r.author = '')
            GROUP BY publisher
            ORDER BY COUNT(*) DESC
            LIMIT 10
        ";
        
        // publisherカラムが存在するか確認
        $columns_sql = "SHOW COLUMNS FROM `{$backup_table}`";
        $columns = $g_db->getAll($columns_sql, null, DB_FETCHMODE_ASSOC);
        $has_publisher = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'publisher') {
                $has_publisher = true;
                break;
            }
        }
        
        $publisher_stats = [];
        if ($has_publisher) {
            $publisher_stats = $g_db->getAll($publisher_stats_sql, null, DB_FETCHMODE_ASSOC);
        }
        
        $preview_data = [
            'stats' => $preview_stats,
            'authors' => $author_stats,
            'samples' => $samples,
            'publishers' => $publisher_stats
        ];
    }
}

// 復元実行
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_restore'])) {
    $restore_table = $_POST['backup_table'] ?? '';
    
    if (!preg_match('/^[a-z0-9_]+$/i', $restore_table)) {
        $error = '無効なテーブル名です';
    } else {
        // 復元処理
        $restore_sql = "
            UPDATE b_book_repository r
            INNER JOIN `{$restore_table}` b ON r.asin = b.asin
            SET r.author = b.author
            WHERE b.author IS NOT NULL 
            AND b.author != ''
            AND (r.author IS NULL OR r.author = '')
        ";
        
        $result = $g_db->query($restore_sql);
        
        if (DB::isError($result)) {
            $error = '復元エラー: ' . $result->getMessage();
        } else {
            // ログを記録
            $logDir = dirname(__DIR__) . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            $logFile = $logDir . '/author_restore_' . date('Y-m-d_His') . '.log';
            $logMessage = "[" . date('Y-m-d H:i:s') . "] ";
            $logMessage .= "Restored from {$restore_table}\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            header('Location: restore_authors.php?success=1');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>著者情報復元プレビュー</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">
            <i class="fas fa-eye mr-2"></i>著者情報復元プレビュー
        </h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$backup_table): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600">バックアップテーブルを選択してください。</p>
                <a href="/admin/check_backup_tables.php" 
                   class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    テーブル一覧に戻る
                </a>
            </div>
        <?php elseif ($preview_data): ?>
            
            <!-- 復元サマリー -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-chart-bar mr-2"></i>復元サマリー
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-green-50 rounded-lg">
                        <div class="text-3xl font-bold text-green-600">
                            <?php echo number_format($preview_data['stats']['restorable_count']); ?>
                        </div>
                        <div class="text-sm text-gray-600">復元可能な本</div>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <div class="text-3xl font-bold text-blue-600">
                            <?php echo number_format($preview_data['stats']['unique_authors']); ?>
                        </div>
                        <div class="text-sm text-gray-600">ユニークな著者数</div>
                    </div>
                    <div class="p-4 bg-purple-50 rounded-lg">
                        <div class="text-3xl font-bold text-purple-600">
                            <?php echo htmlspecialchars($backup_table); ?>
                        </div>
                        <div class="text-sm text-gray-600">バックアップテーブル</div>
                    </div>
                </div>
            </div>
            
            <!-- 著者別統計 -->
            <?php if (!empty($preview_data['authors'])): ?>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-user-edit mr-2"></i>著者別復元件数（上位20名）
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($preview_data['authors'] as $author): ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span class="text-gray-700"><?php echo htmlspecialchars($author['author']); ?></span>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                            <?php echo number_format($author['book_count']); ?>冊
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 出版社別統計 -->
            <?php if (!empty($preview_data['publishers'])): ?>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-building mr-2"></i>出版社別復元件数
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">出版社</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">冊数</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($preview_data['publishers'] as $pub): ?>
                            <tr>
                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($pub['publisher']); ?></td>
                                <td class="px-4 py-2 text-sm text-right"><?php echo number_format($pub['book_count']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- サンプルデータ -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-list mr-2"></i>復元対象サンプル（最大50件）
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ASIN</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">復元される著者</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($preview_data['samples'] as $sample): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-mono">
                                    <?php echo htmlspecialchars($sample['asin']); ?>
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <?php echo htmlspecialchars($sample['title']); ?>
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded">
                                        <?php echo htmlspecialchars($sample['new_author']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- アクションボタン -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i>確認
                </h2>
                <p class="text-gray-700 mb-4">
                    上記の <strong class="text-green-600"><?php echo number_format($preview_data['stats']['restorable_count']); ?>件</strong> 
                    の著者情報を復元します。この操作は取り消せません。
                </p>
                <div class="flex gap-4">
                    <form method="post" onsubmit="return confirm('本当に復元を実行しますか？')">
                        <input type="hidden" name="backup_table" value="<?php echo htmlspecialchars($backup_table); ?>">
                        <button type="submit" name="confirm_restore" value="1"
                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-check mr-2"></i>復元を実行
                        </button>
                    </form>
                    <a href="/admin/check_backup_tables.php" 
                       class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        <i class="fas fa-times mr-2"></i>キャンセル
                    </a>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</body>
</html>