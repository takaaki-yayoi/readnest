<?php
/**
 * 管理画面：エンベディング管理
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/embedding_generator.php');
require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = 'エンベディング管理';

// 処理結果
$result = null;

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $generator = new EmbeddingGenerator();
        
        if ($action === 'generate_batch') {
            // 一括生成
            $limit = intval($_POST['limit'] ?? 10);
            $result = $generator->generateBatchEmbeddings($limit);
            
        } else if ($action === 'generate_single') {
            // 個別生成
            $asin = trim($_POST['asin'] ?? '');
            if (!empty($asin)) {
                $success = $generator->generateBookEmbedding($asin);
                $result = [
                    'single' => true,
                    'asin' => $asin,
                    'success' => $success
                ];
            }
        }
    } catch (Exception $e) {
        $result = [
            'error' => $e->getMessage()
        ];
    }
}

// 統計情報を取得
$stats_sql = "
    SELECT 
        COUNT(*) as total_books,
        SUM(CASE WHEN combined_embedding IS NOT NULL THEN 1 ELSE 0 END) as books_with_embedding,
        SUM(CASE WHEN description IS NOT NULL AND description != '' THEN 1 ELSE 0 END) as books_with_description,
        SUM(CASE WHEN description IS NOT NULL AND combined_embedding IS NULL THEN 1 ELSE 0 END) as books_need_embedding
    FROM b_book_repository
";

$stats_row = $g_db->getRow($stats_sql, [], DB_FETCHMODE_ASSOC);

// エラーチェック
if (DB::isError($stats_row)) {
    $stats = [
        'total_books' => 0,
        'books_with_embedding' => 0,
        'books_with_description' => 0,
        'books_need_embedding' => 0,
        'coverage_percentage' => 0
    ];
} else {
    $stats = [
        'total_books' => $stats_row['total_books'] ?? 0,
        'books_with_embedding' => $stats_row['books_with_embedding'] ?? 0,
        'books_with_description' => $stats_row['books_with_description'] ?? 0,
        'books_need_embedding' => $stats_row['books_need_embedding'] ?? 0,
        'coverage_percentage' => 0
    ];
    
    if ($stats['total_books'] > 0) {
        $stats['coverage_percentage'] = round(($stats['books_with_embedding'] / $stats['total_books']) * 100, 2);
    }
}

// 最近エンベディング生成された本を取得
$recent_sql = "
    SELECT 
        asin,
        title,
        author,
        LEFT(description, 100) as description_preview,
        embedding_model,
        embedding_generated_at
    FROM b_book_repository
    WHERE combined_embedding IS NOT NULL
    ORDER BY embedding_generated_at DESC
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
            <i class="fas fa-vector-square text-purple-600 mr-2"></i>
            エンベディング管理
        </h1>
        <p class="text-gray-600">OpenAI text-embedding-3-smallモデルを使用して書籍の内容をベクトル化し、高精度な類似本検索を実現します</p>
    </div>

    <!-- OpenAI API設定状態 -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">
            <i class="fas fa-info-circle mr-2"></i>OpenAI API設定
        </h3>
        <?php if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)): ?>
            <p class="text-sm text-green-700">
                <i class="fas fa-check-circle mr-1"></i>
                APIキーが設定されています（text-embedding-3-smallモデルを使用）
            </p>
            <p class="text-xs text-gray-600 mt-1">
                料金: $0.02 / 1Mトークン（非常に安価）
            </p>
        <?php else: ?>
            <p class="text-sm text-red-700">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                APIキーが設定されていません。config.phpにOPENAI_API_KEYを設定してください。
            </p>
        <?php endif; ?>
    </div>

    <?php if ($result !== null): ?>
        <div class="mb-6">
            <?php if (isset($result['error'])): ?>
                <div class="rounded-md bg-red-50 p-4">
                    <p class="text-sm text-red-800">
                        <i class="fas fa-times-circle mr-2"></i>
                        エラー: <?php echo htmlspecialchars($result['error']); ?>
                    </p>
                </div>
            <?php elseif (isset($result['single'])): ?>
                <div class="rounded-md <?php echo $result['success'] ? 'bg-green-50' : 'bg-red-50'; ?> p-4">
                    <p class="text-sm <?php echo $result['success'] ? 'text-green-800' : 'text-red-800'; ?>">
                        <i class="fas <?php echo $result['success'] ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-2"></i>
                        ASIN: <?php echo htmlspecialchars($result['asin']); ?> のエンベディング生成が
                        <?php echo $result['success'] ? '完了しました' : '失敗しました'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">一括生成結果</h3>
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

    <!-- 統計情報 -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-pie text-indigo-600 mr-2"></i>
            エンベディング統計
        </h2>
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded p-4">
                <p class="text-sm text-gray-600">総書籍数</p>
                <p class="text-2xl font-bold"><?php echo number_format($stats['total_books']); ?></p>
            </div>
            <div class="bg-green-50 rounded p-4">
                <p class="text-sm text-gray-600">エンベディング済み</p>
                <p class="text-2xl font-bold text-green-600"><?php echo number_format($stats['books_with_embedding']); ?></p>
            </div>
            <div class="bg-yellow-50 rounded p-4">
                <p class="text-sm text-gray-600">生成可能（説明文あり）</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['books_need_embedding']); ?></p>
            </div>
            <div class="bg-blue-50 rounded p-4">
                <p class="text-sm text-gray-600">カバー率</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo $stats['coverage_percentage']; ?>%</p>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 h-3 rounded-full" 
                     style="width: <?php echo $stats['coverage_percentage']; ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-8">
        <!-- 一括生成 -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-layer-group text-green-600 mr-2"></i>
                一括エンベディング生成
            </h2>
            <?php if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)): ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate_batch">
                    <div class="mb-4">
                        <label for="limit" class="block text-sm font-medium text-gray-700 mb-2">
                            生成件数（API制限に注意）
                        </label>
                        <select name="limit" id="limit" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="5">5件</option>
                            <option value="10" selected>10件</option>
                            <option value="20">20件</option>
                            <option value="50">50件</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                        <i class="fas fa-rocket mr-2"></i>
                        エンベディング生成開始
                    </button>
                </form>
                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-md">
                    <p class="text-xs text-amber-800">
                        <i class="fas fa-coins mr-1"></i>
                        料金目安: 1000冊で約$0.10程度
                    </p>
                </div>
            <?php else: ?>
                <p class="text-gray-600">OpenAI APIキーを設定してください</p>
            <?php endif; ?>
        </div>

        <!-- 個別生成 -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-cube text-blue-600 mr-2"></i>
                個別エンベディング生成
            </h2>
            <?php if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)): ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate_single">
                    <div class="mb-4">
                        <label for="asin" class="block text-sm font-medium text-gray-700 mb-2">
                            ASIN / ISBN
                        </label>
                        <input type="text" name="asin" id="asin" placeholder="例: B00ABCDEFG" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-magic mr-2"></i>
                        指定した本のエンベディング生成
                    </button>
                </form>
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-xs text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        説明文がない本は事前に説明文を取得してください
                    </p>
                </div>
            <?php else: ?>
                <p class="text-gray-600">OpenAI APIキーを設定してください</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($recent_books)): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-history text-indigo-600 mr-2"></i>
            最近エンベディング生成された本
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ASIN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">モデル</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">生成日時</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recent_books as $book): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($book['asin']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars(mb_substr($book['title'], 0, 20)); ?>...</td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($book['author']); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">
                                <?php echo htmlspecialchars($book['embedding_model'] ?? 'unknown'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm"><?php echo date('m/d H:i', strtotime($book['embedding_generated_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include(__DIR__ . '/layout/footer.php'); ?>