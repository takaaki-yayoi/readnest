<?php
/**
 * 説明文専用embedding生成スクリプト
 * Google Books APIで説明文を取得し、OpenAI APIでembeddingを生成
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

// 必要なライブラリを読み込み
require_once(dirname(__DIR__) . '/library/google_books_api.php');
require_once(dirname(__DIR__) . '/library/openai_client.php');

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// メモリ制限を緩和
ini_set('memory_limit', '512M');
set_time_limit(0);

class DescriptionEmbeddingGenerator {
    private $db;
    private $googleBooks;
    private $openaiClient;
    private $logFile;
    private $processedCount = 0;
    private $successCount = 0;
    private $failedCount = 0;
    private $googleApiCount = 0;
    private $openaiApiCount = 0;
    
    // レート制限設定
    const GOOGLE_API_DAILY_LIMIT = 1000;
    const OPENAI_API_REQUESTS_PER_MINUTE = 60;
    const BATCH_SIZE = 50;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        // APIクライアントを初期化
        $this->googleBooks = new GoogleBooksAPI();
        $this->openaiClient = getOpenAIClient();
        
        if (!$this->openaiClient) {
            throw new Exception("OpenAI APIクライアントの初期化に失敗しました");
        }
        
        // ログディレクトリの確認
        $logDir = dirname(__FILE__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // ログファイルの設定
        $this->logFile = $logDir . '/description_embedding_' . date('Y-m-d_His') . '.log';
        
        $this->log("=== 説明文embedding生成開始 ===");
    }
    
    /**
     * メイン処理
     * @param int $limit 処理数上限（0 = 無制限）
     * @param bool $forceRegenerate 既存のembeddingを再生成するか
     */
    public function run($limit = 100, $forceRegenerate = false) {
        try {
            // Step 1: 説明文がない本に説明文を取得
            $this->fetchMissingDescriptions($limit);
            
            // Step 2: 説明文があるがembeddingがない本にembeddingを生成
            $this->generateMissingEmbeddings($limit, $forceRegenerate);
            
            $this->log("=== 処理完了 ===");
            $this->log("処理数: {$this->processedCount}");
            $this->log("成功: {$this->successCount}");
            $this->log("失敗: {$this->failedCount}");
            $this->log("Google API使用: {$this->googleApiCount}回");
            $this->log("OpenAI API使用: {$this->openaiApiCount}回");
            
            return [
                'success' => true,
                'processed' => $this->processedCount,
                'successful' => $this->successCount,
                'failed' => $this->failedCount
            ];
            
        } catch (Exception $e) {
            $this->log("エラー: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 説明文がない本に説明文を取得
     */
    private function fetchMissingDescriptions($limit) {
        $this->log("\n--- Step 1: 説明文の取得 ---");
        
        // 説明文がない本を取得（高評価順）
        $sql = "
            SELECT DISTINCT 
                br.asin,
                br.title,
                br.author,
                br.isbn,
                AVG(bl.rating) as avg_rating
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON br.asin = bl.amazon_id
            WHERE (br.description IS NULL OR br.description = '' OR br.description = 'NULL')
            AND br.title IS NOT NULL
            GROUP BY br.asin
            ORDER BY avg_rating DESC, RAND()
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            throw new Exception("データベースエラー: " . $books->getMessage());
        }
        
        $this->log("説明文取得対象: " . count($books) . "冊");
        
        foreach ($books as $index => $book) {
            $this->processedCount++;
            $this->log("\n[{$index}/{$this->processedCount}] {$book['title']} (ASIN: {$book['asin']})");
            
            try {
                // Google Books APIで説明文を取得
                $bookInfo = $this->googleBooks->getBookInfo(
                    $book['isbn'] ?? $book['asin'],
                    $book['title'],
                    $book['author']
                );
                
                $this->googleApiCount++;
                
                if ($bookInfo && !empty($bookInfo['description'])) {
                    // 説明文を保存
                    $sql = "UPDATE b_book_repository SET description = ? WHERE asin = ?";
                    $result = $this->db->query($sql, [$bookInfo['description'], $book['asin']]);
                    
                    if (!DB::isError($result)) {
                        $this->successCount++;
                        $this->log("  ✓ 説明文取得成功: " . mb_substr($bookInfo['description'], 0, 50) . "...");
                    } else {
                        $this->failedCount++;
                        $this->log("  ✗ データベース更新失敗");
                    }
                } else {
                    $this->log("  - 説明文が見つかりませんでした");
                }
                
                // レート制限対策
                if ($this->googleApiCount % 10 == 0) {
                    $this->log("  ... 10件処理完了、5秒待機...");
                    sleep(5);
                }
                
            } catch (Exception $e) {
                $this->failedCount++;
                $this->log("  ✗ エラー: " . $e->getMessage());
            }
        }
    }
    
    /**
     * 説明文embeddingを生成
     */
    private function generateMissingEmbeddings($limit, $forceRegenerate = false) {
        $this->log("\n--- Step 2: 説明文embeddingの生成 ---");
        
        // embeddingがない本を取得
        $whereClause = $forceRegenerate 
            ? "br.description IS NOT NULL AND br.description != '' AND br.description != 'NULL'"
            : "br.description IS NOT NULL AND br.description != '' AND br.description != 'NULL' 
               AND (br.description_embedding IS NULL OR br.has_description_embedding = 0)";
        
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.description,
                AVG(bl.rating) as avg_rating
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON br.asin = bl.amazon_id
            WHERE $whereClause
            GROUP BY br.asin
            ORDER BY avg_rating DESC, RAND()
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            throw new Exception("データベースエラー: " . $books->getMessage());
        }
        
        $this->log("embedding生成対象: " . count($books) . "冊");
        
        foreach ($books as $index => $book) {
            $this->log("\n[{$index}] {$book['title']}");
            
            try {
                // 説明文からembeddingを生成
                $embedding = $this->generateDescriptionEmbedding($book['description']);
                
                if ($embedding) {
                    // embeddingを保存
                    $sql = "
                        UPDATE b_book_repository 
                        SET 
                            description_embedding = ?,
                            description_embedding_generated_at = NOW(),
                            has_description_embedding = 1
                        WHERE asin = ?
                    ";
                    
                    $result = $this->db->query($sql, [json_encode($embedding), $book['asin']]);
                    
                    if (!DB::isError($result)) {
                        $this->successCount++;
                        $this->log("  ✓ embedding生成成功");
                    } else {
                        $this->failedCount++;
                        $this->log("  ✗ データベース更新失敗: " . $result->getMessage());
                    }
                } else {
                    $this->failedCount++;
                    $this->log("  ✗ embedding生成失敗");
                }
                
                // レート制限対策
                usleep(1000000); // 1秒待機
                
            } catch (Exception $e) {
                $this->failedCount++;
                $this->log("  ✗ エラー: " . $e->getMessage());
            }
        }
    }
    
    /**
     * 説明文からembeddingを生成
     */
    private function generateDescriptionEmbedding($description) {
        if (empty($description)) {
            return null;
        }
        
        try {
            // 説明文を整形（最大8000文字）
            $text = mb_substr($description, 0, 8000);
            
            // OpenAI APIでembeddingを生成
            $response = $this->openaiClient->createEmbedding(
                $text,
                'text-embedding-3-small'
            );
            
            $this->openaiApiCount++;
            
            if (isset($response['data'][0]['embedding'])) {
                return $response['data'][0]['embedding'];
            }
            
        } catch (Exception $e) {
            $this->log("  embedding生成エラー: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * ログ出力
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        // ファイルに出力
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // CLIの場合は標準出力にも
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
}

// 実行
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    try {
        $generator = new DescriptionEmbeddingGenerator();
        
        // CLIパラメータまたはGETパラメータから設定を取得
        $limit = isset($argv[1]) ? intval($argv[1]) : (isset($_GET['limit']) ? intval($_GET['limit']) : 100);
        $forceRegenerate = isset($argv[2]) ? ($argv[2] === 'force') : isset($_GET['force']);
        
        $result = $generator->run($limit, $forceRegenerate);
        
        if (!php_sapi_name() === 'cli') {
            // Web実行の場合はJSON出力
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        
    } catch (Exception $e) {
        if (php_sapi_name() === 'cli') {
            echo "エラー: " . $e->getMessage() . "\n";
            exit(1);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
} else {
    // Web UIを表示
    $d_site_title = '説明文Embedding生成 - 管理画面';
    $d_content = '
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <h1 class="text-2xl font-bold mb-6">説明文Embedding生成</h1>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">処理内容</h2>
            <ol class="list-decimal list-inside space-y-2 text-gray-700">
                <li>Google Books APIから説明文を取得</li>
                <li>説明文からOpenAI APIでembeddingを生成</li>
                <li>データベースのdescription_embeddingフィールドに保存</li>
            </ol>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="get" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        処理件数上限
                    </label>
                    <input type="number" name="limit" value="100" min="1" max="1000"
                           class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="force" value="1" class="mr-2">
                        <span class="text-sm text-gray-700">既存のembeddingを再生成する</span>
                    </label>
                </div>
                
                <div>
                    <button type="submit" name="run" value="1"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-play mr-2"></i>処理を開始
                    </button>
                </div>
            </form>
        </div>
    </div>
    ';
    
    include(dirname(__DIR__) . '/template/modern/t_base.php');
}
?>