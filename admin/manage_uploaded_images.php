<?php
/**
 * アップロードされた画像の管理画面
 */
require_once('../config.php');
require_once('../admin/admin_auth.php');
require_once('../library/image_helpers.php');

// 管理者認証
requireAdmin();

$page_title = '画像管理・統計';

$g_db = DB_Connect();

// ページネーション
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// 削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['book_id'])) {
        $book_id = (int)$_POST['book_id'];
        
        // 書籍情報を取得
        $book = $g_db->getRow("SELECT * FROM b_book_list WHERE book_id = ?", [$book_id], DB_FETCHMODE_ASSOC);
        
        if ($book && !empty($book['image_url']) && strpos($book['image_url'], '/uploads/book_covers/') === 0) {
            // ファイルを削除
            $file_path = $_SERVER['DOCUMENT_ROOT'] . $book['image_url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // DBをデフォルト画像に更新
            $g_db->query("UPDATE b_book_list SET image_url = '/img/no-image-book.png' WHERE book_id = ?", [$book_id]);
            
            $message = "画像を削除しました。";
        }
    }
}

// アップロードされた画像を持つ書籍を取得
$sql = "SELECT bl.*, u.nickname, u.email 
        FROM b_book_list bl
        LEFT JOIN b_user u ON bl.user_id = u.user_id
        WHERE bl.image_url LIKE '/uploads/book_covers/%'
        ORDER BY bl.update_date DESC
        LIMIT ? OFFSET ?";
$books = $g_db->getAll($sql, [$per_page, $offset], DB_FETCHMODE_ASSOC);

// 総件数を取得
$total = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE image_url LIKE '/uploads/book_covers/%'");
$total_pages = ceil($total / $per_page);

// アップロード容量の統計
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/book_covers';
$total_size = 0;
$file_count = 0;

if (is_dir($upload_dir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($upload_dir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $total_size += $file->getSize();
            $file_count++;
        }
    }
}

// 全体の書籍画像統計
$stats_sql = "SELECT 
    COUNT(*) as total_books,
    SUM(CASE WHEN image_url IS NULL OR image_url = '' THEN 1 ELSE 0 END) as no_image,
    SUM(CASE WHEN image_url LIKE '%no-image%' OR image_url LIKE '%noimage%' THEN 1 ELSE 0 END) as placeholder_image,
    SUM(CASE WHEN image_url LIKE 'http://%' OR image_url LIKE 'https://%' THEN 1 ELSE 0 END) as external_image,
    SUM(CASE WHEN image_url LIKE '/uploads/book_covers/%' THEN 1 ELSE 0 END) as uploaded_image,
    SUM(CASE WHEN image_url LIKE '%amazon%' THEN 1 ELSE 0 END) as amazon_image,
    SUM(CASE WHEN image_url LIKE '%rakuten%' THEN 1 ELSE 0 END) as rakuten_image
    FROM b_book_list";
$image_stats = $g_db->getRow($stats_sql, null, DB_FETCHMODE_ASSOC);

// ユーザー別のアップロード統計
$user_stats_sql = "SELECT 
    u.nickname,
    COUNT(bl.book_id) as upload_count,
    MAX(bl.update_date) as last_upload
    FROM b_book_list bl
    LEFT JOIN b_user u ON bl.user_id = u.user_id
    WHERE bl.image_url LIKE '/uploads/book_covers/%'
    GROUP BY bl.user_id
    ORDER BY upload_count DESC
    LIMIT 10";
$top_uploaders = $g_db->getAll($user_stats_sql, null, DB_FETCHMODE_ASSOC);

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// ヘッダーを読み込み
include('layout/header.php');
?>

<style>
    .stats {
        background-color: #e8f5e9;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 30px;
        display: flex;
        gap: 30px;
    }
    .stat-item {
        flex: 1;
    }
    .stat-label {
        font-size: 14px;
        color: #666;
    }
    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #2e7d32;
    }
    .book-image {
        width: 60px;
        height: 80px;
        object-fit: cover;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .user-info {
        font-size: 14px;
        color: #666;
    }
    .file-info {
        font-size: 12px;
        color: #999;
    }
    .no-data {
        text-align: center;
        padding: 40px;
        color: #666;
    }
</style>

<div class="bg-white rounded-lg shadow-md p-6">
        
    <?php if (isset($message)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-label">総アップロード画像数</div>
                <div class="stat-value"><?php echo number_format($total); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">総ファイル数</div>
                <div class="stat-value"><?php echo number_format($file_count); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">使用容量</div>
                <div class="stat-value"><?php echo formatBytes($total_size); ?></div>
            </div>
        </div>
        
        <!-- 全体統計 -->
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #495057;">全書籍画像統計</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <span class="stat-label">総書籍数</span>
                    <div style="font-size: 20px; font-weight: bold; color: #212529;"><?php echo number_format($image_stats['total_books'] ?? 0); ?></div>
                </div>
                <div>
                    <span class="stat-label">画像なし</span>
                    <div style="font-size: 20px; font-weight: bold; color: #dc3545;"><?php echo number_format($image_stats['no_image'] ?? 0); ?></div>
                </div>
                <div>
                    <span class="stat-label">プレースホルダー</span>
                    <div style="font-size: 20px; font-weight: bold; color: #ffc107;"><?php echo number_format($image_stats['placeholder_image'] ?? 0); ?></div>
                </div>
                <div>
                    <span class="stat-label">外部URL</span>
                    <div style="font-size: 20px; font-weight: bold; color: #17a2b8;"><?php echo number_format($image_stats['external_image'] ?? 0); ?></div>
                </div>
                <div>
                    <span class="stat-label">Amazon画像</span>
                    <div style="font-size: 20px; font-weight: bold; color: #fd7e14;"><?php echo number_format($image_stats['amazon_image'] ?? 0); ?></div>
                </div>
                <div>
                    <span class="stat-label">楽天画像</span>
                    <div style="font-size: 20px; font-weight: bold; color: #6f42c1;"><?php echo number_format($image_stats['rakuten_image'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        
        <!-- トップアップローダー -->
        <?php if (!empty($top_uploaders)): ?>
        <div style="background-color: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #495057;">アップロード数ランキング TOP10</h2>
            <table style="width: 100%; background-color: white; border-radius: 4px;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 10px;">ユーザー</th>
                        <th style="text-align: right; padding: 10px;">アップロード数</th>
                        <th style="text-align: right; padding: 10px;">最終アップロード</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_uploaders as $uploader): ?>
                    <tr>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($uploader['nickname'] ?? '名無しさん'); ?></td>
                        <td style="text-align: right; padding: 10px;"><?php echo number_format($uploader['upload_count']); ?></td>
                        <td style="text-align: right; padding: 10px; color: #666; font-size: 14px;">
                            <?php echo date('Y/m/d', strtotime($uploader['last_upload'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if (empty($books)): ?>
        <div class="no-data">
            アップロードされた画像はありません。
        </div>
        <?php else: ?>
        <table class="w-full">
            <thead>
                <tr>
                    <th style="width: 80px;">画像</th>
                    <th>書籍情報</th>
                    <th>アップロードユーザー</th>
                    <th>ファイル情報</th>
                    <th style="width: 100px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                <tr>
                    <td>
                        <img src="<?php echo htmlspecialchars($book['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($book['name']); ?>" 
                             class="book-image"
                             onerror="this.src='/img/no-image-book.png'">
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($book['name']); ?></strong><br>
                        <span class="user-info">
                            <?php echo htmlspecialchars($book['author'] ?? '著者不明'); ?><br>
                            ID: <?php echo $book['book_id']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="user-info">
                            <?php echo htmlspecialchars($book['nickname'] ?? '名無しさん'); ?><br>
                            <?php echo htmlspecialchars($book['email'] ?? ''); ?>
                        </span>
                    </td>
                    <td>
                        <span class="file-info">
                            <?php 
                            $file_path = $_SERVER['DOCUMENT_ROOT'] . $book['image_url'];
                            if (file_exists($file_path)) {
                                echo "サイズ: " . formatBytes(filesize($file_path)) . "<br>";
                                echo "更新: " . date('Y/m/d H:i', filemtime($file_path));
                            } else {
                                echo "ファイルが見つかりません";
                            }
                            ?>
                        </span>
                    </td>
                    <td>
                        <form method="post" onsubmit="return confirm('この画像を削除しますか？');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                            <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">削除</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-6 space-x-2">
            <?php if ($page > 1): ?>
            <a href="?page=1" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">最初</a>
            <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">前へ</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <?php if ($i == $page): ?>
                <span class="px-3 py-2 bg-blue-600 text-white rounded"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="?page=<?php echo $i; ?>" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">次へ</a>
            <a href="?page=<?php echo $total_pages; ?>" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">最後</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
</div>

<?php
// フッターを読み込み
include('layout/footer.php');
?>