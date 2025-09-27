<?php
/**
 * 書籍の説明文を取得・更新するクラス
 * Google Books APIから取得した情報をb_book_repositoryに保存
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');
require_once(dirname(__FILE__) . '/google_books_api.php');

class BookDescriptionUpdater {
    private $db;
    private $googleBooksAPI;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        $this->googleBooksAPI = new GoogleBooksAPI();
    }
    
    /**
     * 指定した本の説明文を取得・更新
     */
    public function updateBookDescription(string $asin): bool {
        // 現在の書籍情報を取得
        $sql = "
            SELECT asin, title, author, isbn, description, google_data_updated_at
            FROM b_book_repository
            WHERE asin = ?
        ";
        
        $book = $this->db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($book) || !$book) {
            error_log("Book not found: {$asin}");
            return false;
        }
        
        // すでに説明文があり、30日以内に更新されている場合はスキップ
        if (!empty($book['description']) && 
            !empty($book['google_data_updated_at']) &&
            strtotime($book['google_data_updated_at']) > strtotime('-30 days')) {
            return true;
        }
        
        // Google Books APIから情報を取得
        $bookInfo = null;
        
        // ISBNがある場合は優先的に使用
        if (!empty($book['isbn'])) {
            $bookInfo = $this->googleBooksAPI->getBookInfo($book['isbn']);
        }
        
        // ISBNで見つからない場合はタイトル・著者で検索
        if ($bookInfo === null && !empty($book['title'])) {
            $bookInfo = $this->googleBooksAPI->getBookInfo(null, $book['title'], $book['author']);
        }
        
        if ($bookInfo === null) {
            error_log("Google Books API: No data found for {$book['title']} by {$book['author']}");
            return false;
        }
        
        // データベースを更新
        return $this->saveBookInfo($asin, $bookInfo);
    }
    
    /**
     * Google Books情報をデータベースに保存
     */
    private function saveBookInfo(string $asin, array $bookInfo): bool {
        $sql = "
            UPDATE b_book_repository
            SET 
                description = ?,
                google_categories = ?,
                google_average_rating = ?,
                google_data_updated_at = NOW()
            WHERE asin = ?
        ";
        
        $params = [
            $bookInfo['description'] ?? null,
            !empty($bookInfo['categories']) ? json_encode($bookInfo['categories'], JSON_UNESCAPED_UNICODE) : null,
            $bookInfo['averageRating'] ?? null,
            $asin
        ];
        
        $result = $this->db->query($sql, $params);
        
        if (DB::isError($result)) {
            error_log("Failed to update book description: " . $result->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * 説明文がない本を一括更新（バッチ処理用）
     */
    public function updateMissingDescriptions(int $limit = 50): array {
        // 説明文がない本を取得
        $sql = "
            SELECT asin
            FROM b_book_repository
            WHERE description IS NULL
            OR google_data_updated_at IS NULL
            OR google_data_updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY RAND()
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            return ['error' => $books->getMessage()];
        }
        
        $results = [
            'total' => count($books),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($books as $book) {
            // API制限を考慮して少し待機
            usleep(200000); // 0.2秒
            
            $success = $this->updateBookDescription($book['asin']);
            
            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['details'][] = [
                'asin' => $book['asin'],
                'success' => $success
            ];
        }
        
        return $results;
    }
    
    /**
     * 本の説明文を取得（キャッシュ優先）
     */
    public function getDescription(string $asin): ?string {
        $sql = "SELECT description FROM b_book_repository WHERE asin = ?";
        $description = $this->db->getOne($sql, [$asin]);
        
        if (!DB::isError($description) && !empty($description)) {
            return $description;
        }
        
        // 説明文がない場合は取得を試みる
        if ($this->updateBookDescription($asin)) {
            // 再度取得
            $description = $this->db->getOne($sql, [$asin]);
            if (!DB::isError($description)) {
                return $description;
            }
        }
        
        return null;
    }
    
    /**
     * 説明文を使った類似本検索
     */
    public function findSimilarBooksByDescription(string $asin, int $userId, int $limit = 10): array {
        // ベース本の説明文を取得
        $baseDescription = $this->getDescription($asin);
        
        if (empty($baseDescription)) {
            return [];
        }
        
        // 説明文がある他の本を取得（ユーザーが読んでいない本）
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.image_url,
                br.description,
                br.google_categories
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
            WHERE br.description IS NOT NULL
            AND br.asin != ?
            AND bl.book_id IS NULL
            ORDER BY RAND()
            LIMIT 100
        ";
        
        $candidates = $this->db->getAll($sql, [$userId, $asin], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($candidates)) {
            return [];
        }
        
        // 類似度を計算
        $similarBooks = [];
        foreach ($candidates as $book) {
            $similarity = $this->googleBooksAPI->analyzeContentSimilarity(
                $baseDescription,
                $book['description']
            );
            
            if ($similarity > 0.1) { // 閾値
                $book['similarity_score'] = round($similarity * 100);
                $book['reason'] = '内容が類似している作品';
                $similarBooks[] = $book;
            }
        }
        
        // 類似度でソート
        usort($similarBooks, function($a, $b) {
            return $b['similarity_score'] - $a['similarity_score'];
        });
        
        return array_slice($similarBooks, 0, $limit);
    }
}
?>