<?php
/**
 * ユーザー管理画面（リニューアル版）
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
    include(__DIR__ . '/403.php');
    exit;
}

// header.phpでの二重認証チェックを防ぐ
define('SKIP_HEADER_AUTH_CHECK', true);

$page_title = 'ユーザー管理';

// CSRF トークンを生成
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

// アクション処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'セッションの有効期限が切れました。';
    } else {
        $action = $_POST['action'] ?? '';
        $user_id = $_POST['user_id'] ?? '';
        
        if ($user_id && $user_id !== $_SESSION['AUTH_USER']) { // 自分自身は操作できない
            switch ($action) {
                case 'deactivate':
                    $result = $g_db->query("UPDATE b_user SET status = ?, regist_date = NULL WHERE user_id = ?", [USER_STATUS_INACTIVE, $user_id]);
                    
                    if (!DB::isError($result)) {
                        $message = 'ユーザーを無効化しました。';
                    } else {
                        $error = '無効化に失敗しました：' . $result->getMessage();
                    }
                    break;
                    
                case 'activate':
                    $result = $g_db->query(
                        "UPDATE b_user SET status = ?, regist_date = ? WHERE user_id = ? AND status != ?",
                        [USER_STATUS_ACTIVE, date('Y-m-d H:i:s'), $user_id, USER_STATUS_ACTIVE]
                    );
                    
                    if (!DB::isError($result)) {
                        $message = 'ユーザーを有効化しました。';
                    } else {
                        $error = '有効化に失敗しました：' . $result->getMessage();
                    }
                    break;
            }
        }
    }
}

// 検索条件
$search_keyword = trim($_GET['keyword'] ?? '');
$filter_status = $_GET['status'] ?? 'active';


// デバッグ用に現在のステータスを表示
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
}

// WHERE条件構築
$where_conditions = [];
$params = [];

if ($filter_status === 'active') {
    $where_conditions[] = "u.status = " . USER_STATUS_ACTIVE;
} elseif ($filter_status === 'inactive') {
    $where_conditions[] = "u.status = " . USER_STATUS_INACTIVE;
} elseif ($filter_status === 'deleted') {
    $where_conditions[] = "u.status = " . USER_STATUS_DELETED;
} elseif ($filter_status === 'interim') {
    $where_conditions[] = "u.status = " . USER_STATUS_INTERIM;
}
// 'all'の場合は条件を追加しない（すべて表示）

if ($search_keyword) {
    $where_conditions[] = "(u.user_id LIKE ? OR u.nickname LIKE ? OR u.email LIKE ?)";
    $search_pattern = "%{$search_keyword}%";
    $params = array_merge($params, [$search_pattern, $search_pattern, $search_pattern]);
}

$where_clause = buildWhereClause($where_conditions);


// デバッグ情報（開発時のみ）
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
}

// ページネーション
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;

// 統計情報
$active_users_count = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE status = ?", [USER_STATUS_ACTIVE]),
    0
);

// 仮登録ユーザー
$interim_users_count = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE status = ?", [USER_STATUS_INTERIM]),
    0
);

// 無効ユーザー
$inactive_users_count = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE status = ?", [USER_STATUS_INACTIVE]),
    0
);

// 削除済みユーザー
$deleted_users_count = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE status = ?", [USER_STATUS_DELETED]),
    0
);

// 総件数取得
$count_sql = "SELECT COUNT(*) FROM b_user u" . $where_clause;

// デバッグ用
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
}

$count_result = $g_db->getOne($count_sql, $params);
if (DB::isError($count_result)) {
    error_log("User count error: " . $count_result->getMessage());
}
$total_count = safeDbResult($count_result, 0);

$pagination = getPaginationInfo($page, $total_count, $per_page);

// ユーザーリスト取得（基本情報とGoogle連携情報を含む）
// regist_dateがUnixタイムスタンプまたは日付文字列の両方に対応
// LIMIT句は直接埋め込む（PDOの制限のため）
$list_sql = "SELECT u.*, 
             CASE 
                WHEN u.regist_date REGEXP '^[0-9]+$' AND CAST(u.regist_date AS UNSIGNED) > 1000000000
                THEN FROM_UNIXTIME(CAST(u.regist_date AS UNSIGNED))
                ELSE u.regist_date
             END as regist_date_formatted,
             g.google_id,
             g.google_email,
             g.google_name
             FROM b_user u
             LEFT JOIN b_google_auth g ON CAST(u.user_id AS CHAR) = g.user_id " . 
             $where_clause . " 
             ORDER BY u.user_id DESC 
             LIMIT " . intval($per_page) . " OFFSET " . intval($pagination['offset']);

// LIMITとOFFSETはSQLに直接埋め込んだので、検索パラメータのみ使用
$list_params = $params;

// デバッグ用SQL出力
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
}

$list_result = $g_db->getAll($list_sql, $list_params, DB_FETCHMODE_ASSOC);
if (DB::isError($list_result)) {
    error_log("User list error: " . $list_result->getMessage());
}
$users = safeDbResult($list_result, []);

// ユーザーごとの統計情報を取得
foreach ($users as &$user) {
    // 本の数
    $book_count_result = $g_db->getOne(
        "SELECT COUNT(*) FROM b_book_list WHERE user_id = ?",
        [$user['user_id']]
    );
    $user['book_count'] = safeDbResult($book_count_result, 0);
    
    // 読了本の数（eventフィールドを使用）
    $finished_count_result = $g_db->getOne(
        "SELECT COUNT(*) FROM b_book_event WHERE user_id = ? AND event = ?",
        [$user['user_id'], READING_FINISH]
    );
    $user['finished_count'] = safeDbResult($finished_count_result, 0);
    
    // 最終アクティビティ
    $last_activity_result = $g_db->getOne(
        "SELECT MAX(event_date) FROM b_book_event WHERE user_id = ?",
        [$user['user_id']]
    );
    $user['last_activity'] = safeDbResult($last_activity_result, null);
}
unset($user);

include(__DIR__ . '/layout/header.php');
include(__DIR__ . '/layout/submenu.php');
?>

<?php if ($message): ?>
    <?php showFlashMessage('success', $message); ?>
<?php endif; ?>

<?php if ($error): ?>
    <?php showFlashMessage('error', $error); ?>
<?php endif; ?>

<!-- 統計カード -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">総ユーザー数</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo safeNumber($active_users_count + $inactive_users_count); ?></p>
                <p class="text-sm text-gray-500 mt-1">登録済みアカウント</p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">有効ユーザー</p>
                <p class="text-3xl font-bold text-green-600"><?php echo safeNumber($active_users_count); ?></p>
                <p class="text-sm text-gray-500 mt-1">アクティブアカウント</p>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">無効ユーザー</p>
                <p class="text-3xl font-bold text-gray-500"><?php echo safeNumber($inactive_users_count); ?></p>
                <p class="text-sm text-gray-500 mt-1">非アクティブアカウント</p>
            </div>
            <div class="p-3 bg-gray-100 rounded-full">
                <i class="fas fa-user-times text-gray-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- デバッグ情報 -->
<?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
    <p><strong>デバッグ情報:</strong></p>
    <pre><?php
    echo "検索キーワード: " . var_export($search_keyword, true) . "\n";
    echo "ステータス: " . var_export($filter_status, true) . "\n";
    echo "WHERE句: " . htmlspecialchars($where_clause) . "\n";
    echo "パラメータ: " . print_r($params, true);
    echo "総件数: " . $total_count . "\n";
    echo "ユーザー数: " . count($users) . "\n";
    echo "\n定数値:\n";
    echo "USER_STATUS_ACTIVE: " . USER_STATUS_ACTIVE . "\n";
    echo "USER_STATUS_INTERIM: " . USER_STATUS_INTERIM . "\n";
    echo "USER_STATUS_INACTIVE: " . USER_STATUS_INACTIVE . "\n";
    echo "USER_STATUS_DELETED: " . USER_STATUS_DELETED . "\n";
    ?></pre>
</div>
<?php endif; ?>

<!-- フィルター -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6">
        <form method="get" action="users.php" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
            <div class="flex-1">
                <label for="keyword" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search text-gray-400 mr-1"></i>
                    ユーザー検索
                </label>
                <input type="text" 
                       id="keyword" 
                       name="keyword" 
                       value="<?php echo safeHtml($search_keyword); ?>"
                       placeholder="ユーザーID、ニックネーム、メールアドレスで検索"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
            </div>
            
            <div class="md:w-48">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">ステータス</label>
                <select id="status" name="status" onchange="submitStatusForm()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                    <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>
                        本登録 (<?php echo safeNumber($active_users_count); ?>)
                    </option>
                    <option value="interim" <?php echo $filter_status === 'interim' ? 'selected' : ''; ?>>
                        仮登録 (<?php echo safeNumber($interim_users_count); ?>)
                    </option>
                    <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>
                        無効 (<?php echo safeNumber($inactive_users_count); ?>)
                    </option>
                    <option value="deleted" <?php echo $filter_status === 'deleted' ? 'selected' : ''; ?>>
                        削除済み (<?php echo safeNumber($deleted_users_count); ?>)
                    </option>
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>
                        すべて (<?php echo safeNumber($active_users_count + $interim_users_count + $inactive_users_count + $deleted_users_count); ?>)
                    </option>
                </select>
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="px-4 py-2 bg-readnest-primary text-white font-medium rounded-lg hover:bg-readnest-accent transition-colors">
                    <i class="fas fa-search mr-2"></i>検索
                </button>
                
                <?php if ($filter_status !== 'active' || $search_keyword): ?>
                <a href="users.php?status=active" class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>クリア
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ユーザー一覧 -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-users text-readnest-primary mr-2"></i>
                ユーザー一覧
            </h3>
            <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                <?php echo safeNumber($total_count); ?>件
            </span>
        </div>
    </div>
    
    <?php if (empty($users)): ?>
        <div class="p-12 text-center">
            <i class="fas fa-user-slash text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">該当するユーザーがいません</p>
            <p class="text-gray-400 text-sm mt-2">検索条件を変更してお試しください</p>
            <?php if (isset($_GET['debug'])): ?>
            <div class="mt-4 text-left bg-gray-100 p-4 rounded text-xs">
                <p><strong>Debug info:</strong></p>
                <p>Total count: <?php echo $total_count; ?></p>
                <p>Users array count: <?php echo count($users); ?></p>
                <p>Users empty: <?php echo empty($users) ? 'true' : 'false'; ?></p>
                <p>List result is error: <?php echo DB::isError($list_result) ? 'true' : 'false'; ?></p>
                <?php if (DB::isError($list_result)): ?>
                <p>Error: <?php echo htmlspecialchars($list_result->getMessage()); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- モバイル表示 -->
        <div class="md:hidden">
            <?php foreach ($users as $user): ?>
            <div class="p-4 border-b border-gray-200 last:border-b-0">
                <div class="space-y-3">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-1">
                                <h4 class="font-medium text-gray-900">
                                    <?php echo safeHtml($user['nickname']); ?>
                                </h4>
                                
                                <?php if (in_array($user['email'], ADMIN_EMAILS, true)): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-crown mr-1"></i>管理者
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($user['google_id'])): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    G
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($user['x_oauth_token'])): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-900 text-white">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                    X
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($user['regist_date']): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    有効
                                </span>
                                <?php elseif (strpos($user['nickname'], '削除済みユーザー_') === 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    削除済み
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    無効
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-sm text-gray-600"><?php echo safeHtml($user['user_id']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo safeHtml($user['email']); ?></p>
                            
                            <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                <span>
                                    <i class="fas fa-book mr-1"></i>
                                    <?php echo safeNumber($user['book_count']); ?>冊
                                </span>
                                <span>
                                    <i class="fas fa-check-circle text-green-400 mr-1"></i>
                                    <?php echo safeNumber($user['finished_count']); ?>冊読了
                                </span>
                                <span>
                                    <i class="fas fa-calendar mr-1"></i>
                                    <?php echo safeDate($user['regist_date_formatted'] ?? $user['regist_date']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($user['user_id'] !== $_SESSION['AUTH_USER']): ?>
                        <div class="ml-4">
                            <?php if ($user['regist_date']): ?>
                                <?php echo confirmationForm(
                                    'このユーザーを無効化しますか？',
                                    'deactivate',
                                    ['user_id' => $user['user_id']],
                                    '<i class="fas fa-user-times"></i>',
                                    'text-xs text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors'
                                ); ?>
                            <?php else: ?>
                                <?php echo confirmationForm(
                                    'このユーザーを有効化しますか？',
                                    'activate',
                                    ['user_id' => $user['user_id']],
                                    '<i class="fas fa-user-check"></i>',
                                    'text-xs text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 p-2 rounded-lg transition-colors'
                                ); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- デスクトップ表示 -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ユーザー
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            登録日
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            読書統計
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            最終活動
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            連携
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ステータス
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-500"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo safeHtml($user['nickname']); ?>
                                        </p>
                                        
                                        <?php if (in_array($user['email'], ADMIN_EMAILS, true)): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                            <i class="fas fa-crown mr-1"></i>管理者
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-500"><?php echo safeHtml($user['user_id']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo safeHtml($user['email']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo safeDate($user['regist_date_formatted'] ?? $user['regist_date']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="space-y-1">
                                <div class="flex items-center">
                                    <i class="fas fa-book text-gray-400 mr-2 w-4"></i>
                                    <span><?php echo safeNumber($user['book_count']); ?>冊登録</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-400 mr-2 w-4"></i>
                                    <span><?php echo safeNumber($user['finished_count']); ?>冊読了</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo safeDate($user['last_activity'], 'Y/m/d', 'なし'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <?php if (!empty($user['google_id'])): ?>
                                    <div class="group relative">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 cursor-help">
                                            <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24">
                                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                            </svg>
                                            Google
                                        </span>
                                        <div class="hidden group-hover:block absolute z-10 -top-2 left-1/2 transform -translate-x-1/2 -translate-y-full">
                                            <div class="bg-gray-900 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                                                <?php echo safeHtml($user['email'] ?? 'Google連携済み'); ?>
                                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($user['x_oauth_token'])): ?>
                                    <div class="group relative">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-900 text-white cursor-help">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                            </svg>
                                            X
                                        </span>
                                        <div class="hidden group-hover:block absolute z-10 -top-2 left-1/2 transform -translate-x-1/2 -translate-y-full">
                                            <div class="bg-gray-900 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                                                @<?php echo safeHtml($user['x_screen_name'] ?? 'X連携済み'); ?>
                                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (empty($user['google_id']) && empty($user['x_oauth_token'])): ?>
                                    <span class="text-gray-400 text-xs">なし</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($user['regist_date']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>有効
                                </span>
                            <?php elseif (strpos($user['nickname'], '削除済みユーザー_') === 0): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-trash mr-1"></i>削除済み
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-times-circle mr-1"></i>無効
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($user['user_id'] !== $_SESSION['AUTH_USER'] && intval($user['status']) !== USER_STATUS_DELETED): ?>
                                <?php if (intval($user['status']) === USER_STATUS_ACTIVE): ?>
                                    <?php echo confirmationForm(
                                        'このユーザーを無効化しますか？\\n\\nユーザー: ' . $user['nickname'],
                                        'deactivate',
                                        ['user_id' => $user['user_id']],
                                        '<i class="fas fa-user-times mr-1"></i>無効化',
                                        'text-red-600 hover:text-red-800 hover:bg-red-50 px-3 py-1 rounded-lg transition-colors'
                                    ); ?>
                                <?php else: ?>
                                    <?php echo confirmationForm(
                                        'このユーザーを有効化しますか？\\n\\nユーザー: ' . $user['nickname'],
                                        'activate',
                                        ['user_id' => $user['user_id']],
                                        '<i class="fas fa-user-check mr-1"></i>有効化',
                                        'text-green-600 hover:text-green-800 hover:bg-green-50 px-3 py-1 rounded-lg transition-colors'
                                    ); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($pagination['has_prev']): ?>
                    <a href="?page=<?php echo $pagination['prev_page']; ?>&status=<?php echo urlencode($filter_status); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        前へ
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                    <a href="?page=<?php echo $pagination['next_page']; ?>&status=<?php echo urlencode($filter_status); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        次へ
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            <span class="font-medium"><?php echo safeNumber(($pagination['current_page'] - 1) * $pagination['per_page'] + 1); ?></span>
                            -
                            <span class="font-medium"><?php echo safeNumber(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count'])); ?></span>
                            件 / 全
                            <span class="font-medium"><?php echo safeNumber($pagination['total_count']); ?></span>
                            件
                        </p>
                    </div>
                    
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php if ($pagination['has_prev']): ?>
                            <a href="?page=<?php echo $pagination['prev_page']; ?>&status=<?php echo urlencode($filter_status); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $pagination['current_page'] - 2);
                            $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $pagination['current_page'] ? 'z-10 bg-readnest-primary border-readnest-primary text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['has_next']): ?>
                            <a href="?page=<?php echo $pagination['next_page']; ?>&status=<?php echo urlencode($filter_status); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>


<?php include(__DIR__ . '/layout/footer.php'); ?>

<script>
// グローバル関数として定義（onchangeからも呼べるように）
function submitStatusForm() {
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        const form = statusSelect.closest('form');
        if (form) {
            console.log('Submitting form with status:', statusSelect.value);
            // 明示的にactionを設定
            if (!form.action || form.action === '') {
                form.action = 'users.php';
            }
            form.submit();
        }
    }
}

// ページ読み込み完了後に実行
document.addEventListener('DOMContentLoaded', function() {
    console.log('Users.php loaded');
    
    // ステータス選択要素を確認
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        console.log('Status select element found, current value:', statusSelect.value);
    } else {
        console.error('Status select element not found!');
    }
});

// デバッグ：現在のステータス値を表示
console.log('Current filter status:', '<?php echo $filter_status; ?>');
</script>