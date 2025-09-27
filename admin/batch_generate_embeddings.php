<?php
/**
 * バッチ処理スクリプト: 説明文取得とembedding生成
 * 高評価の本（rating >= 4）を優先的に処理
 * cron実行対応版（API制限まで自動処理）
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
}

// 動的embedding生成ライブラリを読み込み
require_once(dirname(__DIR__) . '/library/dynamic_embedding_generator.php');

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// メモリ制限を緩和
ini_set('memory_limit', '512M');
set_time_limit(0);

class EmbeddingBatchProcessor {
    private $db;
    private $logFile;
    private $batchId;
    private $processedCount = 0;
    private $successCount = 0;
    private $failedCount = 0;
    private $skippedCount = 0;
    private $googleApiCount = 0;
    private $openaiApiCount = 0;
    
    // レート制限設定
    const GOOGLE_API_DAILY_LIMIT = 1000;
    const GOOGLE_API_REQUESTS_PER_MINUTE = 10;
    const OPENAI_API_REQUESTS_PER_MINUTE = 60;
    const BATCH_SIZE = 10;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        // バッチIDを生成
        $this->batchId = 'batch_' . date('YmdHis') . '_' . uniqid();
        
        // ログディレクトリの確認
        $logDir = dirname(__FILE__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // ログファイルの設定
        $this->logFile = $logDir . '/embedding_batch_' . date('Y-m-d') . '.log';
    }
    
    /**
     * メインの処理実行
     * @param int|null $limit 処理数上限（nullの場合はAPI制限まで処理）
     * @return array 処理結果の詳細
     */
    public function run($limit = null) {
        $this->log("=== バッチ処理開始 ===");
        $this->log("Batch ID: " . $this->batchId);
        
        $result = [
            'success' => true,
            'processed' => 0,
            'google_api_used' => 0,
            'openai_api_used' => 0,
            'stopped_reason' => null
        ];
        
        try {
            // 今日のGoogle API使用状況を確認
            $googleUsage = $this->getApiUsageToday('google_books');
            if ($googleUsage >= self::GOOGLE_API_DAILY_LIMIT) {
                $this->log("警告: Google Books APIの日次制限に到達しています");
                $this->updateBatchSummary('stopped', 'Google API日次制限到達');
                $result['stopped_reason'] = 'Google API日次制限到達';
                $result['success'] = false;
                return $result;
            }
            
            $remainingGoogleQuota = self::GOOGLE_API_DAILY_LIMIT - $googleUsage;
            $this->log("Google API残りクォータ: " . $remainingGoogleQuota);
            
            // limitが指定されていない場合は、残りクォータまで処理
            if ($limit === null || $limit === 'max') {
                // 安全マージンを設けて95%まで使用
                $limit = floor($remainingGoogleQuota * 0.95);
                $this->log("処理数上限: API制限まで（最大" . $limit . "件）");
            } else {
                $limit = min(intval($limit), $remainingGoogleQuota);
                $this->log("処理数上限: " . $limit);
            }
            
            if ($limit <= 0) {
                $this->log("処理可能な件数がありません");
                $result['stopped_reason'] = 'API制限により処理不可';
                return $result;
            }
            
            // バッチサマリーを記録
            $this->createBatchSummary($limit);
            
            // 処理対象の本を取得
            $books = $this->getTargetBooks($limit);
            
            if (empty($books)) {
                $this->log("処理対象の本がありません");
                $this->updateBatchSummary('completed');
                $result['stopped_reason'] = '処理対象なし';
                return $result;
            }
            
            $this->log("処理対象: " . count($books) . "冊");
            
            // 各本を処理
            foreach ($books as $index => $book) {
                // API制限の再確認（処理中に制限に達する可能性があるため）
                $currentGoogleUsage = $this->getApiUsageToday('google_books');
                if ($currentGoogleUsage >= self::GOOGLE_API_DAILY_LIMIT * 0.95) {
                    $this->log("Google API日次制限（95%）に到達したため処理を中断");
                    $result['stopped_reason'] = 'Google API日次制限到達';
                    break;
                }
                
                $this->processBook($book);
                
                // レート制限対策（10件ごとに長めの待機）
                if ($this->processedCount > 0 && $this->processedCount % 10 == 0) {
                    $this->log("10冊処理完了、60秒待機（レート制限対策）...");
                    sleep(60);
                } elseif ($this->processedCount % 5 == 0) {
                    $this->log("5冊処理完了、10秒待機...");
                    sleep(10);
                }
            }
            
            $this->updateBatchSummary('completed');
            
            // === 動的生成されたembeddingの再生成処理 ===
            $this->log("=== 動的生成embedding再生成処理 ===");
            $this->processEmbeddingsWithoutDescription();
            
            $this->log("=== バッチ処理完了 ===");
            $this->log("処理数: " . $this->processedCount . "冊");
            $this->log("Google API使用: " . $this->googleApiCount . "回");
            $this->log("OpenAI API使用: " . $this->openaiApiCount . "回");
            
            $result['processed'] = $this->processedCount;
            $result['google_api_used'] = $this->googleApiCount;
            $result['openai_api_used'] = $this->openaiApiCount;
            
        } catch (Exception $e) {
            $this->log("エラー発生: " . $e->getMessage());
            $this->updateBatchSummary('failed', $e->getMessage());
            $result['success'] = false;
            $result['stopped_reason'] = 'エラー: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * 高評価の本を取得
     */
    private function getTargetBooks($limit) {
        $sql = "
            SELECT DISTINCT
                br.asin,
                br.title,
                br.author,
                br.description,
                br.combined_embedding,
                AVG(bl.rating) as avg_rating,
                COUNT(DISTINCT bl.user_id) as user_count,
                br.process_attempts,
                br.google_books_checked
            FROM b_book_repository br
            INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
            WHERE bl.rating >= 4
            AND (br.description IS NULL OR br.combined_embedding IS NULL)
            AND (br.process_attempts IS NULL OR br.process_attempts < 3)
            AND (br.google_books_checked IS NULL OR br.google_books_checked = 0)
            GROUP BY br.asin
            ORDER BY AVG(bl.rating) DESC, COUNT(DISTINCT bl.user_id) DESC
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            throw new Exception("データベースエラー: " . $books->getMessage());
        }
        
        return $books;
    }
    
    /**
     * 個別の本を処理
     */
    private function processBook($book) {
        $startTime = time();
        $this->log("処理開始: " . $book['title'] . " (ASIN: " . $book['asin'] . ")");
        
        try {
            // 処理ログを記録
            $this->createProcessLog($book['asin']);
            
            $needsDescription = empty($book['description']);
            $needsEmbedding = empty($book['combined_embedding']);
            
            // 説明文の取得（必要な場合）
            if ($needsDescription) {
                $description = $this->fetchDescription($book);
                if ($description) {
                    $this->updateBookDescription($book['asin'], $description);
                    $book['description'] = $description;
                }
            }
            
            // Embeddingの生成（説明文がある場合、またはタイトル・著者情報のみで生成）
            if ($needsEmbedding) {
                if (!empty($book['description'])) {
                    // 説明文ありでembedding生成
                    $embedding = $this->generateEmbedding($book);
                    if ($embedding) {
                        $this->updateBookEmbedding($book['asin'], $embedding);
                    }
                } else {
                    // 説明文なしでもタイトルと著者でembedding生成
                    $this->log("  説明文なし。タイトルと著者情報のみでEmbedding生成");
                    $embedding = $this->generateEmbedding($book);
                    if ($embedding) {
                        $this->updateBookEmbeddingWithoutDescription($book['asin'], $embedding);
                    }
                }
            }
            
            $processingTime = time() - $startTime;
            $this->updateProcessLog($book['asin'], 'success', $processingTime);
            $this->processedCount++;
            $this->successCount++;
            
            $this->log("処理完了: " . $book['title'] . " (" . $processingTime . "秒)");
            
        } catch (Exception $e) {
            $processingTime = time() - $startTime;
            $this->updateProcessLog($book['asin'], 'failed', $processingTime, $e->getMessage());
            $this->log("エラー: " . $book['title'] . " - " . $e->getMessage());
            $this->processedCount++;
            $this->failedCount++;
            
            // 処理試行回数を増やす
            $this->incrementProcessAttempts($book['asin'], $e->getMessage());
        }
    }
    
    /**
     * Google Books APIから説明文を取得
     */
    private function fetchDescription($book) {
        // APIキーの確認
        if (!defined('GOOGLE_BOOKS_API_KEY')) {
            throw new Exception("Google Books APIキーが設定されていません");
        }
        
        $this->log("  Google Books APIから説明文を取得中...");
        
        // レート制限チェック
        $this->checkGoogleRateLimit();
        
        // ISBNまたはタイトルで検索
        $query = !empty($book['asin']) ? 'isbn:' . $book['asin'] : 'intitle:' . urlencode($book['title']);
        $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query . "&key=" . GOOGLE_BOOKS_API_KEY;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // API使用をカウント
        $this->incrementApiUsage('google_books');
        $this->googleApiCount++;
        
        if ($httpCode !== 200) {
            throw new Exception("Google Books API エラー: HTTP " . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['items'][0]['volumeInfo']['description'])) {
            return $data['items'][0]['volumeInfo']['description'];
        }
        
        // 説明文が見つからなかった場合、フラグを立てる
        $this->markGoogleBooksChecked($book['asin']);
        
        return null;
    }
    
    /**
     * OpenAI APIでEmbeddingを生成
     */
    private function generateEmbedding($book) {
        // APIキーの確認
        if (!defined('OPENAI_API_KEY')) {
            throw new Exception("OpenAI APIキーが設定されていません");
        }
        
        $this->log("  OpenAI APIでEmbeddingを生成中...");
        
        // レート制限チェック
        $this->checkOpenAIRateLimit();
        
        // Embeddingテキストの準備
        $text = $book['title'];
        if (!empty($book['author'])) {
            $text .= ' by ' . $book['author'];
        }
        if (!empty($book['description'])) {
            $text .= '. ' . $book['description'];
        }
        
        // テキストを適切な長さに制限（8191トークン制限対策）
        $text = mb_substr($text, 0, 8000);
        
        $url = 'https://api.openai.com/v1/embeddings';
        $headers = [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'Content-Type: application/json'
        ];
        
        $data = [
            'model' => 'text-embedding-3-small',
            'input' => $text
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            // エラー時もAPI使用をカウント
            $this->incrementApiUsage('openai');
            $this->openaiApiCount++;
            throw new Exception("OpenAI API エラー: HTTP " . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['data'][0]['embedding'])) {
            // トークン使用量を記録（OpenAI APIのレスポンスから取得）
            $tokenCount = isset($result['usage']['total_tokens']) ? $result['usage']['total_tokens'] : mb_strlen($text);
            $this->incrementApiUsage('openai', $tokenCount);
            $this->openaiApiCount++;
            
            return json_encode($result['data'][0]['embedding']);
        }
        
        return null;
    }
    
    /**
     * 本の説明文を更新
     */
    private function updateBookDescription($asin, $description) {
        $sql = "UPDATE b_book_repository SET description = ? WHERE asin = ?";
        $result = $this->db->query($sql, [$description, $asin]);
        
        if (DB::isError($result)) {
            throw new Exception("説明文の更新に失敗: " . $result->getMessage());
        }
    }
    
    /**
     * 本のEmbeddingを更新
     */
    private function updateBookEmbedding($asin, $embedding) {
        $sql = "
            UPDATE b_book_repository 
            SET combined_embedding = ?,
                embedding_generated_at = NOW(),
                embedding_model = 'text-embedding-3-small'
            WHERE asin = ?
        ";
        
        $result = $this->db->query($sql, [$embedding, $asin]);
        
        if (DB::isError($result)) {
            throw new Exception("Embeddingの更新に失敗: " . $result->getMessage());
        }
    }
    
    /**
     * 処理試行回数を増やす
     */
    private function incrementProcessAttempts($asin, $errorMessage) {
        $sql = "
            UPDATE b_book_repository 
            SET process_attempts = process_attempts + 1,
                last_error_message = ?
            WHERE asin = ?
        ";
        
        $this->db->query($sql, [$errorMessage, $asin]);
    }
    
    /**
     * Google Booksで確認済みのフラグを立てる
     */
    private function markGoogleBooksChecked($asin) {
        $sql = "
            UPDATE b_book_repository 
            SET google_books_checked = 1,
                google_books_checked_at = NOW()
            WHERE asin = ?
        ";
        
        $result = $this->db->query($sql, [$asin]);
        
        if (!DB::isError($result)) {
            $this->log("  Google Booksで説明文が見つからないことを記録");
        }
    }
    
    /**
     * 説明文なしでEmbeddingを更新
     */
    private function updateBookEmbeddingWithoutDescription($asin, $embedding) {
        $sql = "
            UPDATE b_book_repository 
            SET combined_embedding = ?,
                embedding_generated_at = NOW(),
                embedding_model = 'text-embedding-3-small',
                embedding_type = 'title_author_only',
                embedding_has_description = 0
            WHERE asin = ?
        ";
        
        $result = $this->db->query($sql, [$embedding, $asin]);
        
        if (DB::isError($result)) {
            throw new Exception("Embeddingの更新に失敗: " . $result->getMessage());
        }
        
        $this->log("  タイトルと著者情報のみでEmbedding生成完了");
    }
    
    /**
     * Google APIのレート制限チェック
     */
    private function checkGoogleRateLimit() {
        static $lastRequestTime = 0;
        static $requestsInMinute = 0;
        static $minuteStart = 0;
        
        $now = time();
        
        // 分単位のレート制限
        if ($now - $minuteStart >= 60) {
            $requestsInMinute = 0;
            $minuteStart = $now;
        }
        
        if ($requestsInMinute >= self::GOOGLE_API_REQUESTS_PER_MINUTE) {
            $waitTime = 60 - ($now - $minuteStart);
            $this->log("  Google APIレート制限対策: " . $waitTime . "秒待機");
            sleep($waitTime);
            $requestsInMinute = 0;
            $minuteStart = time();
        }
        
        $requestsInMinute++;
        $lastRequestTime = $now;
    }
    
    /**
     * OpenAI APIのレート制限チェック
     */
    private function checkOpenAIRateLimit() {
        static $lastRequestTime = 0;
        static $requestsInMinute = 0;
        static $minuteStart = 0;
        
        $now = time();
        
        // 分単位のレート制限
        if ($now - $minuteStart >= 60) {
            $requestsInMinute = 0;
            $minuteStart = $now;
        }
        
        if ($requestsInMinute >= self::OPENAI_API_REQUESTS_PER_MINUTE) {
            $waitTime = 60 - ($now - $minuteStart);
            $this->log("  OpenAI APIレート制限対策: " . $waitTime . "秒待機");
            sleep($waitTime);
            $requestsInMinute = 0;
            $minuteStart = time();
        }
        
        $requestsInMinute++;
        $lastRequestTime = $now;
    }
    
    /**
     * API使用状況を取得
     */
    private function getApiUsageToday($provider) {
        $sql = "
            SELECT request_count 
            FROM api_usage_tracking 
            WHERE api_provider = ? AND usage_date = CURDATE()
        ";
        
        $count = $this->db->getOne($sql, [$provider]);
        
        if (DB::isError($count)) {
            return 0;
        }
        
        return intval($count);
    }
    
    /**
     * API使用回数を増やす
     */
    private function incrementApiUsage($provider, $tokenCount = 0) {
        // コスト計算（OpenAI text-embedding-3-small: $0.02 per 1M tokens）
        $costEstimate = 0;
        if ($provider === 'openai' && $tokenCount > 0) {
            $costEstimate = ($tokenCount / 1000000) * 0.02;
        }
        
        $sql = "
            INSERT INTO api_usage_tracking 
            (api_provider, usage_date, request_count, token_count, cost_estimate, last_request_time)
            VALUES (?, CURDATE(), 1, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                request_count = request_count + 1,
                token_count = token_count + VALUES(token_count),
                cost_estimate = cost_estimate + VALUES(cost_estimate),
                last_request_time = NOW()
        ";
        
        $this->db->query($sql, [$provider, $tokenCount, $costEstimate]);
    }
    
    /**
     * バッチサマリーを作成
     */
    private function createBatchSummary($totalBooks) {
        $sql = "
            INSERT INTO embedding_batch_summary 
            (batch_id, total_books, status)
            VALUES (?, ?, 'running')
        ";
        
        $this->db->query($sql, [$this->batchId, $totalBooks]);
    }
    
    /**
     * バッチサマリーを更新
     */
    private function updateBatchSummary($status, $error = null) {
        // 総処理時間を計算
        $totalProcessingTime = time() - strtotime($this->batchId);
        
        $sql = "
            UPDATE embedding_batch_summary 
            SET 
                end_time = NOW(),
                processed_books = ?,
                successful_books = ?,
                failed_books = ?,
                skipped_books = ?,
                google_api_requests = ?,
                openai_api_requests = ?,
                total_processing_time_seconds = ?,
                status = ?,
                error_summary = ?
            WHERE batch_id = ?
        ";
        
        $this->db->query($sql, [
            $this->processedCount,
            $this->successCount,
            $this->failedCount,
            $this->skippedCount,
            $this->googleApiCount,
            $this->openaiApiCount,
            $totalProcessingTime,
            $status,
            $error,
            $this->batchId
        ]);
    }
    
    /**
     * 処理ログを作成
     */
    private function createProcessLog($asin) {
        $sql = "
            INSERT INTO embedding_batch_log 
            (batch_id, asin, status, process_type, created_at)
            VALUES (?, ?, 'processing', 'both', NOW())
        ";
        
        $this->db->query($sql, [$this->batchId, $asin]);
    }
    
    /**
     * 処理ログを更新
     */
    private function updateProcessLog($asin, $status, $processingTime, $error = null) {
        // API使用状況をJSON形式で記録
        $apiRequests = json_encode([
            'google_books' => $this->googleApiCount,
            'openai' => $this->openaiApiCount
        ]);
        
        // embeddingの次元数（text-embedding-3-smallは1536次元）
        $embeddingDimensions = ($status === 'success') ? 1536 : null;
        
        $sql = "
            UPDATE embedding_batch_log 
            SET 
                status = ?,
                processing_time_seconds = ?,
                error_message = ?,
                api_requests = ?,
                embedding_dimensions = ?,
                updated_at = NOW()
            WHERE batch_id = ? AND asin = ?
        ";
        
        $this->db->query($sql, [$status, $processingTime, $error, $apiRequests, $embeddingDimensions, $this->batchId, $asin]);
    }
    
    /**
     * ログ出力
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        // ファイルに出力
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // CLIの場合はコンソールにも出力
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
    
    /**
     * 説明文なしで生成されたembeddingを再生成
     * 説明文が後から取得された本のembeddingを更新
     */
    private function processEmbeddingsWithoutDescription() {
        try {
            // 更新が必要な本を取得（embedding_has_description = 0 かつ description が存在）
            $sql = "
                SELECT asin, title, author, description
                FROM b_book_repository
                WHERE embedding_has_description = 0
                AND embedding_type = 'dynamic_no_desc'
                AND description IS NOT NULL
                AND description != ''
                LIMIT 5
            ";
            
            $books = $this->db->getAll($sql, [], DB_FETCHMODE_ASSOC);
            
            if (DB::isError($books) || empty($books)) {
                $this->log("説明文なしembeddingの更新対象: 0件");
                return;
            }
            
            $this->log("説明文なしembeddingの更新対象: " . count($books) . "件");
            
            $updateCount = 0;
            foreach ($books as $book) {
                $this->log("  再生成: " . $book['title'] . " (ASIN: " . $book['asin'] . ")");
                
                // 再生成処理
                if (regenerateEmbeddingWithDescription($book['asin'])) {
                    $updateCount++;
                    $this->openaiApiCount++;
                    $this->log("    ✓ 説明文込みで再生成成功");
                } else {
                    $this->log("    ✗ 再生成失敗");
                }
                
                // API rate limitを考慮
                usleep(500000); // 500ms待機
            }
            
            $this->log("説明文込みembedding再生成完了: " . $updateCount . "/" . count($books) . "件");
            
            // 残りの更新が必要な本の数を確認
            $remaining_sql = "
                SELECT COUNT(*) as count
                FROM b_book_repository
                WHERE embedding_has_description = 0
                AND embedding_type = 'dynamic_no_desc'
                AND description IS NOT NULL
                AND description != ''
            ";
            
            $remaining = $this->db->getOne($remaining_sql);
            if (!DB::isError($remaining) && $remaining > 0) {
                $this->log("残り更新対象: " . $remaining . "件");
            }
            
        } catch (Exception $e) {
            $this->log("動的embedding再生成エラー: " . $e->getMessage());
        }
    }
}

// 実行
if (php_sapi_name() === 'cli') {
    // CLI実行
    $limit = isset($argv[1]) ? $argv[1] : 'max';
    if ($limit === 'max') {
        $limit = null;  // nullの場合はAPI制限まで処理
    } else {
        $limit = intval($limit);
    }
    
    $processor = new EmbeddingBatchProcessor();
    $result = $processor->run($limit);
    
    // 結果をJSON形式で出力（cron実行時のログ用）
    echo "\n=== 処理結果 ===\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    exit($result['success'] ? 0 : 1);
    
} elseif (isset($_GET['run'])) {
    // Web実行（管理画面から）
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $processor = new EmbeddingBatchProcessor();
    $result = $processor->run($limit);
    
    header('Content-Type: application/json');
    echo json_encode($result);
}
?>