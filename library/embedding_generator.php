<?php
/**
 * OpenAI text-embedding-3-smallモデルを使用したエンベディング生成
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');

class EmbeddingGenerator {
    private $db;
    private $apiKey;
    private $model = 'text-embedding-3-small';
    private $dimensions = 1536; // text-embedding-3-smallのデフォルト次元数
    
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
     * テキストのエンベディングを生成
     */
    public function generateEmbedding(string $text): ?array {
        if (empty($text)) {
            return null;
        }
        
        // テキストの前処理（最大8191トークンまで）
        $text = $this->preprocessText($text);
        
        // OpenAI APIを呼び出し
        $url = 'https://api.openai.com/v1/embeddings';
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'model' => $this->model,
            'input' => $text,
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
            error_log("OpenAI Embedding API error: HTTP $httpCode - $response");
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['data'][0]['embedding'])) {
            error_log("OpenAI Embedding API error: Invalid response structure");
            return null;
        }
        
        return $result['data'][0]['embedding'];
    }
    
    /**
     * 本のエンベディングを生成して保存
     */
    public function generateBookEmbedding(string $asin): bool {
        // 本の情報を取得
        $sql = "SELECT title, author, description FROM b_book_repository WHERE asin = ?";
        $book = $this->db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($book) || empty($book)) {
            error_log("Book not found: $asin");
            return false;
        }
        
        // タイトル+著者のエンベディングを生成
        $titleText = $book['title'];
        if (!empty($book['author'])) {
            $titleText .= ' ' . $book['author'];
        }
        $titleEmbedding = $this->generateEmbedding($titleText);
        
        // 説明文のエンベディングを生成（存在する場合）
        $descriptionEmbedding = null;
        if (!empty($book['description'])) {
            $descriptionEmbedding = $this->generateEmbedding($book['description']);
        }
        
        // 結合エンベディングを生成（タイトル+説明文）
        $combinedText = $titleText;
        if (!empty($book['description'])) {
            // 説明文を適切な長さに切り詰め
            $maxDescLength = 3000; // 文字数制限
            $description = mb_substr($book['description'], 0, $maxDescLength);
            $combinedText .= "\n\n" . $description;
        }
        $combinedEmbedding = $this->generateEmbedding($combinedText);
        
        if (!$combinedEmbedding) {
            error_log("Failed to generate embedding for: $asin");
            return false;
        }
        
        // データベースに保存
        $updateSql = "
            UPDATE b_book_repository
            SET title_embedding = ?,
                description_embedding = ?,
                combined_embedding = ?,
                embedding_generated_at = NOW(),
                embedding_model = ?
            WHERE asin = ?
        ";
        
        $params = [
            $titleEmbedding ? json_encode($titleEmbedding) : null,
            $descriptionEmbedding ? json_encode($descriptionEmbedding) : null,
            json_encode($combinedEmbedding),
            $this->model,
            $asin
        ];
        
        $result = $this->db->query($updateSql, $params);
        
        if (DB::isError($result)) {
            error_log("Failed to save embedding: " . $result->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * 複数の本のエンベディングを一括生成
     */
    public function generateBatchEmbeddings(int $limit = 10): array {
        // エンベディングが未生成の本を取得（説明文があるものを優先）
        $sql = "
            SELECT asin, title, author
            FROM b_book_repository
            WHERE combined_embedding IS NULL
            AND description IS NOT NULL
            AND description != ''
            ORDER BY google_data_updated_at DESC
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            return [
                'success' => false,
                'error' => $books->getMessage()
            ];
        }
        
        $results = [
            'total' => count($books),
            'success' => 0,
            'failed' => 0,
            'processed' => []
        ];
        
        foreach ($books as $book) {
            $success = $this->generateBookEmbedding($book['asin']);
            
            if ($success) {
                $results['success']++;
                $results['processed'][] = [
                    'asin' => $book['asin'],
                    'title' => $book['title'],
                    'status' => 'success'
                ];
            } else {
                $results['failed']++;
                $results['processed'][] = [
                    'asin' => $book['asin'],
                    'title' => $book['title'],
                    'status' => 'failed'
                ];
            }
            
            // API制限対策（少し待機）
            usleep(200000); // 0.2秒
        }
        
        return $results;
    }
    
    /**
     * テキストの前処理
     */
    private function preprocessText(string $text): string {
        // 改行を統一
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // 連続する空白文字を1つに
        $text = preg_replace('/\s+/', ' ', $text);
        
        // HTMLタグを除去
        $text = strip_tags($text);
        
        // 最大長に切り詰め（約8000トークン相当）
        $maxLength = 24000; // 日本語の場合、1文字≈3トークン程度
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }
        
        return trim($text);
    }
    
    /**
     * コサイン類似度を計算
     */
    public static function cosineSimilarity(array $vec1, array $vec2): float {
        if (count($vec1) !== count($vec2)) {
            throw new InvalidArgumentException('Vectors must have the same dimension');
        }
        
        $dotProduct = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;
        
        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $norm1 += $vec1[$i] * $vec1[$i];
            $norm2 += $vec2[$i] * $vec2[$i];
        }
        
        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);
        
        if ($norm1 == 0 || $norm2 == 0) {
            return 0.0;
        }
        
        return $dotProduct / ($norm1 * $norm2);
    }
}
?>