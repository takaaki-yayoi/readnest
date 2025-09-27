<?php
/**
 * 著者情報同期の進捗確認ページ
 */

require_once(dirname(__DIR__) . '/modern_config.php');
require_once('admin_auth.php');
requireAdmin();

// ログファイルのパス
$logDir = dirname(__DIR__) . '/logs';
$logFiles = [];

if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if (strpos($file, 'author_sync_') === 0 || strpos($file, 'author_fix_') === 0) {
            $logFiles[] = $file;
        }
    }
    rsort($logFiles); // 新しい順にソート
}

// 特定のログファイルを表示
$selectedLog = $_GET['log'] ?? '';
$logContent = '';
if ($selectedLog && in_array($selectedLog, $logFiles)) {
    $logPath = $logDir . '/' . $selectedLog;
    if (file_exists($logPath)) {
        $logContent = file_get_contents($logPath);
    }
}

// AJAX用のエンドポイント
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] === 'tail' && $selectedLog) {
        $logPath = $logDir . '/' . $selectedLog;
        if (file_exists($logPath)) {
            // 最後の50行を取得
            $lines = [];
            $fp = fopen($logPath, 'r');
            if ($fp) {
                while (!feof($fp)) {
                    $lines[] = fgets($fp);
                    if (count($lines) > 50) {
                        array_shift($lines);
                    }
                }
                fclose($fp);
            }
            echo json_encode([
                'content' => implode('', $lines),
                'size' => filesize($logPath),
                'modified' => filemtime($logPath)
            ]);
        }
    } elseif ($_GET['ajax'] === 'stats') {
        // 統計情報を取得
        $stats = [
            'needs_sync' => 0,  // listにあってrepoにない
            'missing_in_repo' => 0,
            'missing_both' => 0
        ];
        
        // 同期が必要な本の数を取得
        $sql = "
            SELECT 
                SUM(CASE 
                    WHEN bl.author IS NOT NULL AND bl.author != '' 
                    AND bl.amazon_id IS NOT NULL AND bl.amazon_id != ''
                    AND (br.author IS NULL OR br.author = '') 
                    THEN 1 ELSE 0 
                END) as needs_sync,
                SUM(CASE WHEN br.author IS NULL OR br.author = '' THEN 1 ELSE 0 END) as missing_in_repo,
                SUM(CASE WHEN (bl.author IS NULL OR bl.author = '') AND (br.author IS NULL OR br.author = '') THEN 1 ELSE 0 END) as missing_both
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
        ";
        $result = $g_db->getRow($sql, null, DB_FETCHMODE_ASSOC);
        if (!DB::isError($result)) {
            $stats = $result;
        }
        
        echo json_encode($stats);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>同期処理進捗確認 - ReadNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-sync-alt mr-2"></i>同期処理進捗確認
            </h1>
            <p class="text-gray-600">著者情報同期処理の進捗とログを確認できます</p>
            <p class="text-sm text-gray-500 mt-1">
                ※ b_book_list→b_book_repositoryの一方向同期のみ実行します
            </p>
        </div>

        <!-- リアルタイム統計 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-chart-line mr-2"></i>現在の状況
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="stats">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600" id="needs_sync">-</div>
                    <div class="text-sm text-gray-600">同期待ち</div>
                    <div class="text-xs text-gray-500">list→repo</div>
                </div>
                <div class="p-4 bg-orange-50 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600" id="missing_repo">-</div>
                    <div class="text-sm text-gray-600">repository著者なし</div>
                    <div class="text-xs text-gray-500">全体</div>
                </div>
                <div class="p-4 bg-purple-50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600" id="missing_both">-</div>
                    <div class="text-sm text-gray-600">両方著者なし</div>
                    <div class="text-xs text-gray-500">API必要</div>
                </div>
            </div>
        </div>

        <!-- アクション -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-play-circle mr-2"></i>処理実行
            </h2>
            <div class="flex gap-4">
                <a href="/admin/sync_authors.php" 
                   class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-sync mr-2"></i>同期処理を実行
                </a>
                <a href="/admin/missing_authors.php" 
                   class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-edit mr-2"></i>手動編集画面へ
                </a>
            </div>
        </div>

        <!-- ログファイル一覧 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-file-alt mr-2"></i>ログファイル
            </h2>
            
            <?php if (empty($logFiles)): ?>
                <p class="text-gray-500">ログファイルがありません</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($logFiles as $file): ?>
                        <?php 
                        $filePath = $logDir . '/' . $file;
                        $fileSize = filesize($filePath);
                        $fileTime = filemtime($filePath);
                        $isSelected = ($file === $selectedLog);
                        ?>
                        <div class="flex items-center justify-between p-3 <?php echo $isSelected ? 'bg-blue-50 border-l-4 border-blue-500' : 'hover:bg-gray-50'; ?>">
                            <a href="?log=<?php echo urlencode($file); ?>" 
                               class="flex-1 text-blue-600 hover:underline">
                                <i class="fas fa-file mr-2"></i>
                                <?php echo htmlspecialchars($file); ?>
                            </a>
                            <div class="text-sm text-gray-500">
                                <span class="mr-4"><?php echo number_format($fileSize / 1024, 2); ?> KB</span>
                                <span><?php echo date('Y-m-d H:i:s', $fileTime); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ログ内容表示 -->
        <?php if ($selectedLog): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">
                    <i class="fas fa-terminal mr-2"></i>ログ内容: <?php echo htmlspecialchars($selectedLog); ?>
                </h2>
                <div class="flex gap-2">
                    <button id="autoScroll" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        <i class="fas fa-arrow-down mr-1"></i>自動スクロール ON
                    </button>
                    <button id="refresh" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i class="fas fa-sync mr-1"></i>更新
                    </button>
                </div>
            </div>
            <div id="logContent" class="bg-gray-900 text-green-400 p-4 rounded font-mono text-sm overflow-x-auto h-96 overflow-y-auto">
                <pre><?php echo htmlspecialchars($logContent); ?></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    // 統計情報の更新
    function updateStats() {
        fetch('sync_authors_progress.php?ajax=stats')
            .then(response => response.json())
            .then(data => {
                document.getElementById('needs_sync').textContent = 
                    data.needs_sync ? Number(data.needs_sync).toLocaleString() : '0';
                document.getElementById('missing_repo').textContent = 
                    data.missing_in_repo ? Number(data.missing_in_repo).toLocaleString() : '0';
                document.getElementById('missing_both').textContent = 
                    data.missing_both ? Number(data.missing_both).toLocaleString() : '0';
            });
    }

    // ログの自動更新
    let autoScrollEnabled = true;
    let updateInterval = null;
    
    <?php if ($selectedLog): ?>
    function updateLog() {
        fetch('sync_authors_progress.php?ajax=tail&log=<?php echo urlencode($selectedLog); ?>')
            .then(response => response.json())
            .then(data => {
                const logDiv = document.getElementById('logContent');
                logDiv.querySelector('pre').textContent = data.content;
                
                if (autoScrollEnabled) {
                    logDiv.scrollTop = logDiv.scrollHeight;
                }
            });
    }

    document.getElementById('autoScroll').addEventListener('click', function() {
        autoScrollEnabled = !autoScrollEnabled;
        this.textContent = autoScrollEnabled ? '自動スクロール ON' : '自動スクロール OFF';
        this.className = autoScrollEnabled 
            ? 'px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700' 
            : 'px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700';
    });

    document.getElementById('refresh').addEventListener('click', updateLog);

    // 5秒ごとに自動更新
    updateInterval = setInterval(updateLog, 5000);
    <?php endif; ?>

    // 初回実行
    updateStats();
    
    // 10秒ごとに統計を更新
    setInterval(updateStats, 10000);
    </script>
</body>
</html>