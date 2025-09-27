<?php
/**
 * Google Books API クライアント
 * 書籍の詳細情報（説明文など）を取得
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');

class GoogleBooksAPI {
    private $apiKey;
    private $db;
    private $cacheTable = 'google_books_cache';
    private $baseUrl = 'https://www.googleapis.com/books/v1/volumes';
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        // Google Books API キー（オプション - なくても動作）
        $this->apiKey = defined('GOOGLE_BOOKS_API_KEY') ? GOOGLE_BOOKS_API_KEY : null;
        
        // キャッシュテーブルの作成
        $this->createCacheTableIfNotExists();
    }
    
    /**
     * ISBNまたはタイトル・著者で書籍情報を検索
     */
    public function getBookInfo(string $isbn = null, string $title = null, string $author = null): ?array {
        // キャッシュを確認
        $cached = $this->getCachedInfo($isbn, $title, $author);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            // Google Books APIで検索
            $bookInfo = null;
            
            if (!empty($isbn)) {
                // ISBN検索（最も正確）
                $bookInfo = $this->searchByISBN($isbn);
            }
            
            if ($bookInfo === null && !empty($title)) {
                // タイトル・著者検索
                $bookInfo = $this->searchByTitleAuthor($title, $author);
            }
            
            if ($bookInfo !== null) {
                // キャッシュに保存
                $this->saveCache($isbn, $title, $author, $bookInfo);
            }
            
            return $bookInfo;
            
        } catch (Exception $e) {
            error_log('Google Books API Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ISBNで検索
     */
    private function searchByISBN(string $isbn): ?array {
        // ISBNのハイフンを除去
        $isbn = str_replace('-', '', $isbn);
        
        $query = 'isbn:' . $isbn;
        return $this->searchBooks($query);
    }
    
    /**
     * タイトルと著者で検索
     */
    private function searchByTitleAuthor(string $title, string $author = null): ?array {
        $query = 'intitle:' . urlencode($title);
        
        if (!empty($author)) {
            $query .= '+inauthor:' . urlencode($author);
        }
        
        // 日本語の本を優先
        $query .= '&langRestrict=ja';
        
        return $this->searchBooks($query);
    }
    
    /**
     * Google Books APIを呼び出し
     */
    private function searchBooks(string $query): ?array {
        $url = $this->baseUrl . '?q=' . $query;
        
        if ($this->apiKey) {
            $url .= '&key=' . $this->apiKey;
        }
        
        // 詳細情報を含める
        $url .= '&maxResults=1';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: ja,en'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Google Books API HTTP Error: ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['items']) || empty($data['items'])) {
            return null;
        }
        
        // 最初の結果を取得
        $item = $data['items'][0];
        $volumeInfo = $item['volumeInfo'] ?? [];
        
        return $this->parseVolumeInfo($volumeInfo);
    }
    
    /**
     * Google Books APIのレスポンスを解析
     */
    private function parseVolumeInfo(array $volumeInfo): array {
        return [
            'title' => $volumeInfo['title'] ?? '',
            'subtitle' => $volumeInfo['subtitle'] ?? '',
            'authors' => $volumeInfo['authors'] ?? [],
            'publisher' => $volumeInfo['publisher'] ?? '',
            'publishedDate' => $volumeInfo['publishedDate'] ?? '',
            'description' => $volumeInfo['description'] ?? '',  // 書籍の説明文
            'pageCount' => $volumeInfo['pageCount'] ?? 0,
            'categories' => $volumeInfo['categories'] ?? [],  // ジャンル/カテゴリ
            'averageRating' => $volumeInfo['averageRating'] ?? null,
            'ratingsCount' => $volumeInfo['ratingsCount'] ?? 0,
            'language' => $volumeInfo['language'] ?? '',
            'imageLinks' => [
                'thumbnail' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
                'smallThumbnail' => $volumeInfo['imageLinks']['smallThumbnail'] ?? ''
            ],
            'industryIdentifiers' => $volumeInfo['industryIdentifiers'] ?? [],  // ISBN情報
            'maturityRating' => $volumeInfo['maturityRating'] ?? '',
            'previewLink' => $volumeInfo['previewLink'] ?? '',
            'infoLink' => $volumeInfo['infoLink'] ?? ''
        ];
    }
    
    /**
     * 書籍の説明文のみを取得（簡易版）
     */
    public function getDescription(string $title, string $author = null): ?string {
        $bookInfo = $this->getBookInfo(null, $title, $author);
        return $bookInfo['description'] ?? null;
    }
    
    /**
     * 書籍のカテゴリを取得
     */
    public function getCategories(string $title, string $author = null): array {
        $bookInfo = $this->getBookInfo(null, $title, $author);
        return $bookInfo['categories'] ?? [];
    }
    
    /**
     * キャッシュテーブルを作成
     */
    private function createCacheTableIfNotExists(): void {
        $sql = "
            CREATE TABLE IF NOT EXISTS {$this->cacheTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                isbn VARCHAR(20),
                title VARCHAR(500),
                author VARCHAR(255),
                book_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_isbn (isbn),
                INDEX idx_title_author (title(255), author)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ";
        
        $this->db->query($sql);
    }
    
    /**
     * キャッシュから取得
     */
    private function getCachedInfo($isbn, $title, $author): ?array {
        $sql = "
            SELECT book_data
            FROM {$this->cacheTable}
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($isbn)) {
            $sql .= " AND isbn = ?";
            $params[] = $isbn;
        } else if (!empty($title)) {
            $sql .= " AND title = ?";
            $params[] = $title;
            if (!empty($author)) {
                $sql .= " AND author = ?";
                $params[] = $author;
            }
        } else {
            return null;
        }
        
        $sql .= " AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 1";
        
        $result = $this->db->getOne($sql, $params);
        
        if (!DB::isError($result) && $result) {
            return json_decode($result, true);
        }
        
        return null;
    }
    
    /**
     * キャッシュに保存
     */
    private function saveCache($isbn, $title, $author, array $bookInfo): void {
        $sql = "
            INSERT INTO {$this->cacheTable} 
            (isbn, title, author, book_data)
            VALUES (?, ?, ?, ?)
        ";
        
        $this->db->query($sql, [
            $isbn ?: '',
            $title ?: $bookInfo['title'],
            $author ?: implode(', ', $bookInfo['authors']),
            json_encode($bookInfo, JSON_UNESCAPED_UNICODE)
        ]);
    }
    
    /**
     * 説明文を使った類似本判定
     */
    public function analyzeContentSimilarity(string $description1, string $description2): float {
        if (empty($description1) || empty($description2)) {
            return 0.0;
        }
        
        // 簡易的な類似度計算（実際はもっと高度な自然言語処理が必要）
        $words1 = $this->extractKeywords($description1);
        $words2 = $this->extractKeywords($description2);
        
        $common = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        if (count($union) === 0) {
            return 0.0;
        }
        
        // Jaccard係数
        return count($common) / count($union);
    }
    
    /**
     * テキストからキーワード抽出
     */
    private function extractKeywords(string $text): array {
        // 日本語の形態素解析が理想的だが、簡易版として実装
        $text = mb_strtolower($text);
        
        // 不要な文字を除去
        $text = preg_replace('/[。、！？「」『』（）\[\]【】〜ー]/u', ' ', $text);
        
        // 単語に分割
        $words = preg_split('/[\s　]+/u', $text);
        
        // ストップワードを除去
        $stopWords = ['の', 'は', 'が', 'を', 'に', 'で', 'と', 'から', 'まで', 'より', 'も', 'や', 'など', 'こと', 'もの', 'これ', 'それ', 'あれ'];
        
        $keywords = [];
        foreach ($words as $word) {
            if (mb_strlen($word) >= 2 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
    }
}
?>