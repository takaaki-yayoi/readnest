<?php
/**
 * 仮登録ユーザークリーンアップ管理画面
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

require_once('admin_auth.php');
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者権限チェック
requireAdmin();

// メッセージ初期化
$message = '';
$message_type = '';

// クリーンアップ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cleanup') {
        try {
            global $g_db;
            
            // 1時間以上前に作成された仮登録ユーザーを検索
            $select_sql = "SELECT user_id, email, nickname, create_date 
                           FROM b_user 
                           WHERE status = ? 
                           AND create_date < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                           AND regist_date IS NULL";
            
            $expired_users = $g_db->getAll($select_sql, array(USER_STATUS_INTERIM), DB_FETCHMODE_ASSOC);
            
            if (DB::isError($expired_users)) {
                throw new Exception('期限切れユーザーの検索に失敗しました: ' . $expired_users->getMessage());
            }
            
            $count = count($expired_users);
            
            if ($count > 0) {
                // 期限切れユーザーを削除
                $delete_sql = "DELETE FROM b_user 
                               WHERE status = ? 
                               AND create_date < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                               AND regist_date IS NULL";
                
                $result = $g_db->query($delete_sql, array(USER_STATUS_INTERIM));
                
                if (DB::isError($result)) {
                    throw new Exception('期限切れユーザーの削除に失敗しました: ' . $result->getMessage());
                }
                
                $affected_rows = $g_db->affectedRows();
                $message = "{$affected_rows}件の期限切れ仮登録ユーザーを削除しました。";
                $message_type = 'success';
            } else {
                $message = "削除対象の仮登録ユーザーはありません。";
                $message_type = 'info';
            }
        } catch (Exception $e) {
            $message = "エラーが発生しました: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// 統計情報を取得
try {
    $stats_sql = "SELECT 
                    COUNT(CASE WHEN create_date >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) THEN 1 END) as last_10m,
                    COUNT(CASE WHEN create_date >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 1 END) as last_30m,
                    COUNT(CASE WHEN create_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as last_1h,
                    COUNT(CASE WHEN create_date < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as expired,
                    COUNT(*) as total
                  FROM b_user 
                  WHERE status = ? AND regist_date IS NULL";
    
    $stats = $g_db->getRow($stats_sql, array(USER_STATUS_INTERIM), DB_FETCHMODE_ASSOC);
    
    if (DB::isError($stats)) {
        $stats = ['last_10m' => 0, 'last_30m' => 0, 'last_1h' => 0, 'expired' => 0, 'total' => 0];
    }
    
    // 期限切れユーザーの詳細を取得
    $expired_sql = "SELECT user_id, email, nickname, create_date,
                           TIMESTAMPDIFF(MINUTE, create_date, NOW()) as minutes_ago
                    FROM b_user 
                    WHERE status = ? 
                    AND create_date < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    AND regist_date IS NULL
                    ORDER BY create_date DESC
                    LIMIT 100";
    
    $expired_users = $g_db->getAll($expired_sql, array(USER_STATUS_INTERIM), DB_FETCHMODE_ASSOC);
    
    if (DB::isError($expired_users)) {
        $expired_users = [];
    }
    
} catch (Exception $e) {
    $stats = ['last_10m' => 0, 'last_30m' => 0, 'last_1h' => 0, 'expired' => 0, 'total' => 0];
    $expired_users = [];
}

// ヘッダー情報
$page_title = '仮登録ユーザー管理';
include('layout/header.php');
include('layout/submenu.php');
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">
            <i class="fas fa-user-clock mr-3"></i>仮登録ユーザー管理
        </h1>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php 
                echo $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 
                    ($message_type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 
                     'bg-blue-100 border-blue-400 text-blue-700'); 
            ?> border">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <!-- 統計情報 -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">過去10分</div>
                <div class="text-3xl font-bold text-gray-800"><?php echo $stats['last_10m']; ?></div>
                <div class="text-xs text-gray-400">件の仮登録</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">過去30分</div>
                <div class="text-3xl font-bold text-gray-800"><?php echo $stats['last_30m']; ?></div>
                <div class="text-xs text-gray-400">件の仮登録</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">過去1時間</div>
                <div class="text-3xl font-bold text-gray-800"><?php echo $stats['last_1h']; ?></div>
                <div class="text-xs text-gray-400">件の仮登録</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-2 border-red-200">
                <div class="text-sm text-red-600 mb-1">期限切れ</div>
                <div class="text-3xl font-bold text-red-600"><?php echo $stats['expired']; ?></div>
                <div class="text-xs text-red-400">件（削除対象）</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">合計</div>
                <div class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></div>
                <div class="text-xs text-gray-400">件の仮登録</div>
            </div>
        </div>

        <!-- アクションボタン -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">クリーンアップ実行</h2>
            <p class="text-gray-600 mb-4">
                1時間以上経過した仮登録ユーザーを削除します。
                削除されたメールアドレスは再度登録可能になります。
            </p>
            
            <form method="POST" onsubmit="return confirm('期限切れの仮登録ユーザーを削除しますか？');">
                <input type="hidden" name="action" value="cleanup">
                <button type="submit" 
                        class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-6 rounded-lg transition duration-200 <?php echo $stats['expired'] == 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                        <?php echo $stats['expired'] == 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-trash-alt mr-2"></i>
                    期限切れユーザーを削除（<?php echo $stats['expired']; ?>件）
                </button>
            </form>
        </div>

        <!-- 期限切れユーザー一覧 -->
        <?php if (count($expired_users) > 0): ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                    期限切れ仮登録ユーザー（最新100件）
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ユーザーID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                メールアドレス
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ニックネーム
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                作成日時
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                経過時間
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($expired_users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars((string)$user['user_id']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars((string)$user['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars((string)$user['nickname']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('Y-m-d H:i:s', strtotime($user['create_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="text-red-600 font-medium">
                                    <?php 
                                    $hours = floor($user['minutes_ago'] / 60);
                                    $minutes = $user['minutes_ago'] % 60;
                                    echo $hours > 0 ? "{$hours}時間{$minutes}分前" : "{$minutes}分前";
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- 設定情報 -->
        <div class="mt-8 bg-blue-50 rounded-lg p-6">
            <h3 class="font-semibold text-blue-800 mb-2">
                <i class="fas fa-info-circle mr-2"></i>システム設定
            </h3>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>・仮登録の有効期限: <strong>1時間</strong></li>
                <li>・推奨cron実行間隔: <strong>10分ごと</strong></li>
                <li>・cron設定: <code class="bg-blue-100 px-2 py-1 rounded">*/10 * * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/clean_interim_users.php</code></li>
            </ul>
        </div>
    </div>
</div>

<?php include('layout/footer.php'); ?>