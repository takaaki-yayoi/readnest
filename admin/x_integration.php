<?php
/**
 * X (Twitter) Integration Management
 * X連携管理画面
 * PHP 8.2.28対応版
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

$page_title = 'X連携管理';

// アクション処理
$action_message = '';
$action_type = '';


// 連携解除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disconnect_user'])) {
    $disconnect_user_id = $_POST['user_id'] ?? '';
    if ($disconnect_user_id) {
        $sql = "UPDATE b_user SET 
                x_oauth_token = NULL,
                x_oauth_token_secret = NULL,
                x_screen_name = NULL,
                x_user_id = NULL,
                x_connected_at = NULL,
                x_post_enabled = 0
                WHERE user_id = ?";
        $result = $g_db->query($sql, array($disconnect_user_id));
        if (!DB::isError($result)) {
            $action_message = "ユーザー {$disconnect_user_id} のX連携を解除しました。";
            $action_type = 'success';
        } else {
            $action_message = "連携解除に失敗しました: " . $result->getMessage();
            $action_type = 'error';
        }
    }
}

// X API設定の確認
$x_api_configured = false;
$x_api_key_length = 0;
$x_api_secret_length = 0;

if (file_exists(BASEDIR . '/config/x_api.php')) {
    require_once BASEDIR . '/config/x_api.php';
    $x_api_configured = defined('X_API_KEY') && defined('X_API_SECRET') && X_API_KEY && X_API_SECRET;
    if ($x_api_configured) {
        $x_api_key_length = strlen(X_API_KEY);
        $x_api_secret_length = strlen(X_API_SECRET);
    }
}

// データベースカラムの確認
$db_columns_exist = false;
$x_columns = [];
$check_sql = "SHOW COLUMNS FROM b_user LIKE 'x_%'";
$result = $g_db->query($check_sql);
if (!DB::isError($result)) {
    // PDO互換性のため、fetchRow()の代わりにfetch()を使用
    if ($result instanceof PDOStatement) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $x_columns[] = $row;
        }
    } else {
        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $x_columns[] = $row;
        }
    }
    $db_columns_exist = count($x_columns) > 0;
}

// 連携済みユーザーの取得
$connected_users_sql = "SELECT user_id, nickname, email, x_oauth_token, x_screen_name, x_user_id, x_connected_at, x_post_enabled 
                        FROM b_user 
                        WHERE x_oauth_token IS NOT NULL AND x_oauth_token != ''
                        ORDER BY x_connected_at DESC";
$connected_users_result = $g_db->query($connected_users_sql);
$connected_users = [];
if (!DB::isError($connected_users_result)) {
    // getAll()メソッドを使用してすべての行を取得
    $connected_users = $g_db->getAll($connected_users_sql, array(), DB_FETCHMODE_ASSOC);
    if (DB::isError($connected_users)) {
        $connected_users = [];
    }
}

// X関連エラーログの取得
$error_log_path = '/home/icotfeels/readnest.jp/log/dokusho_error_log.txt';
$x_errors = [];
if (file_exists($error_log_path) && is_readable($error_log_path)) {
    $log_content = file_get_contents($error_log_path);
    $entries = explode('-----------------------------------------', $log_content);
    foreach ($entries as $entry) {
        if (stripos($entry, 'x_connect') !== false || 
            stripos($entry, 'x_callback') !== false || 
            stripos($entry, 'oauth') !== false ||
            stripos($entry, 'X_API') !== false ||
            stripos($entry, 'twitter') !== false) {
            $x_errors[] = trim($entry);
        }
    }
    // 最新の10件のみ表示
    $x_errors = array_slice($x_errors, -10);
    $x_errors = array_reverse($x_errors);
}

// 統計情報の取得
$total_users = safeDbResult($g_db->getOne("SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL"), 0);
$connected_count = count($connected_users);
$enabled_count = 0;
foreach ($connected_users as $user) {
    if ($user['x_post_enabled']) {
        $enabled_count++;
    }
}


include('layout/header.php');
include('layout/submenu.php');
?>

<!-- アクションメッセージ -->
<?php if ($action_message): ?>
    <div class="mb-4 p-4 rounded-lg <?php echo $action_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo htmlspecialchars($action_message); ?>
    </div>
<?php endif; ?>

<!-- 統計情報 -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fab fa-x-twitter text-blue-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">X連携ユーザー</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo safeNumber($connected_count); ?> / <?php echo safeNumber($total_users); ?></p>
                <p class="text-xs text-gray-500">全ユーザー中</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-toggle-on text-green-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">自動投稿有効</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo safeNumber($enabled_count); ?></p>
                <p class="text-xs text-gray-500">連携ユーザー中</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 <?php echo $x_api_configured ? 'bg-green-100' : 'bg-red-100'; ?> rounded-full">
                <i class="fas fa-key <?php echo $x_api_configured ? 'text-green-600' : 'text-red-600'; ?> text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">API設定</p>
                <p class="text-2xl font-bold <?php echo $x_api_configured ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $x_api_configured ? '設定済み' : '未設定'; ?>
                </p>
                <p class="text-xs text-gray-500">
                    <?php if ($x_api_configured): ?>
                        Key: <?php echo $x_api_key_length; ?>文字, Secret: <?php echo $x_api_secret_length; ?>文字
                    <?php else: ?>
                        x_api.phpを確認してください
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- システムステータス -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="p-6 border-b">
        <h3 class="text-lg font-semibold text-gray-900">システムステータス</h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            <!-- API設定 -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">X API設定</p>
                    <p class="text-sm text-gray-500">config/x_api.php</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $x_api_configured ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $x_api_configured ? '正常' : '要確認'; ?>
                </span>
            </div>
            
            <!-- データベースカラム -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">データベースカラム</p>
                    <p class="text-sm text-gray-500">b_userテーブル x_* カラム</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $db_columns_exist ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $db_columns_exist ? '正常 (' . count($x_columns) . 'カラム)' : '要確認'; ?>
                </span>
            </div>
            
            <!-- エラーログ -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">X関連エラー</p>
                    <p class="text-sm text-gray-500">過去のエラーログ</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo empty($x_errors) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                    <?php echo empty($x_errors) ? 'エラーなし' : count($x_errors) . '件のエラー'; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- 連携済みユーザー一覧 -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="p-6 border-b">
        <h3 class="text-lg font-semibold text-gray-900">X連携済みユーザー</h3>
    </div>
    <div class="p-6">
        <?php if (empty($connected_users)): ?>
            <p class="text-gray-500 text-center py-4">X連携しているユーザーはいません</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Xアカウント</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">連携日時</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">自動投稿</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($connected_users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo safeHtml($user['nickname']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo safeHtml($user['user_id']); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php if ($user['x_screen_name']): ?>
                                            <a href="https://x.com/<?php echo urlencode($user['x_screen_name']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                                @<?php echo safeHtml($user['x_screen_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-500">未設定</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-gray-500">ID: <?php echo safeHtml($user['x_user_id'] ?? '不明'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo safeDate($user['x_connected_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $user['x_post_enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $user['x_post_enabled'] ? '有効' : '無効'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <form method="POST" class="inline-block" onsubmit="return confirm('このユーザーのX連携を解除しますか？');">
                                        <input type="hidden" name="user_id" value="<?php echo safeHtml($user['user_id']); ?>">
                                        <button type="submit" name="disconnect_user" class="text-red-600 hover:text-red-900">
                                            連携解除
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- デバッグ情報 -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="p-6 border-b flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">デバッグ情報</h3>
        <button onclick="toggleDebug()" class="text-sm text-blue-600 hover:underline">
            <span id="debug-toggle-text">表示</span>
        </button>
    </div>
    <div id="debug-content" class="p-6" style="display: none;">
        <!-- データベースカラム詳細 -->
        <div class="mb-6">
            <h4 class="font-medium mb-2">X連携用データベースカラム</h4>
            <?php if (!empty($x_columns)): ?>
                <div class="bg-gray-50 p-4 rounded">
                    <table class="text-sm">
                        <thead>
                            <tr>
                                <th class="text-left pr-4">カラム名</th>
                                <th class="text-left pr-4">データ型</th>
                                <th class="text-left pr-4">Null許可</th>
                                <th class="text-left">デフォルト値</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($x_columns as $column): ?>
                                <tr>
                                    <td class="pr-4"><?php echo htmlspecialchars($column['Field']); ?></td>
                                    <td class="pr-4"><?php echo htmlspecialchars($column['Type']); ?></td>
                                    <td class="pr-4"><?php echo htmlspecialchars($column['Null']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-red-600">X連携用カラムが見つかりません</p>
            <?php endif; ?>
        </div>
        
        <!-- OAuth署名テスト -->
        <div class="mb-6">
            <h4 class="font-medium mb-2">OAuth署名生成テスト</h4>
            <?php if ($x_api_configured): ?>
                <?php
                $oauth_params = [
                    'oauth_callback' => 'https://readnest.jp/x_callback.php',
                    'oauth_consumer_key' => X_API_KEY,
                    'oauth_nonce' => 'test_nonce_12345',
                    'oauth_signature_method' => 'HMAC-SHA1',
                    'oauth_timestamp' => '1234567890',
                    'oauth_version' => '1.0'
                ];
                
                ksort($oauth_params);
                $param_string = '';
                foreach ($oauth_params as $key => $value) {
                    $param_string .= rawurlencode($key) . '=' . rawurlencode((string)$value) . '&';
                }
                $param_string = rtrim($param_string, '&');
                
                $signature_base = 'POST&' . rawurlencode('https://api.twitter.com/oauth/request_token') . '&' . rawurlencode($param_string);
                $signing_key = rawurlencode(X_API_SECRET) . '&';
                $oauth_signature = base64_encode(hash_hmac('sha1', $signature_base, $signing_key, true));
                ?>
                <div class="bg-gray-50 p-4 rounded text-xs font-mono">
                    <p class="mb-2"><strong>署名ベース文字列:</strong></p>
                    <p class="break-all mb-4"><?php echo htmlspecialchars($signature_base); ?></p>
                    <p><strong>生成された署名:</strong> <?php echo htmlspecialchars($oauth_signature); ?></p>
                </div>
            <?php else: ?>
                <p class="text-red-600">API設定が不完全なため、署名テストを実行できません</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ログ管理へのリンク -->
<div class="bg-blue-50 border border-blue-200 rounded-lg shadow">
    <div class="p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-blue-900 mb-2">
                    <i class="fas fa-file-alt mr-2"></i>ログの確認
                </h3>
                <p class="text-sm text-blue-700">
                    X連携に関するエラーログやシステムログを確認するには、ログ管理ページをご利用ください。
                </p>
                <?php if (!empty($x_errors)): ?>
                    <p class="text-sm text-red-600 mt-2">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        <?php echo count($x_errors); ?>件のX関連エラーが検出されています。
                    </p>
                <?php endif; ?>
            </div>
            <a href="/admin/logs.php?type=x_api" 
               class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                <i class="fas fa-external-link-alt mr-2"></i>ログ管理を開く
            </a>
        </div>
    </div>
</div>

<script>
function toggleDebug() {
    const content = document.getElementById('debug-content');
    const toggleText = document.getElementById('debug-toggle-text');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggleText.textContent = '非表示';
    } else {
        content.style.display = 'none';
        toggleText.textContent = '表示';
    }
}
</script>

<?php include('layout/footer.php'); ?>