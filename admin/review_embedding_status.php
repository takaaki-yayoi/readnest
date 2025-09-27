<?php
/**
 * レビューembedding生成状況管理画面
 */

declare(strict_types=1);

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../library/session.php');
require_once(__DIR__ . '/../library/review_embedding_generator.php');
require_once(__DIR__ . '/admin_auth.php');

// 管理者権限チェック
if (!checkLogin() || !isAdmin()) {
    header('Location: /');
    exit;
}

$message = '';
$error = '';

// バッチ実行リクエスト処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'run_batch') {
        try {
            $limit = min(500, max(1, (int)($_POST['limit'] ?? 100)));
            $generator = new ReviewEmbeddingGenerator();
            $results = $generator->generateBatch($limit);
            
            $message = sprintf(
                "バッチ処理が完了しました。処理数: %d, 成功: %d, 失敗: %d, スキップ: %d",
                $results['total'],
                $results['success'],
                $results['failed'],
                $results['skipped']
            );
        } catch (Exception $e) {
            $error = "バッチ処理中にエラーが発生しました: " . $e->getMessage();
        }
    }
}

// 統計情報を取得
$stats = [];

// 全体統計
$sql = "
    SELECT 
        COUNT(DISTINCT CONCAT(bl.book_id, '-', bl.user_id)) as total_reviews,
        COUNT(DISTINCT CASE WHEN re.review_embedding IS NOT NULL THEN CONCAT(re.book_id, '-', re.user_id) END) as with_embedding,
        COUNT(DISTINCT CASE WHEN re.review_embedding IS NULL THEN CONCAT(bl.book_id, '-', bl.user_id) END) as need_embedding,
        COUNT(DISTINCT CASE WHEN LENGTH(bl.memo) < 10 THEN CONCAT(bl.book_id, '-', bl.user_id) END) as too_short,
        COUNT(DISTINCT CASE WHEN re.last_error_message IS NOT NULL THEN CONCAT(re.book_id, '-', re.user_id) END) as with_errors
    FROM b_book_list bl
    LEFT JOIN review_embeddings re ON bl.book_id = re.book_id AND bl.user_id = re.user_id
    WHERE bl.memo IS NOT NULL AND bl.memo != ''
";
$result = $g_db->getRow($sql);
$stats['overall'] = DB::isError($result) ? [
    'total_reviews' => 0,
    'with_embedding' => 0,
    'need_embedding' => 0,
    'too_short' => 0,
    'with_errors' => 0
] : $result;

// 最近のバッチ実行履歴
$sql = "
    SELECT 
        batch_id,
        start_time,
        end_time,
        total_reviews,
        successful_reviews,
        failed_reviews,
        skipped_reviews,
        status,
        TIMESTAMPDIFF(SECOND, start_time, IFNULL(end_time, NOW())) as duration_seconds
    FROM review_embedding_batch_summary
    ORDER BY start_time DESC
    LIMIT 10
";
$result = $g_db->getAll($sql);
$stats['recent_batches'] = DB::isError($result) ? [] : $result;

// 日別処理統計
$sql = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as processed,
        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM review_embedding_batch_log
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
";
$result = $g_db->getAll($sql);
$stats['daily'] = DB::isError($result) ? [] : $result;

// エラーのあるレビュー（最新10件）
$sql = "
    SELECT 
        re.book_id,
        re.user_id,
        br.title,
        u.nickname,
        re.last_error_message,
        re.process_attempts,
        re.updated_at
    FROM review_embeddings re
    JOIN b_book_repository br ON re.book_id = br.book_id
    JOIN u_users u ON re.user_id = u.user_id
    WHERE re.last_error_message IS NOT NULL
    ORDER BY re.updated_at DESC
    LIMIT 10
";
$result = $g_db->getAll($sql);
$stats['errors'] = DB::isError($result) ? [] : $result;

// カバレッジ計算
$coverage = 0;
if ($stats['overall']['total_reviews'] > 0) {
    $coverage = ($stats['overall']['with_embedding'] / $stats['overall']['total_reviews']) * 100;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>レビューEmbedding管理 - ReadNest Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">レビューEmbedding生成管理</h1>
            
            <?php if ($message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- 概要統計 -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-blue-600 text-sm font-medium">総レビュー数</div>
                    <div class="text-2xl font-bold text-blue-800">
                        <?php echo number_format($stats['overall']['total_reviews']); ?>
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-green-600 text-sm font-medium">Embedding生成済み</div>
                    <div class="text-2xl font-bold text-green-800">
                        <?php echo number_format($stats['overall']['with_embedding']); ?>
                    </div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="text-yellow-600 text-sm font-medium">未処理</div>
                    <div class="text-2xl font-bold text-yellow-800">
                        <?php echo number_format($stats['overall']['need_embedding']); ?>
                    </div>
                    <?php if ($stats['overall']['need_embedding'] > 0): ?>
                    <div class="text-xs text-yellow-600 mt-1">
                        (10文字以上)
                    </div>
                    <?php endif; ?>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-gray-600 text-sm font-medium">スキップ対象</div>
                    <div class="text-2xl font-bold text-gray-800">
                        <?php echo number_format($stats['overall']['too_short']); ?>
                    </div>
                    <div class="text-xs text-gray-600 mt-1">
                        (10文字未満)
                    </div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-purple-600 text-sm font-medium">カバレッジ</div>
                    <div class="text-2xl font-bold text-purple-800">
                        <?php echo number_format($coverage, 1); ?>%
                    </div>
                    <div class="text-xs text-purple-600 mt-1">
                        <?php 
                        $processable = $stats['overall']['total_reviews'] - $stats['overall']['too_short'];
                        if ($processable > 0) {
                            $actual_coverage = ($stats['overall']['with_embedding'] / $processable) * 100;
                            echo '実質 ' . number_format($actual_coverage, 1) . '%';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- バッチ実行フォーム -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">バッチ処理実行</h2>
                <form method="POST" class="flex items-end gap-4">
                    <input type="hidden" name="action" value="run_batch">
                    <div>
                        <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">
                            処理件数（最大500）
                        </label>
                        <input type="number" 
                               id="limit" 
                               name="limit" 
                               value="100" 
                               min="1" 
                               max="500"
                               class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors"
                            onclick="return confirm('バッチ処理を開始しますか？')">
                        バッチ実行
                    </button>
                </form>
                <p class="text-sm text-gray-600 mt-2">
                    ※ 未処理のレビューから指定件数分のembeddingを生成します。
                </p>
            </div>
            
            <!-- 処理グラフ -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">日別処理統計（過去30日）</h2>
                <div style="height: 300px;">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
            
            <!-- 最近のバッチ実行履歴 -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">最近のバッチ実行履歴</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2 border text-left">バッチID</th>
                                <th class="px-4 py-2 border text-left">開始時刻</th>
                                <th class="px-4 py-2 border text-left">処理時間</th>
                                <th class="px-4 py-2 border text-right">処理数</th>
                                <th class="px-4 py-2 border text-right">成功</th>
                                <th class="px-4 py-2 border text-right">失敗</th>
                                <th class="px-4 py-2 border text-center">ステータス</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_batches'] as $batch): ?>
                            <tr>
                                <td class="px-4 py-2 border text-xs">
                                    <?php echo htmlspecialchars(substr($batch['batch_id'], 0, 20)); ?>
                                </td>
                                <td class="px-4 py-2 border">
                                    <?php echo htmlspecialchars($batch['start_time']); ?>
                                </td>
                                <td class="px-4 py-2 border">
                                    <?php echo number_format($batch['duration_seconds']); ?>秒
                                </td>
                                <td class="px-4 py-2 border text-right">
                                    <?php echo number_format($batch['total_reviews']); ?>
                                </td>
                                <td class="px-4 py-2 border text-right text-green-600">
                                    <?php echo number_format($batch['successful_reviews']); ?>
                                </td>
                                <td class="px-4 py-2 border text-right text-red-600">
                                    <?php echo number_format($batch['failed_reviews']); ?>
                                </td>
                                <td class="px-4 py-2 border text-center">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $batch['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                   ($batch['status'] === 'running' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'); ?>">
                                        <?php echo htmlspecialchars($batch['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- エラーリスト -->
            <?php if (count($stats['errors']) > 0): ?>
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4 text-red-600">エラーのあるレビュー</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2 border text-left">書籍</th>
                                <th class="px-4 py-2 border text-left">ユーザー</th>
                                <th class="px-4 py-2 border text-left">エラー</th>
                                <th class="px-4 py-2 border text-center">試行回数</th>
                                <th class="px-4 py-2 border text-left">最終更新</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['errors'] as $error): ?>
                            <tr>
                                <td class="px-4 py-2 border">
                                    <a href="/book_detail.php?book_id=<?php echo $error['book_id']; ?>" 
                                       class="text-blue-600 hover:underline">
                                        <?php echo htmlspecialchars(mb_substr($error['title'], 0, 30)); ?>
                                    </a>
                                </td>
                                <td class="px-4 py-2 border">
                                    <?php echo htmlspecialchars($error['nickname']); ?>
                                </td>
                                <td class="px-4 py-2 border text-sm text-red-600">
                                    <?php echo htmlspecialchars(mb_substr($error['last_error_message'], 0, 50)); ?>
                                </td>
                                <td class="px-4 py-2 border text-center">
                                    <?php echo $error['process_attempts']; ?>
                                </td>
                                <td class="px-4 py-2 border text-sm">
                                    <?php echo htmlspecialchars($error['updated_at']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- コマンドラインツール情報 -->
            <div class="bg-blue-50 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">コマンドラインツール</h2>
                <p class="text-sm text-gray-700 mb-2">
                    より大規模なバッチ処理はコマンドラインから実行できます：
                </p>
                <pre class="bg-gray-800 text-green-400 p-4 rounded overflow-x-auto">
# ドライラン（処理対象の確認）
php batch/generate_review_embeddings.php --dry-run

# 100件処理
php batch/generate_review_embeddings.php --limit=100

# エラーになったレビューも再処理
php batch/generate_review_embeddings.php --force --limit=50
                </pre>
            </div>
        </div>
    </div>
    
    <script>
    // 日別処理グラフ
    const ctx = document.getElementById('dailyChart');
    if (ctx) {
        const dailyData = <?php echo json_encode($stats['daily']); ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [{
                    label: '成功',
                    data: dailyData.map(d => d.success),
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                }, {
                    label: '失敗',
                    data: dailyData.map(d => d.failed),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    </script>
</body>
</html>