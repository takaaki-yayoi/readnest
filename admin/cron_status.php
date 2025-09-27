<?php
/**
 * Cron実行状況確認ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');

// ページタイトルを設定
$page_title = 'Cron実行状況';
$current_page = 'cron_status';

// ページネーション
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// フィルタ
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';

// 条件構築
$where = [];
$params = [];

if ($filter_type) {
    $where[] = 'cron_type = ?';
    $params[] = $filter_type;
}

if ($filter_status) {
    $where[] = 'status = ?';
    $params[] = $filter_status;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 総件数を取得
$count_sql = "SELECT COUNT(*) as count FROM b_cron_log {$where_clause}";
$count_result = $g_db->getRow($count_sql, $params);
$total_count = $count_result['count'] ?? 0;
$total_pages = ceil($total_count / $per_page);

// ログを取得
$logs_sql = "
    SELECT * FROM b_cron_log 
    {$where_clause}
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;

$logs = $g_db->getAll($logs_sql, $params, DB_FETCHMODE_ASSOC);

// cron種類の一覧を取得
$types_sql = "SELECT DISTINCT cron_type FROM b_cron_log ORDER BY cron_type";
$types_result = $g_db->getAll($types_sql, array(), DB_FETCHMODE_ASSOC);
$cron_types = array();
if ($types_result && !DB::isError($types_result)) {
    foreach ($types_result as $row) {
        $cron_types[] = $row['cron_type'];
    }
}

// 統計情報を取得
$stats_sql = "
    SELECT 
        cron_type,
        COUNT(*) as total_runs,
        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
        SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count,
        AVG(execution_time) as avg_time,
        MAX(created_at) as last_run
    FROM b_cron_log
    WHERE created_at > ?
    GROUP BY cron_type
";

$week_ago = time() - (7 * 24 * 60 * 60);
$stats = $g_db->getAll($stats_sql, [$week_ago], DB_FETCHMODE_ASSOC);

// cron定義
$cron_definitions = [
    'cache_warmer' => [
        'name' => 'キャッシュウォーマー',
        'description' => 'キャッシュを事前に生成してパフォーマンスを向上',
        'recommended_schedule' => '*/10 * * * *',
        'command' => 'php ' . dirname(__DIR__) . '/cron/cache_warmer.php'
    ],
    'clear_activities_cache' => [
        'name' => '活動キャッシュクリア',
        'description' => 'ニックネーム表示問題を自動修正',
        'recommended_schedule' => '*/30 * * * *',
        'command' => 'php ' . dirname(__DIR__) . '/cron/clear_activities_cache.php'
    ],
    'update_popular_books' => [
        'name' => '人気の本更新',
        'description' => '人気の本のキャッシュを更新',
        'recommended_schedule' => '0 * * * *',
        'command' => 'php ' . dirname(__DIR__) . '/cron/update_popular_books.php'
    ],
    'updateDB' => [
        'name' => 'データベース更新',
        'description' => '書籍情報を更新',
        'recommended_schedule' => '0 3 * * *',
        'command' => 'php ' . dirname(__DIR__) . '/cron/updateDB.php'
    ],
    'update_sakka_cloud' => [
        'name' => '作家クラウド更新',
        'description' => '作家クラウドデータを更新',
        'recommended_schedule' => '0 4 * * *',
        'command' => 'php ' . dirname(__DIR__) . '/cron/update_sakka_cloud.php'
    ],
    'update_static_stats' => [
        'name' => '統計情報更新',
        'description' => 'サイト統計情報を更新',
        'recommended_schedule' => '0 */6 * * *',
        'command' => 'php ' . dirname(__DIR__) . '/cron/update_static_stats.php'
    ],
    'update_sitemap' => [
        'name' => 'サイトマップ更新',
        'description' => 'sitemap.xmlを生成・更新しGoogleに通知',
        'recommended_schedule' => '0 2 * * *',
        'command' => 'php ' . dirname(__DIR__) . '/cron/update_sitemap.php'
    ],
    'update_popular_tags_cache' => [
        'name' => '人気のタグキャッシュ更新',
        'description' => '人気のタグを事前集計してキャッシュ',
        'recommended_schedule' => '0 5 * * *',
        'command' => 'php ' . dirname(__DIR__) . '/cron/update_popular_tags_cache.php'
    ]
];

// レイアウトヘッダーを読み込み
include('layout/header.php');
?>

<div class="space-y-6">
    <?php include('layout/submenu.php'); ?>

            <!-- 手動実行ボタン -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-yellow-900 mb-2">
                    <i class="fas fa-play-circle mr-2"></i>手動実行
                </h3>
                <p class="text-sm text-yellow-800 mb-3">
                    各cronジョブを手動で実行できます。処理には時間がかかる場合があります。
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <a href="/cron/cache_warmer.php" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                       onclick="return confirm('キャッシュウォーマーを実行しますか？');">
                        <i class="fas fa-fire mr-2"></i>
                        キャッシュウォーマー
                    </a>
                    <a href="/cron/clear_activities_cache.php" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                       onclick="return confirm('活動キャッシュをクリアしますか？');">
                        <i class="fas fa-broom mr-2"></i>
                        活動キャッシュクリア
                    </a>
                    <a href="/cron/update_popular_books.php" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700"
                       onclick="return confirm('人気の本を更新しますか？');">
                        <i class="fas fa-star mr-2"></i>
                        人気の本更新
                    </a>
                    <a href="/cron/update_static_stats.php" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                       onclick="return confirm('統計情報を更新しますか？');">
                        <i class="fas fa-chart-bar mr-2"></i>
                        統計情報更新
                    </a>
                    <a href="/cron/updateDB.php" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
                       onclick="return confirm('データベースを更新しますか？\n処理には数分かかる場合があります。');">
                        <i class="fas fa-database mr-2"></i>
                        データベース更新
                    </a>
                    <a href="/cron/update_sakka_cloud.php" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                       onclick="return confirm('作家クラウドを更新しますか？');">
                        <i class="fas fa-cloud mr-2"></i>
                        作家クラウド更新
                    </a>
                </div>
            </div>

            <!-- cron設定ガイド -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>cron設定方法
                </h3>
                <div class="text-sm text-blue-800 space-y-2">
                    <?php foreach ($cron_definitions as $type => $def): ?>
                    <div class="bg-white rounded p-3">
                        <p class="font-medium"><?php echo html($def['name']); ?></p>
                        <p class="text-xs text-gray-600 mb-1"><?php echo html($def['description']); ?></p>
                        <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                            <?php echo html($def['recommended_schedule'] . ' ' . $def['command']); ?>
                        </code>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 統計情報 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <?php foreach ($cron_definitions as $type => $def): ?>
                <?php 
                $type_stats = null;
                foreach ($stats as $stat) {
                    if ($stat['cron_type'] === $type) {
                        $type_stats = $stat;
                        break;
                    }
                }
                ?>
                <div class="bg-white rounded-lg shadow p-4">
                    <h4 class="font-semibold text-gray-900"><?php echo html($def['name']); ?></h4>
                    <?php if ($type_stats): ?>
                        <div class="mt-2 space-y-1 text-sm">
                            <p>成功率: 
                                <span class="font-medium <?php echo $type_stats['success_count'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo round(($type_stats['success_count'] / $type_stats['total_runs']) * 100, 1); ?>%
                                </span>
                            </p>
                            <p>平均実行時間: 
                                <span class="font-medium">
                                    <?php echo number_format((float)$type_stats['avg_time']); ?>ms
                                </span>
                            </p>
                            <p>最終実行: 
                                <span class="text-xs text-gray-600">
                                    <?php echo date('Y-m-d H:i:s', intval($type_stats['last_run'])); ?>
                                </span>
                            </p>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 mt-2">まだ実行されていません</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- フィルタ -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="get" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">種類</label>
                        <select name="type" class="px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">すべて</option>
                            <?php foreach ($cron_types as $type): ?>
                            <option value="<?php echo html($type); ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                                <?php echo html($cron_definitions[$type]['name'] ?? $type); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ステータス</label>
                        <select name="status" class="px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">すべて</option>
                            <option value="success" <?php echo $filter_status === 'success' ? 'selected' : ''; ?>>成功</option>
                            <option value="error" <?php echo $filter_status === 'error' ? 'selected' : ''; ?>>エラー</option>
                            <option value="partial" <?php echo $filter_status === 'partial' ? 'selected' : ''; ?>>部分的</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        <i class="fas fa-filter mr-2"></i>フィルタ
                    </button>
                    <a href="?" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        <i class="fas fa-times mr-2"></i>クリア
                    </a>
                </form>
            </div>

            <!-- 実行ログ -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-900">
                        <i class="fas fa-list mr-2"></i>実行ログ
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    実行日時
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    種類
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ステータス
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    実行時間
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    メッセージ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($logs && !DB::isError($logs)): ?>
                                <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('Y-m-d H:i:s', intval($log['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">
                                            <?php echo html($cron_definitions[$log['cron_type']]['name'] ?? $log['cron_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php
                                        $status_classes = [
                                            'success' => 'bg-green-100 text-green-800',
                                            'error' => 'bg-red-100 text-red-800',
                                            'partial' => 'bg-yellow-100 text-yellow-800'
                                        ];
                                        $status_labels = [
                                            'success' => '成功',
                                            'error' => 'エラー',
                                            'partial' => '部分的'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 rounded text-xs font-medium <?php echo $status_classes[$log['status']] ?? ''; ?>">
                                            <?php echo $status_labels[$log['status']] ?? $log['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format((float)$log['execution_time']); ?>ms
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate" title="<?php echo html($log['message']); ?>">
                                            <?php echo html(mb_substr($log['message'], 0, 50)); ?>...
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        ログがありません
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ページネーション -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            <?php echo number_format($total_count); ?>件中 
                            <?php echo number_format($offset + 1); ?>〜<?php echo number_format(min($offset + $per_page, $total_count)); ?>件を表示
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&type=<?php echo urlencode($filter_type); ?>&status=<?php echo urlencode($filter_status); ?>" 
                               class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&type=<?php echo urlencode($filter_type); ?>&status=<?php echo urlencode($filter_status); ?>" 
                               class="px-3 py-1 rounded <?php echo $i === $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&type=<?php echo urlencode($filter_type); ?>&status=<?php echo urlencode($filter_status); ?>" 
                               class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
</div>

<?php include('layout/footer.php'); ?>