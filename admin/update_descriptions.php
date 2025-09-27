<?php
/**
 * 管理画面：書籍説明文の更新
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/book_description_updater.php');
require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = '書籍説明文の更新';

// 処理結果
$result = null;

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $updater = new BookDescriptionUpdater();
    
    if ($action === 'update_batch') {
        // 一括更新
        $limit = intval($_POST['limit'] ?? 10);
        $result = $updater->updateBatch($limit);
        
    } else if ($action === 'update_single') {
        // 個別更新
        $asin = trim($_POST['asin'] ?? '');
        if (!empty($asin)) {
            $success = $updater->updateBookDescription($asin);
            $result = [
                'single' => true,
                'asin' => $asin,
                'success' => $success
            ];
        }
    }
}

// 統計情報を取得
$stats_sql = "
    SELECT 
        COUNT(*) as total_books,
        SUM(CASE WHEN description IS NOT NULL AND description != '' THEN 1 ELSE 0 END) as books_with_description,
        SUM(CASE WHEN description IS NULL OR description = '' THEN 1 ELSE 0 END) as books_without_description
    FROM b_book_repository
";

$stats_row = $g_db->getRow($stats_sql, [], DB_FETCHMODE_ASSOC);

// エラーチェック
if (DB::isError($stats_row)) {
    $stats = [
        'total_books' => 0,
        'books_with_description' => 0,
        'books_without_description' => 0,
        'coverage_percentage' => 0
    ];
} else {
    $stats = [
        'total_books' => $stats_row['total_books'] ?? 0,
        'books_with_description' => $stats_row['books_with_description'] ?? 0,
        'books_without_description' => $stats_row['books_without_description'] ?? 0,
        'coverage_percentage' => 0
    ];
    
    if ($stats['total_books'] > 0) {
        $stats['coverage_percentage'] = round(($stats['books_with_description'] / $stats['total_books']) * 100, 2);
    }
}

// 最近更新された本を取得
$recent_sql = "
    SELECT 
        asin,
        title,
        author,
        LEFT(description, 100) as description_preview,
        google_data_updated_at
    FROM b_book_repository
    WHERE description IS NOT NULL AND description != ''
    ORDER BY google_data_updated_at DESC
    LIMIT 10
";

$recent_books = $g_db->getAll($recent_sql, [], DB_FETCHMODE_ASSOC);
if (DB::isError($recent_books)) {
    $recent_books = [];
}

include(__DIR__ . '/layout/header.php');

// サブメニューを表示
include(__DIR__ . '/layout/submenu.php');
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">
            <i class="fas fa-book-medical text-blue-600 mr-2"></i>
            書籍説明文の更新
        </h1>
        <p class="text-gray-600">Google Books APIから書籍の説明文を取得してデータベースに保存します</p>
    </div>

    <?php if ($result !== null): ?>
        <div class="mb-6">
            <?php if (isset($result['single'])): ?>
                <div class="rounded-md <?php echo $result['success'] ? 'bg-green-50' : 'bg-red-50'; ?> p-4">
                    <p class="text-sm <?php echo $result['success'] ? 'text-green-800' : 'text-red-800'; ?>">
                        <i class="fas <?php echo $result['success'] ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-2"></i>
                        ASIN: <?php echo htmlspecialchars($result['asin']); ?> の更新が
                        <?php echo $result['success'] ? '完了しました' : '失敗しました'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">一括更新結果</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-gray-600">処理件数</p>
                            <p class="text-2xl font-bold"><?php echo $result['total']; ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-gray-600">成功</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo $result['success']; ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-gray-600">失敗</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo $result['failed']; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- API設定状態 -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">
            <i class="fas fa-key mr-2"></i>Google Books API設定
        </h3>
        <?php if (defined('GOOGLE_BOOKS_API_KEY') && !empty(GOOGLE_BOOKS_API_KEY)): ?>
            <p class="text-sm text-green-700">
                <i class="fas fa-check-circle mr-1"></i>
                APIキーが設定されています（制限: 1,000回/日、申請で最大100,000回/日まで無料）
            </p>
        <?php else: ?>
            <p class="text-sm text-amber-700">
                <i class="fas fa-info-circle mr-1"></i>
                APIキーなしで動作中（1,000回/日制限、IPアドレスごと）
            </p>
        <?php endif; ?>
    </div>

    <!-- 統計情報 -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
            データベース統計
        </h2>
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded p-4">
                <p class="text-sm text-gray-600">総書籍数</p>
                <p class="text-2xl font-bold"><?php echo number_format($stats['total_books']); ?></p>
            </div>
            <div class="bg-green-50 rounded p-4">
                <p class="text-sm text-gray-600">説明文あり</p>
                <p class="text-2xl font-bold text-green-600"><?php echo number_format($stats['books_with_description']); ?></p>
            </div>
            <div class="bg-yellow-50 rounded p-4">
                <p class="text-sm text-gray-600">説明文なし</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['books_without_description']); ?></p>
            </div>
            <div class="bg-blue-50 rounded p-4">
                <p class="text-sm text-gray-600">カバー率</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo $stats['coverage_percentage']; ?>%</p>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full" 
                     style="width: <?php echo $stats['coverage_percentage']; ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-8">
        <!-- 一括更新 -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-sync-alt text-green-600 mr-2"></i>
                一括更新
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_batch">
                <div class="mb-4">
                    <label for="limit" class="block text-sm font-medium text-gray-700 mb-2">
                        更新件数（API制限があるため少なめに）
                    </label>
                    <select name="limit" id="limit" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="5">5件</option>
                        <option value="10" selected>10件</option>
                        <option value="20">20件</option>
                        <option value="50">50件</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <i class="fas fa-play mr-2"></i>
                    説明文がない本を更新
                </button>
            </form>
        </div>

        <!-- 個別更新 -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-book text-blue-600 mr-2"></i>
                個別更新
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_single">
                <div class="mb-4">
                    <label for="asin" class="block text-sm font-medium text-gray-700 mb-2">
                        ASIN / ISBN
                    </label>
                    <input type="text" name="asin" id="asin" placeholder="例: B00ABCDEFG" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>
                    指定した本の説明文を更新
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($recent_books)): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-clock text-indigo-600 mr-2"></i>
            最近更新された本
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ASIN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">説明文（抜粋）</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">更新日時</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recent_books as $book): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($book['asin']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars(mb_substr($book['title'], 0, 20)); ?>...</td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($book['author']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($book['description_preview']); ?>...</td>
                        <td class="px-6 py-4 text-sm"><?php echo date('m/d H:i', strtotime($book['google_data_updated_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include(__DIR__ . '/layout/footer.php'); ?>