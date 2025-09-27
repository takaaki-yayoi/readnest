<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(dirname(__FILE__))) . '/library/form_helpers.php');

// メインコンテンツを生成
ob_start();
?>

<div class="min-h-screen bg-gray-50">
    <!-- ヘッダー -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">Embedding生成進捗管理</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="batch_generate_embeddings.php" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        バッチ処理実行
                    </a>
                    <a href="index.php" 
                       class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                        管理画面トップ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- 全体統計 -->
        <div class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">全体の処理状況</h2>
            <?php if ($overallStats): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-600 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">総書籍数</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= number_format($overallStats['total_books']) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-600 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">処理済み</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= number_format($overallStats['processed_books']) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-600 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">未処理</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= number_format($overallStats['remaining_books']) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-600 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">進捗率</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= $overallStats['progress_percentage'] ?>%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 進捗バー -->
            <div class="mt-6 bg-white p-6 rounded-lg shadow">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">全体の進捗</span>
                    <span class="text-sm text-gray-500"><?= $overallStats['processed_books'] ?> / <?= $overallStats['total_books'] ?> 冊</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?= $overallStats['progress_percentage'] ?>%"></div>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <p class="text-red-700">統計データの取得に失敗しました。</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- API制限状況 -->
        <div class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">API制限状況</h2>
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600"><?= $apiLimitStatus['google_requests_today'] ?></div>
                            <div class="text-sm text-gray-500">今日のGoogle APIリクエスト数</div>
                            <div class="text-xs text-gray-400 mt-1">制限: 1,000 / 日</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold <?= $apiLimitStatus['google_limit_reached'] ? 'text-red-600' : 'text-green-600' ?>">
                                <?= $apiLimitStatus['google_limit_reached'] ? '制限到達' : '正常' ?>
                            </div>
                            <div class="text-sm text-gray-500">Google API状況</div>
                            <?php if ($apiLimitStatus['last_error_time']): ?>
                            <div class="text-xs text-red-500 mt-1">最終エラー: <?= $apiLimitStatus['last_error_time'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-600"><?= date('H:i') ?></div>
                            <div class="text-sm text-gray-500">現在時刻</div>
                            <div class="text-xs text-gray-400 mt-1">制限リセット: 翌日 00:00</div>
                        </div>
                    </div>
                    
                    <?php if ($apiLimitStatus['google_limit_reached']): ?>
                    <div class="mt-4 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    Google Books APIの1日の制限に到達しました。明日まで待ってから処理を再開してください。
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 評価別処理状況 -->
        <?php if (!empty($highRatingStats)): ?>
        <div class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">評価別処理状況</h2>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">評価</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">総数</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">処理済み</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">未処理</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">進捗率</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">進捗バー</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($highRatingStats as $stat): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($stat['rating_range']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= number_format($stat['total_books']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= number_format($stat['processed_books']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= number_format($stat['remaining_books']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= $stat['progress_percentage'] ?>%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $stat['progress_percentage'] ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- バッチ実行履歴 -->
        <?php if (!empty($batchHistory)): ?>
        <div class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">バッチ実行履歴</h2>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">バッチID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">開始時刻</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">処理結果</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">実行時間</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">成功率</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">API使用数</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($batchHistory as $batch): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">
                                <?= htmlspecialchars(substr($batch['batch_id'], -12)) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('m/d H:i', strtotime($batch['start_time'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusColors = [
                                    'running' => 'bg-yellow-100 text-yellow-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'stopped' => 'bg-gray-100 text-gray-800'
                                ];
                                $statusColor = $statusColors[$batch['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusColor ?>">
                                    <?= htmlspecialchars($batch['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                成功: <?= $batch['successful_books'] ?> / 
                                失敗: <?= $batch['failed_books'] ?> / 
                                スキップ: <?= $batch['skipped_books'] ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($batch['actual_duration_seconds']): ?>
                                    <?= gmdate('H:i:s', $batch['actual_duration_seconds']) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= $batch['success_rate'] ?>%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                G: <?= $batch['google_api_requests'] ?> / 
                                O: <?= $batch['openai_api_requests'] ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- 最近処理された本 -->
        <?php if (!empty($recentlyProcessed)): ?>
        <div class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">最近処理された本</h2>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">タイトル</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">著者</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">平均評価</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">処理日時</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">モデル</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentlyProcessed as $book): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($book['title']) ?>
                                </div>
                                <?php if (!empty($book['description_preview'])): ?>
                                <div class="text-xs text-gray-500 mt-1 truncate max-w-xs">
                                    <?= htmlspecialchars($book['description_preview']) ?>...
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($book['author']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($book['avg_rating']): ?>
                                    <?= number_format($book['avg_rating'], 1) ?> (<?= $book['rating_count'] ?>件)
                                <?php else: ?>
                                    未評価
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('m/d H:i', strtotime($book['embedding_generated_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                <?= htmlspecialchars($book['embedding_model']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- エラーログ -->
        <?php if (!empty($errorBooks)): ?>
        <div class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">最近のエラー</h2>
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($errorBooks as $error): ?>
                        <li class="py-3">
                            <div class="flex justify-between">
                                <div class="text-sm text-red-700 flex-1 pr-4">
                                    <?= htmlspecialchars($error['message']) ?>
                                </div>
                                <div class="text-xs text-gray-500 whitespace-nowrap">
                                    <?= date('m/d H:i', strtotime($error['timestamp'])) ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- アクション -->
        <div class="text-center">
            <a href="batch_generate_embeddings.php?limit=10" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-4">
                少量処理 (10冊)
            </a>
            <a href="batch_generate_embeddings.php?limit=50" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mr-4">
                中規模処理 (50冊)
            </a>
            <a href="batch_generate_embeddings.php?limit=100" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                大規模処理 (100冊)
            </a>
        </div>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                <strong>注意:</strong> Google Books APIの制限（1,000リクエスト/日）に注意してください。
                制限に到達した場合は翌日まで待つ必要があります。
            </p>
        </div>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>