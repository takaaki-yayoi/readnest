<?php
/**
 * 統合ログビューア
 * すべてのログ機能を一つのページに統合
 */

declare(strict_types=1);

require_once('../config.php');
require_once('../library/database.php');

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

$page_title = 'ログ管理';

// ログタイプの選択
$log_type = $_GET['type'] ?? 'system';
$filter = $_GET['filter'] ?? '';
$lines = intval($_GET['lines'] ?? 100);

// ReadNestエラーログ
$readnest_log_path = '/home/icotfeels/readnest.jp/log/dokusho_error_log.txt';
$readnest_log_content = '';
$readnest_log_info = [];

// システムエラーログのパス候補
$system_log_paths = [
    '/var/log/php_errors.log',
    '/var/log/httpd/error_log',
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    '/home/icotfeels/logs/error_log',
    '/home/icotfeels/public_html/error_log',
    ini_get('error_log'),
];

// ログクリア処理
if (isset($_POST['clear_log']) && $_POST['log_type'] === 'readnest') {
    if (file_exists($readnest_log_path) && is_writable($readnest_log_path)) {
        file_put_contents($readnest_log_path, '');
        header('Location: /admin/logs.php?type=readnest&cleared=1');
        exit;
    }
}

// security.phpを読み込んでhtml関数を使用
require_once(dirname(dirname(__FILE__)) . '/library/security.php');

require_once(__DIR__ . '/layout/header.php');
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">ログ管理</h1>
        <a href="/admin/" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i>管理画面に戻る
        </a>
    </div>

    <?php if (isset($_GET['cleared'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i>ログファイルをクリアしました。
    </div>
    <?php endif; ?>

    <!-- ログタイプ選択タブ -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b">
            <nav class="flex -mb-px">
                <a href="?type=system" 
                   class="py-3 px-6 <?php echo $log_type === 'system' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600 hover:text-gray-800'; ?>">
                    <i class="fas fa-server mr-2"></i>システムログ
                </a>
                <a href="?type=readnest" 
                   class="py-3 px-6 <?php echo $log_type === 'readnest' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600 hover:text-gray-800'; ?>">
                    <i class="fas fa-book mr-2"></i>ReadNestログ
                </a>
                <a href="?type=x_api" 
                   class="py-3 px-6 <?php echo $log_type === 'x_api' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600 hover:text-gray-800'; ?>">
                    <i class="fab fa-x-twitter mr-2"></i>X関連ログ
                </a>
            </nav>
        </div>

        <div class="p-6">
            <!-- フィルターと設定 -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-4">
                    <!-- 行数選択 -->
                    <form method="get" class="flex items-center gap-2">
                        <input type="hidden" name="type" value="<?php echo html($log_type); ?>">
                        <?php if ($filter): ?>
                        <input type="hidden" name="filter" value="<?php echo html($filter); ?>">
                        <?php endif; ?>
                        <label for="lines" class="text-sm">表示行数:</label>
                        <select name="lines" id="lines" class="px-2 py-1 border rounded text-sm" onchange="this.form.submit()">
                            <option value="50" <?php echo $lines == 50 ? 'selected' : ''; ?>>50行</option>
                            <option value="100" <?php echo $lines == 100 ? 'selected' : ''; ?>>100行</option>
                            <option value="200" <?php echo $lines == 200 ? 'selected' : ''; ?>>200行</option>
                            <option value="500" <?php echo $lines == 500 ? 'selected' : ''; ?>>500行</option>
                            <option value="1000" <?php echo $lines == 1000 ? 'selected' : ''; ?>>1000行</option>
                        </select>
                    </form>

                    <!-- フィルターボタン -->
                    <?php if ($log_type === 'system' && !$filter): ?>
                    <a href="?type=system&lines=<?php echo $lines; ?>&filter=x" 
                       class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                        X関連のみ表示
                    </a>
                    <?php elseif ($filter): ?>
                    <a href="?type=<?php echo html($log_type); ?>&lines=<?php echo $lines; ?>" 
                       class="px-3 py-1 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">
                        すべて表示
                    </a>
                    <?php endif; ?>
                </div>

                <!-- クリアボタン（ReadNestログのみ） -->
                <?php if ($log_type === 'readnest' && file_exists($readnest_log_path) && is_writable($readnest_log_path)): ?>
                <form method="post" onsubmit="return confirm('ログファイルをクリアしてもよろしいですか？');">
                    <input type="hidden" name="log_type" value="readnest">
                    <button type="submit" name="clear_log" class="px-4 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                        <i class="fas fa-trash mr-2"></i>ログをクリア
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- ログ内容表示 -->
            <?php if ($log_type === 'system'): ?>
                <?php
                // システムログの処理
                $system_log_content = '';
                $system_log_path = '';
                
                foreach ($system_log_paths as $path) {
                    if ($path && file_exists($path) && is_readable($path)) {
                        $system_log_path = $path;
                        $command = sprintf('tail -n %d %s 2>&1', $lines, escapeshellarg($path));
                        $system_log_content = shell_exec($command);
                        
                        // X関連フィルター
                        if ($filter === 'x' && $system_log_content) {
                            $lines_array = explode("\n", $system_log_content);
                            $filtered_lines = [];
                            foreach ($lines_array as $line) {
                                if (stripos($line, 'x_connect') !== false || 
                                    stripos($line, 'x_callback') !== false || 
                                    stripos($line, 'x_disconnect') !== false ||
                                    stripos($line, 'oauth') !== false ||
                                    stripos($line, 'twitter') !== false ||
                                    stripos($line, '[X ') !== false) {
                                    $filtered_lines[] = $line;
                                }
                            }
                            $system_log_content = implode("\n", $filtered_lines);
                        }
                        break;
                    }
                }
                ?>
                
                <?php if ($system_log_path): ?>
                    <p class="text-sm text-gray-600 mb-2">ログファイル: <?php echo html($system_log_path); ?></p>
                    <div class="bg-gray-900 text-gray-100 p-4 rounded overflow-x-auto">
                        <pre class="text-xs font-mono whitespace-pre-wrap"><?php 
                            if ($system_log_content) {
                                echo html($system_log_content);
                            } else {
                                echo '<span class="text-gray-500">ログが空です。</span>';
                            }
                        ?></pre>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 p-4 rounded">
                        <p class="text-yellow-800">システムログファイルが見つかりません。</p>
                    </div>
                <?php endif; ?>

            <?php elseif ($log_type === 'readnest'): ?>
                <?php
                // ReadNestログの処理
                if (file_exists($readnest_log_path)) {
                    $readnest_log_info = [
                        'size' => filesize($readnest_log_path),
                        'modified' => date('Y-m-d H:i:s', filemtime($readnest_log_path)),
                        'readable' => is_readable($readnest_log_path)
                    ];
                    
                    if (is_readable($readnest_log_path)) {
                        $max_size = $lines * 1024; // 行数に応じたサイズ
                        if ($readnest_log_info['size'] > $max_size) {
                            $fp = fopen($readnest_log_path, 'r');
                            fseek($fp, -$max_size, SEEK_END);
                            $readnest_log_content = fread($fp, $max_size);
                            fclose($fp);
                            $readnest_log_content = '... (ファイルの最後の部分のみ表示) ...' . PHP_EOL . PHP_EOL . $readnest_log_content;
                        } else {
                            $readnest_log_content = file_get_contents($readnest_log_path);
                        }
                    }
                }
                ?>
                
                <?php if (file_exists($readnest_log_path)): ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                        <div>
                            <span class="text-gray-600">サイズ:</span>
                            <span class="font-medium"><?php echo number_format($readnest_log_info['size']); ?> バイト</span>
                        </div>
                        <div>
                            <span class="text-gray-600">最終更新:</span>
                            <span class="font-medium"><?php echo $readnest_log_info['modified']; ?></span>
                        </div>
                    </div>
                    
                    <?php if ($readnest_log_content): ?>
                        <div class="bg-gray-900 text-gray-100 p-4 rounded overflow-x-auto max-h-96 overflow-y-auto">
                            <pre class="text-xs font-mono whitespace-pre-wrap"><?php 
                                $entries = explode('-----------------------------------------', $readnest_log_content);
                                foreach ($entries as $entry) {
                                    if (trim($entry)) {
                                        echo '<div class="mb-4 pb-4 border-b border-gray-700">';
                                        echo html($entry);
                                        echo '</div>';
                                    }
                                }
                            ?></pre>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">ログが空です。</p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="bg-yellow-50 p-4 rounded">
                        <p class="text-yellow-800">ReadNestログファイルが見つかりません。</p>
                        <p class="text-sm text-yellow-600 mt-1">パス: <?php echo html($readnest_log_path); ?></p>
                    </div>
                <?php endif; ?>

            <?php elseif ($log_type === 'x_api'): ?>
                <?php
                // X関連ログの処理（両方のログから抽出）
                $x_logs = [];
                
                // ReadNestログからX関連を抽出
                if (file_exists($readnest_log_path) && is_readable($readnest_log_path)) {
                    $content = file_get_contents($readnest_log_path);
                    $entries = explode('-----------------------------------------', $content);
                    foreach ($entries as $entry) {
                        if (trim($entry) && (
                            stripos($entry, 'x_connect') !== false || 
                            stripos($entry, 'x_callback') !== false || 
                            stripos($entry, 'oauth') !== false ||
                            stripos($entry, '[X ') !== false)) {
                            $x_logs[] = ['source' => 'ReadNest', 'content' => trim($entry)];
                        }
                    }
                }
                
                // システムログからX関連を抽出
                foreach ($system_log_paths as $path) {
                    if ($path && file_exists($path) && is_readable($path)) {
                        $command = sprintf('tail -n %d %s 2>&1 | grep -i -E "x_connect|x_callback|oauth|twitter|\[X "', $lines, escapeshellarg($path));
                        $content = shell_exec($command);
                        if ($content) {
                            $lines_array = explode("\n", $content);
                            foreach ($lines_array as $line) {
                                if (trim($line)) {
                                    $x_logs[] = ['source' => 'System', 'content' => trim($line)];
                                }
                            }
                        }
                        break;
                    }
                }
                
                // 最新のものから表示（最大件数制限）
                $x_logs = array_slice($x_logs, -$lines);
                ?>
                
                <?php if (!empty($x_logs)): ?>
                    <p class="text-sm text-gray-600 mb-2">X関連のログエントリ: <?php echo count($x_logs); ?>件</p>
                    <div class="bg-gray-900 text-gray-100 p-4 rounded overflow-x-auto max-h-96 overflow-y-auto">
                        <?php foreach (array_reverse($x_logs) as $log): ?>
                            <div class="mb-4 pb-4 border-b border-gray-700">
                                <div class="text-xs text-blue-400 mb-1">[<?php echo $log['source']; ?>ログ]</div>
                                <pre class="text-xs font-mono whitespace-pre-wrap"><?php echo html($log['content']); ?></pre>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-blue-50 p-4 rounded">
                        <p class="text-blue-800">X関連のログエントリが見つかりません。</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 追加情報 -->
    <div class="bg-gray-100 rounded-lg p-4 mt-6">
        <h3 class="font-medium mb-2">ログファイルの場所</h3>
        <ul class="text-sm text-gray-600 space-y-1">
            <li><i class="fas fa-folder mr-1"></i>ReadNestログ: <code><?php echo html($readnest_log_path); ?></code></li>
            <li><i class="fas fa-folder mr-1"></i>システムログ: <code><?php echo html(ini_get('error_log') ?: '設定なし'); ?></code></li>
        </ul>
    </div>
</div>

<?php require_once(__DIR__ . '/layout/footer.php'); ?>