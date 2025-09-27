<?php
/**
 * Cron管理画面
 * cronジョブの一覧表示と手動実行
 */

declare(strict_types=1);

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = 'Cron管理';
$current_page = 'cron_management';

// 手動実行処理
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_cron'])) {
    $cron_script = $_POST['cron_script'];
    $allowed_scripts = [
        'cache_warmer.php',
        'clear_activities_cache.php', 
        'update_popular_books.php',
        'updateDB.php',
        'update_sakka_cloud.php',
        'update_static_stats.php',
        'update_sitemap.php',
        'update_popular_tags_cache.php',
        'update_user_reading_stats.php'
    ];
    
    if (in_array($cron_script, $allowed_scripts)) {
        $script_path = dirname(__DIR__) . '/cron/' . $cron_script;
        if (file_exists($script_path)) {
            $start_time = microtime(true);
            ob_start();
            $output = shell_exec("php " . escapeshellarg($script_path) . " 2>&1");
            ob_end_clean();
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            if ($output !== null) {
                $message = "Cron実行成功: {$cron_script} (実行時間: {$execution_time}ms)";
                
                // 実行ログを記録
                $log_sql = "INSERT INTO b_cron_log (cron_type, status, message, execution_time, created_at) VALUES (?, ?, ?, ?, ?)";
                $g_db->query($log_sql, [
                    str_replace('.php', '', $cron_script),
                    'success',
                    substr($output, 0, 500),
                    intval($execution_time),
                    time()
                ]);
            } else {
                $error = "Cron実行失敗: {$cron_script}";
            }
        } else {
            $error = "Cronスクリプトが見つかりません: {$cron_script}";
        }
    } else {
        $error = "不正なCronスクリプト: {$cron_script}";
    }
}

// Cronジョブ定義
$cron_jobs = [
    [
        'name' => 'キャッシュウォーマー',
        'script' => 'cache_warmer.php',
        'description' => '各種キャッシュを事前生成してパフォーマンスを向上',
        'schedule' => '*/10 * * * *',
        'schedule_desc' => '10分ごと',
        'icon' => 'fas fa-fire',
        'color' => 'orange'
    ],
    [
        'name' => 'アクティビティキャッシュクリア',
        'script' => 'clear_activities_cache.php',
        'description' => 'ニックネーム表示問題を自動修正',
        'schedule' => '*/30 * * * *',
        'schedule_desc' => '30分ごと',
        'icon' => 'fas fa-broom',
        'color' => 'purple'
    ],
    [
        'name' => '人気の本更新',
        'script' => 'update_popular_books.php',
        'description' => 'ホームページの人気の本表示を更新',
        'schedule' => '0 * * * *',
        'schedule_desc' => '毎時0分',
        'icon' => 'fas fa-book',
        'color' => 'blue'
    ],
    [
        'name' => 'データベース更新',
        'script' => 'updateDB.php',
        'description' => '書籍情報を更新（update_sakka_cloud.phpを呼び出し）',
        'schedule' => '0 3 * * *',
        'schedule_desc' => '毎日午前3時',
        'icon' => 'fas fa-database',
        'color' => 'green'
    ],
    [
        'name' => '作家クラウド更新',
        'script' => 'update_sakka_cloud.php',
        'description' => '作家クラウドのデータを更新',
        'schedule' => '0 4 * * *',
        'schedule_desc' => '毎日午前4時',
        'icon' => 'fas fa-cloud',
        'color' => 'indigo'
    ],
    [
        'name' => 'サイト統計更新',
        'script' => 'update_static_stats.php',
        'description' => 'ホームページのサイト統計を更新',
        'schedule' => '0 */6 * * *',
        'schedule_desc' => '6時間ごと',
        'icon' => 'fas fa-chart-bar',
        'color' => 'red'
    ],
    [
        'name' => 'サイトマップ更新',
        'script' => 'update_sitemap.php',
        'description' => 'sitemap.xmlを生成・更新しGoogleに通知',
        'schedule' => '0 2 * * *',
        'schedule_desc' => '毎日午前2時',
        'icon' => 'fas fa-sitemap',
        'color' => 'teal'
    ],
    [
        'name' => '人気のタグキャッシュ更新',
        'script' => 'update_popular_tags_cache.php',
        'description' => '人気のタグを事前集計してキャッシュ',
        'schedule' => '0 5 * * *',
        'schedule_desc' => '毎日午前5時',
        'icon' => 'fas fa-tags',
        'color' => 'pink'
    ],
    [
        'name' => 'ユーザー読書統計更新',
        'script' => 'update_user_reading_stats.php',
        'description' => 'ユーザーの月間・累計読了数を更新（ランキング表示用）',
        'schedule' => '0 3 * * *',
        'schedule_desc' => '毎日午前3時',
        'icon' => 'fas fa-user-chart',
        'color' => 'cyan'
    ]
];

// 最新の実行ログを取得
$logs_sql = "SELECT cron_type, status, execution_time, created_at, message 
             FROM b_cron_log 
             ORDER BY created_at DESC 
             LIMIT 50";
$logs = $g_db->getAll($logs_sql, [], DB_FETCHMODE_ASSOC);
if (DB::isError($logs)) {
    $logs = [];
}

// 各Cronの最終実行情報を取得
$last_runs = [];
foreach ($cron_jobs as $job) {
    $cron_type = str_replace('.php', '', $job['script']);
    $last_run_sql = "SELECT status, execution_time, created_at 
                     FROM b_cron_log 
                     WHERE cron_type = ? 
                     ORDER BY created_at DESC 
                     LIMIT 1";
    $last_run = $g_db->getRow($last_run_sql, [$cron_type], DB_FETCHMODE_ASSOC);
    if (!DB::isError($last_run) && $last_run) {
        $last_runs[$job['script']] = $last_run;
    }
}

// レイアウトヘッダーを読み込み
include('layout/header.php');
?>

<div class="space-y-6">
    <?php include('layout/submenu.php'); ?>

    <?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Cronジョブ一覧 -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-clock text-blue-500 mr-2"></i>
                Cronジョブ一覧
            </h2>
        </div>
        
        <div class="p-6">
            <div class="grid gap-4">
                <?php foreach ($cron_jobs as $job): ?>
                <?php 
                    $last_run = $last_runs[$job['script']] ?? null;
                    $status_class = 'gray';
                    $status_text = '未実行';
                    
                    if ($last_run) {
                        if ($last_run['status'] === 'success') {
                            $status_class = 'green';
                            $status_text = '成功';
                        } elseif ($last_run['status'] === 'error') {
                            $status_class = 'red';
                            $status_text = 'エラー';
                        } else {
                            $status_class = 'yellow';
                            $status_text = '部分的成功';
                        }
                    }
                ?>
                <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <div class="bg-<?php echo $job['color']; ?>-100 rounded-full p-2 mr-3">
                                    <i class="<?php echo $job['icon']; ?> text-<?php echo $job['color']; ?>-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($job['name']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($job['script']); ?></p>
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($job['description']); ?></p>
                            
                            <div class="flex items-center gap-4 text-sm">
                                <span class="text-gray-500">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    推奨: <?php echo htmlspecialchars($job['schedule_desc']); ?>
                                </span>
                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($job['schedule']); ?>
                                </span>
                            </div>
                            
                            <?php if ($last_run): ?>
                            <div class="mt-2 flex items-center gap-4 text-sm">
                                <span class="text-<?php echo $status_class; ?>-600">
                                    <i class="fas fa-circle mr-1"></i>
                                    <?php echo $status_text; ?>
                                </span>
                                <span class="text-gray-500">
                                    最終実行: <?php echo date('Y-m-d H:i:s', $last_run['created_at']); ?>
                                </span>
                                <span class="text-gray-500">
                                    実行時間: <?php echo number_format($last_run['execution_time'], 2); ?>ms
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <form method="post" class="ml-4">
                            <input type="hidden" name="cron_script" value="<?php echo htmlspecialchars($job['script']); ?>">
                            <button type="submit" name="execute_cron" value="1" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm transition-colors">
                                <i class="fas fa-play mr-1"></i>
                                手動実行
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Cron設定例 -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-terminal text-green-500 mr-2"></i>
                Cron設定例
            </h2>
        </div>
        
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">
                以下のコマンドをサーバーのcrontabに追加してください：
            </p>
            
            <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                <pre># ReadNest Cron Jobs
# キャッシュウォーマー（10分ごと）
*/10 * * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/cache_warmer.php

# アクティビティキャッシュクリア（30分ごと）
*/30 * * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/clear_activities_cache.php

# 人気の本更新（毎時）
0 * * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/update_popular_books.php

# サイトマップ更新（毎日午前2時）
0 2 * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/update_sitemap.php

# ユーザー読書統計更新（毎日午前3時）
0 3 * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/update_user_reading_stats.php

# データベース更新（毎日午前3時）
0 3 * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/updateDB.php

# 作家クラウド更新（毎日午前4時）
0 4 * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/update_sakka_cloud.php

# 人気のタグキャッシュ更新（毎日午前5時）
0 5 * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/update_popular_tags_cache.php

# サイト統計更新（6時間ごと）
0 */6 * * * /usr/bin/php <?php echo dirname(__DIR__); ?>/cron/update_static_stats.php</pre>
            </div>
        </div>
    </div>

    <!-- 実行ログ -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-history text-purple-500 mr-2"></i>
                最近の実行ログ
            </h2>
        </div>
        
        <div class="p-6">
            <?php if (empty($logs)): ?>
                <p class="text-gray-500 text-center py-8">まだ実行ログがありません</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    タイプ
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ステータス
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    実行時間
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    実行日時
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    メッセージ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($logs as $log): ?>
                            <?php
                                $status_class = 'gray';
                                if ($log['status'] === 'success') {
                                    $status_class = 'green';
                                } elseif ($log['status'] === 'error') {
                                    $status_class = 'red';
                                } elseif ($log['status'] === 'partial') {
                                    $status_class = 'yellow';
                                }
                            ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($log['cron_type']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_class; ?>-100 text-<?php echo $status_class; ?>-800">
                                        <?php echo htmlspecialchars($log['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo number_format($log['execution_time'], 2); ?>ms
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('Y-m-d H:i:s', $log['created_at']); ?>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($log['message']); ?>">
                                        <?php echo htmlspecialchars(substr($log['message'], 0, 50)); ?>
                                        <?php if (strlen($log['message']) > 50): ?>...<?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('layout/footer.php'); ?>