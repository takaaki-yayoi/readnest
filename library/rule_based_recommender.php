<?php
/**
 * ルールベース推薦システム
 * 評価、お気に入り、作家クラウドのデータに基づく推薦
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');
require_once(dirname(__FILE__) . '/ai_similarity_analyzer.php');

class RuleBasedRecommender {
    private $db;
    private int $userId;
    
    public function __construct(int $userId) {
        global $g_db;
        $this->db = $g_db;
        $this->userId = $userId;
    }
    
    /**
     * ルールベースの推薦を取得
     */
    public function getRecommendations(int $limit = 20): array {
        $recommendations = [];
        
        // 各カテゴリから多めに取得（後で重複除去するため）
        $perCategoryLimit = max(15, intval($limit * 0.8));
        
        // 1. 高評価著者の未読本
        $highRatedAuthors = $this->getHighRatedAuthorBooks($perCategoryLimit);
        foreach ($highRatedAuthors as $book) {
            // 著者名が空の場合の処理
            if (empty($book['author']) || $book['author'] === '') {
                $book['reason'] = "★{$book['avg_rating']}評価の作品";
            } else {
                $book['reason'] = "★{$book['avg_rating']}評価の{$book['author']}の作品";
            }
            $book['category'] = 'high_rated_author';
            $recommendations[] = $book;
        }
        
        // 2. お気に入り著者の未読本
        $favoriteAuthors = $this->getFavoriteAuthorBooks($perCategoryLimit);
        foreach ($favoriteAuthors as $book) {
            // 著者名が空の場合の処理
            if (empty($book['author']) || $book['author'] === '') {
                $book['reason'] = "お気に入りの作品シリーズ";
            } else {
                $book['reason'] = "お気に入りの{$book['author']}の作品";
            }
            $book['category'] = 'favorite_author';
            $recommendations[] = $book;
        }
        
        // 3. 頻繁に読む著者の未読本（作家クラウド）
        $frequentAuthors = $this->getFrequentAuthorBooks($perCategoryLimit);
        foreach ($frequentAuthors as $book) {
            // 著者名が空の場合の処理
            if (empty($book['author']) || $book['author'] === '') {
                $book['reason'] = "よく読む作品シリーズ";
            } else {
                $book['reason'] = "{$book['read_count']}冊読んでいる{$book['author']}の作品";
            }
            $book['category'] = 'frequent_author';
            $recommendations[] = $book;
        }
        
        // 4. 高評価本に類似した本（AI分析を使用）
        $similarBooks = $this->getSimilarBooksWithAI($perCategoryLimit);
        foreach ($similarBooks as $book) {
            // reasonに基準本の情報が含まれている場合はそのまま使用
            if (!isset($book['reason']) || empty($book['reason'])) {
                if (!empty($book['similar_to'])) {
                    $book['reason'] = "「{$book['similar_to']}」と類似";
                } else {
                    $book['reason'] = "高評価本と類似";
                }
            }
            $book['category'] = 'similar_to_high_rated';
            $recommendations[] = $book;
        }
        
        // 5. 同じジャンルの高評価本
        $genreBooks = $this->getGenreBasedBooks($perCategoryLimit);
        foreach ($genreBooks as $book) {
            $book['reason'] = "{$book['genre']}ジャンルの人気作品";
            $book['category'] = 'popular_in_genre';
            $recommendations[] = $book;
        }
        
        // スコアでソート
        usort($recommendations, function($a, $b) {
            return ($b['score'] ?? 0) - ($a['score'] ?? 0);
        });
        
        // 重複を除去（ASINでユニーク化）
        $uniqueBooks = [];
        $seenAsins = [];
        foreach ($recommendations as $book) {
            if (!in_array($book['asin'], $seenAsins)) {
                $uniqueBooks[] = $book;
                $seenAsins[] = $book['asin'];
            }
        }
        
        return array_slice($uniqueBooks, 0, $limit);
    }
    
    /**
     * 高評価を付けた著者の未読本を取得
     */
    private function getHighRatedAuthorBooks(int $limit): array {
        // まず高評価著者を取得（著者名が空でないもののみ）
        $authorSql = "
            SELECT 
                br.author,
                ROUND(AVG(bl.rating), 1) as avg_rating
            FROM b_book_list bl
            INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.rating >= 4
            AND br.author IS NOT NULL
            AND br.author != ''
            GROUP BY br.author
            ORDER BY avg_rating DESC
            LIMIT 15
        ";
        
        $highRatedAuthors = $this->db->getAll($authorSql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($highRatedAuthors) || empty($highRatedAuthors)) {
            error_log('No high rated authors found for user: ' . $this->userId);
            return [];
        }
        
        $recommendations = [];
        
        foreach ($highRatedAuthors as $authorData) {
            // 各著者の未読本を取得
            $bookSql = "
                SELECT 
                    br.asin,
                    br.title,
                    br.author,
                    br.image_url,
                    ? as avg_rating,
                    90 + ? * 2 as score
                FROM b_book_repository br
                LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                WHERE br.author = ?
                AND bl.book_id IS NULL
                ORDER BY br.title
                LIMIT 2
            ";
            
            $books = $this->db->getAll($bookSql, [
                $authorData['avg_rating'],
                $authorData['avg_rating'],
                $this->userId,
                $authorData['author']
            ], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($books) && $books) {
                $recommendations = array_merge($recommendations, $books);
            }
        }
        
        // スコアでソート
        usort($recommendations, function($a, $b) {
            return ($b['score'] ?? 0) - ($a['score'] ?? 0);
        });
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * お気に入りに入れた本の著者の未読本を取得
     */
    private function getFavoriteAuthorBooks(int $limit): array {
        // まずb_book_favoritesテーブルが存在するか確認
        $checkTableSql = "SHOW TABLES LIKE 'b_book_favorites'";
        $tableExists = $this->db->getOne($checkTableSql);
        
        if (!$tableExists) {
            // テーブルが存在しない場合は高評価本から取得
            $sql = "
                SELECT 
                    br2.asin,
                    br2.title,
                    br2.author,
                    br2.image_url,
                    COUNT(DISTINCT bl_fav.book_id) as favorite_count,
                    85 + COUNT(DISTINCT bl_fav.book_id) * 3 as score
                FROM b_book_list bl_fav
                INNER JOIN b_book_repository br ON bl_fav.amazon_id = br.asin
                INNER JOIN b_book_repository br2 ON br.author = br2.author
                LEFT JOIN b_book_list bl ON bl.amazon_id = br2.asin AND bl.user_id = ?
                WHERE bl_fav.user_id = ?
                AND bl_fav.rating = 5
                AND bl.book_id IS NULL
                AND br2.asin != br.asin
                AND br.author IS NOT NULL
                AND br.author != ''
                GROUP BY br2.asin, br2.title, br2.author, br2.image_url
                ORDER BY score DESC
                LIMIT ?
            ";
        } else {
            // b_book_favoritesテーブルを使用
            $sql = "
                SELECT 
                    br2.asin,
                    br2.title,
                    br2.author,
                    br2.image_url,
                    COUNT(DISTINCT bf.id) as favorite_count,
                    85 + COUNT(DISTINCT bf.id) * 5 as score
                FROM b_book_favorites bf
                INNER JOIN b_book_list bl_fav ON bf.book_id = bl_fav.book_id
                INNER JOIN b_book_repository br ON bl_fav.amazon_id = br.asin
                INNER JOIN b_book_repository br2 ON br.author = br2.author
                LEFT JOIN b_book_list bl ON bl.amazon_id = br2.asin AND bl.user_id = ?
                WHERE bf.user_id = ?
                AND bl.book_id IS NULL
                AND br2.asin != br.asin
                AND br.author IS NOT NULL
                AND br.author != ''
                GROUP BY br2.asin, br2.title, br2.author, br2.image_url
                ORDER BY score DESC
                LIMIT ?
            ";
        }
        
        $result = $this->db->getAll($sql, [$this->userId, $this->userId, $limit], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result)) {
            error_log('Favorite author books error: ' . $result->getMessage());
            return [];
        }
        
        return $result ?: [];
    }
    
    /**
     * 頻繁に読む著者の未読本を取得（作家クラウド）
     */
    private function getFrequentAuthorBooks(int $limit): array {
        // まず頻読著者を取得（著者名が空でないもののみ）
        $authorSql = "
            SELECT 
                br.author,
                COUNT(DISTINCT bl.book_id) as read_count
            FROM b_book_list bl
            INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.status IN (3, 4)
            AND br.author IS NOT NULL
            AND br.author != ''
            GROUP BY br.author
            HAVING read_count >= 2
            ORDER BY read_count DESC
            LIMIT 12
        ";
        
        $frequentAuthors = $this->db->getAll($authorSql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($frequentAuthors) || empty($frequentAuthors)) {
            return [];
        }
        
        $recommendations = [];
        
        foreach ($frequentAuthors as $authorData) {
            // 各著者の未読本を取得
            $bookSql = "
                SELECT 
                    br.asin,
                    br.title,
                    br.author,
                    br.image_url,
                    ? as read_count,
                    80 + ? * 3 as score
                FROM b_book_repository br
                LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                WHERE br.author = ?
                AND bl.book_id IS NULL
                ORDER BY RAND()
                LIMIT 3
            ";
            
            $books = $this->db->getAll($bookSql, [
                $authorData['read_count'],
                $authorData['read_count'],
                $this->userId,
                $authorData['author']
            ], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($books) && $books) {
                $recommendations = array_merge($recommendations, $books);
            }
        }
        
        // スコアでソート
        usort($recommendations, function($a, $b) {
            return ($b['score'] ?? 0) - ($a['score'] ?? 0);
        });
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * 高評価本に類似した本を取得（AI分析版）
     */
    private function getSimilarBooksWithAI(int $limit): array {
        // OpenAI APIが利用可能な場合はAI分析を使用
        if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
            try {
                $analyzer = new AISimilarityAnalyzer();
                return $analyzer->findSimilarBooks($this->userId, $limit);
            } catch (Exception $e) {
                error_log('AI Similarity Analysis failed, falling back to simple method: ' . $e->getMessage());
                // フォールバック
                return $this->getSimilarBooks($limit);
            }
        }
        
        // APIキーがない場合は従来の方法を使用
        return $this->getSimilarBooks($limit);
    }
    
    /**
     * 高評価本に類似した本を取得（従来版）
     */
    private function getSimilarBooks(int $limit): array {
        // より多様な高評価本を取得（最新と古い本を混ぜる）
        $highRatedSql = "
            (
                SELECT 
                    br.asin,
                    br.title,
                    br.author,
                    bl.rating,
                    bl.update_date
                FROM b_book_list bl
                INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND bl.rating >= 4
                ORDER BY bl.update_date DESC
                LIMIT 5
            )
            UNION
            (
                SELECT 
                    br.asin,
                    br.title,
                    br.author,
                    bl.rating,
                    bl.update_date
                FROM b_book_list bl
                INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND bl.rating >= 4
                ORDER BY bl.update_date ASC
                LIMIT 5
            )
            ORDER BY rating DESC, update_date DESC
            LIMIT 10
        ";
        
        $highRatedBooks = $this->db->getAll($highRatedSql, [$this->userId, $this->userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($highRatedBooks) || empty($highRatedBooks)) {
            return [];
        }
        
        $recommendations = [];
        $usedKeywords = []; // 重複キーワードを避ける
        
        foreach ($highRatedBooks as $baseBook) {
            // タイトルの類似性で検索（簡易版）
            $keywords = $this->extractKeywords($baseBook['title']);
            
            if (empty($keywords)) {
                continue;
            }
            
            // 既に使用したキーワードの組み合わせはスキップ
            $keywordHash = md5(implode('', $keywords));
            if (in_array($keywordHash, $usedKeywords)) {
                continue;
            }
            $usedKeywords[] = $keywordHash;
            
            $conditions = [];
            $params = [$this->userId];
            
            foreach ($keywords as $keyword) {
                if (mb_strlen($keyword) >= 2) {
                    $conditions[] = "br.title LIKE ?";
                    $params[] = '%' . $keyword . '%';
                }
            }
            
            if (empty($conditions)) {
                continue;
            }
            
            // 各ベース本から最大2冊までに制限
            $sql = "
                SELECT 
                    br.asin,
                    br.title,
                    br.author,
                    br.image_url,
                    ? as similar_to,
                    75 + ? * 2 as score
                FROM b_book_repository br
                LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                WHERE bl.book_id IS NULL
                AND br.asin != ?
                AND (" . implode(' OR ', $conditions) . ")
                ORDER BY RAND()
                LIMIT 2
            ";
            
            array_unshift($params, $baseBook['title'], $baseBook['rating']);
            $params[] = $baseBook['asin'];
            
            $similar = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($similar) && $similar) {
                $recommendations = array_merge($recommendations, $similar);
            }
            
            // 十分な推薦数が集まったら終了
            if (count($recommendations) >= $limit * 2) {
                break;
            }
        }
        
        // ランダムにシャッフルして多様性を確保
        shuffle($recommendations);
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * ジャンルベースの推薦
     */
    private function getGenreBasedBooks(int $limit): array {
        // ユーザーがよく読むジャンルを特定（簡易版：タイトルから推測）
        $genreKeywords = [
            'ミステリー' => ['ミステリ', '殺人', '探偵', '事件'],
            'ファンタジー' => ['ファンタジー', '魔法', '異世界', '冒険'],
            'SF' => ['SF', '宇宙', 'ロボット', '未来'],
            'ビジネス' => ['ビジネス', '経営', 'マーケティング', '仕事'],
            '小説' => ['小説', '物語', 'ストーリー']
        ];
        
        // ユーザーの読書傾向からジャンルを推定
        $userGenres = [];
        
        $historySql = "
            SELECT br.title
            FROM b_book_list bl
            INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.status IN (3, 4)
        ";
        
        $history = $this->db->getAll($historySql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($history) && $history) {
            foreach ($history as $book) {
                foreach ($genreKeywords as $genre => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (mb_stripos($book['title'], $keyword) !== false) {
                            $userGenres[$genre] = ($userGenres[$genre] ?? 0) + 1;
                        }
                    }
                }
            }
        }
        
        if (empty($userGenres)) {
            return [];
        }
        
        // 最も多いジャンルを特定
        arsort($userGenres);
        $topGenre = array_key_first($userGenres);
        $topKeywords = $genreKeywords[$topGenre];
        
        // そのジャンルの人気本を取得
        $conditions = [];
        $params = [];
        
        foreach ($topKeywords as $keyword) {
            $conditions[] = "br.title LIKE ?";
            $params[] = '%' . $keyword . '%';
        }
        
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.image_url,
                ? as genre,
                COUNT(DISTINCT bl_all.book_id) as reader_count,
                70 + COUNT(DISTINCT bl_all.book_id) as score
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
            LEFT JOIN b_book_list bl_all ON bl_all.amazon_id = br.asin
            WHERE bl.book_id IS NULL
            AND (" . implode(' OR ', $conditions) . ")
            GROUP BY br.asin, br.title, br.author, br.image_url
            ORDER BY reader_count DESC
            LIMIT ?
        ";
        
        // パラメータを正しい順序で配列に追加
        $allParams = [];
        $allParams[] = $topGenre;
        $allParams[] = $this->userId;
        $allParams = array_merge($allParams, $params);
        $allParams[] = $limit;
        
        $result = $this->db->getAll($sql, $allParams, DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result)) {
            error_log('Genre based books error: ' . $result->getMessage());
            return [];
        }
        
        return $result ?: [];
    }
    
    /**
     * タイトルからキーワードを抽出
     */
    private function extractKeywords(string $title): array {
        // 記号を除去
        $title = preg_replace('/[「」『』（）\(\)\[\]【】]/u', ' ', $title);
        
        // スペースで分割
        $words = preg_split('/[\s　]+/u', $title);
        
        // 2文字以上の単語を抽出
        $keywords = [];
        foreach ($words as $word) {
            if (mb_strlen($word) >= 2) {
                $keywords[] = $word;
            }
        }
        
        return array_slice($keywords, 0, 3); // 最大3キーワード
    }
}