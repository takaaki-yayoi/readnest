<?php
/**
 * タイトルベースのエンベディング生成クラス
 * 説明文取得を省略し、タイトルのみで効率的にエンベディングを生成
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');

class TitleEmbeddingGenerator {
    private $db;
    private $apiKey;
    private $model = 'text-embedding-3-small'; // 安価なモデル
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
            throw new Exception('OpenAI API key is not configured');
        }
        $this->apiKey = OPENAI_API_KEY;
    }
    
    /**
     * タイトルからエンベディングを生成
     */
    public function generateEmbedding(string $title): ?array {
        if (empty($title)) {
            return null;
        }
        
        $url = 'https://api.openai.com/v1/embeddings';
        $data = [
            'model' => $this->model,
            'input' => $title, // タイトルのみを使用
            'encoding_format' => 'float'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("OpenAI API error (HTTP $httpCode): $response");
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['data'][0]['embedding'])) {
            error_log("Unexpected API response format");
            return null;
        }
        
        return $result['data'][0]['embedding'];
    }
    
    /**
     * 本のタイトルエンベディングを生成して保存
     */
    public function generateBookEmbedding(string $asin): bool {
        // 本の情報を取得
        $sql = "SELECT asin, title FROM b_book_repository WHERE asin = ?";
        $book = $this->db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($book) || empty($book)) {
            error_log("Book not found: $asin");
            return false;
        }
        
        if (empty($book['title'])) {
            error_log("Book has no title: $asin");
            return false;
        }
        
        // エンベディングを生成
        $embedding = $this->generateEmbedding($book['title']);
        
        if (!$embedding) {
            error_log("Failed to generate embedding for: " . $book['title']);
            return false;
        }
        
        // データベースに保存
        $updateSql = "
            UPDATE b_book_repository 
            SET 
                combined_embedding = ?,
                embedding_generated_at = NOW(),
                embedding_model = ?
            WHERE asin = ?
        ";
        
        $result = $this->db->query($updateSql, [
            json_encode($embedding),
            $this->model,
            $asin
        ]);
        
        if (DB::isError($result)) {
            error_log("Failed to save embedding: " . $result->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * 複数の本を一括処理
     */
    public function generateBatch(int $limit = 100, int $offset = 0): array {
        // エンベディングがない本を取得
        $sql = "
            SELECT asin, title
            FROM b_book_repository
            WHERE combined_embedding IS NULL
            AND title IS NOT NULL
            AND title != ''
            ORDER BY asin
            LIMIT ? OFFSET ?
        ";
        
        $books = $this->db->getAll($sql, [$limit, $offset], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            return [
                'success' => 0,
                'failed' => 0,
                'total' => 0,
                'errors' => ['Database error: ' . $books->getMessage()]
            ];
        }
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'total' => count($books),
            'processed' => []
        ];
        
        foreach ($books as $book) {
            $success = $this->generateBookEmbedding($book['asin']);
            
            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['processed'][] = [
                'asin' => $book['asin'],
                'title' => $book['title'],
                'success' => $success
            ];
            
            // API制限対策（RPMが3,500なので余裕を持って）
            usleep(100000); // 0.1秒待機
        }
        
        return $results;
    }
    
    /**
     * 人気本を優先的に処理
     */
    public function generatePopularBooks(int $limit = 100): array {
        $sql = "
            SELECT 
                br.asin,
                br.title,
                COUNT(bl.book_id) as reader_count
            FROM b_book_repository br
            INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
            WHERE br.combined_embedding IS NULL
            AND br.title IS NOT NULL
            AND br.title != ''
            GROUP BY br.asin, br.title
            ORDER BY reader_count DESC
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            return [
                'success' => 0,
                'failed' => 0,
                'total' => 0,
                'errors' => ['Database error: ' . $books->getMessage()]
            ];
        }
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'total' => count($books),
            'processed' => []
        ];
        
        foreach ($books as $book) {
            $success = $this->generateBookEmbedding($book['asin']);
            
            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['processed'][] = [
                'asin' => $book['asin'],
                'title' => $book['title'],
                'reader_count' => $book['reader_count'],
                'success' => $success
            ];
            
            usleep(100000); // API制限対策（0.1秒待機）
        }
        
        return $results;
    }
    
    /**
     * 統計情報を取得
     */
    public function getStatistics(): array {
        $sql = "
            SELECT 
                COUNT(*) as total_books,
                SUM(CASE WHEN title IS NOT NULL AND title != '' THEN 1 ELSE 0 END) as with_title,
                SUM(CASE WHEN combined_embedding IS NOT NULL THEN 1 ELSE 0 END) as with_embedding,
                SUM(CASE WHEN title IS NOT NULL AND title != '' AND combined_embedding IS NULL THEN 1 ELSE 0 END) as need_embedding
            FROM b_book_repository
        ";
        
        $stats = $this->db->getRow($sql, [], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($stats)) {
            return [
                'total_books' => 0,
                'with_title' => 0,
                'with_embedding' => 0,
                'need_embedding' => 0,
                'coverage' => 0
            ];
        }
        
        $stats['coverage'] = $stats['with_title'] > 0 
            ? round(($stats['with_embedding'] / $stats['with_title']) * 100, 2)
            : 0;
            
        // API料金の見積もり（$0.02 per 1M tokens、タイトルは平均50トークンと仮定）
        $stats['estimated_cost'] = round(($stats['need_embedding'] * 50 / 1000000) * 0.02, 4);
        
        return $stats;
    }
}
?>