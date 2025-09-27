<?php
/**
 * データベース最適化ページ
 * PHP 5.6以上対応版
 */

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

// ページタイトルを設定
$page_title = 'データベース最適化';

// レイアウトヘッダーを読み込み
include('layout/header.php');
include('layout/submenu.php');

$message = '';
$error = '';

// インデックス追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_indexes') {
    try {
        $indexes_added = 0;
        $errors = array();
        
        // インデックスを追加
        $indexes = array(
            array(
                'table' => 'b_book_event',
                'name' => 'idx_event_date_desc',
                'columns' => 'event_date DESC',
                'description' => '最新の活動を高速に取得'
            ),
            array(
                'table' => 'b_book_event',
                'name' => 'idx_event_type_date',
                'columns' => 'event, event_date DESC',
                'description' => 'イベントタイプと日付の複合インデックス'
            ),
            array(
                'table' => 'b_book_list',
                'name' => 'idx_memo_updated_desc',
                'columns' => 'memo_updated DESC',
                'description' => 'レビュー更新日での並び替えを高速化'
            ),
            array(
                'table' => 'b_book_list',
                'name' => 'idx_update_date_desc',
                'columns' => 'update_date DESC',
                'description' => '更新日での並び替えを高速化'
            ),
            array(
                'table' => 'b_book_list',
                'name' => 'idx_status_update',
                'columns' => 'status, update_date',
                'description' => '読了本の統計用'
            ),
            array(
                'table' => 'b_user',
                'name' => 'idx_diary_policy',
                'columns' => 'diary_policy',
                'description' => '公開設定でのフィルタリングを高速化'
            )
        );
        
        foreach ($indexes as $index) {
            // インデックスが既に存在するかチェック
            $check_sql = "SHOW INDEX FROM {$index['table']} WHERE Key_name = ?";
            $existing = $g_db->getAll($check_sql, array($index['name']), DB_FETCHMODE_ASSOC);
            
            if (empty($existing) || DB::isError($existing)) {
                // インデックスを追加
                $add_sql = "ALTER TABLE {$index['table']} ADD INDEX {$index['name']} ({$index['columns']})";
                $result = $g_db->query($add_sql);
                
                if (DB::isError($result)) {
                    $errors[] = "{$index['table']}.{$index['name']}: " . $result->getMessage();
                } else {
                    $indexes_added++;
                }
            }
        }
        
        if ($indexes_added > 0) {
            $message = "{$indexes_added}個のインデックスを追加しました。";
        } else {
            $message = "すべてのインデックスは既に存在しています。";
        }
        
        if (!empty($errors)) {
            $error = "エラー: " . implode(", ", $errors);
        }
        
    } catch (Exception $e) {
        $error = 'インデックス追加に失敗しました: ' . $e->getMessage();
    }
}

// 現在のインデックス情報を取得
$tables = array('b_book_event', 'b_book_list', 'b_user');
$current_indexes = array();

foreach ($tables as $table) {
    $sql = "SHOW INDEX FROM $table";
    $indexes = $g_db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($indexes)) {
        $current_indexes[$table] = $indexes;
    }
}

?>

<div class="space-y-6">
    <?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <i class="fas fa-check-circle mr-2"></i><?php echo html($message); ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo html($error); ?>
    </div>
    <?php endif; ?>

    <!-- パフォーマンス最適化の説明 -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">
            <i class="fas fa-info-circle mr-2"></i>データベース最適化について
        </h3>
        <p class="text-sm text-blue-800">
            index.phpの初回アクセスが遅い問題を解決するため、適切なインデックスを追加してクエリを高速化します。
        </p>
    </div>

    <!-- インデックス追加フォーム -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">
            <i class="fas fa-database text-indigo-500 mr-2"></i>
            推奨インデックスの追加
        </h2>
        
        <form method="post" class="mb-4">
            <input type="hidden" name="action" value="add_indexes">
            <button type="submit" 
                    class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-plus-circle mr-2"></i>
                推奨インデックスを追加
            </button>
        </form>
        
        <div class="text-sm text-gray-600">
            <p class="mb-2">以下のインデックスが追加されます：</p>
            <ul class="list-disc list-inside space-y-1">
                <li>b_book_event: event_date, event+event_date（最新活動の高速化）</li>
                <li>b_book_list: memo_updated, update_date, status+update_date（レビュー・統計の高速化）</li>
                <li>b_user: diary_policy（公開設定フィルタの高速化）</li>
            </ul>
        </div>
    </div>

    <!-- 現在のインデックス一覧 -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-list text-purple-500 mr-2"></i>
                現在のインデックス
            </h2>
        </div>
        
        <div class="p-6 space-y-6">
            <?php foreach ($current_indexes as $table => $indexes): ?>
            <div>
                <h3 class="font-semibold text-gray-900 mb-2"><?php echo html($table); ?></h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">インデックス名</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">カラム</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ユニーク</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">タイプ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($indexes)): ?>
                                <?php 
                                $grouped_indexes = array();
                                foreach ($indexes as $index) {
                                    $key_name = $index['Key_name'];
                                    if (!isset($grouped_indexes[$key_name])) {
                                        $grouped_indexes[$key_name] = array(
                                            'columns' => array(),
                                            'unique' => $index['Non_unique'] == '0',
                                            'type' => $index['Index_type']
                                        );
                                    }
                                    $grouped_indexes[$key_name]['columns'][] = $index['Column_name'];
                                }
                                ?>
                                <?php foreach ($grouped_indexes as $key_name => $info): ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo html($key_name); ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo html(implode(', ', $info['columns'])); ?></td>
                                    <td class="px-4 py-2 text-sm">
                                        <?php if ($info['unique']): ?>
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">ユニーク</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">通常</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo html($info['type']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-center text-gray-500">インデックスがありません</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include('layout/footer.php'); ?>