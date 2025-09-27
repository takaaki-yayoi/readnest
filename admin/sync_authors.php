<?php
/**
 * 著者情報同期スクリプト
 * b_book_listの著者情報をb_book_repositoryに同期する
 * Google Books APIは使用しない
 */

// CLIとWebの両方から実行可能
if (php_sapi_name() === 'cli') {
    // CLI実行時は設定ファイルを直接読み込む
    require_once(dirname(__DIR__) . '/config.php');
    require_once(dirname(__DIR__) . '/library/database.php');
} else {
    // Web実行時は認証チェックを含む
    require_once(dirname(__DIR__) . '/modern_config.php');
    require_once('admin_auth.php');
    requireAdmin();
}

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '128M');
set_time_limit(0);

/**
 * 著者情報の同期処理
 */
class AuthorSyncProcessor {
    private $db;
    private $processedCount = 0;
    private $fixedRepoCount = 0;
    private $createdRepoCount = 0;
    private $logFile;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        // ログファイル設定
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $this->logFile = $logDir . '/author_sync_' . date('Y-m-d_His') . '.log';
    }
    
    /**
     * 実行
     */
    public function run($limit = null) {
        $this->log("===== 著者情報同期処理開始 =====");
        $this->log("開始時刻: " . date('Y-m-d H:i:s'));
        $this->log("");
        
        // b_book_list → b_book_repository
        $this->log("b_book_listからb_book_repositoryへの同期");
        $this->syncListToRepository($limit);
        
        // 結果表示
        $this->log("");
        $this->log("===== 処理結果 =====");
        $this->log("処理件数: {$this->processedCount}");
        $this->log("b_book_repository更新: {$this->fixedRepoCount}");
        $this->log("b_book_repository新規作成: {$this->createdRepoCount}");
        $this->log("処理完了: " . date('Y-m-d H:i:s'));
        
        return [
            'processed' => $this->processedCount,
            'fixed_repo' => $this->fixedRepoCount,
            'created_repo' => $this->createdRepoCount
        ];
    }
    
    /**
     * b_book_list → b_book_repository
     */
    private function syncListToRepository($limit = null) {
        // 対象件数を先に取得
        $totalCountSql = "
            SELECT COUNT(DISTINCT bl.amazon_id)
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.author IS NOT NULL 
            AND bl.author != ''
            AND bl.amazon_id IS NOT NULL 
            AND bl.amazon_id != ''
            AND (br.author IS NULL OR br.author = '')
        ";
        $totalCount = $this->db->getOne($totalCountSql);
        $this->log("  対象件数: {$totalCount}件");
        
        $batchSize = 50;
        $offset = 0;
        $totalProcessed = 0;
        
        while (true) {
            // バッチ取得
            $sql = "
                SELECT DISTINCT 
                    bl.amazon_id,
                    bl.author as list_author,
                    bl.name as title
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.author IS NOT NULL 
                AND bl.author != ''
                AND bl.amazon_id IS NOT NULL 
                AND bl.amazon_id != ''
                AND (br.author IS NULL OR br.author = '')
                LIMIT ? OFFSET ?
            ";
            
            $books = $this->db->getAll($sql, [$batchSize, $offset], DB_FETCHMODE_ASSOC);
            
            if (DB::isError($books) || empty($books)) {
                break;
            }
            
            $this->log("  バッチ処理: " . ($offset + 1) . "〜" . ($offset + count($books)) . "件目");
            
            foreach ($books as $book) {
                $this->processedCount++;
                $totalProcessed++;
                
                // repositoryの存在確認
                $check = $this->db->getOne(
                    "SELECT COUNT(*) FROM b_book_repository WHERE asin = ?",
                    [$book['amazon_id']]
                );
                
                if ($check > 0) {
                    // 更新
                    $result = $this->db->query(
                        "UPDATE b_book_repository SET author = ? WHERE asin = ?",
                        [$book['list_author'], $book['amazon_id']]
                    );
                    if (!DB::isError($result)) {
                        $this->fixedRepoCount++;
                        $this->log("    更新: {$book['amazon_id']} - {$book['list_author']}", false);
                    }
                } else {
                    // 新規作成
                    $result = $this->db->query(
                        "INSERT INTO b_book_repository (asin, title, author) VALUES (?, ?, ?)",
                        [$book['amazon_id'], $book['title'], $book['list_author']]
                    );
                    if (!DB::isError($result)) {
                        $this->createdRepoCount++;
                        $this->log("    作成: {$book['amazon_id']} - {$book['list_author']}", false);
                    }
                }
                
                if ($limit && $totalProcessed >= $limit) {
                    return;
                }
            }
            
            $offset += $batchSize;
            
            // メモリ解放
            unset($books);
            
            // 進捗表示
            if ($totalCount > 0) {
                $progress = round(($totalProcessed / min($totalCount, $limit ?: $totalCount)) * 100, 1);
                $this->log("  進捗: {$progress}% ({$totalProcessed}/{$totalCount}件)");
            }
            
            if ($this->processedCount % 100 == 0 && $this->processedCount > 0) {
                $this->log("  {$this->processedCount}件処理完了");
                sleep(1);
            }
        }
        
        $this->log("  処理完了: 処理件数={$totalProcessed}");
    }
    
    /**
     * ログ出力
     */
    private function log($message, $showInConsole = true) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        // ファイルに書き込み
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // コンソール/Web出力
        if ($showInConsole) {
            if (php_sapi_name() === 'cli') {
                echo $message . "\n";
            } else {
                echo nl2br(htmlspecialchars($message)) . "\n";
                flush();
            }
        }
    }
}

// 実行
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && isAdmin())) {
    $processor = new AuthorSyncProcessor();
    
    // 処理件数の制限
    $limit = null;
    if (php_sapi_name() === 'cli' && isset($argv[1])) {
        $limit = intval($argv[1]);
        echo "処理件数制限: {$limit}件\n\n";
    } elseif (isset($_GET['limit'])) {
        $limit = intval($_GET['limit']);
    }
    
    $result = $processor->run($limit);
    
    if (php_sapi_name() !== 'cli') {
        echo "<hr>";
        echo "<h2>処理結果</h2>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
} else {
    // Web画面
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>著者情報同期処理</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="bg-gray-100 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-6">著者情報同期処理</h1>
            
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">処理内容</h2>
                <p class="text-gray-700 mb-4">
                    b_book_listテーブルに著者情報があり、b_book_repositoryテーブルに著者情報がない本を検索し、<br>
                    repositoryテーブルに著者情報を同期します。
                </p>
                <div class="mt-4 p-3 bg-blue-50 border-l-4 border-blue-400">
                    <p class="text-sm text-blue-800">
                        <strong>注意:</strong> repositoryテーブルに著者情報があれば、サイト上では正しく表示されるため、<br>
                        逆方向の同期（repository→list）は行いません。
                    </p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">実行</h2>
                <form method="get" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            処理件数制限（空欄で制限なし）
                        </label>
                        <input type="number" name="limit" class="px-4 py-2 border rounded-lg w-full" 
                               placeholder="例: 100">
                    </div>
                    <button type="submit" name="run" value="1" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            onclick="return confirm('同期処理を実行しますか？')">
                        実行
                    </button>
                </form>
            </div>
            
            <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-yellow-800">
                    <strong>推奨:</strong> 
                    最初は少ない件数（例: 10件）でテストしてから、全体を処理してください。<br>
                    <code class="bg-gray-100 px-2 py-1 rounded">php <?php echo __FILE__; ?> 10</code>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>