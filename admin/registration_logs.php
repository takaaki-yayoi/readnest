<?php
/**
 * ユーザー登録ログ管理画面
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/registration_logger.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = 'ユーザー登録ログ';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');

// 期間の取得
$period = $_GET['period'] ?? 'today';
$valid_periods = ['today', 'week', 'month'];
if (!in_array($period, $valid_periods)) {
    $period = 'today';
}

// 統計データを取得
$stats = RegistrationLogger::getRegistrationStats($period);

// ログファイルの取得
$log_dir = dirname(__DIR__) . '/logs/registration';
$current_log_file = $log_dir . '/' . date('Y-m') . '_registration.log';
$log_exists = file_exists($current_log_file);
$recent_logs = [];

if ($log_exists) {
    // 最新のログエントリを取得（最大50件）
    $all_logs = file($current_log_file);
    $all_logs = array_reverse($all_logs); // 新しい順に
    $recent_logs = array_slice($all_logs, 0, 50);
}

include('layout/header.php');
?>

<div class="page-header">
    <h1>ユーザー登録ログ</h1>
</div>

<!-- パンくずリスト -->
<div class="mb-4 text-sm text-gray-600">
    <a href="/admin/" class="hover:text-admin-primary">管理画面</a>
    <span class="mx-2">/</span>
    <a href="/admin/users.php" class="hover:text-admin-primary">ユーザー管理</a>
    <span class="mx-2">/</span>
    <span class="text-gray-900">登録ログ</span>
</div>

<!-- 期間選択タブ -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="border-b">
        <nav class="flex space-x-8 px-6" aria-label="Tabs">
            <?php foreach (['today' => '今日', 'week' => '過去7日', 'month' => '過去30日'] as $p => $label): ?>
            <a href="?period=<?php echo $p; ?>" 
               class="<?php echo $period === $p ? 'border-admin-primary text-admin-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                <?php echo $label; ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
    
    <!-- 統計サマリー -->
    <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900"><?php echo safeNumber($stats['registrations_started'] ?? 0); ?></p>
                <p class="text-sm text-gray-600">登録開始</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-green-600"><?php echo safeNumber($stats['interim_success'] ?? 0); ?></p>
                <p class="text-sm text-gray-600">仮登録成功</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600"><?php echo safeNumber($stats['activations_success'] ?? 0); ?></p>
                <p class="text-sm text-gray-600">本登録完了</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-red-600"><?php echo safeNumber($stats['mail_failures'] ?? 0); ?></p>
                <p class="text-sm text-gray-600">メール失敗</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-orange-600"><?php echo safeNumber($stats['duplicate_attempts'] ?? 0); ?></p>
                <p class="text-sm text-gray-600">重複試行</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-600"><?php echo safeNumber($stats['expired_attempts'] ?? 0); ?></p>
                <p class="text-sm text-gray-600">期限切れ</p>
            </div>
        </div>
        
        <?php if (!empty($stats['errors'])): ?>
        <div class="mt-4 p-4 bg-red-50 rounded-lg">
            <h3 class="text-sm font-medium text-red-800 mb-2">エラー (<?php echo count($stats['errors']); ?>件)</h3>
            <div class="space-y-1">
                <?php foreach (array_slice($stats['errors'], 0, 5) as $error): ?>
                <p class="text-xs text-red-700"><?php echo safeHtml($error); ?></p>
                <?php endforeach; ?>
                <?php if (count($stats['errors']) > 5): ?>
                <p class="text-xs text-red-600 italic">他 <?php echo count($stats['errors']) - 5; ?>件のエラー</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 最近のログ -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b">
        <h2 class="text-lg font-semibold text-gray-900">最近のログエントリ</h2>
    </div>
    
    <div class="p-6">
        <?php if (!$log_exists || empty($recent_logs)): ?>
            <p class="text-gray-500 text-center py-8">ログデータがありません。</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日時</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">レベル</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">イベント</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">詳細</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recent_logs as $log_line): ?>
                        <?php
                        // ログ行をパース
                        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[(\w+)\] ([^|]+) \| (.*)$/', trim($log_line), $matches)) {
                            $timestamp = $matches[1];
                            $level = $matches[2];
                            $event = $matches[3];
                            $data = $matches[4];
                            
                            // レベルに応じた色設定
                            $level_class = match($level) {
                                'ERROR' => 'text-red-600 bg-red-50',
                                'WARNING' => 'text-orange-600 bg-orange-50',
                                'INFO' => 'text-green-600 bg-green-50',
                                default => 'text-gray-600 bg-gray-50'
                            };
                        ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                <?php echo safeHtml($timestamp); ?>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $level_class; ?>">
                                    <?php echo $level; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?php echo safeHtml(trim($event)); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <div class="max-w-xs truncate" title="<?php echo safeHtml($data); ?>">
                                    <?php echo safeHtml($data); ?>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 管理アクション -->
<div class="mt-6 bg-gray-50 rounded-lg p-6">
    <h3 class="text-sm font-medium text-gray-900 mb-4">管理アクション</h3>
    <div class="flex flex-wrap gap-3">
        <a href="/admin/clean_interim_users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-user-clock mr-2"></i>
            仮登録ユーザー管理
        </a>
        <a href="/admin/check_mail_config.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-envelope mr-2"></i>
            メール設定確認
        </a>
        <?php if ($log_exists): ?>
        <a href="/logs/registration/<?php echo basename($current_log_file); ?>" download class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-download mr-2"></i>
            ログファイルをダウンロード
        </a>
        <?php endif; ?>
    </div>
</div>

<?php include('layout/footer.php'); ?>