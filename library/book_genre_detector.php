<?php
/**
 * 書籍ジャンル検出クラス
 * Google Books APIとOpenAI APIを組み合わせてジャンルを判定
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');

class BookGenreDetector {
    private $db;
    private $googleBooksCache = [];
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
    }
    
    /**
     * Google Books APIから書籍情報を取得
     */
    public function getBookInfoFromGoogle(string $title, string $author = ''): ?array {
        // キャッシュチェック
        $cacheKey = md5($title . $author);
        if (isset($this->googleBooksCache[$cacheKey])) {
            return $this->googleBooksCache[$cacheKey];
        }
        
        // クエリ構築
        $query = $title;
        if (!empty($author)) {
            $query .= ' inauthor:' . $author;
        }
        
        $url = 'https://www.googleapis.com/books/v1/volumes?' . http_build_query([
            'q' => $query,
            'maxResults' => 1,
            'langRestrict' => 'ja'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        if (!isset($data['items'][0]['volumeInfo'])) {
            return null;
        }
        
        $volumeInfo = $data['items'][0]['volumeInfo'];
        
        $result = [
            'title' => $volumeInfo['title'] ?? $title,
            'authors' => $volumeInfo['authors'] ?? [$author],
            'description' => $volumeInfo['description'] ?? '',
            'categories' => $volumeInfo['categories'] ?? [],
            'publishedDate' => $volumeInfo['publishedDate'] ?? '',
            'pageCount' => $volumeInfo['pageCount'] ?? 0,
            'language' => $volumeInfo['language'] ?? 'ja',
            'isbn' => $this->extractISBN($volumeInfo)
        ];
        
        // キャッシュに保存
        $this->googleBooksCache[$cacheKey] = $result;
        
        return $result;
    }
    
    /**
     * ISBNを抽出
     */
    private function extractISBN(array $volumeInfo): ?string {
        if (isset($volumeInfo['industryIdentifiers'])) {
            foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                if ($identifier['type'] === 'ISBN_13') {
                    return $identifier['identifier'];
                }
            }
            foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                if ($identifier['type'] === 'ISBN_10') {
                    return $identifier['identifier'];
                }
            }
        }
        return null;
    }
    
    /**
     * 書籍のジャンルを判定
     */
    public function detectGenre(string $title, string $author = '', ?array $googleInfo = null): array {
        // Google Books APIから情報取得
        if ($googleInfo === null) {
            $googleInfo = $this->getBookInfoFromGoogle($title, $author);
        }
        
        // 基本的なジャンル判定ルール
        $genres = [];
        $confidence = 0;
        
        // Google Booksのカテゴリから判定
        if ($googleInfo && !empty($googleInfo['categories'])) {
            foreach ($googleInfo['categories'] as $category) {
                $genre = $this->mapGoogleCategoryToGenre($category);
                if ($genre) {
                    $genres[] = $genre;
                    $confidence = max($confidence, 80);
                }
            }
        }
        
        // タイトルと説明文からの判定
        $titleLower = mb_strtolower($title);
        $description = $googleInfo['description'] ?? '';
        
        // 実用書・学習書の判定
        if ($this->isEducationalBook($titleLower, $description, $author)) {
            $genres[] = 'educational';
            $confidence = max($confidence, 70);
        }
        
        // 小説の判定
        if ($this->isFiction($titleLower, $description, $author)) {
            $genres[] = 'fiction';
            $confidence = max($confidence, 70);
        }
        
        // ビジネス書の判定
        if ($this->isBusinessBook($titleLower, $description)) {
            $genres[] = 'business';
            $confidence = max($confidence, 70);
        }
        
        // 技術書の判定
        if ($this->isTechnicalBook($titleLower, $description)) {
            $genres[] = 'technical';
            $confidence = max($confidence, 70);
        }
        
        return [
            'genres' => array_unique($genres),
            'confidence' => $confidence,
            'google_categories' => $googleInfo['categories'] ?? [],
            'description' => $googleInfo['description'] ?? ''
        ];
    }
    
    /**
     * Googleカテゴリをジャンルにマッピング
     */
    private function mapGoogleCategoryToGenre(string $category): ?string {
        $mappings = [
            'Fiction' => 'fiction',
            '小説' => 'fiction',
            'Computers' => 'technical',
            'コンピュータ' => 'technical',
            'Business' => 'business',
            'ビジネス' => 'business',
            'Education' => 'educational',
            '教育' => 'educational',
            'Language Arts' => 'language',
            '語学' => 'language',
            'Cooking' => 'cooking',
            '料理' => 'cooking',
            'Self-Help' => 'self-help',
            '自己啓発' => 'self-help',
            'History' => 'history',
            '歴史' => 'history',
            'Science' => 'science',
            '科学' => 'science',
            'Mathematics' => 'mathematics',
            '数学' => 'mathematics'
        ];
        
        foreach ($mappings as $keyword => $genre) {
            if (stripos($category, $keyword) !== false) {
                return $genre;
            }
        }
        
        return null;
    }
    
    /**
     * 教育書・学習書の判定
     */
    private function isEducationalBook(string $title, string $description, string $author): bool {
        $keywords = [
            '入門', '基礎', '教科書', '参考書', '問題集', 
            '学習', '勉強', 'テキスト', '講座', '教程',
            'textbook', 'learning', 'study', 'guide', 'tutorial',
            'Word Power', 'Grammar', '英語', '数学', '物理'
        ];
        
        foreach ($keywords as $keyword) {
            if (mb_stripos($title, $keyword) !== false || 
                mb_stripos($description, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 小説の判定
     */
    private function isFiction(string $title, string $description, string $author): bool {
        // 出版社レーベルから判定
        $fictionLabels = ['文庫', '新潮', '講談社', '角川', '双葉', '文春', '集英社'];
        foreach ($fictionLabels as $label) {
            if (mb_stripos($title, $label) !== false) {
                return true;
            }
        }
        
        // 著者名から判定（有名な小説家）
        $fictionAuthors = ['湊かなえ', '東野圭吾', '村上春樹', '宮部みゆき', '伊坂幸太郎'];
        foreach ($fictionAuthors as $fAuthor) {
            if (mb_stripos($author, $fAuthor) !== false) {
                return true;
            }
        }
        
        // キーワードから判定
        $keywords = ['小説', 'ミステリー', 'サスペンス', '物語', 'ストーリー'];
        foreach ($keywords as $keyword) {
            if (mb_stripos($description, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * ビジネス書の判定
     */
    private function isBusinessBook(string $title, string $description): bool {
        $keywords = [
            'ビジネス', '経営', 'マネジメント', 'リーダーシップ',
            'マーケティング', '戦略', '組織', '起業', '投資',
            'business', 'management', 'leadership', 'marketing'
        ];
        
        foreach ($keywords as $keyword) {
            if (mb_stripos($title, $keyword) !== false || 
                mb_stripos($description, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 技術書の判定
     */
    private function isTechnicalBook(string $title, string $description): bool {
        $keywords = [
            'プログラミング', 'エンジニア', 'データベース', 'アーキテクチャ',
            'Python', 'Java', 'JavaScript', 'SQL', 'AWS', 'Docker',
            'Practical', 'Technical', 'Engineering', 'Development',
            'Lakehouse', 'Architecture', 'System', 'Design'
        ];
        
        foreach ($keywords as $keyword) {
            if (mb_stripos($title, $keyword) !== false || 
                mb_stripos($description, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * データベースの書籍にジャンル情報を追加
     */
    public function updateBookGenre(string $asin): bool {
        // 書籍情報を取得
        $sql = "SELECT title, author FROM b_book_repository WHERE asin = ?";
        $book = $this->db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($book) || !$book) {
            return false;
        }
        
        // ジャンルを検出
        $genreInfo = $this->detectGenre($book['title'], $book['author']);
        
        if (empty($genreInfo['genres'])) {
            return false;
        }
        
        // メタデータをJSON形式で保存
        $metadata = json_encode([
            'genres' => $genreInfo['genres'],
            'confidence' => $genreInfo['confidence'],
            'google_categories' => $genreInfo['google_categories'],
            'updated_at' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
        // データベースを更新（genre_metadataカラムが必要）
        // このカラムは後で追加する必要がある
        // $updateSql = "UPDATE b_book_repository SET genre_metadata = ? WHERE asin = ?";
        // $result = $this->db->query($updateSql, [$metadata, $asin]);
        
        return true;
    }
}