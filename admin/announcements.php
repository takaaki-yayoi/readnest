<?php
/**
 * お知らせ管理画面（リニューアル版）
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

$page_title = 'お知らせ管理';

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
    // CSRF検証
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'セッションの有効期限が切れました。';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $type = $_POST['type'] ?? 'general';
                
                // タイプの検証
                $valid_types = ['general', 'bug_fix', 'new_feature', 'maintenance'];
                if (!in_array($type, $valid_types)) {
                    $type = 'general';
                }
                
                if (empty($title) || empty($content)) {
                    $error = 'タイトルと内容を入力してください。';
                } else {
                    // タイプフィールドが存在するかチェック
                    $check_column = $g_db->getOne("SHOW COLUMNS FROM b_announcement LIKE 'type'");
                    
                    if ($check_column) {
                        // タイプフィールドがある場合
                        $result = $g_db->query(
                            "INSERT INTO b_announcement (title, content, type, created) VALUES (?, ?, ?, NOW())",
                            [$title, $content, $type]
                        );
                    } else {
                        // タイプフィールドがない場合（下位互換性）
                        $result = $g_db->query(
                            "INSERT INTO b_announcement (title, content, created) VALUES (?, ?, NOW())",
                            [$title, $content]
                        );
                    }
                    
                    if (!DB::isError($result)) {
                        $message = 'お知らせを追加しました。';
                    } else {
                        $error = '追加に失敗しました：' . $result->getMessage();
                    }
                }
                break;
                
            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $type = $_POST['type'] ?? 'general';
                
                // タイプの検証
                $valid_types = ['general', 'bug_fix', 'new_feature', 'maintenance'];
                if (!in_array($type, $valid_types)) {
                    $type = 'general';
                }
                
                if ($id <= 0 || empty($title) || empty($content)) {
                    $error = 'IDが無効、またはタイトルと内容を入力してください。';
                } else {
                    // タイプフィールドが存在するかチェック
                    $check_column = $g_db->getOne("SHOW COLUMNS FROM b_announcement LIKE 'type'");
                    
                    if ($check_column) {
                        // タイプフィールドがある場合
                        $result = $g_db->query(
                            "UPDATE b_announcement SET title = ?, content = ?, type = ? WHERE id = ?",
                            [$title, $content, $type, $id]
                        );
                    } else {
                        // タイプフィールドがない場合（下位互換性）
                        $result = $g_db->query(
                            "UPDATE b_announcement SET title = ?, content = ? WHERE id = ?",
                            [$title, $content, $id]
                        );
                    }
                    
                    if (!DB::isError($result)) {
                        $message = 'お知らせを更新しました。';
                    } else {
                        $error = '更新に失敗しました：' . $result->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $result = $g_db->query("DELETE FROM b_announcement WHERE id = ?", [$id]);
                    
                    if (!DB::isError($result)) {
                        $message = 'お知らせを削除しました。';
                    } else {
                        $error = '削除に失敗しました：' . $result->getMessage();
                    }
                }
                break;
        }
    }
}

// 検索条件
$search_keyword = trim($_GET['keyword'] ?? '');

// WHERE条件構築
$where_conditions = [];
$params = [];

if ($search_keyword) {
    $where_conditions[] = "(title LIKE ? OR content LIKE ?)";
    $search_pattern = "%{$search_keyword}%";
    $params = array_merge($params, [$search_pattern, $search_pattern]);
}

$where_clause = buildWhereClause($where_conditions);

// ページネーション設定
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// 総件数を取得
$count_result = $g_db->getOne("SELECT COUNT(*) FROM b_announcement" . $where_clause, $params);
if (DB::isError($count_result)) {
    error_log("Announcement count error: " . $count_result->getMessage());
}
$total_count = safeDbResult($count_result, 0);

$pagination = getPaginationInfo($page, $total_count, $per_page);

// お知らせ一覧を取得
$list_params = array_merge($params, [$per_page, $pagination['offset']]);
$list_result = $g_db->getAll(
    "SELECT * FROM b_announcement" . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?", 
    $list_params, 
    DB_FETCHMODE_ASSOC
);
if (DB::isError($list_result)) {
    error_log("Announcement list error: " . $list_result->getMessage());
}
$announcements = safeDbResult($list_result, []);


// 日付カラムを自動検出
$date_column = null;
if (!empty($announcements)) {
    $first_row = $announcements[0];
    // 一般的な日付カラム名をチェック
    $possible_date_columns = ['regist_date', 'created_at', 'created', 'date', 'timestamp'];
    foreach ($possible_date_columns as $col) {
        if (isset($first_row[$col])) {
            $date_column = $col;
            break;
        }
    }
}

include('layout/header.php');
?>

<script>
function editAnnouncement(id, title, content, type) {
    // デバッグ: 要素の存在確認
    const editIdElement = document.getElementById('edit_id');
    const editTitleElement = document.getElementById('edit_title');
    const editContentElement = document.getElementById('edit_content');
    const editTypeElement = document.getElementById('edit_type');
    const editModalElement = document.getElementById('editModal');
    
    if (!editIdElement || !editTitleElement || !editContentElement || !editTypeElement || !editModalElement) {
        console.error('編集フォームの要素が見つかりません');
        console.log('edit_id:', editIdElement);
        console.log('edit_title:', editTitleElement);
        console.log('edit_content:', editContentElement);
        console.log('edit_type:', editTypeElement);
        console.log('editModal:', editModalElement);
        return;
    }
    
    // JSONでエンコードされた文字列をデコード
    if (typeof title === 'string' && title.startsWith('"')) {
        title = JSON.parse(title);
    }
    if (typeof content === 'string' && content.startsWith('"')) {
        content = JSON.parse(content);
    }
    if (typeof type === 'string' && type.startsWith('"')) {
        type = JSON.parse(type);
    }
    
    editIdElement.value = id;
    editTitleElement.value = title;
    editContentElement.value = content;
    editTypeElement.value = type;
    editModalElement.classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// DOMContentLoadedで初期化
document.addEventListener('DOMContentLoaded', function() {
    // モーダル外をクリックで閉じる
    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    }
    
    // Escキーで閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && editModal && !editModal.classList.contains('hidden')) {
            closeEditModal();
        }
    });
});
</script>

<?php if ($message): ?>
    <?php showFlashMessage('success', $message); ?>
<?php endif; ?>

<?php if ($error): ?>
    <?php showFlashMessage('error', $error); ?>
<?php endif; ?>

<div class="space-y-6">


<!-- 編集モーダル -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">お知らせを編集</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo safeHtml($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="space-y-4">
                    <div>
                        <label for="edit_title" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-heading text-gray-400 mr-1"></i>
                            タイトル <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="edit_title" 
                               name="title" 
                               required
                               maxlength="255"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent transition-colors">
                    </div>
                    
                    <div>
                        <label for="edit_type" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tag text-gray-400 mr-1"></i>
                            タイプ
                        </label>
                        <select id="edit_type" 
                                name="type"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent transition-colors">
                            <option value="general">一般</option>
                            <option value="new_feature">新機能</option>
                            <option value="bug_fix">不具合修正</option>
                            <option value="maintenance">メンテナンス</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="edit_content" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-align-left text-gray-400 mr-1"></i>
                            内容 <span class="text-red-500">*</span>
                        </label>
                        <textarea id="edit_content" 
                                  name="content" 
                                  required
                                  rows="6"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent transition-colors"></textarea>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" 
                            onclick="closeEditModal()"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                        キャンセル
                    </button>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-admin-accent text-white font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-accent transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        更新する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 検索フォーム -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6">
        <form method="get" action="" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
            <div class="flex-1">
                <label for="keyword" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search text-gray-400 mr-1"></i>
                    お知らせ検索
                </label>
                <input type="text" 
                       id="keyword" 
                       name="keyword" 
                       value="<?php echo safeHtml($search_keyword); ?>"
                       placeholder="タイトルまたは内容で検索"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="px-4 py-2 bg-readnest-primary text-white font-medium rounded-lg hover:bg-readnest-accent transition-colors">
                    <i class="fas fa-search mr-2"></i>検索
                </button>
                
                <?php if ($search_keyword): ?>
                <a href="announcements.php" class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>クリア
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- 新規追加フォーム -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="fas fa-plus-circle text-readnest-primary mr-2"></i>
            新しいお知らせを追加
        </h3>
    </div>
    <form method="post" action="" class="p-6">
        <input type="hidden" name="csrf_token" value="<?php echo safeHtml($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="action" value="create">
        
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-heading text-gray-400 mr-1"></i>
                    タイトル <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       required
                       maxlength="255"
                       placeholder="お知らせのタイトルを入力してください"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent transition-colors">
            </div>
            
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-tag text-gray-400 mr-1"></i>
                    タイプ
                </label>
                <select id="type" 
                        name="type"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent transition-colors">
                    <option value="general">一般</option>
                    <option value="new_feature">新機能</option>
                    <option value="bug_fix">不具合修正</option>
                    <option value="maintenance">メンテナンス</option>
                </select>
            </div>
            
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-align-left text-gray-400 mr-1"></i>
                    内容 <span class="text-red-500">*</span>
                </label>
                <textarea id="content" 
                          name="content" 
                          required
                          rows="6"
                          placeholder="お知らせの内容を入力してください"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent transition-colors"></textarea>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" 
                    class="inline-flex items-center px-6 py-3 bg-admin-accent text-white font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-accent transition-colors shadow-lg">
                <i class="fas fa-plus-circle mr-2"></i>
                お知らせを追加
            </button>
        </div>
    </form>
</div>

<!-- お知らせ一覧 -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-list text-readnest-primary mr-2"></i>
                お知らせ一覧
            </h3>
            <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                <?php echo safeNumber($total_count); ?>件
            </span>
        </div>
    </div>
    
    <?php if (empty($announcements)): ?>
        <div class="p-12 text-center">
            <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">お知らせがありません</p>
            <p class="text-gray-400 text-sm mt-2">上のフォームから新しいお知らせを追加できます</p>
        </div>
    <?php else: ?>
        <div class="overflow-hidden">
            <!-- モバイル表示 -->
            <div class="md:hidden">
                <?php foreach ($announcements as $announcement): ?>
                <div class="p-4 border-b border-gray-200 last:border-b-0">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <h4 class="font-medium text-gray-900 text-sm leading-tight">
                                <?php echo safeHtml($announcement['title']); ?>
                            </h4>
                            <span class="text-xs text-gray-500 ml-2 whitespace-nowrap">
                                #<?php echo safeHtml($announcement['id']); ?>
                            </span>
                        </div>
                        
                        <p class="text-sm text-gray-600 line-clamp-2">
                            <?php echo safeHtml($announcement['content'] ?? ''); ?>
                        </p>
                        
                        <?php if (isset($announcement['type'])): ?>
                        <div class="mt-2">
                            <?php
                            $type_badges = [
                                'general' => ['class' => 'bg-gray-100 text-gray-700', 'icon' => 'info-circle', 'label' => '一般'],
                                'new_feature' => ['class' => 'bg-green-100 text-green-700', 'icon' => 'sparkles', 'label' => '新機能'],
                                'bug_fix' => ['class' => 'bg-red-100 text-red-700', 'icon' => 'bug', 'label' => '不具合修正'],
                                'maintenance' => ['class' => 'bg-yellow-100 text-yellow-700', 'icon' => 'wrench', 'label' => 'メンテナンス']
                            ];
                            $badge = $type_badges[$announcement['type']] ?? $type_badges['general'];
                            ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $badge['class']; ?>">
                                <i class="fas fa-<?php echo $badge['icon']; ?> mr-1"></i>
                                <?php echo $badge['label']; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center justify-between pt-2">
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                <?php 
                                if ($date_column && isset($announcement[$date_column])) {
                                    echo safeDate($announcement[$date_column], 'Y/m/d H:i');
                                } else {
                                    echo 'ID: ' . safeHtml($announcement['id']);
                                }
                                ?>
                            </span>
                            
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        onclick="setTimeout(() => editAnnouncement(<?php echo intval($announcement['id']); ?>, <?php echo htmlspecialchars(json_encode($announcement['title']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($announcement['content'] ?? ''), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($announcement['type'] ?? 'general'), ENT_QUOTES); ?>), 0)" 
                                        class="text-xs text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-lg transition-colors">
                                    <i class="fas fa-edit mr-1"></i>編集
                                </button>
                                <?php echo confirmationForm(
                                    'このお知らせを削除しますか？',
                                    'delete',
                                    ['id' => $announcement['id']],
                                    '<i class="fas fa-trash mr-1"></i>削除',
                                    'text-xs text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-lg transition-colors'
                                ); ?>
                            </div>
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
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                タイトル
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                タイプ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                内容
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                登録日時
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($announcements as $announcement): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                #<?php echo safeHtml($announcement['id']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs">
                                    <p class="font-medium truncate" title="<?php echo safeHtml($announcement['title']); ?>">
                                        <?php echo safeHtml($announcement['title']); ?>
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if (isset($announcement['type'])): ?>
                                <?php
                                $type_badges = [
                                    'general' => ['class' => 'bg-gray-100 text-gray-700', 'icon' => 'info-circle', 'label' => '一般'],
                                    'new_feature' => ['class' => 'bg-green-100 text-green-700', 'icon' => 'sparkles', 'label' => '新機能'],
                                    'bug_fix' => ['class' => 'bg-red-100 text-red-700', 'icon' => 'bug', 'label' => '不具合修正'],
                                    'maintenance' => ['class' => 'bg-yellow-100 text-yellow-700', 'icon' => 'wrench', 'label' => 'メンテナンス']
                                ];
                                $badge = $type_badges[$announcement['type']] ?? $type_badges['general'];
                                ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $badge['class']; ?>">
                                    <i class="fas fa-<?php echo $badge['icon']; ?> mr-1"></i>
                                    <?php echo $badge['label']; ?>
                                </span>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="max-w-md">
                                    <p class="line-clamp-2" title="<?php echo safeHtml($announcement['content'] ?? ''); ?>">
                                        <?php echo safeHtml($announcement['content'] ?? ''); ?>
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($date_column && isset($announcement[$date_column])): ?>
                                <div class="flex flex-col">
                                    <span><?php echo safeDate($announcement[$date_column], 'Y/m/d'); ?></span>
                                    <span class="text-xs text-gray-400"><?php echo safeDate($announcement[$date_column], 'H:i'); ?></span>
                                </div>
                                <?php else: ?>
                                <span>ID: <?php echo safeHtml($announcement['id']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2">
                                    <button type="button" 
                                            onclick="setTimeout(() => editAnnouncement(<?php echo intval($announcement['id']); ?>, <?php echo htmlspecialchars(json_encode($announcement['title']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($announcement['content'] ?? ''), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($announcement['type'] ?? 'general'), ENT_QUOTES); ?>), 0)" 
                                            class="text-blue-600 hover:text-blue-800 hover:bg-blue-50 px-3 py-1 rounded-lg transition-colors">
                                        <i class="fas fa-edit mr-1"></i>編集
                                    </button>
                                    <?php echo confirmationForm(
                                        'このお知らせを削除しますか？\\n\\nタイトル: ' . str_replace("'", "\\'", $announcement['title']),
                                        'delete',
                                        ['id' => $announcement['id']],
                                        '<i class="fas fa-trash mr-1"></i>削除',
                                        'text-red-600 hover:text-red-800 hover:bg-red-50 px-3 py-1 rounded-lg transition-colors'
                                    ); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?page=<?php echo $pagination['prev_page']; ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            前へ
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?php echo $pagination['next_page']; ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
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
                                <a href="?page=<?php echo $pagination['prev_page']; ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $pagination['current_page'] - 2);
                            $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $pagination['current_page'] ? 'z-10 bg-readnest-primary border-readnest-primary text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['has_next']): ?>
                                <a href="?page=<?php echo $pagination['next_page']; ?>&keyword=<?php echo urlencode($search_keyword); ?>" 
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



<?php include('layout/footer.php'); ?>