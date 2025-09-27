<?php
/**
 * 問い合わせ管理画面（リニューアル版）
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

$page_title = '問い合わせ管理';

// CSRF トークンを生成
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

// ステータスラベル
$status_labels = [
    'new' => '新規',
    'in_progress' => '対応中',
    'resolved' => '解決済み',
    'closed' => 'クローズ'
];

$status_colors = [
    'new' => 'bg-red-100 text-red-800',
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'resolved' => 'bg-green-100 text-green-800',
    'closed' => 'bg-gray-100 text-gray-800'
];

$category_labels = [
    'question' => '使い方に関する質問',
    'request' => '機能改善のご要望',
    'bug' => '不具合の報告',
    'other' => 'その他'
];

// アクション処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'セッションの有効期限が切れました。';
    } else {
        $action = $_POST['action'] ?? '';
        $id = intval($_POST['id'] ?? 0);
        
        if ($id > 0) {
            switch ($action) {
                case 'update_status':
                    $status = $_POST['status'] ?? '';
                    $admin_notes = trim($_POST['admin_notes'] ?? '');
                    
                    if (array_key_exists($status, $status_labels)) {
                        $result = $g_db->query(
                            "UPDATE b_contact SET status = ?, admin_notes = ?, updated_at = ? WHERE id = ?",
                            [$status, $admin_notes, date('Y-m-d H:i:s'), $id]
                        );
                        
                        if (!DB::isError($result)) {
                            $message = 'ステータスを更新しました。';
                        } else {
                            $error = '更新に失敗しました：' . $result->getMessage();
                        }
                    } else {
                        $error = '無効なステータスです。';
                    }
                    break;
                    
                case 'delete':
                    $result = $g_db->query("DELETE FROM b_contact WHERE id = ?", [$id]);
                    
                    if (!DB::isError($result)) {
                        $message = '問い合わせを削除しました。';
                    } else {
                        $error = '削除に失敗しました：' . $result->getMessage();
                    }
                    break;
            }
        }
    }
}

// 検索・フィルター条件
$filter_status = $_GET['status'] ?? '';
$filter_category = $_GET['category'] ?? '';
$search_keyword = trim($_GET['keyword'] ?? '');

// WHERE条件構築
$where_conditions = [];
$params = [];

if ($filter_status && array_key_exists($filter_status, $status_labels)) {
    $where_conditions[] = "status = ?";
    $params[] = $filter_status;
}

if ($filter_category && array_key_exists($filter_category, $category_labels)) {
    $where_conditions[] = "category = ?";
    $params[] = $filter_category;
}

if ($search_keyword) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_pattern = "%{$search_keyword}%";
    $params = array_merge($params, [$search_pattern, $search_pattern, $search_pattern, $search_pattern]);
}

$where_clause = buildWhereClause($where_conditions);

// ページネーション
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// 総件数とリスト取得
$count_result = $g_db->getOne("SELECT COUNT(*) FROM b_contact" . $where_clause, $params);
if (DB::isError($count_result)) {
    error_log("Contact count error: " . $count_result->getMessage());
}
$total_count = safeDbResult($count_result, 0);

// デバッグ情報

$pagination = getPaginationInfo($page, $total_count, $per_page);

// list_paramsを先に定義
$list_params = array_merge($params, [$per_page, $pagination['offset']]);

// 通常のパラメータ付きクエリ
$list_result = $g_db->getAll("SELECT * FROM b_contact" . $where_clause . " ORDER BY created_at DESC LIMIT ? OFFSET ?", $list_params, DB_FETCHMODE_ASSOC);
if (DB::isError($list_result)) {
    error_log("Contact list error: " . $list_result->getMessage());
    // created_atカラムが存在しない場合はidで並び替え
    $list_result = $g_db->getAll("SELECT * FROM b_contact" . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?", $list_params, DB_FETCHMODE_ASSOC);
    if (DB::isError($list_result)) {
        error_log("Contact list error (id sort): " . $list_result->getMessage());
    }
}
$contacts = safeDbResult($list_result, []);

// デバッグ情報

// ステータス別件数
$status_counts_result = safeDbResult(
    $g_db->getAll("SELECT status, COUNT(*) as count FROM b_contact GROUP BY status", null, DB_FETCHMODE_ASSOC),
    []
);

$status_counts = [];
foreach ($status_counts_result as $row) {
    $status_counts[$row['status']] = $row['count'];
}

include('layout/header.php');
?>

<?php if ($message): ?>
    <?php showFlashMessage('success', $message); ?>
<?php endif; ?>

<?php if ($error): ?>
    <?php showFlashMessage('error', $error); ?>
<?php endif; ?>

<!-- 統計カード -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <?php foreach ($status_labels as $status => $label): ?>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600"><?php echo safeHtml($label); ?></p>
                <p class="text-2xl font-bold <?php echo $status === 'new' ? 'text-red-600' : 'text-gray-900'; ?>">
                    <?php echo safeNumber($status_counts[$status] ?? 0); ?>
                </p>
            </div>
            <div class="<?php echo $status_colors[$status]; ?> p-2 rounded-full">
                <i class="fas <?php echo $status === 'new' ? 'fa-bell' : ($status === 'in_progress' ? 'fa-clock' : ($status === 'resolved' ? 'fa-check' : 'fa-archive')); ?>"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- フィルター -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6">
        <form method="get" action="" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
            <div class="flex-1">
                <label for="keyword" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search text-gray-400 mr-1"></i>
                    キーワード検索
                </label>
                <input type="text" 
                       id="keyword" 
                       name="keyword" 
                       value="<?php echo safeHtml($search_keyword); ?>"
                       placeholder="名前、メール、件名、内容で検索"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
            </div>
            
            <div class="md:w-48">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">ステータス</label>
                <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                    <option value="">すべて</option>
                    <?php foreach ($status_labels as $status => $label): ?>
                    <option value="<?php echo safeHtml($status); ?>" <?php echo $filter_status === $status ? 'selected' : ''; ?>>
                        <?php echo safeHtml($label); ?> (<?php echo safeNumber($status_counts[$status] ?? 0); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:w-48">
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">種別</label>
                <select id="category" name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                    <option value="">すべて</option>
                    <?php foreach ($category_labels as $category => $label): ?>
                    <option value="<?php echo safeHtml($category); ?>" <?php echo $filter_category === $category ? 'selected' : ''; ?>>
                        <?php echo safeHtml($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="px-4 py-2 bg-readnest-primary text-white font-medium rounded-lg hover:bg-readnest-accent transition-colors">
                    <i class="fas fa-search mr-2"></i>検索
                </button>
                
                <?php if ($filter_status || $filter_category || $search_keyword): ?>
                <a href="contacts.php" class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>クリア
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- 問い合わせ一覧 -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-envelope text-readnest-primary mr-2"></i>
                問い合わせ一覧
            </h3>
            <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                <?php echo safeNumber($total_count); ?>件
            </span>
        </div>
    </div>
    
    <?php if (empty($contacts)): ?>
        <div class="p-12 text-center">
            <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">該当する問い合わせがありません</p>
            <p class="text-gray-400 text-sm mt-2">検索条件を変更してお試しください</p>
            <?php if (isset($_GET['debug'])): ?>
            <div class="mt-4 text-left bg-gray-100 p-4 rounded">
                <p class="text-xs font-bold">Debug info:</p>
                <p class="text-xs">Total count: <?php echo $total_count; ?></p>
                <p class="text-xs">WHERE clause: <?php echo htmlspecialchars($where_clause); ?></p>
                <p class="text-xs">Params: <?php echo htmlspecialchars(print_r($params, true)); ?></p>
                <p class="text-xs">List params: <?php echo htmlspecialchars(print_r($list_params, true)); ?></p>
                <p class="text-xs">Filter status: <?php echo htmlspecialchars($filter_status); ?></p>
                <p class="text-xs">Filter category: <?php echo htmlspecialchars($filter_category); ?></p>
                <p class="text-xs">Search keyword: <?php echo htmlspecialchars($search_keyword); ?></p>
                <p class="text-xs">Contacts array count: <?php echo count($contacts); ?></p>
                <p class="text-xs">Contacts empty check: <?php echo empty($contacts) ? 'true' : 'false'; ?></p>
                <?php if (!empty($contacts)): ?>
                <p class="text-xs">First contact: <?php echo htmlspecialchars(print_r($contacts[0] ?? 'none', true)); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="space-y-0">
            <?php foreach ($contacts as $contact): ?>
            <div class="border-b border-gray-200 last:border-b-0">
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="space-y-4">
                        <!-- ヘッダー -->
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_colors[$contact['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo safeHtml(getStatusLabel($contact['status'], $status_labels)); ?>
                                    </span>
                                    
                                    <span class="text-sm text-gray-500">
                                        <?php echo safeHtml(getStatusLabel($contact['category'], $category_labels)); ?>
                                    </span>
                                    
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo safeDate($contact['created_at'], 'Y/m/d H:i'); ?>
                                    </span>
                                </div>
                                
                                <h4 class="text-lg font-medium text-gray-900 mb-1">
                                    <?php echo safeHtml($contact['subject']); ?>
                                </h4>
                                
                                <div class="text-sm text-gray-600 flex items-center space-x-4 mb-3">
                                    <span>
                                        <i class="fas fa-user mr-1"></i>
                                        <?php echo safeHtml($contact['name']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-envelope mr-1"></i>
                                        <?php echo safeHtml($contact['email']); ?>
                                    </span>
                                    <?php if ($contact['user_id']): ?>
                                    <span>
                                        <i class="fas fa-id-card mr-1"></i>
                                        User ID: <?php echo safeHtml($contact['user_id']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2 ml-4">
                                <button onclick="toggleContactDetails(<?php echo $contact['id']; ?>)" 
                                        class="text-sm text-readnest-primary hover:text-readnest-accent">
                                    <i class="fas fa-edit mr-1"></i>編集
                                </button>
                                
                                <?php echo confirmationForm(
                                    'この問い合わせを削除しますか？',
                                    'delete',
                                    ['id' => $contact['id']],
                                    '<i class="fas fa-trash mr-1"></i>削除',
                                    'text-sm text-red-600 hover:text-red-800'
                                ); ?>
                            </div>
                        </div>
                        
                        <!-- メッセージ内容 -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap"><?php echo safeHtml($contact['message']); ?></p>
                        </div>
                        
                        <!-- 管理者メモ -->
                        <?php if ($contact['admin_notes']): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm font-medium text-yellow-800 mb-1">
                                <i class="fas fa-sticky-note mr-1"></i>管理者メモ
                            </p>
                            <p class="text-sm text-gray-700"><?php echo safeHtml($contact['admin_notes']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 編集フォーム（初期は非表示） -->
                        <div id="contact-details-<?php echo $contact['id']; ?>" class="hidden">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <form method="post" action="" class="space-y-4">
                                    <input type="hidden" name="csrf_token" value="<?php echo safeHtml($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">ステータス</label>
                                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                                                <?php foreach ($status_labels as $status => $label): ?>
                                                <option value="<?php echo safeHtml($status); ?>" <?php echo $contact['status'] === $status ? 'selected' : ''; ?>>
                                                    <?php echo safeHtml($label); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">管理者メモ</label>
                                            <textarea name="admin_notes" 
                                                      rows="3" 
                                                      placeholder="内部メモを入力してください"
                                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent"><?php echo safeHtml($contact['admin_notes'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end space-x-2">
                                        <button type="button" 
                                                onclick="toggleContactDetails(<?php echo $contact['id']; ?>)" 
                                                class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                            キャンセル
                                        </button>
                                        <button type="submit" 
                                                class="px-4 py-2 bg-readnest-primary text-white font-medium rounded-lg hover:bg-readnest-accent transition-colors">
                                            <i class="fas fa-save mr-2"></i>保存
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($pagination['has_prev']): ?>
                    <a href="?page=<?php echo $pagination['prev_page']; ?>&status=<?php echo urlencode($filter_status); ?>&category=<?php echo urlencode($filter_category); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        前へ
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                    <a href="?page=<?php echo $pagination['next_page']; ?>&status=<?php echo urlencode($filter_status); ?>&category=<?php echo urlencode($filter_category); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
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
                            <a href="?page=<?php echo $pagination['prev_page']; ?>&status=<?php echo urlencode($filter_status); ?>&category=<?php echo urlencode($filter_category); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $pagination['current_page'] - 2);
                            $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&category=<?php echo urlencode($filter_category); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $pagination['current_page'] ? 'z-10 bg-readnest-primary border-readnest-primary text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['has_next']): ?>
                            <a href="?page=<?php echo $pagination['next_page']; ?>&status=<?php echo urlencode($filter_status); ?>&category=<?php echo urlencode($filter_category); ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
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

<script>
function toggleContactDetails(id) {
    const element = document.getElementById('contact-details-' + id);
    element.classList.toggle('hidden');
}
</script>

<?php include('layout/footer.php'); ?>