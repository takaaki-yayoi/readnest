<?php
/**
 * バッチ処理スクリプト: 著者情報の補完
 * b_book_listとb_book_repositoryの著者情報を相互補完
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

// Google Books API を読み込み（現在は使用しない）
// require_once(dirname(__DIR__) . '/library/google_books_api.php');

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// メモリ制限を緩和（バッチ処理に対応）
ini_set('memory_limit', '128M');
set_time_limit(0);

class AuthorFixBatchProcessor {
    private $db;
    private $logFile;
    private $processedCount = 0;
    private $fixedRepoCount = 0;
    private $createdRepoCount = 0;
    private $googleApiCount = 0;
    private $failedCount = 0;
    
    // バッチサイズ設定
    const BATCH_SIZE = 100;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        // ログファイル設定
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $this->logFile = $logDir . '/author_fix_' . date('Y-m-d_His') . '.log';
    }
    
    /**
     * メイン処理
     */
    public function run($limit = null) {
        $this->log("===== 著者情報同期処理開始 =====");
        
        // b_book_listの著者情報をb_book_repositoryに同期
        $this->fixRepositoryAuthors($limit);
        
        // Google Books APIは現在使用しない
        // 後で必要になったら以下のコメントを外してください：
        // $this->fetchFromGoogleBooks($limit);
        
        $this->log("===== 処理完了 =====");
        $this->log("処理件数: {$this->processedCount}");
        $this->log("b_book_repository修正: {$this->fixedRepoCount}");
        $this->log("b_book_repository新規作成: {$this->createdRepoCount}");
        $this->log("失敗: {$this->failedCount}");
        
        return [
            'processed' => $this->processedCount,
            'fixed_repo' => $this->fixedRepoCount,
            'created_repo' => $this->createdRepoCount,
            'failed' => $this->failedCount
        ];
    }
    
    /**
     * b_book_listの著者情報をb_book_repositoryに同期
     */
    private function fixRepositoryAuthors($limit = null) {
        $this->log("\nb_book_listからb_book_repositoryへの同期開始");
        
        $batchSize = 50; // 一度に処理する件数
        $offset = 0;
        $totalProcessed = 0;
        $maxToProcess = $limit ?: PHP_INT_MAX;
        
        while ($totalProcessed < $maxToProcess) {
            // バッチ単位で取得
            $currentBatchSize = min($batchSize, $maxToProcess - $totalProcessed);
            
            $sql = "
                SELECT DISTINCT 
                    bl.amazon_id,
                    bl.author as list_author,
                    bl.name as title,
                    br.author as repo_author,
                    COUNT(DISTINCT bl.user_id) as reader_count
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.author IS NOT NULL 
                AND bl.author != ''
                AND bl.amazon_id IS NOT NULL 
                AND bl.amazon_id != ''
                AND (br.author IS NULL OR br.author = '')
                GROUP BY bl.amazon_id
                ORDER BY reader_count DESC
                LIMIT ? OFFSET ?
            ";
            
            $books = $this->db->getAll($sql, [$currentBatchSize, $offset], DB_FETCHMODE_ASSOC);
            
            if (DB::isError($books)) {
                $this->log("エラー: " . $books->getMessage());
                return;
            }
            
            // データがなくなったら終了
            if (empty($books)) {
                break;
            }
            
            $this->log("バッチ処理: " . ($offset + 1) . "～" . ($offset + count($books)) . "件目");
            
            foreach ($books as $book) {
                $this->processedCount++;
                $totalProcessed++;
                
                // b_book_repositoryにレコードが存在するか確認
                $check_sql = "SELECT COUNT(*) FROM b_book_repository WHERE asin = ?";
                $exists = $this->db->getOne($check_sql, [$book['amazon_id']]);
                
                if ($exists) {
                    // 更新
                    $update_sql = "UPDATE b_book_repository SET author = ? WHERE asin = ?";
                    $result = $this->db->query($update_sql, [$book['list_author'], $book['amazon_id']]);
                    
                    if (!DB::isError($result)) {
                        $this->fixedRepoCount++;
                        $this->log("更新: ASIN={$book['amazon_id']}, 著者={$book['list_author']}");
                    } else {
                        $this->failedCount++;
                        $this->log("更新失敗: " . $result->getMessage());
                    }
                } else {
                    // 新規作成
                    $insert_sql = "INSERT INTO b_book_repository (asin, title, author) VALUES (?, ?, ?)";
                    $result = $this->db->query($insert_sql, [
                        $book['amazon_id'],
                        $book['title'],
                        $book['list_author']
                    ]);
                    
                    if (!DB::isError($result)) {
                        $this->createdRepoCount++;
                        $this->log("作成: ASIN={$book['amazon_id']}, タイトル={$book['title']}, 著者={$book['list_author']}");
                    } else {
                        $this->failedCount++;
                        $this->log("作成失敗: " . $result->getMessage());
                    }
                }
                
                // レート制限対策
                if ($this->processedCount % 100 == 0) {
                    $this->log("100件処理完了、メモリ使用量: " . round(memory_get_usage() / 1024 / 1024, 2) . "MB");
                    sleep(1);
                }
            }
            
            // 次のバッチへ
            $offset += $batchSize;
            
            // メモリ解放
            unset($books);
            
            // バッチごとに少し待機
            usleep(100000); // 0.1秒
        }
        
        $this->log("処理完了: 処理件数={$this->processedCount}");
    }
    
    /**
     * Google Books APIから著者情報を取得（現在は使用しない）
     * 後で使用する場合はこの関数のコメントを外してください
     */
    /*
    private function fetchFromGoogleBooks($limit = null) {
        $this->log("\n[Step 3] Google Books APIからの取得開始");
        
        $batchSize = 10; // API呼び出しは少なめに
        $offset = 0;
        $totalProcessed = 0;
        $startCount = $this->processedCount;
        
        $remaining = min(
            $limit ?: self::BATCH_SIZE,
            self::GOOGLE_API_DAILY_LIMIT - $this->googleApiCount
        );
        
        if ($remaining <= 0) {
            $this->log("Google API制限に達しているため、スキップします");
            return;
        }
        
        $api = new GoogleBooksAPI();
        $requestCount = 0;
        
        while ($totalProcessed < $remaining) {
            // バッチ単位で取得
            $currentBatchSize = min($batchSize, $remaining - $totalProcessed);
            
            $sql = "
                SELECT 
                    bl.book_id,
                    bl.amazon_id,
                    bl.isbn,
                    bl.name as title,
                    COUNT(DISTINCT bl.user_id) as reader_count
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE (bl.author IS NULL OR bl.author = '')
                AND (br.author IS NULL OR br.author = '' OR br.author IS NULL)
                GROUP BY bl.book_id
                ORDER BY 
                    CASE WHEN bl.isbn IS NOT NULL AND bl.isbn != '' THEN 0 ELSE 1 END,
                    reader_count DESC
                LIMIT ? OFFSET ?
            ";
            
            $books = $this->db->getAll($sql, [$currentBatchSize, $offset], DB_FETCHMODE_ASSOC);
            
            if (DB::isError($books)) {
                $this->log("エラー: " . $books->getMessage());
                return;
            }
            
            // データがなくなったら終了
            if (empty($books)) {
                break;
            }
            
            $this->log("バッチ処理: " . ($offset + 1) . "～" . ($offset + count($books)) . "件目");
            
            foreach ($books as $book) {
                $this->processedCount++;
                $this->googleApiCount++;
                $totalProcessed++;
                $requestCount++;
                
                // レート制限
                if ($requestCount >= self::GOOGLE_API_REQUESTS_PER_MINUTE) {
                    $this->log("レート制限: 60秒待機");
                    sleep(60);
                    $requestCount = 0;
                }
                
                // ISBNまたはタイトルで検索
                $search_term = !empty($book['isbn']) ? $book['isbn'] : $book['title'];
                $this->log("Google API検索: {$search_term}");
                
                $google_info = $api->searchBooks($search_term);
                
                if ($google_info && isset($google_info['items'][0])) {
                    $volume = $google_info['items'][0]['volumeInfo'];
                    $author = isset($volume['authors']) ? implode(', ', $volume['authors']) : '';
                    
                    if (!empty($author)) {
                        // b_book_listを更新
                        $update_sql = "UPDATE b_book_list SET author = ? WHERE book_id = ?";
                        $result = $this->db->query($update_sql, [$author, $book['book_id']]);
                        
                        if (!DB::isError($result)) {
                            $this->fixedListCount++;
                            $this->log("b_book_list更新: book_id={$book['book_id']}, 著者={$author}");
                        }
                        
                        // b_book_repositoryも更新（ASINがある場合）
                        if (!empty($book['amazon_id'])) {
                            $check_sql = "SELECT COUNT(*) FROM b_book_repository WHERE asin = ?";
                            $exists = $this->db->getOne($check_sql, [$book['amazon_id']]);
                            
                            if ($exists) {
                                $update_sql = "UPDATE b_book_repository SET author = ? WHERE asin = ?";
                                $result = $this->db->query($update_sql, [$author, $book['amazon_id']]);
                                
                                if (!DB::isError($result)) {
                                    $this->fixedRepoCount++;
                                    $this->log("b_book_repository更新: ASIN={$book['amazon_id']}, 著者={$author}");
                                }
                            } else {
                                $insert_sql = "INSERT INTO b_book_repository (asin, title, author) VALUES (?, ?, ?)";
                                $result = $this->db->query($insert_sql, [
                                    $book['amazon_id'],
                                    $book['title'],
                                    $author
                                ]);
                                
                                if (!DB::isError($result)) {
                                    $this->createdRepoCount++;
                                    $this->log("b_book_repository作成: ASIN={$book['amazon_id']}, 著者={$author}");
                                }
                            }
                        }
                    } else {
                        $this->log("著者情報が見つかりません: {$search_term}");
                    }
                } else {
                    $this->log("Google APIで見つかりません: {$search_term}");
                }
                
                // API制限に達したら終了
                if ($this->googleApiCount >= self::GOOGLE_API_DAILY_LIMIT) {
                    $this->log("Google API日次制限に達しました");
                    return;
                }
                
                // メモリ使用量チェック
                if ($this->processedCount % 10 == 0) {
                    $this->log("メモリ使用量: " . round(memory_get_usage() / 1024 / 1024, 2) . "MB");
                }
                
                // 少し待機
                usleep(500000); // 0.5秒
            }
            
            // 次のバッチへ
            $offset += $batchSize;
            
            // メモリ解放
            unset($books);
        }
        
        $this->log("Step 3 完了: 処理件数=" . ($this->processedCount - $startCount));
    }
    */
    
    /**
     * ログ出力
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        // ファイルに書き込み
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // CLIまたはWebで出力
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        } else {
            echo nl2br(htmlspecialchars($logMessage));
            flush();
        }
    }
}

// 実行
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && isAdmin())) {
    $processor = new AuthorFixBatchProcessor();
    
    // 処理件数の制限（CLIパラメータまたはGETパラメータ）
    $limit = null;
    if (php_sapi_name() === 'cli' && isset($argv[1])) {
        $limit = intval($argv[1]);
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
        <title>著者情報補完バッチ処理</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="bg-gray-100 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-6">著者情報補完バッチ処理</h1>
            
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">処理内容</h2>
                <p class="text-gray-700 mb-4">
                    b_book_listテーブルの著者情報をb_book_repositoryテーブルに同期します。
                </p>
                <div class="mt-3 p-3 bg-blue-50 border-l-4 border-blue-400">
                    <p class="text-sm text-blue-800">
                        <strong>現在の設定:</strong><br>
                        • Google Books APIは無効化されています<br>
                        • b_book_list→b_book_repositoryの一方向同期のみ実行<br>
                        • repositoryに情報があればサイト上では正しく表示されます
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
                            onclick="return confirm('バッチ処理を実行しますか？')">
                        実行
                    </button>
                </form>
            </div>
            
            <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-yellow-800">
                    <strong>注意:</strong> 
                    大量のデータを処理する場合は、コマンドラインから実行することを推奨します。<br>
                    <code class="bg-gray-100 px-2 py-1 rounded">php <?php echo __FILE__; ?> [limit]</code>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>