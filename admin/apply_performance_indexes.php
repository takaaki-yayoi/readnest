<?php
/**
 * パフォーマンス改善用インデックス適用ツール
 */

declare(strict_types=1);

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者権限チェック
if (!isAdmin()) {
    header('Location: /admin/login.php');
    exit;
}

$action = $_POST['action'] ?? '';
$results = [];
$existing_indexes = [];

// 既存のインデックスを確認
function checkExistingIndexes($table_name) {
    global $g_db;
    $indexes = [];
    
    $sql = "SHOW INDEX FROM $table_name";
    $result = $g_db->getAll($sql, null, DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($result)) {
        foreach ($result as $row) {
            $indexes[$row['Key_name']] = [
                'column' => $row['Column_name'],
                'seq' => $row['Seq_in_index'],
                'unique' => !$row['Non_unique']
            ];
        }
    }
    
    return $indexes;
}

// インデックス作成の実行
if ($action === 'apply') {
    $indexes_to_create = [
        'b_book_list' => [
            'idx_finished_update_date' => 'finished_date, update_date',
            'idx_user_finished_date' => 'user_id, finished_date',
            'idx_user_status_finished' => 'user_id, status, finished_date'
        ],
        'b_book_event' => [
            'idx_user_event_date' => 'user_id, event_date',
            'idx_event_date_desc' => 'event_date DESC'
        ],
        'b_user' => [
            'idx_diary_policy_status' => 'diary_policy, status'
        ]
    ];
    
    foreach ($indexes_to_create as $table => $indexes) {
        $existing = checkExistingIndexes($table);
        
        foreach ($indexes as $index_name => $columns) {
            if (isset($existing[$index_name])) {
                $results[] = "✓ インデックス '$index_name' は既に存在します（$table）";
            } else {
                $sql = "CREATE INDEX $index_name ON $table($columns)";
                $result = $g_db->query($sql);
                
                if (DB::isError($result)) {
                    $results[] = "✗ エラー: '$index_name' の作成に失敗しました - " . $result->getMessage();
                } else {
                    $results[] = "✓ インデックス '$index_name' を作成しました（$table）";
                }
            }
        }
    }
    
    // 年月インデックスは特殊なので別処理
    $year_month_index = "idx_year_month_finished";
    $existing = checkExistingIndexes('b_book_list');
    
    if (!isset($existing[$year_month_index])) {
        // MySQLバージョンによってはこの形式のインデックスがサポートされない場合がある
        $sql = "CREATE INDEX $year_month_index ON b_book_list(user_id, status, finished_date)";
        $result = $g_db->query($sql);
        
        if (DB::isError($result)) {
            $results[] = "✗ 注意: 年月集計用インデックスの作成に失敗しました（代替インデックスを使用）";
        } else {
            $results[] = "✓ 年月集計用インデックスを作成しました";
        }
    }
}

// 現在のインデックス状況を取得
$tables = ['b_book_list', 'b_book_event', 'b_user'];
foreach ($tables as $table) {
    $existing_indexes[$table] = checkExistingIndexes($table);
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パフォーマンスインデックス管理 - ReadNest Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">パフォーマンスインデックス管理</h1>
            
            <?php if (!empty($results)): ?>
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="font-bold mb-2">実行結果</h3>
                <ul class="space-y-1">
                    <?php foreach ($results as $result): ?>
                        <li class="<?php echo strpos($result, '✗') === 0 ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo htmlspecialchars($result); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-4">推奨インデックス</h2>
                
                <div class="space-y-4">
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold mb-2">b_book_list テーブル</h3>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <code class="bg-gray-100 px-2 py-1 rounded">idx_finished_update_date</code>
                                - 読了日と更新日の複合インデックス
                                <?php echo isset($existing_indexes['b_book_list']['idx_finished_update_date']) ? 
                                    '<span class="text-green-600 ml-2">✓ 作成済み</span>' : 
                                    '<span class="text-orange-600 ml-2">未作成</span>'; ?>
                            </li>
                            <li>
                                <code class="bg-gray-100 px-2 py-1 rounded">idx_user_finished_date</code>
                                - ユーザー別読了日インデックス
                                <?php echo isset($existing_indexes['b_book_list']['idx_user_finished_date']) ? 
                                    '<span class="text-green-600 ml-2">✓ 作成済み</span>' : 
                                    '<span class="text-orange-600 ml-2">未作成</span>'; ?>
                            </li>
                            <li>
                                <code class="bg-gray-100 px-2 py-1 rounded">idx_user_status_finished</code>
                                - ユーザー・ステータス・読了日の複合インデックス
                                <?php echo isset($existing_indexes['b_book_list']['idx_user_status_finished']) ? 
                                    '<span class="text-green-600 ml-2">✓ 作成済み</span>' : 
                                    '<span class="text-orange-600 ml-2">未作成</span>'; ?>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold mb-2">b_book_event テーブル</h3>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <code class="bg-gray-100 px-2 py-1 rounded">idx_user_event_date</code>
                                - ユーザー別イベント日時インデックス
                                <?php echo isset($existing_indexes['b_book_event']['idx_user_event_date']) ? 
                                    '<span class="text-green-600 ml-2">✓ 作成済み</span>' : 
                                    '<span class="text-orange-600 ml-2">未作成</span>'; ?>
                            </li>
                            <li>
                                <code class="bg-gray-100 px-2 py-1 rounded">idx_event_date_desc</code>
                                - イベント日時降順インデックス
                                <?php echo isset($existing_indexes['b_book_event']['idx_event_date_desc']) ? 
                                    '<span class="text-green-600 ml-2">✓ 作成済み</span>' : 
                                    '<span class="text-orange-600 ml-2">未作成</span>'; ?>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold mb-2">b_user テーブル</h3>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <code class="bg-gray-100 px-2 py-1 rounded">idx_diary_policy_status</code>
                                - 公開設定とステータスの複合インデックス
                                <?php echo isset($existing_indexes['b_user']['idx_diary_policy_status']) ? 
                                    '<span class="text-green-600 ml-2">✓ 作成済み</span>' : 
                                    '<span class="text-orange-600 ml-2">未作成</span>'; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-4">現在のインデックス一覧</h2>
                
                <?php foreach ($existing_indexes as $table => $indexes): ?>
                <div class="mb-4">
                    <h3 class="font-semibold mb-2"><?php echo $table; ?></h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left">インデックス名</th>
                                    <th class="px-4 py-2 text-left">カラム</th>
                                    <th class="px-4 py-2 text-left">ユニーク</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $grouped_indexes = [];
                                foreach ($indexes as $name => $info) {
                                    if (!isset($grouped_indexes[$name])) {
                                        $grouped_indexes[$name] = [];
                                    }
                                    $grouped_indexes[$name][$info['seq']] = $info;
                                }
                                
                                foreach ($grouped_indexes as $name => $index_info): 
                                    ksort($index_info);
                                    $columns = array_column($index_info, 'column');
                                    // 最初の要素から unique フラグを取得
                                    $first_key = array_key_first($index_info);
                                    $is_unique = isset($index_info[$first_key]['unique']) ? $index_info[$first_key]['unique'] : false;
                                ?>
                                <tr class="border-b">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($name); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars(implode(', ', $columns)); ?></td>
                                    <td class="px-4 py-2"><?php echo $is_unique ? 'Yes' : 'No'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <form method="post" action="" onsubmit="return confirm('インデックスを作成しますか？');">
                <button type="submit" name="action" value="apply" 
                        class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    推奨インデックスを作成
                </button>
            </form>
            
            <div class="mt-6">
                <a href="/admin/" class="text-blue-600 hover:underline">← 管理画面に戻る</a>
            </div>
        </div>
    </div>
</body>
</html>