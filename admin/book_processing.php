<?php
/**
 * 統合書籍処理管理画面
 * 説明文取得とエンベディング生成を一括管理
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/integrated_book_processor.php');
require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = '書籍データ処理';

// プロセッサーを初期化
$processor = new IntegratedBookProcessor();

// 処理結果
$result = null;

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'process_batch') {
        // 一括処理
        $limit = intval($_POST['limit'] ?? 10);
        $result = $processor->processBatch($limit);
        
    } else if ($action === 'process_single') {
        // 個別処理
        $asin = trim($_POST['asin'] ?? '');
        if (!empty($asin)) {
            $processResult = $processor->processBook($asin);
            $result = [
                'single' => true,
                'asin' => $asin,
                'success' => $processResult['success'],
                'description_status' => $processResult['description_status'],
                'embedding_status' => $processResult['embedding_status']
            ];
        }
        
    } else if ($action === 'process_for_user') {
        // ユーザー向け優先処理
        $userId = intval($_POST['user_id'] ?? $_SESSION['AUTH_USER']);
        $limit = intval($_POST['limit'] ?? 10);
        $result = $processor->processForUser($userId, $limit);
    }
}

// 統計情報を取得
$stats = $processor->getStatistics();

// 最近処理された本を取得
$recent_sql = "
    SELECT 
        br.asin,
        br.title,
        br.author,
        CASE 
            WHEN br.description IS NOT NULL AND br.description != '' THEN '✓'
            ELSE '✗'
        END as has_description,
        CASE 
            WHEN br.combined_embedding IS NOT NULL THEN '✓'
            ELSE '✗'
        END as has_embedding,
        GREATEST(
            COALESCE(br.google_data_updated_at, '2000-01-01'),
            COALESCE(br.embedding_generated_at, '2000-01-01')
        ) as last_processed
    FROM b_book_repository br
    WHERE br.google_data_updated_at IS NOT NULL 
       OR br.embedding_generated_at IS NOT NULL
    ORDER BY last_processed DESC
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
            <i class="fas fa-cogs text-indigo-600 mr-2"></i>
            統合書籍データ処理
        </h1>
        <p class="text-gray-600">説明文の取得とエンベディング生成を一括で処理します</p>
    </div>

    <!-- API設定状態 -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">
                <i class="fas fa-key mr-2"></i>Google Books API
            </h3>
            <?php if (defined('GOOGLE_BOOKS_API_KEY') && !empty(GOOGLE_BOOKS_API_KEY)): ?>
                <p class="text-sm text-green-700">
                    <i class="fas fa-check-circle mr-1"></i>
                    設定済み（1,000回/日、無料）
                </p>
            <?php else: ?>
                <p class="text-sm text-amber-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    キーなしで動作中（制限あり）
                </p>
            <?php endif; ?>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-purple-900 mb-2">
                <i class="fas fa-robot mr-2"></i>OpenAI API
            </h3>
            <?php if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)): ?>
                <p class="text-sm text-green-700">
                    <i class="fas fa-check-circle mr-1"></i>
                    設定済み（$0.02/1Mトークン）
                </p>
            <?php else: ?>
                <p class="text-sm text-red-700">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    未設定（エンベディング無効）
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($result !== null): ?>
        <div class="mb-6">
            <?php if (isset($result['single'])): ?>
                <!-- 個別処理の結果 -->
                <div class="rounded-md <?php echo $result['success'] ? 'bg-green-50' : 'bg-red-50'; ?> p-4">
                    <h3 class="text-lg font-semibold <?php echo $result['success'] ? 'text-green-800' : 'text-red-800'; ?> mb-2">
                        <i class="fas <?php echo $result['success'] ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-2"></i>
                        ASIN: <?php echo htmlspecialchars($result['asin']); ?>
                    </h3>
                    <div class="grid grid-cols-2 gap-4 mt-2">
                        <div>
                            <span class="text-sm font-medium">説明文:</span>
                            <span class="text-sm ml-2"><?php echo htmlspecialchars($result['description_status']); ?></span>
                        </div>
                        <div>
                            <span class="text-sm font-medium">エンベディング:</span>
                            <span class="text-sm ml-2"><?php echo htmlspecialchars($result['embedding_status']); ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- 一括処理の結果 -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">処理結果</h3>
                    <div class="grid grid-cols-5 gap-4">
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
                        <div class="text-center">
                            <p class="text-gray-600">説明文取得</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $result['description_fetched'] ?? 0; ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-gray-600">エンベディング生成</p>
                            <p class="text-2xl font-bold text-purple-600"><?php echo $result['embedding_generated'] ?? 0; ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($result['processed'])): ?>
                        <div class="mt-4 max-h-40 overflow-y-auto bg-white rounded p-2">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-1">タイトル</th>
                                        <th class="text-left py-1">説明文</th>
                                        <th class="text-left py-1">エンベディング</th>
                                        <th class="text-left py-1">結果</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result['processed'] as $item): ?>
                                    <tr class="border-b">
                                        <td class="py-1"><?php echo htmlspecialchars(mb_substr($item['title'] ?? '', 0, 20)); ?>...</td>
                                        <td class="py-1"><?php echo htmlspecialchars($item['description_status']); ?></td>
                                        <td class="py-1"><?php echo htmlspecialchars($item['embedding_status']); ?></td>
                                        <td class="py-1">
                                            <?php if ($item['success']): ?>
                                                <span class="text-green-600">✓</span>
                                            <?php else: ?>
                                                <span class="text-red-600">✗</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 統計情報 -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
            処理統計
        </h2>
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded p-4">
                <p class="text-sm text-gray-600">説明文カバー率</p>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['description_coverage']; ?>%</p>
                <p class="text-xs text-gray-500 mt-1">
                    <?php echo number_format($stats['books_with_description']); ?> / <?php echo number_format($stats['total_books']); ?>
                </p>
            </div>
            <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded p-4">
                <p class="text-sm text-gray-600">エンベディングカバー率</p>
                <p class="text-3xl font-bold text-purple-600"><?php echo $stats['embedding_coverage']; ?>%</p>
                <p class="text-xs text-gray-500 mt-1">
                    <?php echo number_format($stats['books_with_embedding']); ?> / <?php echo number_format($stats['total_books']); ?>
                </p>
            </div>
            <div class="bg-gradient-to-r from-green-50 to-green-100 rounded p-4">
                <p class="text-sm text-gray-600">完全処理率</p>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['full_coverage']; ?>%</p>
                <p class="text-xs text-gray-500 mt-1">
                    <?php echo number_format($stats['fully_processed']); ?> / <?php echo number_format($stats['total_books']); ?>
                </p>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-yellow-50 rounded p-3">
                <p class="text-sm text-gray-600">説明文が必要</p>
                <p class="text-xl font-bold text-yellow-600"><?php echo number_format($stats['needs_description']); ?>件</p>
            </div>
            <div class="bg-orange-50 rounded p-3">
                <p class="text-sm text-gray-600">エンベディングが必要</p>
                <p class="text-xl font-bold text-orange-600"><?php echo number_format($stats['needs_embedding']); ?>件</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-8">
        <!-- 一括処理 -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-layer-group text-green-600 mr-2"></i>
                一括処理
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="process_batch">
                <div class="mb-4">
                    <label for="limit" class="block text-sm font-medium text-gray-700 mb-2">
                        処理件数
                    </label>
                    <select name="limit" id="limit" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="5">5件</option>
                        <option value="10" selected>10件</option>
                        <option value="20">20件</option>
                        <option value="30">30件</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <i class="fas fa-play mr-2"></i>
                    自動処理開始
                </button>
                <p class="text-xs text-gray-500 mt-2">
                    説明文がない本を優先的に処理します
                </p>
            </form>
        </div>

        <!-- 個別処理 -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-book text-blue-600 mr-2"></i>
                個別処理
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="process_single">
                <div class="mb-4">
                    <label for="asin" class="block text-sm font-medium text-gray-700 mb-2">
                        ASIN / ISBN
                    </label>
                    <input type="text" name="asin" id="asin" placeholder="例: B00ABCDEFG" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-magic mr-2"></i>
                    指定した本を処理
                </button>
                <p class="text-xs text-gray-500 mt-2">
                    説明文取得とエンベディング生成を実行
                </p>
            </form>
        </div>

        <!-- ユーザー向け優先処理 -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-user-cog text-purple-600 mr-2"></i>
                ユーザー向け処理
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="process_for_user">
                <div class="mb-4">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                        ユーザーID
                    </label>
                    <input type="number" name="user_id" id="user_id" 
                           value="<?php echo $_SESSION['AUTH_USER']; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label for="user_limit" class="block text-sm font-medium text-gray-700 mb-2">
                        処理件数
                    </label>
                    <select name="limit" id="user_limit" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="5">5件</option>
                        <option value="10" selected>10件</option>
                        <option value="20">20件</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                    <i class="fas fa-star mr-2"></i>
                    高評価本を優先処理
                </button>
                <p class="text-xs text-gray-500 mt-2">
                    ユーザーの高評価本を優先的に処理
                </p>
            </form>
        </div>
    </div>

    <?php if (!empty($recent_books)): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-history text-indigo-600 mr-2"></i>
            最近処理された本
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ASIN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">説明文</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">エンベディング</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">最終処理</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recent_books as $book): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($book['asin']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars(mb_substr($book['title'], 0, 20)); ?>...</td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($book['author']); ?></td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($book['has_description'] === '✓'): ?>
                                <span class="text-green-600 text-lg">✓</span>
                            <?php else: ?>
                                <span class="text-red-600 text-lg">✗</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($book['has_embedding'] === '✓'): ?>
                                <span class="text-green-600 text-lg">✓</span>
                            <?php else: ?>
                                <span class="text-red-600 text-lg">✗</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm"><?php echo date('m/d H:i', strtotime($book['last_processed'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include(__DIR__ . '/layout/footer.php'); ?>