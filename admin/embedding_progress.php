<?php
/**
 * Embedding生成進捗管理ページ
 */

// 管理者認証を要求
require_once(__DIR__ . '/admin_auth.php');
requireAdmin();

require_once(dirname(__DIR__) . '/modern_config.php');

global $g_db;

// 統計データの取得
function getProgressStats() {
    global $g_db;
    
    $stats = [
        'total_books' => 0,
        'processed_books' => 0,
        'books_with_description' => 0,
        'remaining_books' => 0,
        'progress_percentage' => 0,
        'high_rating' => ['total' => 0, 'processed' => 0],
        'good_rating' => ['total' => 0, 'processed' => 0],
        'api_usage' => ['google' => 0, 'openai' => 0],
        'google_books_checked' => 0,
        'max_attempts_reached' => 0,
        'embeddings_without_desc' => 0,
        'total_api_attempts' => 0,
        'successful_api_calls' => 0
    ];
    
    // 全体統計
    $sql = "
        SELECT 
            COUNT(*) as total_books,
            COUNT(CASE WHEN combined_embedding IS NOT NULL THEN 1 END) as processed_books,
            COUNT(CASE WHEN description IS NOT NULL THEN 1 END) as books_with_description,
            COUNT(CASE WHEN combined_embedding IS NULL THEN 1 END) as remaining_books,
            COUNT(CASE WHEN google_books_checked = 1 THEN 1 END) as google_books_checked,
            COUNT(CASE WHEN process_attempts >= 3 THEN 1 END) as max_attempts_reached,
            COUNT(CASE WHEN embedding_type = 'title_author_only' THEN 1 END) as embeddings_without_desc,
            COUNT(CASE WHEN google_books_checked = 1 OR description IS NOT NULL THEN 1 END) as total_api_attempts,
            COUNT(CASE WHEN description IS NOT NULL THEN 1 END) as successful_api_calls
        FROM b_book_repository
    ";
    
    $result = $g_db->getRow($sql, [], DB_FETCHMODE_ASSOC);
    if (!DB::isError($result)) {
        $stats['total_books'] = $result['total_books'];
        $stats['processed_books'] = $result['processed_books'];
        $stats['books_with_description'] = $result['books_with_description'];
        $stats['remaining_books'] = $result['remaining_books'];
        $stats['google_books_checked'] = $result['google_books_checked'];
        $stats['max_attempts_reached'] = $result['max_attempts_reached'];
        $stats['embeddings_without_desc'] = $result['embeddings_without_desc'];
        $stats['total_api_attempts'] = $result['total_api_attempts'];
        $stats['successful_api_calls'] = $result['successful_api_calls'];
        $stats['progress_percentage'] = $result['total_books'] > 0 
            ? round(($result['processed_books'] / $result['total_books']) * 100, 2) 
            : 0;
    }
    
    // 高評価本の統計
    $sql = "
        SELECT 
            COUNT(DISTINCT br.asin) as total,
            COUNT(DISTINCT CASE WHEN br.combined_embedding IS NOT NULL THEN br.asin END) as processed,
            AVG(bl.rating) as avg_rating_group
        FROM b_book_repository br
        INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
        WHERE bl.rating >= 4
        GROUP BY CASE 
            WHEN bl.rating >= 4.5 THEN 'high'
            WHEN bl.rating >= 4.0 THEN 'good'
        END
    ";
    
    $results = $g_db->getAll($sql, [], DB_FETCHMODE_ASSOC);
    if (!DB::isError($results)) {
        foreach ($results as $row) {
            if ($row['avg_rating_group'] >= 4.5) {
                $stats['high_rating'] = ['total' => $row['total'], 'processed' => $row['processed']];
            } else {
                $stats['good_rating'] = ['total' => $row['total'], 'processed' => $row['processed']];
            }
        }
    }
    
    // 今日のAPI使用状況
    $sql = "
        SELECT api_provider, request_count
        FROM api_usage_tracking
        WHERE usage_date = CURDATE()
    ";
    
    $results = $g_db->getAll($sql, [], DB_FETCHMODE_ASSOC);
    if (!DB::isError($results)) {
        foreach ($results as $row) {
            if ($row['api_provider'] == 'google_books') {
                $stats['api_usage']['google'] = $row['request_count'];
            } elseif ($row['api_provider'] == 'openai') {
                $stats['api_usage']['openai'] = $row['request_count'];
            }
        }
    }
    
    return $stats;
}

// 最近の処理履歴を取得
function getRecentBatches() {
    global $g_db;
    
    $sql = "
        SELECT 
            batch_id,
            start_time,
            end_time,
            total_books,
            successful_books,
            failed_books,
            status,
            google_api_requests,
            openai_api_requests
        FROM embedding_batch_summary
        ORDER BY start_time DESC
        LIMIT 10
    ";
    
    $results = $g_db->getAll($sql, [], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($results)) {
        return [];
    }
    
    return $results;
}

// 最近処理された本を取得
function getRecentlyProcessedBooks() {
    global $g_db;
    
    $sql = "
        SELECT 
            br.asin,
            br.title,
            br.author,
            br.embedding_generated_at,
            LENGTH(br.description) as desc_length,
            AVG(bl.rating) as avg_rating
        FROM b_book_repository br
        LEFT JOIN b_book_list bl ON br.asin = bl.amazon_id
        WHERE br.embedding_generated_at IS NOT NULL
        GROUP BY br.asin
        ORDER BY br.embedding_generated_at DESC
        LIMIT 20
    ";
    
    $results = $g_db->getAll($sql, [], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($results)) {
        return [];
    }
    
    return $results;
}

// エラーが発生した本を取得（embedding_batch_logテーブルから）
function getErrorBooks() {
    global $g_db;
    
    $sql = "
        SELECT 
            br.asin,
            br.title,
            br.author,
            ebl.error_message,
            ebl.updated_at as error_date,
            br.process_attempts,
            ebl.batch_id
        FROM embedding_batch_log ebl
        INNER JOIN b_book_repository br ON ebl.asin = br.asin
        WHERE ebl.status = 'failed'
        AND br.combined_embedding IS NULL
        ORDER BY ebl.updated_at DESC
        LIMIT 20
    ";
    
    $results = $g_db->getAll($sql, [], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($results)) {
        // embedding_batch_logテーブルがない場合は旧方式で取得
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.last_error_message as error_message,
                br.process_attempts
            FROM b_book_repository br
            WHERE br.last_error_message IS NOT NULL
            AND br.combined_embedding IS NULL
            ORDER BY br.process_attempts DESC
            LIMIT 20
        ";
        
        $results = $g_db->getAll($sql, [], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($results)) {
            return [];
        }
    }
    
    return $results;
}

// 処理詳細ログを取得
function getProcessingLogs($batchId = null) {
    global $g_db;
    
    $sql = "
        SELECT 
            ebl.batch_id,
            ebl.asin,
            br.title,
            ebl.status,
            ebl.processing_time_seconds,
            ebl.error_message,
            ebl.created_at,
            ebl.updated_at
        FROM embedding_batch_log ebl
        INNER JOIN b_book_repository br ON ebl.asin = br.asin
    ";
    
    if ($batchId) {
        $sql .= " WHERE ebl.batch_id = ?";
        $params = [$batchId];
    } else {
        $sql .= " ORDER BY ebl.updated_at DESC LIMIT 50";
        $params = [];
    }
    
    $results = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
    
    if (DB::isError($results)) {
        return [];
    }
    
    return $results;
}

// データ取得
$stats = getProgressStats();
$recentBatches = getRecentBatches();
$recentBooks = getRecentlyProcessedBooks();
$errorBooks = getErrorBooks();

// ビューからAPI使用状況を取得（v_todays_api_usage）
$todaysApiUsage = [];
$apiUsageSql = "SELECT * FROM v_todays_api_usage";
$apiUsageResult = $g_db->getAll($apiUsageSql, [], DB_FETCHMODE_ASSOC);
if (!DB::isError($apiUsageResult)) {
    foreach ($apiUsageResult as $row) {
        $todaysApiUsage[$row['api_provider']] = $row;
    }
}

// ページ設定
$d_site_title = 'Embedding生成進捗管理 - ReadNest Admin';
$g_meta_description = 'Embedding生成の進捗管理';
$g_meta_keyword = 'admin,embedding,progress';

// ページタイトル
$page_title = 'Embedding生成進捗管理 - ReadNest Admin';

// ヘッダーを読み込み
include(__DIR__ . '/layout/header.php');

// サブメニューを表示
include(__DIR__ . '/layout/submenu.php');
?>

<div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                Embedding生成進捗管理
            </h1>
            <p class="text-gray-600">説明文取得とEmbedding生成の進捗状況</p>
        </div>
        
        <!-- 全体統計 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-600">総書籍数</h3>
                    <i class="fas fa-book text-blue-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_books']); ?></p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-600">処理済み</h3>
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['processed_books']); ?></p>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $stats['progress_percentage']; ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1"><?php echo $stats['progress_percentage']; ?>%</p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-600">高評価本</h3>
                    <i class="fas fa-star text-yellow-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800">
                    <?php 
                    $totalHighRating = $stats['high_rating']['total'] + $stats['good_rating']['total'];
                    echo number_format($totalHighRating); 
                    ?>
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    処理済: <?php 
                    $processedHighRating = $stats['high_rating']['processed'] + $stats['good_rating']['processed'];
                    echo number_format($processedHighRating); 
                    ?>
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-600">説明文あり</h3>
                    <i class="fas fa-file-text text-purple-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['books_with_description']); ?></p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-600">未処理</h3>
                    <i class="fas fa-hourglass-half text-orange-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['remaining_books']); ?></p>
            </div>
        </div>
        
        <!-- Google Books API成功率 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-chart-pie text-purple-600 mr-2"></i>
                Google Books API 説明文取得率
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="col-span-2">
                    <?php 
                    $apiAttempts = $stats['google_books_checked'] + $stats['books_with_description'];
                    $successRate = $apiAttempts > 0 ? round(($stats['books_with_description'] / $apiAttempts) * 100, 1) : 0;
                    $failureRate = 100 - $successRate;
                    ?>
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium text-gray-700">説明文取得成功</span>
                            <span class="text-sm font-semibold text-green-600">
                                <?php echo number_format($stats['books_with_description']); ?>冊 (<?php echo $successRate; ?>%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-green-500 h-4 rounded-full" style="width: <?php echo $successRate; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium text-gray-700">説明文取得失敗</span>
                            <span class="text-sm font-semibold text-red-600">
                                <?php echo number_format($stats['google_books_checked']); ?>冊 (<?php echo $failureRate; ?>%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-red-500 h-4 rounded-full" style="width: <?php echo $failureRate; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="text-sm text-gray-600 mt-4">
                        <p>総API呼び出し数: <?php echo number_format($apiAttempts); ?>回</p>
                    </div>
                </div>
                
                <div class="flex items-center justify-center">
                    <div class="text-center">
                        <div class="relative inline-flex items-center justify-center">
                            <svg class="w-32 h-32">
                                <circle cx="64" cy="64" r="60" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                                <circle cx="64" cy="64" r="60" stroke="#10b981" stroke-width="8" fill="none"
                                        stroke-dasharray="<?php echo 377 * ($successRate / 100); ?> 377"
                                        stroke-dashoffset="0"
                                        transform="rotate(-90 64 64)"></circle>
                            </svg>
                            <div class="absolute text-2xl font-bold"><?php echo $successRate; ?>%</div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">取得成功率</p>
                    </div>
                </div>
            </div>
            
            <?php if ($failureRate > 30): ?>
            <div class="mt-4 p-3 bg-amber-50 rounded-lg">
                <p class="text-sm text-amber-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    説明文取得失敗率が高くなっています。これらの本はタイトルと著者情報のみでEmbeddingを生成します。
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- API使用状況 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-globe text-blue-600 mr-2"></i>
                API使用状況（今日）
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Google Books API</h3>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">使用回数: <?php echo $stats['api_usage']['google']; ?> / 1000</span>
                        <span class="text-sm <?php echo $stats['api_usage']['google'] > 900 ? 'text-red-600' : 'text-green-600'; ?>">
                            残り: <?php echo 1000 - $stats['api_usage']['google']; ?>
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="<?php echo $stats['api_usage']['google'] > 900 ? 'bg-red-500' : 'bg-blue-500'; ?> h-2 rounded-full" 
                             style="width: <?php echo min(100, ($stats['api_usage']['google'] / 1000) * 100); ?>%"></div>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">
                        <p>確認済み（説明文なし）: <?php echo number_format($stats['google_books_checked']); ?>冊</p>
                    </div>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">OpenAI API</h3>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">使用回数: <?php echo $stats['api_usage']['openai']; ?></span>
                        <span class="text-sm text-green-600">制限なし（レート制限のみ）</span>
                    </div>
                    <?php if (!empty($todaysApiUsage['openai'])): ?>
                    <div class="mt-2 text-xs text-gray-500">
                        <p>トークン使用: <?php echo number_format($todaysApiUsage['openai']['token_count'] ?? 0); ?></p>
                        <p>推定コスト: $<?php echo number_format($todaysApiUsage['openai']['cost_estimate'] ?? 0, 4); ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="mt-2 text-xs text-gray-500">
                        <p>説明文なしembedding: <?php echo number_format($stats['embeddings_without_desc']); ?>件</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- バッチ実行ボタン -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-play-circle text-green-600 mr-2"></i>
                バッチ実行
            </h2>
            <div class="flex items-center gap-4">
                <button onclick="runBatch(10)" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    10件処理
                </button>
                <button onclick="runBatch(50)" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    50件処理
                </button>
                <button onclick="runBatch(100)" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    100件処理
                </button>
                <span id="batch-status" class="text-gray-600"></span>
            </div>
        </div>
        
        <!-- 最近の処理履歴 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-history text-purple-600 mr-2"></i>
                最近のバッチ実行履歴
            </h2>
            <?php if (!empty($recentBatches)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">実行日時</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">ステータス</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">処理数</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">API使用</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBatches as $batch): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2 text-sm"><?php echo $batch['start_time']; ?></td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 text-xs rounded 
                                            <?php echo $batch['status'] == 'completed' ? 'bg-green-100 text-green-800' : 
                                                      ($batch['status'] == 'running' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo $batch['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <?php echo $batch['successful_books']; ?> / <?php echo $batch['total_books']; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        G: <?php echo $batch['google_api_requests']; ?>, 
                                        O: <?php echo $batch['openai_api_requests']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">実行履歴がありません</p>
            <?php endif; ?>
        </div>
        
        <!-- 最近処理された本 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-clock text-indigo-600 mr-2"></i>
                最近処理された本
            </h2>
            <?php if (!empty($recentBooks)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">タイトル</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">著者</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">評価</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">処理日時</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBooks as $book): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td class="px-4 py-2 text-sm">
                                        <?php if ($book['avg_rating']): ?>
                                            <span class="text-amber-600">★<?php echo number_format($book['avg_rating'], 1); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm"><?php echo $book['embedding_generated_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">まだ処理された本がありません</p>
            <?php endif; ?>
        </div>
        
        <!-- エラーが発生した本 -->
        <?php if (!empty($errorBooks)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                処理エラーが発生した本
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">タイトル</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">著者</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">エラー内容</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">試行回数</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">エラー日時</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errorBooks as $book): ?>
                            <tr class="border-t">
                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($book['title']); ?></td>
                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($book['author'] ?? '-'); ?></td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="text-red-600 text-xs">
                                        <?php echo htmlspecialchars(mb_substr($book['error_message'] ?? '', 0, 50)); ?>
                                        <?php if (mb_strlen($book['error_message'] ?? '') > 50): ?>...<?php endif; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-center">
                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">
                                        <?php echo $book['process_attempts'] ?? 0; ?>回
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    <?php echo isset($book['error_date']) ? date('m/d H:i', strtotime($book['error_date'])) : '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 高評価本の処理進捗 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-star text-yellow-500 mr-2"></i>
                高評価本の処理進捗
            </h2>
            <div class="space-y-4">
                <?php if ($stats['high_rating']['total'] > 0): ?>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-700">
                            <i class="fas fa-star text-yellow-500"></i>
                            <i class="fas fa-star text-yellow-500"></i>
                            <i class="fas fa-star text-yellow-500"></i>
                            <i class="fas fa-star text-yellow-500"></i>
                            <i class="fas fa-star-half-alt text-yellow-500"></i>
                            評価 4.5以上
                        </span>
                        <span class="text-sm text-gray-600">
                            <?php echo $stats['high_rating']['processed']; ?> / <?php echo $stats['high_rating']['total']; ?>冊
                            (<?php echo round(($stats['high_rating']['processed'] / $stats['high_rating']['total']) * 100, 1); ?>%)
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 h-3 rounded-full transition-all duration-300" 
                             style="width: <?php echo round(($stats['high_rating']['processed'] / $stats['high_rating']['total']) * 100, 1); ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['good_rating']['total'] > 0): ?>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-700">
                            <i class="fas fa-star text-yellow-500"></i>
                            <i class="fas fa-star text-yellow-500"></i>
                            <i class="fas fa-star text-yellow-500"></i>
                            <i class="fas fa-star text-yellow-500"></i>
                            <i class="far fa-star text-gray-300"></i>
                            評価 4.0～4.5
                        </span>
                        <span class="text-sm text-gray-600">
                            <?php echo $stats['good_rating']['processed']; ?> / <?php echo $stats['good_rating']['total']; ?>冊
                            (<?php echo round(($stats['good_rating']['processed'] / $stats['good_rating']['total']) * 100, 1); ?>%)
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-3 rounded-full transition-all duration-300" 
                             style="width: <?php echo round(($stats['good_rating']['processed'] / $stats['good_rating']['total']) * 100, 1); ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-4 p-4 bg-amber-50 rounded-lg">
                    <p class="text-sm text-amber-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        高評価本（★4.0以上）を優先的に処理しています
                    </p>
                    <?php 
                    $highRatingRemaining = ($stats['high_rating']['total'] - $stats['high_rating']['processed']) + 
                                          ($stats['good_rating']['total'] - $stats['good_rating']['processed']);
                    if ($highRatingRemaining > 0):
                    ?>
                    <p class="text-xs text-amber-700 mt-1">
                        残り高評価本: <?php echo number_format($highRatingRemaining); ?>冊 
                        （推定<?php echo ceil($highRatingRemaining / 950); ?>日で完了）
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 処理統計サマリー -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-chart-bar text-indigo-600 mr-2"></i>
                処理統計
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-gray-50 rounded">
                    <p class="text-3xl font-bold text-gray-800">
                        <?php 
                        $successRate = $stats['total_books'] > 0 
                            ? round(($stats['processed_books'] / $stats['total_books']) * 100, 1)
                            : 0;
                        echo $successRate;
                        ?>%
                    </p>
                    <p class="text-sm text-gray-600 mt-1">全体の処理率</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded">
                    <p class="text-3xl font-bold text-blue-600">
                        <?php echo number_format($stats['remaining_books']); ?>
                    </p>
                    <p class="text-sm text-gray-600 mt-1">残り処理数</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded">
                    <p class="text-3xl font-bold text-green-600">
                        <?php 
                        // 残り日数の推定（1日950件処理として）
                        $daysRemaining = ceil($stats['remaining_books'] / 950);
                        echo $daysRemaining;
                        ?>
                    </p>
                    <p class="text-sm text-gray-600 mt-1">推定完了日数</p>
                </div>
            </div>
            
            <!-- 処理スキップ状況 -->
            <div class="mt-6 p-4 bg-amber-50 rounded-lg">
                <h3 class="font-semibold text-amber-800 mb-2">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    処理スキップ状況
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-amber-700">最大試行回数到達（3回失敗）:</span>
                        <span class="font-semibold"><?php echo number_format($stats['max_attempts_reached']); ?>冊</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-amber-700">Google Booksで説明文なし:</span>
                        <span class="font-semibold"><?php echo number_format($stats['google_books_checked']); ?>冊</span>
                    </div>
                </div>
                <?php if ($stats['max_attempts_reached'] > 0): ?>
                <p class="text-xs text-amber-600 mt-2">
                    ※これらの本は自動処理対象から除外されています
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center">
            <a href="/admin/" class="text-blue-600 hover:underline">
                ← 管理画面に戻る
            </a>
        </div>
    </div>
    
    <script>
    function runBatch(limit) {
        const statusEl = document.getElementById('batch-status');
        statusEl.textContent = '処理中...';
        statusEl.className = 'text-blue-600';
        
        fetch('/admin/batch_generate_embeddings.php?run=1&limit=' + limit)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusEl.textContent = '✓ ' + data.message;
                    statusEl.className = 'text-green-600';
                    setTimeout(() => location.reload(), 3000);
                } else {
                    statusEl.textContent = '✗ ' + data.message;
                    statusEl.className = 'text-red-600';
                }
            })
            .catch(error => {
                statusEl.textContent = '✗ エラーが発生しました';
                statusEl.className = 'text-red-600';
            });
    }
    </script>
</div>

<?php
// フッターを読み込み
include(__DIR__ . '/layout/footer.php');
?>