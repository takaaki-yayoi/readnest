<?php
/**
 * パフォーマンス改善のためのインデックス追加スクリプト
 */

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

// 管理者認証を読み込み
require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = 'パフォーマンスインデックス追加';

// インデックス追加処理
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_indexes') {
    // b_book_listテーブルのインデックス
    $indexes = [
        // 新着レビュー用
        [
            'table' => 'b_book_list',
            'name' => 'idx_memo_updated',
            'sql' => 'CREATE INDEX idx_memo_updated ON b_book_list(memo_updated DESC)',
            'check' => "SHOW INDEX FROM b_book_list WHERE Key_name = 'idx_memo_updated'"
        ],
        [
            'table' => 'b_book_list',
            'name' => 'idx_memo_not_null',
            'sql' => 'CREATE INDEX idx_memo_not_null ON b_book_list(memo(255))',
            'check' => "SHOW INDEX FROM b_book_list WHERE Key_name = 'idx_memo_not_null'"
        ],
        // 人気の本用
        [
            'table' => 'b_book_list',
            'name' => 'idx_book_status_name',
            'sql' => 'CREATE INDEX idx_book_status_name ON b_book_list(book_id, status, name(100))',
            'check' => "SHOW INDEX FROM b_book_list WHERE Key_name = 'idx_book_status_name'"
        ],
        [
            'table' => 'b_book_list',
            'name' => 'idx_image_url',
            'sql' => 'CREATE INDEX idx_image_url ON b_book_list(image_url(100))',
            'check' => "SHOW INDEX FROM b_book_list WHERE Key_name = 'idx_image_url'"
        ],
        // 複合インデックス
        [
            'table' => 'b_book_list',
            'name' => 'idx_book_list_composite',
            'sql' => 'CREATE INDEX idx_book_list_composite ON b_book_list(status, memo_updated, book_id)',
            'check' => "SHOW INDEX FROM b_book_list WHERE Key_name = 'idx_book_list_composite'"
        ],
        // b_userテーブル
        [
            'table' => 'b_user',
            'name' => 'idx_diary_policy',
            'sql' => 'CREATE INDEX idx_diary_policy ON b_user(diary_policy)',
            'check' => "SHOW INDEX FROM b_user WHERE Key_name = 'idx_diary_policy'"
        ]
    ];
    
    foreach ($indexes as $index) {
        // インデックスが既に存在するかチェック
        $exists = $g_db->getOne($index['check']);
        
        if (!$exists || DB::isError($exists)) {
            // インデックスを追加
            $result = $g_db->query($index['sql']);
            
            if (DB::isError($result)) {
                $errors[] = "インデックス {$index['name']} の追加に失敗: " . $result->getMessage();
            } else {
                $messages[] = "インデックス {$index['name']} を追加しました";
            }
        } else {
            $messages[] = "インデックス {$index['name']} は既に存在します";
        }
    }
    
    // 集計テーブルの作成
    require_once(dirname(__DIR__) . '/library/database_optimized_v2.php');
    if (preCalculatePopularBooks()) {
        $messages[] = "人気の本の集計テーブルを作成・更新しました";
    } else {
        $errors[] = "人気の本の集計テーブルの作成に失敗しました";
    }
}

// 現在のインデックス状況を確認
$book_list_indexes = $g_db->getAll("SHOW INDEX FROM b_book_list", null, DB_FETCHMODE_ASSOC);
$user_indexes = $g_db->getAll("SHOW INDEX FROM b_user", null, DB_FETCHMODE_ASSOC);

include('layout/header.php');
include('layout/submenu.php');
?>

<div class="space-y-6">
    <?php if (!empty($messages)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside">
            <?php foreach ($messages as $msg): ?>
            <li><?php echo htmlspecialchars($msg); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">パフォーマンス改善用インデックス</h3>
        </div>
        <div class="p-6">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            インデックスの追加はデータベースのパフォーマンスを向上させますが、大量のデータがある場合は処理に時間がかかる場合があります。
                        </p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_indexes">
                
                <div class="mb-6">
                    <h4 class="font-semibold mb-2">追加予定のインデックス:</h4>
                    <ul class="list-disc list-inside space-y-1 text-sm text-gray-600">
                        <li>memo_updated (新着レビューの高速化)</li>
                        <li>memo (レビュー検索の高速化)</li>
                        <li>book_id, status, name (人気の本の高速化)</li>
                        <li>image_url (画像フィルタリングの高速化)</li>
                        <li>複合インデックス (総合的なパフォーマンス向上)</li>
                        <li>diary_policy (公開設定フィルタリングの高速化)</li>
                        <li>人気の本の集計テーブル作成</li>
                    </ul>
                </div>
                
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-database mr-2"></i>
                    インデックスを追加
                </button>
            </form>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">現在のインデックス状況</h3>
        </div>
        <div class="p-6">
            <h4 class="font-semibold mb-2">b_book_listテーブル:</h4>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">インデックス名</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">カラム</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ユニーク</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($book_list_indexes as $idx): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($idx['Key_name']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($idx['Column_name']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo $idx['Non_unique'] ? 'No' : 'Yes'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <h4 class="font-semibold mb-2">b_userテーブル:</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">インデックス名</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">カラム</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ユニーク</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($user_indexes as $idx): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($idx['Key_name']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($idx['Column_name']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo $idx['Non_unique'] ? 'No' : 'Yes'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include('layout/footer.php');
?>