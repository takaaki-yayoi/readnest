<?php
/**
 * 書籍画像管理ページ
 * 有害コンテンツの検知・削除機能付き
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

requireAdmin();

$page_title = '書籍画像管理';
$message = '';
$message_type = '';
$books = [];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// 検索条件
$search_type = $_GET['type'] ?? 'all';
$search_query = $_GET['q'] ?? '';

// 画像削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'セキュリティトークンが無効です。';
        $message_type = 'error';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'delete_image' && isset($_POST['book_id'])) {
            $book_id = (int)$_POST['book_id'];
            
            // 現在の画像URLを取得
            $current_image = $g_db->getOne(
                "SELECT image_url FROM b_book_list WHERE book_id = ?",
                [$book_id]
            );
            
            if (!DB::isError($current_image)) {
                // ユーザーアップロード画像の場合はファイルも削除
                if (strpos($current_image, '/img/user_uploads/') === 0) {
                    $filepath = dirname(__DIR__) . $current_image;
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                }
                
                // データベースから画像URLを削除（管理者操作なのでupdate_dateは変更しない）
                $update_result = $g_db->query(
                    "UPDATE b_book_list SET image_url = NULL WHERE book_id = ?",
                    [$book_id]
                );
                
                if (!DB::isError($update_result)) {
                    $message = "Book ID {$book_id} の画像を削除しました。";
                    $message_type = 'success';
                    
                    // 管理ログに記録
                    error_log("Admin image deletion: book_id={$book_id}, admin={$_SESSION['admin_email']}");
                } else {
                    $message = '画像の削除に失敗しました。';
                    $message_type = 'error';
                }
            }
        } elseif ($action === 'replace_image' && isset($_POST['book_id']) && isset($_POST['new_url'])) {
            $book_id = (int)$_POST['book_id'];
            $new_url = $_POST['new_url'];
            
            // URLの検証
            if (filter_var($new_url, FILTER_VALIDATE_URL) || strpos($new_url, '/') === 0) {
                $update_result = $g_db->query(
                    "UPDATE b_book_list SET image_url = ? WHERE book_id = ?",
                    [$new_url, $book_id]
                );
                
                if (!DB::isError($update_result)) {
                    $message = "Book ID {$book_id} の画像を更新しました。";
                    $message_type = 'success';
                } else {
                    $message = '画像の更新に失敗しました。';
                    $message_type = 'error';
                }
            } else {
                $message = '無効なURLです。';
                $message_type = 'error';
            }
        }
    }
}

// 書籍リストを取得
$where_conditions = ["1=1"];
$params = [];

if ($search_type === 'user_uploads') {
    $where_conditions[] = "image_url LIKE '/img/user_uploads/%'";
} elseif ($search_type === 'external') {
    $where_conditions[] = "(image_url LIKE 'http://%' OR image_url LIKE 'https://%')";
} elseif ($search_type === 'missing') {
    $where_conditions[] = "(image_url IS NULL OR image_url = '')";
} elseif ($search_type === 'suspicious') {
    // 疑わしい画像（特定のパターンにマッチ）
    $where_conditions[] = "(
        image_url LIKE '%adult%' OR 
        image_url LIKE '%xxx%' OR 
        image_url LIKE '%porn%' OR
        image_url LIKE '%nsfw%'
    )";
}

if (!empty($search_query)) {
    $where_conditions[] = "(name LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $search_pattern = '%' . $search_query . '%';
    $params = array_merge($params, [$search_pattern, $search_pattern, $search_pattern]);
}

$where_clause = implode(' AND ', $where_conditions);

// 総件数を取得
$total_count = $g_db->getOne(
    "SELECT COUNT(*) FROM b_book_list WHERE $where_clause",
    $params
);

// 書籍データを取得
$sql = sprintf(
    "SELECT book_id, name, author, isbn, image_url, update_date 
     FROM b_book_list 
     WHERE %s 
     ORDER BY book_id DESC 
     LIMIT %d, %d",
    $where_clause,
    $offset,
    $per_page
);

$books = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);

// ページネーション計算
$total_pages = ceil($total_count / $per_page);

// CSRFトークン生成
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include('layout/header.php');
?>

<div class="max-w-7xl mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6">書籍画像管理</h1>
    
    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?php 
        echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 
             'bg-red-100 text-red-700 border border-red-200'; 
    ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
    
    <!-- 検索フォーム -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="get" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">画像タイプ</label>
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded">
                        <option value="all" <?php echo $search_type === 'all' ? 'selected' : ''; ?>>すべて</option>
                        <option value="user_uploads" <?php echo $search_type === 'user_uploads' ? 'selected' : ''; ?>>ユーザーアップロード</option>
                        <option value="external" <?php echo $search_type === 'external' ? 'selected' : ''; ?>>外部URL</option>
                        <option value="missing" <?php echo $search_type === 'missing' ? 'selected' : ''; ?>>画像なし</option>
                        <option value="suspicious" <?php echo $search_type === 'suspicious' ? 'selected' : ''; ?>>要確認</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">検索</label>
                    <input type="text" 
                           name="q" 
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="書名、著者、ISBN"
                           class="w-full px-4 py-2 border border-gray-300 rounded">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        検索
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- 統計情報 -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">統計情報</h2>
        <div class="grid grid-cols-4 gap-4">
            <?php
            $stats = [
                'total' => $g_db->getOne("SELECT COUNT(*) FROM b_book_list"),
                'with_image' => $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE image_url IS NOT NULL AND image_url != ''"),
                'user_uploads' => $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE image_url LIKE '/img/user_uploads/%'"),
                'external' => $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE image_url LIKE 'http%'")
            ];
            ?>
            <div class="text-center">
                <div class="text-2xl font-bold"><?php echo number_format((int)$stats['total']); ?></div>
                <div class="text-sm text-gray-600">総書籍数</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo number_format((int)$stats['with_image']); ?></div>
                <div class="text-sm text-gray-600">画像あり</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo number_format((int)$stats['user_uploads']); ?></div>
                <div class="text-sm text-gray-600">ユーザーアップロード</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600"><?php echo number_format((int)$stats['external']); ?></div>
                <div class="text-sm text-gray-600">外部画像</div>
            </div>
        </div>
    </div>
    
    <!-- 書籍リスト -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h2 class="text-lg font-semibold mb-4">
                検索結果（<?php echo number_format((int)$total_count); ?>件）
            </h2>
            
            <?php if (!empty($books)): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">画像</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">書籍情報</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">画像URL</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">更新日</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($books as $book): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <?php if (!empty($book['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($book['image_url']); ?>" 
                                         alt="表紙" 
                                         class="w-16 h-20 object-cover border rounded"
                                         onerror="this.src='/img/no-image-book.png'">
                                <?php else: ?>
                                    <div class="w-16 h-20 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-book text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($book['name']); ?>
                                    </div>
                                    <div class="text-gray-500">
                                        <?php echo htmlspecialchars($book['author'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        ID: <?php echo $book['book_id']; ?> / 
                                        ISBN: <?php echo htmlspecialchars($book['isbn'] ?? '-'); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-xs font-mono text-gray-600 break-all">
                                    <?php echo htmlspecialchars($book['image_url'] ?? '(なし)'); ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars($book['update_date'] ?? '-'); ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex space-x-2">
                                    <?php if (!empty($book['image_url'])): ?>
                                    <form method="post" onsubmit="return confirm('本当に削除しますか？');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="action" value="delete_image">
                                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                        <button type="submit" class="text-red-600 hover:bg-red-50 p-1 rounded" title="画像を削除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <a href="/admin/fix_single_book_image.php?book_id=<?php echo $book['book_id']; ?>" 
                                       class="text-blue-600 hover:bg-blue-50 p-1 rounded" 
                                       title="画像を変更">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <a href="/book_detail.php?book_id=<?php echo $book['book_id']; ?>" 
                                       target="_blank"
                                       class="text-gray-600 hover:bg-gray-50 p-1 rounded" 
                                       title="詳細を見る">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- ページネーション -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="flex space-x-1">
                    <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="px-3 py-2 text-sm <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    <?php if ($total_pages > 10): ?>
                    <span class="px-3 py-2 text-sm text-gray-500">...</span>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <p class="text-gray-500">該当する書籍がありません。</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- AI検知機能の説明 -->
    <div class="bg-blue-50 rounded-lg p-6 mt-6">
        <h3 class="text-lg font-semibold mb-2">
            <i class="fas fa-info-circle mr-2"></i>有害コンテンツ検知について
        </h3>
        <p class="text-sm text-gray-700">
            ユーザーがアップロードした画像については、定期的にAIによる有害コンテンツ検知を実行しています。
            不適切な画像が検出された場合は、自動的に削除され、管理者に通知されます。
        </p>
    </div>
</div>

<?php include('layout/footer.php'); ?>