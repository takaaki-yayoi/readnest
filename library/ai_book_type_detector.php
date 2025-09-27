<?php
/**
 * AI による本のタイプ判定
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');

class AIBookTypeDetector {
    private $apiKey;
    private $db;
    private $cacheTable = 'book_type_cache'; // キャッシュテーブル
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        $this->apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null;
        
        // キャッシュテーブルの作成（存在しない場合）
        $this->createCacheTableIfNotExists();
    }
    
    /**
     * 本のタイプを判定（キャッシュ付き）
     */
    public function detectType(string $title, string $author): array {
        // キャッシュを確認
        $cached = $this->getCachedType($title, $author);
        if ($cached !== null) {
            return $cached;
        }
        
        // APIキーがない場合はフォールバック
        if (empty($this->apiKey)) {
            return $this->fallbackDetection($title, $author);
        }
        
        try {
            // OpenAI APIで判定
            $result = $this->detectTypeWithAI($title, $author);
            
            // キャッシュに保存
            $this->saveCacheType($title, $author, $result);
            
            return $result;
        } catch (Exception $e) {
            error_log('AI Book Type Detection Error: ' . $e->getMessage());
            return $this->fallbackDetection($title, $author);
        }
    }
    
    /**
     * OpenAI APIで本のタイプを判定
     */
    private function detectTypeWithAI(string $title, string $author): array {
        $prompt = "以下の本のジャンルとカテゴリーを判定してください。複数該当する場合は最も適切なものを選んでください。

タイトル: {$title}
著者: {$author}

以下のフォーマットでJSONを返してください：
{
    \"main_type\": \"fiction/non_fiction/educational/reference\",
    \"genre\": \"mystery/romance/fantasy/sf/business/tech/self_help/history/biography/cooking/art/science/psychology/philosophy/poetry/essay/travel/sports/health\",
    \"sub_genre\": \"より具体的なサブジャンル（日本語可）\",
    \"confidence\": 0.0-1.0の信頼度,
    \"reasoning\": \"判定理由（日本語）\"
}";

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'あなたは本のジャンルを正確に判定する専門家です。タイトルと著者名から本のジャンルを判定してください。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 200,
            'response_format' => ['type' => 'json_object']
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('OpenAI API error: HTTP ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid OpenAI response');
        }
        
        $result = json_decode($data['choices'][0]['message']['content'], true);
        if (!$result) {
            throw new Exception('Failed to parse AI response');
        }
        
        return [
            'main_type' => $result['main_type'] ?? 'general',
            'genre' => $result['genre'] ?? 'general',
            'sub_genre' => $result['sub_genre'] ?? '',
            'confidence' => $result['confidence'] ?? 0.5,
            'reasoning' => $result['reasoning'] ?? ''
        ];
    }
    
    /**
     * フォールバック判定（従来のロジック）
     */
    private function fallbackDetection(string $title, string $author): array {
        $titleLower = mb_strtolower($title);
        
        // 基本的なパターンマッチング
        if (mb_stripos($titleLower, 'ミステリ') !== false || 
            mb_stripos($titleLower, '推理') !== false ||
            mb_stripos($titleLower, '探偵') !== false) {
            return [
                'main_type' => 'fiction',
                'genre' => 'mystery',
                'sub_genre' => '',
                'confidence' => 0.7,
                'reasoning' => 'タイトルにミステリー関連キーワードを検出'
            ];
        }
        
        if (mb_stripos($titleLower, 'ビジネス') !== false || 
            mb_stripos($titleLower, '経営') !== false) {
            return [
                'main_type' => 'non_fiction',
                'genre' => 'business',
                'sub_genre' => '',
                'confidence' => 0.7,
                'reasoning' => 'タイトルにビジネス関連キーワードを検出'
            ];
        }
        
        if (mb_stripos($titleLower, 'プログラミング') !== false || 
            mb_stripos($titleLower, 'python') !== false ||
            mb_stripos($titleLower, 'java') !== false) {
            return [
                'main_type' => 'educational',
                'genre' => 'tech',
                'sub_genre' => '',
                'confidence' => 0.8,
                'reasoning' => 'タイトルに技術関連キーワードを検出'
            ];
        }
        
        // デフォルト
        return [
            'main_type' => 'general',
            'genre' => 'general',
            'sub_genre' => '',
            'confidence' => 0.3,
            'reasoning' => '明確なジャンルを特定できませんでした'
        ];
    }
    
    /**
     * キャッシュテーブルを作成
     */
    private function createCacheTableIfNotExists(): void {
        $sql = "
            CREATE TABLE IF NOT EXISTS {$this->cacheTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(500),
                author VARCHAR(255),
                main_type VARCHAR(50),
                genre VARCHAR(50),
                sub_genre VARCHAR(100),
                confidence FLOAT,
                reasoning TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_title_author (title(255), author)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ";
        
        $this->db->query($sql);
    }
    
    /**
     * キャッシュから取得
     */
    private function getCachedType(string $title, string $author): ?array {
        $sql = "
            SELECT main_type, genre, sub_genre, confidence, reasoning
            FROM {$this->cacheTable}
            WHERE title = ? AND author = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            LIMIT 1
        ";
        
        $result = $this->db->getRow($sql, [$title, $author], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($result) && $result) {
            return [
                'main_type' => $result['main_type'],
                'genre' => $result['genre'],
                'sub_genre' => $result['sub_genre'],
                'confidence' => (float)$result['confidence'],
                'reasoning' => $result['reasoning']
            ];
        }
        
        return null;
    }
    
    /**
     * キャッシュに保存
     */
    private function saveCacheType(string $title, string $author, array $result): void {
        $sql = "
            INSERT INTO {$this->cacheTable} 
            (title, author, main_type, genre, sub_genre, confidence, reasoning)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $this->db->query($sql, [
            $title,
            $author,
            $result['main_type'],
            $result['genre'],
            $result['sub_genre'] ?? '',
            $result['confidence'],
            $result['reasoning'] ?? ''
        ]);
    }
    
    /**
     * 同じジャンルの本を検索
     */
    public function findSimilarGenreBooks(string $genre, string $excludeAsin, int $userId, int $limit = 10): array {
        // キャッシュから同じジャンルの本を検索
        $sql = "
            SELECT DISTINCT
                br.asin,
                br.title,
                br.author,
                br.image_url,
                btc.sub_genre,
                btc.confidence
            FROM {$this->cacheTable} btc
            INNER JOIN b_book_repository br ON br.title = btc.title AND br.author = btc.author
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
            WHERE btc.genre = ?
            AND br.asin != ?
            AND bl.book_id IS NULL
            AND btc.confidence >= 0.6
            ORDER BY btc.confidence DESC, RAND()
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$userId, $genre, $excludeAsin, $limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            return [];
        }
        
        return $books;
    }
}
?>