<?php
/**
 * レビューembedding生成ライブラリ
 * OpenAI APIを使用してレビューテキストのembeddingを生成
 */

declare(strict_types=1);

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/database.php');

class ReviewEmbeddingGenerator {
    private $db;
    private $apiKey;
    private $model = 'text-embedding-3-small';
    private $dimensions = 768; // 小さめの次元数でコスト削減
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        // OpenAI APIキーを取得
        if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
            throw new Exception('OpenAI API key is not configured');
        }
        $this->apiKey = OPENAI_API_KEY;
    }
    
    /**
     * レビューのembeddingを生成
     */
    public function generateReviewEmbedding(int $bookId, int $userId, string $reviewText): ?array {
        if (empty(trim($reviewText))) {
            return null;
        }
        
        try {
            // テキストを準備（最大8000トークン程度に制限）
            $text = $this->prepareText($reviewText);
            // OpenAI APIを直接呼び出し
            $url = 'https://api.openai.com/v1/embeddings';
            $headers = [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ];
            
            $data = [
                'model' => $this->model,
                'input' => $text,
                'dimensions' => $this->dimensions,
                'encoding_format' => 'float'
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                $this->logError($bookId, $userId, "API Error: HTTP $httpCode");
                return null;
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['data'][0]['embedding'])) {
                return null;
            }
            
            $embedding = $result['data'][0]['embedding'];
            // データベースに保存
            $saveResult = $this->saveEmbedding($bookId, $userId, $reviewText, $embedding);
            return $embedding;
            
        } catch (Exception $e) {
            error_log("Review embedding generation failed: " . $e->getMessage());
            $this->logError($bookId, $userId, $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * レビュー更新時にembeddingを自動生成
     */
    public function updateReviewEmbedding(int $bookId, int $userId): bool {
        // 最新のレビューを取得
        $sql = "SELECT memo FROM b_book_list WHERE book_id = ? AND user_id = ?";
        $review = $this->db->getRow($sql, [$bookId, $userId]);
        
        if (DB::isError($review)) {
            return false;
        }
        
        if (!$review || empty($review['memo'])) {
            return false;
        }
        
        $embedding = $this->generateReviewEmbedding($bookId, $userId, $review['memo']);
        $result = $embedding !== null;
        return $result;
    }
    
    /**
     * バッチ処理で複数レビューのembeddingを生成
     */
    public function generateBatch(int $limit = 100, ?string $batchId = null): array {
        if (!$batchId) {
            $batchId = 'review_' . date('YmdHis') . '_' . uniqid();
        }
        
        // バッチサマリーを作成
        $this->createBatchSummary($batchId);
        
        // embedding未生成のレビューを取得
        $sql = "
            SELECT DISTINCT bl.book_id, bl.user_id, bl.memo as review_text
            FROM b_book_list bl
            LEFT JOIN review_embeddings re ON bl.book_id = re.book_id AND bl.user_id = re.user_id
            WHERE bl.memo IS NOT NULL 
              AND bl.memo != ''
              AND (re.review_embedding IS NULL OR re.updated_at < bl.update_date)
            ORDER BY bl.update_date DESC
            LIMIT ?
        ";
        
        $reviews = $this->db->getAll($sql, [$limit]);
        
        $results = [
            'batch_id' => $batchId,
            'total' => count($reviews),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0
        ];
        
        foreach ($reviews as $review) {
            $bookId = (int)$review['book_id'];
            $userId = (int)$review['user_id'];
            
            $this->logBatchItem($batchId, $bookId, $userId, 'processing');
            
            $startTime = microtime(true);
            
            try {
                if (strlen($review['review_text']) < 10) {
                    // レビューが短すぎる場合はスキップ
                    $this->logBatchItem($batchId, $bookId, $userId, 'skipped');
                    $results['skipped']++;
                    continue;
                }
                
                $embedding = $this->generateReviewEmbedding(
                    $bookId,
                    $userId,
                    $review['review_text']
                );
                
                if ($embedding) {
                    $processingTime = microtime(true) - $startTime;
                    $this->logBatchItem(
                        $batchId, 
                        $bookId, 
                        $userId, 
                        'success',
                        null,
                        $processingTime,
                        count($embedding)
                    );
                    $results['success']++;
                } else {
                    $this->logBatchItem($batchId, $bookId, $userId, 'failed');
                    $results['failed']++;
                }
                
                // レート制限対策
                usleep(100000); // 0.1秒待機
                
            } catch (Exception $e) {
                $this->logBatchItem(
                    $batchId,
                    $bookId,
                    $userId,
                    'failed',
                    $e->getMessage()
                );
                $results['failed']++;
            }
        }
        
        // バッチサマリーを更新
        $this->updateBatchSummary($batchId, $results);
        
        return $results;
    }
    
    /**
     * テキストの前処理
     */
    private function prepareText(string $text): string {
        // HTMLタグを除去
        $text = strip_tags($text);
        
        // 改行を正規化
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // 連続する空白を単一の空白に
        $text = preg_replace('/\s+/', ' ', $text);
        
        // トリミング
        $text = trim($text);
        
        // 最大長を制限（約4000文字）
        if (mb_strlen($text) > 4000) {
            $text = mb_substr($text, 0, 4000) . '...';
        }
        
        return $text;
    }
    
    /**
     * embeddingをデータベースに保存
     */
    private function saveEmbedding(int $bookId, int $userId, string $reviewText, array $embedding): bool {
        $embeddingJson = json_encode($embedding);
        
        $sql = "
            INSERT INTO review_embeddings (
                book_id, user_id, review_text, review_embedding, 
                embedding_model, embedding_generated_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                review_text = VALUES(review_text),
                review_embedding = VALUES(review_embedding),
                embedding_model = VALUES(embedding_model),
                embedding_generated_at = NOW(),
                updated_at = NOW()
        ";
        
        $result = $this->db->query($sql, [
            $bookId,
            $userId,
            $reviewText,
            $embeddingJson,
            $this->model
        ]);
        
        if (DB::isError($result)) {
            error_log("Database error in saveEmbedding: " . $result->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * エラーログを記録
     */
    private function logError(int $bookId, int $userId, string $errorMessage): void {
        $sql = "
            INSERT INTO review_embeddings (book_id, user_id, last_error_message, process_attempts)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                last_error_message = VALUES(last_error_message),
                process_attempts = process_attempts + 1,
                updated_at = NOW()
        ";
        
        $this->db->query($sql, [$bookId, $userId, $errorMessage]);
    }
    
    /**
     * バッチサマリーを作成
     */
    private function createBatchSummary(string $batchId): void {
        $sql = "
            INSERT INTO review_embedding_batch_summary (batch_id, status)
            VALUES (?, 'running')
        ";
        $this->db->query($sql, [$batchId]);
    }
    
    /**
     * バッチサマリーを更新
     */
    private function updateBatchSummary(string $batchId, array $results): void {
        $sql = "
            UPDATE review_embedding_batch_summary
            SET total_reviews = ?,
                processed_reviews = ?,
                successful_reviews = ?,
                failed_reviews = ?,
                skipped_reviews = ?,
                end_time = NOW(),
                status = 'completed'
            WHERE batch_id = ?
        ";
        
        $this->db->query($sql, [
            $results['total'],
            $results['success'] + $results['failed'] + $results['skipped'],
            $results['success'],
            $results['failed'],
            $results['skipped'],
            $batchId
        ]);
    }
    
    /**
     * バッチ処理の個別アイテムをログ
     */
    private function logBatchItem(
        string $batchId,
        int $bookId,
        int $userId,
        string $status,
        ?string $errorMessage = null,
        ?float $processingTime = null,
        ?int $dimensions = null
    ): void {
        $sql = "
            INSERT INTO review_embedding_batch_log (
                batch_id, book_id, user_id, status, error_message,
                processing_time_seconds, embedding_dimensions
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                error_message = VALUES(error_message),
                processing_time_seconds = VALUES(processing_time_seconds),
                embedding_dimensions = VALUES(embedding_dimensions),
                updated_at = NOW()
        ";
        
        $this->db->query($sql, [
            $batchId,
            $bookId,
            $userId,
            $status,
            $errorMessage,
            $processingTime ? (int)$processingTime : null,
            $dimensions
        ]);
    }
    
    /**
     * 類似レビューを検索
     */
    public function findSimilarReviews(array $embedding, int $limit = 10): array {
        // コサイン類似度を計算するSQL
        $embeddingJson = json_encode($embedding);
        
        $sql = "
            SELECT 
                re.book_id,
                re.user_id,
                re.review_text,
                br.title,
                u.nickname,
                JSON_LENGTH(re.review_embedding) as dimensions
            FROM review_embeddings re
            JOIN b_book_repository br ON re.book_id = br.book_id
            JOIN u_users u ON re.user_id = u.user_id
            WHERE re.review_embedding IS NOT NULL
            ORDER BY RAND()
            LIMIT ?
        ";
        
        // 簡易版: ランダムに取得（本来はベクトル類似度計算が必要）
        return $this->db->getAll($sql, [$limit]);
    }
}

/**
 * レビュー更新時のフック関数
 */
function onReviewUpdate(int $bookId, int $userId): void {
    try {
        $generator = new ReviewEmbeddingGenerator();
        $generator->updateReviewEmbedding($bookId, $userId);
    } catch (Exception $e) {
        error_log("Failed to update review embedding: " . $e->getMessage());
    }
}
?>