<?php
/**
 * ユーザーステータスカラム追加マイグレーション（ブラウザアクセス版）
 * b_userテーブルにstatus列を追加し、既存データの状態を移行
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = 'ユーザーステータス移行';

// 実行フラグ
$execute = $_POST['execute'] ?? false;
$results = [];
$errors = [];

if ($execute === 'yes') {
    try {
        // トランザクション開始
        $g_db->autoCommit(false);
        
        // 1. status列が既に存在するか確認
        $results[] = "1. 既存のカラム構造を確認中...";
        $columns_sql = "SHOW COLUMNS FROM b_user LIKE 'status'";
        $columns_result = $g_db->getOne($columns_sql);
        
        if ($columns_result) {
            $results[] = "   => status列は既に存在します。スキップします。";
        } else {
            // 2. status列を追加
            $results[] = "   => status列を追加中...";
            $add_column_sql = "ALTER TABLE b_user ADD COLUMN status TINYINT NOT NULL DEFAULT 0 COMMENT 'ユーザーステータス: 0=仮登録, 1=本登録, 2=無効, 3=削除済み' AFTER regist_date";
            $result = $g_db->query($add_column_sql);
            if (DB::isError($result)) {
                throw new Exception("status列の追加に失敗: " . $result->getMessage());
            }
            $results[] = "   => status列を追加しました。";
            
            // インデックスを追加
            $results[] = "2. statusインデックスを追加中...";
            $add_index_sql = "ALTER TABLE b_user ADD INDEX idx_status (status)";
            $result = $g_db->query($add_index_sql);
            if (DB::isError($result)) {
                // インデックスが既に存在する場合はエラーを無視
                if (strpos($result->getMessage(), 'Duplicate key name') === false) {
                    throw new Exception("インデックスの追加に失敗: " . $result->getMessage());
                }
            }
            $results[] = "   => インデックスを追加しました。";
        }
        
        // 3. 既存データのステータスを更新
        $results[] = "3. 既存データのステータスを判定・更新中...";
        
        // 順序重要: 削除済みユーザーを最初に処理（regist_dateで判定される前に）
        // 削除済みユーザー（nicknameが'削除済みユーザー_'で始まる）
        $results[] = "   - 削除済みユーザーを更新中...";
        $update_deleted_sql = "UPDATE b_user SET status = ? WHERE nickname LIKE '削除済みユーザー_%'";
        $result = $g_db->query($update_deleted_sql, array(USER_STATUS_DELETED));
        if (DB::isError($result)) {
            throw new Exception("削除済みユーザーの更新に失敗: " . $result->getMessage());
        }
        $deleted_count = $g_db->affectedRows();
        $results[] = "     => {$deleted_count}件を削除済みに設定しました。";
        
        // 仮登録ユーザー（interim_idがあり、regist_dateがNULL、削除済みでない）
        $results[] = "   - 仮登録ユーザーを更新中...";
        $update_interim_sql = "UPDATE b_user SET status = ? WHERE regist_date IS NULL AND interim_id IS NOT NULL AND status != ?";
        $result = $g_db->query($update_interim_sql, array(USER_STATUS_INTERIM, USER_STATUS_DELETED));
        if (DB::isError($result)) {
            throw new Exception("仮登録ユーザーの更新に失敗: " . $result->getMessage());
        }
        $interim_count = $g_db->affectedRows();
        $results[] = "     => {$interim_count}件を仮登録に設定しました。";
        
        // 無効ユーザー（regist_dateがNULL、削除済みでも仮登録でもない）
        $results[] = "   - 無効ユーザーを更新中...";
        $update_inactive_sql = "UPDATE b_user SET status = ? WHERE regist_date IS NULL AND status NOT IN (?, ?)";
        $result = $g_db->query($update_inactive_sql, array(USER_STATUS_INACTIVE, USER_STATUS_DELETED, USER_STATUS_INTERIM));
        if (DB::isError($result)) {
            throw new Exception("無効ユーザーの更新に失敗: " . $result->getMessage());
        }
        $inactive_count = $g_db->affectedRows();
        $results[] = "     => {$inactive_count}件を無効に設定しました。";
        
        // 本登録ユーザー（regist_dateがNULLでない、かつ削除済みでない）
        $results[] = "   - 本登録ユーザーを更新中...";
        $update_active_sql = "UPDATE b_user SET status = ? WHERE regist_date IS NOT NULL AND status != ?";
        $result = $g_db->query($update_active_sql, array(USER_STATUS_ACTIVE, USER_STATUS_DELETED));
        if (DB::isError($result)) {
            throw new Exception("本登録ユーザーの更新に失敗: " . $result->getMessage());
        }
        $active_count = $g_db->affectedRows();
        $results[] = "     => {$active_count}件を本登録に設定しました。";
        
        // 4. 統計情報を表示
        $results[] = "4. 更新後の統計情報:";
        $stats_sql = "SELECT status, COUNT(*) as count FROM b_user GROUP BY status ORDER BY status";
        $stats_result = $g_db->getAll($stats_sql, null, DB_FETCHMODE_ASSOC);
        if (DB::isError($stats_result)) {
            throw new Exception("統計情報の取得に失敗: " . $stats_result->getMessage());
        }
        
        $status_names = [
            USER_STATUS_INTERIM => '仮登録',
            USER_STATUS_ACTIVE => '本登録',
            USER_STATUS_INACTIVE => '無効',
            USER_STATUS_DELETED => '削除済み'
        ];
        
        foreach ($stats_result as $stat) {
            $status_name = $status_names[$stat['status']] ?? '不明';
            $results[] = "   - {$status_name}: {$stat['count']}件";
        }
        
        // 5. 合計を確認
        $total_sql = "SELECT COUNT(*) FROM b_user";
        $total_count = $g_db->getOne($total_sql);
        $results[] = "   - 合計: {$total_count}件";
        
        // コミット
        $g_db->commit();
        $g_db->autoCommit(true);
        
        $results[] = "";
        $results[] = "=== マイグレーション完了 ===";
        
    } catch (Exception $e) {
        // ロールバック
        $g_db->rollback();
        $g_db->autoCommit(true);
        
        $errors[] = "エラーが発生しました: " . $e->getMessage();
    }
}

// 現在の状況を確認
$current_status = [];
try {
    // status列の存在確認
    $columns_sql = "SHOW COLUMNS FROM b_user LIKE 'status'";
    $has_status_column = $g_db->getOne($columns_sql);
    
    if ($has_status_column) {
        $current_status['has_column'] = true;
        
        // 現在の統計
        $stats_sql = "SELECT status, COUNT(*) as count FROM b_user GROUP BY status ORDER BY status";
        $stats_result = $g_db->getAll($stats_sql, null, DB_FETCHMODE_ASSOC);
        if (!DB::isError($stats_result)) {
            $current_status['stats'] = $stats_result;
        }
    } else {
        $current_status['has_column'] = false;
        
        // 予想される分布
        $preview_stats = [];
        
        // 削除済み
        $deleted_sql = "SELECT COUNT(*) FROM b_user WHERE nickname LIKE '削除済みユーザー_%'";
        $deleted_count = $g_db->getOne($deleted_sql);
        $preview_stats[] = ['label' => '削除済み（予想）', 'count' => $deleted_count];
        
        // 本登録
        $active_sql = "SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL";
        $active_count = $g_db->getOne($active_sql);
        $preview_stats[] = ['label' => '本登録（予想）', 'count' => $active_count];
        
        // 仮登録
        $interim_sql = "SELECT COUNT(*) FROM b_user WHERE regist_date IS NULL AND interim_id IS NOT NULL AND nickname NOT LIKE '削除済みユーザー_%'";
        $interim_count = $g_db->getOne($interim_sql);
        $preview_stats[] = ['label' => '仮登録（予想）', 'count' => $interim_count];
        
        // 無効
        $inactive_sql = "SELECT COUNT(*) FROM b_user WHERE regist_date IS NULL AND nickname NOT LIKE '削除済みユーザー_%' AND interim_id IS NULL";
        $inactive_count = $g_db->getOne($inactive_sql);
        $preview_stats[] = ['label' => '無効（予想）', 'count' => $inactive_count];
        
        $current_status['preview_stats'] = $preview_stats;
    }
} catch (Exception $e) {
    $errors[] = "現在の状況確認でエラー: " . $e->getMessage();
}

include('layout/header.php');
include('layout/submenu.php');
?>

<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900">ユーザーステータス移行</h2>
        <p class="mt-1 text-sm text-gray-600">b_userテーブルにstatus列を追加し、既存データを移行します。</p>
    </div>

    <div class="p-6">
        <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <h3 class="text-red-800 font-semibold mb-2">エラー</h3>
            <ul class="list-disc list-inside text-red-700">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <h3 class="text-green-800 font-semibold mb-2">実行結果</h3>
            <pre class="text-sm text-green-700 whitespace-pre-wrap"><?php echo htmlspecialchars(implode("\n", $results)); ?></pre>
        </div>
        <?php endif; ?>

        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">現在の状況</h3>
            
            <?php if ($current_status['has_column'] ?? false): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-blue-800">
                        <i class="fas fa-check-circle mr-2"></i>
                        status列は既に存在します。
                    </p>
                </div>
                
                <?php if (!empty($current_status['stats'])): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">件数</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $status_names = [
                            USER_STATUS_INTERIM => '仮登録',
                            USER_STATUS_ACTIVE => '本登録',
                            USER_STATUS_INACTIVE => '無効',
                            USER_STATUS_DELETED => '削除済み'
                        ];
                        foreach ($current_status['stats'] as $stat):
                            $status_name = $status_names[$stat['status']] ?? '不明';
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($status_name); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($stat['count']); ?>件</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        status列がまだ存在しません。マイグレーションが必要です。
                    </p>
                </div>
                
                <?php if (!empty($current_status['preview_stats'])): ?>
                <h4 class="text-sm font-medium text-gray-700 mb-2">移行後の予想分布:</h4>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">件数</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($current_status['preview_stats'] as $stat): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($stat['label']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($stat['count']); ?>件</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!($current_status['has_column'] ?? false)): ?>
        <form method="post" action="">
            <input type="hidden" name="execute" value="yes">
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-admin-primary text-base font-medium text-white hover:bg-admin-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary sm:ml-3 sm:w-auto sm:text-sm">
                    <i class="fas fa-play mr-2"></i>
                    マイグレーションを実行
                </button>
                <a href="/admin/" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    キャンセル
                </a>
            </div>
        </form>
        <?php else: ?>
        <div class="mt-6">
            <a href="/admin/users.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-admin-primary hover:bg-admin-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary">
                <i class="fas fa-users mr-2"></i>
                ユーザー管理へ
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include('layout/footer.php'); ?>