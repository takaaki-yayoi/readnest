<?php
/**
 * 管理画面 - 著者情報がない本の一覧
 * 著者情報が欠落している本を確認・修正するための管理ツール
 */

// エラー表示（デバッグ用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(dirname(__DIR__) . '/modern_config.php');
require_once(__DIR__ . '/admin_auth.php');

// 管理者認証を要求
requireAdmin();

$mine_user_id = $_SESSION['AUTH_USER'];

// ページネーション
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// フィルタ
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// 統計情報を取得
$stats_sql = "
    SELECT 
        COUNT(*) as total_books,
        SUM(CASE WHEN bl.author IS NULL OR bl.author = '' THEN 1 ELSE 0 END) as missing_in_list,
        SUM(CASE WHEN br.author IS NULL OR br.author = '' THEN 1 ELSE 0 END) as missing_in_repo,
        SUM(CASE WHEN (bl.author IS NULL OR bl.author = '') AND (br.author IS NULL OR br.author = '') THEN 1 ELSE 0 END) as missing_both,
        SUM(CASE WHEN bl.amazon_id IS NOT NULL AND bl.amazon_id != '' THEN 1 ELSE 0 END) as has_asin,
        SUM(CASE WHEN bl.isbn IS NOT NULL AND bl.isbn != '' THEN 1 ELSE 0 END) as has_isbn
    FROM b_book_list bl
    LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
";
$stats = $g_db->getRow($stats_sql, null, DB_FETCHMODE_ASSOC);

// 著者情報がない本を取得
$where_conditions = [];
$params = [];

switch ($filter) {
    case 'no_author_list':
        $where_conditions[] = "(bl.author IS NULL OR bl.author = '')";
        break;
    case 'no_author_repo':
        $where_conditions[] = "bl.amazon_id IS NOT NULL AND bl.amazon_id != '' AND (br.author IS NULL OR br.author = '')";
        break;
    case 'no_author_both':
        $where_conditions[] = "(bl.author IS NULL OR bl.author = '') AND (br.author IS NULL OR br.author = '' OR br.author IS NULL)";
        break;
    case 'no_asin':
        $where_conditions[] = "(bl.amazon_id IS NULL OR bl.amazon_id = '')";
        break;
    default:
        $where_conditions[] = "(bl.author IS NULL OR bl.author = '' OR br.author IS NULL OR br.author = '')";
}

if (!empty($search)) {
    $where_conditions[] = "bl.name LIKE ?";
    $params[] = '%' . $search . '%';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// 総件数を取得
$count_sql = "
    SELECT COUNT(DISTINCT bl.book_id) 
    FROM b_book_list bl
    LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
    $where_clause
";
$total_count = $g_db->getOne($count_sql, $params);

// 本のリストを取得
$sql = "
    SELECT 
        bl.book_id,
        bl.name as title,
        bl.author as list_author,
        br.author as repo_author,
        bl.amazon_id,
        bl.isbn,
        bl.image_url,
        bl.user_id,
        u.nickname,
        bl.create_date,
        bl.update_date,
        COUNT(DISTINCT bl2.user_id) as reader_count
    FROM b_book_list bl
    LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
    LEFT JOIN b_user u ON bl.user_id = u.user_id
    LEFT JOIN b_book_list bl2 ON bl.amazon_id = bl2.amazon_id
    $where_clause
    GROUP BY bl.book_id
    ORDER BY reader_count DESC, bl.update_date DESC
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;

$books = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);

// ページネーション計算
$total_pages = ceil($total_count / $per_page);

// AJAX処理: 著者情報の更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_author') {
        $book_id = intval($_POST['book_id']);
        $author = trim($_POST['author']);
        $update_type = $_POST['update_type'] ?? 'list';
        
        if ($update_type === 'list') {
            // b_book_listを更新
            $update_sql = "UPDATE b_book_list SET author = ? WHERE book_id = ?";
            $result = $g_db->query($update_sql, [$author, $book_id]);
        } else {
            // b_book_repositoryを更新（ASINがある場合）
            $asin_sql = "SELECT amazon_id FROM b_book_list WHERE book_id = ?";
            $asin = $g_db->getOne($asin_sql, [$book_id]);
            
            if ($asin) {
                // repositoryにレコードがあるか確認
                $check_sql = "SELECT COUNT(*) FROM b_book_repository WHERE asin = ?";
                $exists = $g_db->getOne($check_sql, [$asin]);
                
                if ($exists) {
                    $update_sql = "UPDATE b_book_repository SET author = ? WHERE asin = ?";
                } else {
                    // タイトルも必要なので取得
                    $book_sql = "SELECT name FROM b_book_list WHERE book_id = ?";
                    $title = $g_db->getOne($book_sql, [$book_id]);
                    $update_sql = "INSERT INTO b_book_repository (asin, title, author) VALUES (?, ?, ?)";
                    $result = $g_db->query($update_sql, [$asin, $title, $author]);
                    echo json_encode(['success' => !DB::isError($result)]);
                    exit;
                }
                $result = $g_db->query($update_sql, [$author, $asin]);
            } else {
                echo json_encode(['success' => false, 'error' => 'ASINがありません']);
                exit;
            }
        }
        
        echo json_encode(['success' => !DB::isError($result)]);
        exit;
    }
    
    if ($_POST['action'] === 'fetch_google_info') {
        $book_id = intval($_POST['book_id']);
        
        // 本の情報を取得
        $book_sql = "SELECT name, isbn, amazon_id FROM b_book_list WHERE book_id = ?";
        $book = $g_db->getRow($book_sql, [$book_id], DB_FETCHMODE_ASSOC);
        
        if ($book) {
            // Google Books APIから情報を取得
            require_once(dirname(__DIR__) . '/library/google_books_api.php');
            $api = new GoogleBooksAPI();
            
            // ISBNまたはタイトルで検索
            $search_term = !empty($book['isbn']) ? $book['isbn'] : $book['name'];
            $google_info = $api->searchBooks($search_term);
            
            if ($google_info && isset($google_info['items'][0])) {
                $volume = $google_info['items'][0]['volumeInfo'];
                $author = isset($volume['authors']) ? implode(', ', $volume['authors']) : '';
                
                echo json_encode([
                    'success' => true,
                    'author' => $author,
                    'title' => $volume['title'] ?? '',
                    'publisher' => $volume['publisher'] ?? '',
                    'publishedDate' => $volume['publishedDate'] ?? '',
                    'description' => $volume['description'] ?? ''
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => '情報が見つかりません']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => '本が見つかりません']);
        }
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>著者情報管理 - ReadNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-user-edit mr-2"></i>著者情報管理
            </h1>
            <p class="text-gray-600">著者情報が欠落している本を確認・修正できます</p>
        </div>

        <!-- 統計情報 -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['total_books']); ?></div>
                <div class="text-sm text-gray-600">総書籍数</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-red-600"><?php echo number_format($stats['missing_in_list']); ?></div>
                <div class="text-sm text-gray-600">b_book_list著者なし</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-orange-600"><?php echo number_format($stats['missing_in_repo']); ?></div>
                <div class="text-sm text-gray-600">repository著者なし</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-purple-600"><?php echo number_format($stats['missing_both']); ?></div>
                <div class="text-sm text-gray-600">両方著者なし</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['has_asin']); ?></div>
                <div class="text-sm text-gray-600">ASIN有り</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-indigo-600"><?php echo number_format($stats['has_isbn']); ?></div>
                <div class="text-sm text-gray-600">ISBN有り</div>
            </div>
        </div>

        <!-- フィルタ -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="get" class="flex flex-wrap gap-4">
                <select name="filter" class="px-4 py-2 border rounded-lg">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>すべて表示</option>
                    <option value="no_author_list" <?php echo $filter === 'no_author_list' ? 'selected' : ''; ?>>b_book_list著者なし</option>
                    <option value="no_author_repo" <?php echo $filter === 'no_author_repo' ? 'selected' : ''; ?>>repository著者なし</option>
                    <option value="no_author_both" <?php echo $filter === 'no_author_both' ? 'selected' : ''; ?>>両方著者なし</option>
                    <option value="no_asin" <?php echo $filter === 'no_asin' ? 'selected' : ''; ?>>ASINなし</option>
                </select>
                
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="タイトルで検索" class="px-4 py-2 border rounded-lg flex-1">
                
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>検索
                </button>
                
                <a href="/admin/missing_authors.php" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    <i class="fas fa-redo mr-2"></i>リセット
                </a>
            </form>
        </div>

        <!-- 結果 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-semibold">検索結果: <?php echo number_format($total_count); ?>件</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">表紙</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者(List)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者(Repo)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ASIN/ISBN</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">読者数</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($books as $book): ?>
                        <tr id="book-<?php echo $book['book_id']; ?>">
                            <td class="px-4 py-3 text-sm">
                                <a href="/book_detail.php?book_id=<?php echo $book['book_id']; ?>" 
                                   target="_blank" class="text-blue-600 hover:underline">
                                    #<?php echo $book['book_id']; ?>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <img src="<?php echo htmlspecialchars($book['image_url'] ?: '/img/no-image-book.png'); ?>" 
                                     alt="" class="w-12 h-16 object-cover">
                            </td>
                            <td class="px-4 py-3 text-sm max-w-xs">
                                <div class="font-medium text-gray-900 truncate">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    by <?php echo htmlspecialchars($book['nickname']); ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <input type="text" 
                                       id="list-author-<?php echo $book['book_id']; ?>"
                                       value="<?php echo htmlspecialchars($book['list_author'] ?? ''); ?>" 
                                       class="w-full px-2 py-1 border rounded <?php echo empty($book['list_author']) ? 'border-red-300 bg-red-50' : ''; ?>"
                                       placeholder="著者名">
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <input type="text" 
                                       id="repo-author-<?php echo $book['book_id']; ?>"
                                       value="<?php echo htmlspecialchars($book['repo_author'] ?? ''); ?>" 
                                       class="w-full px-2 py-1 border rounded <?php echo empty($book['repo_author']) ? 'border-orange-300 bg-orange-50' : ''; ?>"
                                       placeholder="著者名"
                                       <?php echo empty($book['amazon_id']) ? 'disabled' : ''; ?>>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="text-xs">
                                    <?php if (!empty($book['amazon_id'])): ?>
                                        <span class="text-green-600">ASIN: <?php echo htmlspecialchars($book['amazon_id']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($book['isbn'])): ?>
                                        <span class="text-blue-600">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="px-2 py-1 bg-gray-100 rounded">
                                    <?php echo $book['reader_count']; ?>人
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex gap-1">
                                    <button onclick="updateAuthor(<?php echo $book['book_id']; ?>, 'list')" 
                                            class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs"
                                            title="b_book_listを更新">
                                        <i class="fas fa-save"></i> List
                                    </button>
                                    <?php if (!empty($book['amazon_id'])): ?>
                                    <button onclick="updateAuthor(<?php echo $book['book_id']; ?>, 'repo')" 
                                            class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs"
                                            title="b_book_repositoryを更新">
                                        <i class="fas fa-save"></i> Repo
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="fetchGoogleInfo(<?php echo $book['book_id']; ?>)" 
                                            class="px-2 py-1 bg-purple-600 text-white rounded hover:bg-purple-700 text-xs"
                                            title="Google Books APIから取得">
                                        <i class="fas fa-download"></i> API
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ページネーション -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="flex gap-2">
                <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="px-4 py-2 <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?> rounded-lg">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                <?php if ($total_pages > 10): ?>
                <span class="px-4 py-2 text-gray-500">...</span>
                <a href="?page=<?php echo $total_pages; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="px-4 py-2 bg-white text-gray-700 hover:bg-gray-100 rounded-lg">
                    <?php echo $total_pages; ?>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function updateAuthor(bookId, type) {
        const author = type === 'list' 
            ? document.getElementById(`list-author-${bookId}`).value
            : document.getElementById(`repo-author-${bookId}`).value;
        
        if (!author.trim()) {
            alert('著者名を入力してください');
            return;
        }
        
        fetch('missing_authors.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_author&book_id=${bookId}&author=${encodeURIComponent(author)}&update_type=${type}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('更新しました');
                // 背景色を緑に変更
                const input = document.getElementById(`${type}-author-${bookId}`);
                input.classList.remove('border-red-300', 'bg-red-50', 'border-orange-300', 'bg-orange-50');
                input.classList.add('border-green-300', 'bg-green-50');
                setTimeout(() => {
                    input.classList.remove('border-green-300', 'bg-green-50');
                }, 2000);
            } else {
                alert('更新に失敗しました: ' + (data.error || ''));
            }
        });
    }
    
    function fetchGoogleInfo(bookId) {
        fetch('missing_authors.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=fetch_google_info&book_id=${bookId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 両方のフィールドに著者名を設定
                document.getElementById(`list-author-${bookId}`).value = data.author;
                document.getElementById(`repo-author-${bookId}`).value = data.author;
                
                alert(`Google Books APIから取得しました:\n著者: ${data.author}\nタイトル: ${data.title}`);
            } else {
                alert('取得に失敗しました: ' + (data.error || ''));
            }
        });
    }
    </script>
</body>
</html>