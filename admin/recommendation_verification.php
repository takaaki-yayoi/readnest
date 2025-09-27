<?php
/**
 * レコメンデーション効果検証画面
 * ユーザーID 12を中心とした検証機能
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/integrated_book_processor.php');
require_once(dirname(__DIR__) . '/library/embedding_similarity.php');
require_once(dirname(__DIR__) . '/library/content_based_similarity.php');
require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = 'レコメンデーション効果検証';

// デフォルトユーザーID
$target_user_id = intval($_GET['user_id'] ?? 12);

// プロセッサーを初期化
$processor = new IntegratedBookProcessor();

// 処理結果
$result = null;

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'process_base_books') {
        // ベース本を処理
        $limit = intval($_POST['limit'] ?? 20);
        $result = $processor->processForUser($target_user_id, $limit);
        
    } else if ($action === 'process_recommended') {
        // 推薦される可能性のある本を処理
        $asins = $_POST['asins'] ?? [];
        $processed = [];
        
        foreach ($asins as $asin) {
            $processResult = $processor->processBook($asin);
            $processed[] = [
                'asin' => $asin,
                'success' => $processResult['success'],
                'description_status' => $processResult['description_status'],
                'embedding_status' => $processResult['embedding_status']
            ];
        }
        
        $result = [
            'batch' => true,
            'total' => count($processed),
            'success' => count(array_filter($processed, function($p) { return $p['success']; })),
            'failed' => count(array_filter($processed, function($p) { return !$p['success']; })),
            'processed' => $processed
        ];
    }
}

// ユーザー情報を取得
$user_sql = "
    SELECT 
        u.user_id,
        u.nickname,
        u.email,
        COUNT(DISTINCT bl.book_id) as total_books,
        SUM(CASE WHEN bl.rating >= 4 THEN 1 ELSE 0 END) as high_rated_books,
        AVG(bl.rating) as avg_rating
    FROM b_user u
    LEFT JOIN b_book_list bl ON u.user_id = bl.user_id
    WHERE u.user_id = ?
    GROUP BY u.user_id, u.nickname, u.email
";

$user_info = $g_db->getRow($user_sql, [$target_user_id], DB_FETCHMODE_ASSOC);
if (DB::isError($user_info)) {
    $user_info = ['nickname' => 'Unknown', 'total_books' => 0];
}

// ユーザーの高評価本（ベース本）を取得
$base_books_sql = "
    SELECT 
        br.asin,
        br.title,
        br.author,
        bl.rating,
        bl.update_date,
        CASE WHEN br.description IS NOT NULL AND br.description != '' THEN '✓' ELSE '✗' END as has_description,
        CASE WHEN br.combined_embedding IS NOT NULL THEN '✓' ELSE '✗' END as has_embedding,
        CASE 
            WHEN br.description IS NOT NULL AND br.combined_embedding IS NOT NULL THEN 'ready'
            WHEN br.description IS NOT NULL THEN 'partial'
            ELSE 'none'
        END as processing_status
    FROM b_book_list bl
    INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
    WHERE bl.user_id = ?
    AND bl.rating >= 4
    ORDER BY bl.rating DESC, bl.update_date DESC
    LIMIT 20
";

$base_books = $g_db->getAll($base_books_sql, [$target_user_id], DB_FETCHMODE_ASSOC);
if (DB::isError($base_books)) {
    $base_books = [];
}

// 処理統計を計算
$ready_count = count(array_filter($base_books, function($b) { return $b['processing_status'] === 'ready'; }));
$partial_count = count(array_filter($base_books, function($b) { return $b['processing_status'] === 'partial'; }));
$none_count = count(array_filter($base_books, function($b) { return $b['processing_status'] === 'none'; }));

// レコメンデーションのテスト実行（データが準備できている場合）
$test_recommendations = [];
$test_error = null;

if ($ready_count >= 3) {  // 最低3冊の完全処理済み本がある場合
    try {
        // エンベディングベースの類似本を取得
        $embeddingSimilarity = new EmbeddingSimilarity($target_user_id);
        $test_recommendations = $embeddingSimilarity->findSimilarBooks(10);
        
        if (empty($test_recommendations)) {
            // フォールバック：コンテンツベース
            $contentSimilarity = new ContentBasedSimilarity($target_user_id);
            $test_recommendations = $contentSimilarity->findSimilarBooks(10);
        }
    } catch (Exception $e) {
        $test_error = $e->getMessage();
    }
}

// 推薦候補の処理状況を確認
if (!empty($test_recommendations)) {
    $rec_asins = array_column($test_recommendations, 'asin');
    $placeholders = array_fill(0, count($rec_asins), '?');
    
    $rec_status_sql = "
        SELECT 
            asin,
            CASE WHEN description IS NOT NULL AND description != '' THEN 1 ELSE 0 END as has_desc,
            CASE WHEN combined_embedding IS NOT NULL THEN 1 ELSE 0 END as has_emb
        FROM b_book_repository
        WHERE asin IN (" . implode(',', $placeholders) . ")
    ";
    
    $rec_statuses = $g_db->getAll($rec_status_sql, $rec_asins, DB_FETCHMODE_ASSOC);
    if (!DB::isError($rec_statuses)) {
        $status_map = [];
        foreach ($rec_statuses as $status) {
            $status_map[$status['asin']] = $status;
        }
        
        foreach ($test_recommendations as &$rec) {
            $rec['has_description'] = $status_map[$rec['asin']]['has_desc'] ?? 0;
            $rec['has_embedding'] = $status_map[$rec['asin']]['has_emb'] ?? 0;
        }
    }
}

include(__DIR__ . '/layout/header.php');
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">
            <i class="fas fa-flask text-purple-600 mr-2"></i>
            レコメンデーション効果検証
        </h1>
        <p class="text-gray-600">ユーザーのベース本と推薦本のデータ処理状況を管理し、推薦精度を検証します</p>
    </div>

    <!-- ユーザー選択 -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <form method="GET" action="" class="flex items-center gap-4">
            <label for="user_id" class="font-medium">検証対象ユーザー:</label>
            <input type="number" name="user_id" id="user_id" value="<?php echo $target_user_id; ?>" 
                   class="px-3 py-2 border border-gray-300 rounded-md w-32">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-user-check mr-2"></i>変更
            </button>
            <div class="ml-auto text-sm">
                <span class="font-medium"><?php echo htmlspecialchars($user_info['nickname'] ?? 'User ' . $target_user_id); ?></span>
                <span class="text-gray-600 ml-2">
                    総登録: <?php echo $user_info['total_books'] ?? 0; ?>冊 / 
                    高評価: <?php echo $user_info['high_rated_books'] ?? 0; ?>冊 / 
                    平均評価: <?php echo number_format($user_info['avg_rating'] ?? 0, 1); ?>
                </span>
            </div>
        </form>
    </div>

    <?php if ($result !== null): ?>
        <div class="mb-6">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-green-900 mb-2">処理結果</h3>
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
        </div>
    <?php endif; ?>

    <!-- ベース本の処理状況 -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-star text-yellow-500 mr-2"></i>
                ベース本（高評価本）の処理状況
            </h2>
            <div class="flex gap-4 text-sm">
                <span class="flex items-center">
                    <span class="w-4 h-4 bg-green-500 rounded mr-1"></span>
                    完全処理済: <?php echo $ready_count; ?>
                </span>
                <span class="flex items-center">
                    <span class="w-4 h-4 bg-yellow-500 rounded mr-1"></span>
                    部分処理: <?php echo $partial_count; ?>
                </span>
                <span class="flex items-center">
                    <span class="w-4 h-4 bg-red-500 rounded mr-1"></span>
                    未処理: <?php echo $none_count; ?>
                </span>
            </div>
        </div>
        
        <?php if (!empty($base_books)): ?>
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ASIN</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">著者</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">評価</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">説明文</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">エンベディング</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">状態</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($base_books as $book): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm font-mono"><?php echo htmlspecialchars($book['asin']); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(mb_substr($book['title'], 0, 30)); ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(mb_substr($book['author'], 0, 15)); ?></td>
                            <td class="px-4 py-2 text-center">
                                <?php for ($i = 0; $i < $book['rating']; $i++): ?>
                                    <i class="fas fa-star text-yellow-400 text-xs"></i>
                                <?php endfor; ?>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <?php if ($book['has_description'] === '✓'): ?>
                                    <span class="text-green-600 text-lg">✓</span>
                                <?php else: ?>
                                    <span class="text-red-600 text-lg">✗</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <?php if ($book['has_embedding'] === '✓'): ?>
                                    <span class="text-green-600 text-lg">✓</span>
                                <?php else: ?>
                                    <span class="text-red-600 text-lg">✗</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <?php if ($book['processing_status'] === 'ready'): ?>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">完了</span>
                                <?php elseif ($book['processing_status'] === 'partial'): ?>
                                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">部分</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">未処理</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="process_base_books">
                <input type="hidden" name="limit" value="20">
                <button type="submit" class="w-full bg-purple-600 text-white px-4 py-3 rounded-md hover:bg-purple-700">
                    <i class="fas fa-rocket mr-2"></i>
                    ベース本を一括処理（説明文取得＋エンベディング生成）
                </button>
            </form>
        <?php else: ?>
            <p class="text-gray-500">高評価本がありません。</p>
        <?php endif; ?>
    </div>

    <!-- テスト推薦結果 -->
    <?php if ($ready_count >= 3): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-magic text-indigo-600 mr-2"></i>
            テスト推薦結果
        </h2>
        
        <?php if ($test_error): ?>
            <div class="bg-red-50 border border-red-200 rounded p-4">
                <p class="text-red-800">エラー: <?php echo htmlspecialchars($test_error); ?></p>
            </div>
        <?php elseif (!empty($test_recommendations)): ?>
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">推薦本</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ベース本</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">類似度</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">説明文</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">エンベディング</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">理由</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($test_recommendations as $rec): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm">
                                <div class="font-medium"><?php echo htmlspecialchars(mb_substr($rec['title'], 0, 25)); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($rec['author']); ?></div>
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <?php echo htmlspecialchars(mb_substr($rec['base_book'] ?? '', 0, 20)); ?>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="text-lg font-bold text-indigo-600">
                                    <?php echo $rec['similarity_score'] ?? 0; ?>%
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <?php if ($rec['has_description'] ?? 0): ?>
                                    <span class="text-green-600 text-lg">✓</span>
                                <?php else: ?>
                                    <span class="text-red-600 text-lg">✗</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <?php if ($rec['has_embedding'] ?? 0): ?>
                                    <span class="text-green-600 text-lg">✓</span>
                                <?php else: ?>
                                    <span class="text-red-600 text-lg">✗</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-600">
                                <?php echo htmlspecialchars($rec['reason'] ?? ''); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php 
            // 未処理の推薦本を抽出
            $unprocessed_recs = array_filter($test_recommendations, function($r) {
                return !($r['has_description'] && $r['has_embedding']);
            });
            ?>
            
            <?php if (!empty($unprocessed_recs)): ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="process_recommended">
                    <?php foreach ($unprocessed_recs as $rec): ?>
                        <input type="hidden" name="asins[]" value="<?php echo htmlspecialchars($rec['asin']); ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-3 rounded-md hover:bg-indigo-700">
                        <i class="fas fa-cogs mr-2"></i>
                        未処理の推薦本を処理（<?php echo count($unprocessed_recs); ?>件）
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-gray-500">推薦を生成できませんでした。</p>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-yellow-900 mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            推薦テストの準備が不足
        </h3>
        <p class="text-yellow-800">
            レコメンデーションをテストするには、最低3冊の完全処理済み（説明文＋エンベディング）のベース本が必要です。
            現在: <?php echo $ready_count; ?>冊
        </p>
        <p class="text-sm text-yellow-700 mt-2">
            上記の「ベース本を一括処理」ボタンをクリックして、ベース本のデータを準備してください。
        </p>
    </div>
    <?php endif; ?>

    <!-- 処理の流れ説明 -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            効果検証の流れ
        </h3>
        <ol class="space-y-3 text-sm">
            <li class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold mr-3">1</span>
                <div>
                    <p class="font-medium">ベース本の処理</p>
                    <p class="text-gray-600">ユーザーの高評価本（★4以上）の説明文とエンベディングを準備</p>
                </div>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold mr-3">2</span>
                <div>
                    <p class="font-medium">推薦生成テスト</p>
                    <p class="text-gray-600">処理済みのベース本を基に類似本を推薦</p>
                </div>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3">3</span>
                <div>
                    <p class="font-medium">推薦本の処理</p>
                    <p class="text-gray-600">推薦された本のデータも処理して精度向上</p>
                </div>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold mr-3">4</span>
                <div>
                    <p class="font-medium">効果測定</p>
                    <p class="text-gray-600">実際のレコメンデーション画面で精度を確認</p>
                </div>
            </li>
        </ol>
    </div>
</div>

<?php include(__DIR__ . '/layout/footer.php'); ?>