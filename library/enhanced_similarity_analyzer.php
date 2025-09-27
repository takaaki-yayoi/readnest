<?php
/**
 * 強化版類似性分析クラス
 * ジャンル情報を活用した高精度な類似本提案
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');
require_once(dirname(__FILE__) . '/book_genre_detector.php');
require_once(dirname(__FILE__) . '/base_book_rotation.php');

class EnhancedSimilarityAnalyzer {
    private $db;
    private $genreDetector;
    private $apiKey;
    private $rotationManager;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        $this->genreDetector = new BookGenreDetector();
        
        if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
            $this->apiKey = OPENAI_API_KEY;
        }
    }
    
    /**
     * 改良版：類似本を見つける
     */
    public function findSimilarBooks(int $userId, int $limit = 15): array {
        // ローテーションマネージャーを初期化
        $this->rotationManager = new BaseBookRotation($userId);
        
        // 1. ユーザーの高評価本を取得（ジャンル情報付き）
        $baseBooks = $this->getBaseBookWithGenres($userId);
        
        // 使用したベース本を記録
        if (!empty($baseBooks)) {
            $this->rotationManager->recordBaseBooks(array_column($baseBooks, 'asin'));
        }
        
        if (empty($baseBooks)) {
            return [];
        }
        
        // 2. ジャンルごとにグループ化
        $booksByGenre = $this->groupBooksByGenre($baseBooks);
        
        // 3. 各ジャンルから類似本を取得
        $recommendations = [];
        
        foreach ($booksByGenre as $genre => $books) {
            $genreRecs = $this->findSimilarBooksInGenre($userId, $genre, $books, (int)ceil($limit / count($booksByGenre)));
            $recommendations = array_merge($recommendations, $genreRecs);
        }
        
        // 4. スコアでソート
        usort($recommendations, function($a, $b) {
            return ($b['score'] ?? 0) - ($a['score'] ?? 0);
        });
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * ジャンル情報付きでベース本を取得（多様性重視・重複回避）
     */
    private function getBaseBookWithGenres(int $userId): array {
        // ローテーションマネージャーから除外リストを取得
        if (!isset($this->rotationManager)) {
            $this->rotationManager = new BaseBookRotation($userId);
        }
        
        $excludeAsins = $this->rotationManager->getExclusionList();
        
        // 古い履歴をクリーンアップ（定期的に実行）
        if (rand(1, 100) <= 5) { // 5%の確率で実行
            $this->rotationManager->cleanupOldHistory();
        }
        
        // 多様なベース本を取得するため、複数の条件で取得
        $sqls = [
            // 1. 最近読んだ5つ星の本（除外リスト付き）
            "SELECT br.asin, br.title, br.author, bl.rating, bl.update_date, '最近の5つ星' as source
             FROM b_book_list bl
             INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
             WHERE bl.user_id = ? AND bl.rating = 5
             " . $this->getExclusionClause($excludeAsins) . "
             ORDER BY bl.update_date DESC
             LIMIT 10",
            
            // 2. 最近読んだ4つ星の本（除外リスト付き）
            "SELECT br.asin, br.title, br.author, bl.rating, bl.update_date, '最近の4つ星' as source
             FROM b_book_list bl
             INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
             WHERE bl.user_id = ? AND bl.rating = 4
             " . $this->getExclusionClause($excludeAsins) . "
             ORDER BY bl.update_date DESC
             LIMIT 10",
            
            // 3. 3ヶ月以上前の高評価本（ローテーション用）
            "SELECT br.asin, br.title, br.author, bl.rating, bl.update_date, '過去の高評価' as source
             FROM b_book_list bl
             INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
             WHERE bl.user_id = ? AND bl.rating >= 4
             AND bl.update_date < DATE_SUB(NOW(), INTERVAL 3 MONTH)
             " . $this->getExclusionClause($excludeAsins) . "
             ORDER BY bl.update_date DESC
             LIMIT 8",
            
            // 4. まだ使われていない高評価本（ランダム）
            "SELECT br.asin, br.title, br.author, bl.rating, bl.update_date, 'ランダム未使用' as source
             FROM b_book_list bl
             INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
             WHERE bl.user_id = ? AND bl.rating >= 4
             " . $this->getExclusionClause($excludeAsins) . "
             ORDER BY RAND()
             LIMIT 10",
            
            // 5. 異なる著者の高評価本（著者の多様性確保）
            "SELECT br.asin, br.title, br.author, bl.rating, bl.update_date, '異なる著者' as source
             FROM b_book_list bl
             INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
             WHERE bl.user_id = ? AND bl.rating >= 4
             " . $this->getExclusionClause($excludeAsins) . "
             GROUP BY br.author
             ORDER BY MAX(bl.rating) DESC, MAX(bl.update_date) DESC
             LIMIT 10"
        ];
        
        $allBooks = [];
        $seenAsins = [];
        
        foreach ($sqls as $sql) {
            $books = $this->db->getAll($sql, [$userId], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($books) && $books) {
                foreach ($books as $book) {
                    // 重複を避ける
                    if (!in_array($book['asin'], $seenAsins)) {
                        $allBooks[] = $book;
                        $seenAsins[] = $book['asin'];
                    }
                }
            }
        }
        
        // ローテーションマネージャーで最適化
        if (!empty($allBooks)) {
            $allBooks = $this->rotationManager->optimizeBaseBookSelection($allBooks);
        }
        
        // 最大25冊に制限（処理時間を考慮）
        $allBooks = array_slice($allBooks, 0, 25);
        
        if (empty($allBooks)) {
            // 高評価本がない場合は読了本全体から取得
            $fallbackSql = "
                SELECT br.asin, br.title, br.author, bl.rating, bl.update_date, 'フォールバック' as source
                FROM b_book_list bl
                INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ? AND bl.status IN (3, 4)
                ORDER BY bl.update_date DESC
                LIMIT 20
            ";
            
            $allBooks = $this->db->getAll($fallbackSql, [$userId], DB_FETCHMODE_ASSOC);
            
            if (DB::isError($allBooks) || empty($allBooks)) {
                return [];
            }
        }
        
        // 各本のジャンルを検出
        foreach ($allBooks as &$book) {
            $genreInfo = $this->genreDetector->detectGenre($book['title'], $book['author']);
            $book['genres'] = $genreInfo['genres'];
            $book['genre_confidence'] = $genreInfo['confidence'];
            $book['description'] = $genreInfo['description'];
            
            // デバッグ情報
            error_log("Base book: {$book['title']} - Genres: " . implode(',', $book['genres']) . " (source: {$book['source']})");
        }
        
        return $allBooks;
    }
    
    /**
     * ジャンルごとに本をグループ化
     */
    private function groupBooksByGenre(array $books): array {
        $grouped = [];
        
        foreach ($books as $book) {
            if (empty($book['genres'])) {
                $book['genres'] = ['unknown'];
            }
            
            foreach ($book['genres'] as $genre) {
                if (!isset($grouped[$genre])) {
                    $grouped[$genre] = [];
                }
                $grouped[$genre][] = $book;
            }
        }
        
        return $grouped;
    }
    
    /**
     * 特定ジャンル内で類似本を検索
     */
    private function findSimilarBooksInGenre(int $userId, string $genre, array $baseBooks, int $limit): array {
        // ジャンル特有の検索戦略を使用
        switch ($genre) {
            case 'fiction':
                return $this->findSimilarFiction($userId, $baseBooks, $limit);
            
            case 'technical':
                return $this->findSimilarTechnicalBooks($userId, $baseBooks, $limit);
            
            case 'educational':
            case 'language':
                return $this->findSimilarEducationalBooks($userId, $baseBooks, $limit);
            
            case 'business':
                return $this->findSimilarBusinessBooks($userId, $baseBooks, $limit);
            
            case 'cooking':
                return $this->findSimilarCookingBooks($userId, $baseBooks, $limit);
            
            default:
                return $this->findSimilarByKeywords($userId, $baseBooks, $limit);
        }
    }
    
    /**
     * 小説の類似本を検索
     */
    private function findSimilarFiction(int $userId, array $baseBooks, int $limit): array {
        $recommendations = [];
        $seenAsins = [];
        $processedAuthors = [];
        
        // 最大10冊のベース本を処理（処理時間とのバランス）
        $baseBooksToProcess = array_slice($baseBooks, 0, 10);
        
        foreach ($baseBooksToProcess as $baseBook) {
            // 同じ著者の他の作品（著者ごとに1回のみ）
            if (!empty($baseBook['author']) && !in_array($baseBook['author'], $processedAuthors)) {
                $authorBooks = $this->findByAuthor($userId, $baseBook['author'], $baseBook['asin'], 2);
                foreach ($authorBooks as &$book) {
                    if (!in_array($book['asin'], $seenAsins)) {
                        $book['reason'] = "『{$baseBook['title']}』と同じ{$baseBook['author']}の作品";
                        $book['score'] = 85;
                        $book['similar_to'] = $baseBook['title'];
                        $recommendations[] = $book;
                        $seenAsins[] = $book['asin'];
                    }
                }
                $processedAuthors[] = $baseBook['author'];
            }
            
            // 同じ出版社レーベルの人気作品
            if (preg_match('/(文庫|新書|単行本)/', $baseBook['title'], $matches)) {
                $label = $matches[1];
                $labelBooks = $this->findByLabel($userId, $label, $baseBook['asin'], 1);
                foreach ($labelBooks as &$book) {
                    if (!in_array($book['asin'], $seenAsins)) {
                        $book['reason'] = "『{$baseBook['title']}』と同じ{$label}シリーズの人気作";
                        $book['score'] = 75;
                        $book['similar_to'] = $baseBook['title'];
                        $recommendations[] = $book;
                        $seenAsins[] = $book['asin'];
                    }
                }
            }
            
            // 十分な推薦数が集まったら早期終了
            if (count($recommendations) >= $limit * 2) {
                break;
            }
        }
        
        // スコアでソートして上位を返す
        usort($recommendations, function($a, $b) {
            return ($b['score'] ?? 0) - ($a['score'] ?? 0);
        });
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * 技術書の類似本を検索
     */
    private function findSimilarTechnicalBooks(int $userId, array $baseBooks, int $limit): array {
        $recommendations = [];
        $seenAsins = [];
        $allKeywords = [];
        
        // 最大8冊のベース本を処理
        $baseBooksToProcess = array_slice($baseBooks, 0, 8);
        
        foreach ($baseBooksToProcess as $baseBook) {
            // タイトルから技術キーワードを抽出
            $techKeywords = $this->extractTechnicalKeywords($baseBook['title']);
            
            if (!empty($techKeywords)) {
                // キーワードを集約（重複を避ける）
                foreach ($techKeywords as $keyword) {
                    if (!isset($allKeywords[$keyword])) {
                        $allKeywords[$keyword] = [];
                    }
                    $allKeywords[$keyword][] = $baseBook['title'];
                }
                
                $sql = "
                    SELECT DISTINCT
                        br.asin,
                        br.title,
                        br.author,
                        br.image_url
                    FROM b_book_repository br
                    LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                    WHERE bl.book_id IS NULL
                    AND br.asin != ?
                    AND (
                ";
                
                $params = [$userId, $baseBook['asin']];
                $conditions = [];
                
                foreach ($techKeywords as $keyword) {
                    $conditions[] = "br.title LIKE ?";
                    $params[] = '%' . $keyword . '%';
                }
                
                $sql .= implode(' OR ', $conditions) . ")
                    ORDER BY LENGTH(br.title) ASC
                    LIMIT 3";
                
                $similarBooks = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
                
                if (!DB::isError($similarBooks) && $similarBooks) {
                    foreach ($similarBooks as &$book) {
                        if (!in_array($book['asin'], $seenAsins)) {
                            $book['reason'] = "『{$baseBook['title']}』と同じく" . implode('/', $techKeywords) . "を扱う技術書";
                            $book['score'] = 80 + count($techKeywords) * 2;
                            $book['similar_to'] = $baseBook['title'];
                            $recommendations[] = $book;
                            $seenAsins[] = $book['asin'];
                        }
                    }
                }
            }
        }
        
        // キーワードベースで追加検索（まとめて検索）
        if (!empty($allKeywords) && count($recommendations) < $limit) {
            $topKeywords = array_slice(array_keys($allKeywords), 0, 5);
            $sql = "
                SELECT DISTINCT
                    br.asin,
                    br.title,
                    br.author,
                    br.image_url
                FROM b_book_repository br
                LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                INNER JOIN b_book_list bl_pop ON bl_pop.amazon_id = br.asin
                WHERE bl.book_id IS NULL
                AND (
            ";
            
            $params = [$userId];
            $conditions = [];
            
            foreach ($topKeywords as $keyword) {
                $conditions[] = "br.title LIKE ?";
                $params[] = '%' . $keyword . '%';
            }
            
            $sql .= implode(' OR ', $conditions) . ")
                GROUP BY br.asin
                HAVING COUNT(DISTINCT bl_pop.user_id) >= 2
                ORDER BY COUNT(DISTINCT bl_pop.user_id) DESC
                LIMIT " . ($limit - count($recommendations));
            
            $additionalBooks = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($additionalBooks) && $additionalBooks) {
                foreach ($additionalBooks as &$book) {
                    if (!in_array($book['asin'], $seenAsins)) {
                        $relatedTitles = [];
                        foreach ($topKeywords as $keyword) {
                            if (stripos($book['title'], $keyword) !== false && isset($allKeywords[$keyword])) {
                                $relatedTitles = array_merge($relatedTitles, $allKeywords[$keyword]);
                            }
                        }
                        $book['reason'] = "技術書：" . implode('/', array_unique($topKeywords)) . "関連";
                        $book['score'] = 75;
                        $book['similar_to'] = implode(', ', array_unique(array_slice($relatedTitles, 0, 2)));
                        $recommendations[] = $book;
                        $seenAsins[] = $book['asin'];
                    }
                }
            }
        }
        
        // スコアでソート
        usort($recommendations, function($a, $b) {
            return ($b['score'] ?? 0) - ($a['score'] ?? 0);
        });
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * 教育書・語学書の類似本を検索
     */
    private function findSimilarEducationalBooks(int $userId, array $baseBooks, int $limit): array {
        $recommendations = [];
        $seenAsins = [];
        $languageBooks = [];
        $otherEducational = [];
        
        // 最大8冊のベース本を処理
        $baseBooksToProcess = array_slice($baseBooks, 0, 8);
        
        foreach ($baseBooksToProcess as $baseBook) {
            $titleLower = mb_strtolower($baseBook['title']);
            
            // 言語学習書の判定と分類
            if (stripos($titleLower, 'word power') !== false || 
                stripos($titleLower, '英語') !== false ||
                stripos($titleLower, 'english') !== false ||
                stripos($titleLower, 'toeic') !== false ||
                stripos($titleLower, 'vocabulary') !== false) {
                $languageBooks[] = $baseBook;
            } else {
                $otherEducational[] = $baseBook;
            }
        }
        
        // 言語学習書ベースの推薦
        if (!empty($languageBooks)) {
            // 複数の言語学習書から共通のパターンを抽出
            $langKeywords = [];
            foreach ($languageBooks as $book) {
                if (stripos($book['title'], 'TOEIC') !== false) $langKeywords['TOEIC'] = true;
                if (stripos($book['title'], '単語') !== false) $langKeywords['単語'] = true;
                if (stripos($book['title'], '語彙') !== false) $langKeywords['語彙'] = true;
                if (stripos($book['title'], 'Grammar') !== false) $langKeywords['Grammar'] = true;
                if (stripos($book['title'], 'Vocabulary') !== false) $langKeywords['Vocabulary'] = true;
                if (stripos($book['title'], '初級') !== false) $langKeywords['初級'] = true;
                if (stripos($book['title'], '中級') !== false) $langKeywords['中級'] = true;
                if (stripos($book['title'], '上級') !== false) $langKeywords['上級'] = true;
            }
            
            $sql = "
                SELECT DISTINCT
                    br.asin,
                    br.title,
                    br.author,
                    br.image_url
                FROM b_book_repository br
                LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                INNER JOIN b_book_list bl_pop ON bl_pop.amazon_id = br.asin
                WHERE bl.book_id IS NULL
                AND (
                    br.title LIKE '%英語%'
                    OR br.title LIKE '%English%'
                    OR br.title LIKE '%TOEIC%'
                    OR br.title LIKE '%TOEFL%'
                    OR br.title LIKE '%語彙%'
                    OR br.title LIKE '%単語%'
                    OR br.title LIKE '%Grammar%'
                    OR br.title LIKE '%Vocabulary%'
                    OR br.title LIKE '%リスニング%'
                    OR br.title LIKE '%Listening%'
                )
                GROUP BY br.asin
                HAVING COUNT(DISTINCT bl_pop.user_id) >= 2
                ORDER BY COUNT(DISTINCT bl_pop.user_id) DESC
                LIMIT 10
            ";
            
            $langRecs = $this->db->getAll($sql, [$userId], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($langRecs) && $langRecs) {
                foreach ($langRecs as &$book) {
                    if (!in_array($book['asin'], $seenAsins)) {
                        // より具体的な理由を生成
                        $matchedKeywords = [];
                        foreach (array_keys($langKeywords) as $keyword) {
                            if (stripos($book['title'], $keyword) !== false) {
                                $matchedKeywords[] = $keyword;
                            }
                        }
                        
                        $baseBookTitles = array_slice(array_column($languageBooks, 'title'), 0, 2);
                        $book['reason'] = "語学学習書：" . (!empty($matchedKeywords) ? implode('・', $matchedKeywords) . "を強化" : "英語力向上");
                        $book['score'] = 85 + count($matchedKeywords) * 2;
                        $book['similar_to'] = implode(', ', $baseBookTitles);
                        $recommendations[] = $book;
                        $seenAsins[] = $book['asin'];
                    }
                }
            }
        }
        
        // その他の教育書ベースの推薦
        if (!empty($otherEducational)) {
            foreach ($otherEducational as $baseBook) {
                // 数学、科学、プログラミング入門などの教育書
                $eduKeywords = $this->extractEducationalKeywords($baseBook['title']);
                
                if (!empty($eduKeywords)) {
                    $sql = "
                        SELECT DISTINCT
                            br.asin,
                            br.title,
                            br.author,
                            br.image_url
                        FROM b_book_repository br
                        LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                        WHERE bl.book_id IS NULL
                        AND br.asin != ?
                        AND (
                    ";
                    
                    $params = [$userId, $baseBook['asin']];
                    $conditions = [];
                    
                    foreach ($eduKeywords as $keyword) {
                        $conditions[] = "br.title LIKE ?";
                        $params[] = '%' . $keyword . '%';
                    }
                    
                    $sql .= implode(' OR ', $conditions) . ")
                        ORDER BY RAND()
                        LIMIT 2";
                    
                    $eduBooks = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
                    
                    if (!DB::isError($eduBooks) && $eduBooks) {
                        foreach ($eduBooks as &$book) {
                            if (!in_array($book['asin'], $seenAsins)) {
                                $book['reason'] = "『{$baseBook['title']}』と同様の" . implode('・', $eduKeywords) . "学習書";
                                $book['score'] = 80;
                                $book['similar_to'] = $baseBook['title'];
                                $recommendations[] = $book;
                                $seenAsins[] = $book['asin'];
                            }
                        }
                    }
                }
            }
        }
        
        // スコアでソート
        usort($recommendations, function($a, $b) {
            return ($b['score'] ?? 0) - ($a['score'] ?? 0);
        });
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * 教育系キーワードを抽出
     */
    private function extractEducationalKeywords(string $title): array {
        $keywords = [];
        
        $eduTerms = [
            '数学', '物理', '化学', '生物', '地理', '歴史',
            '入門', '基礎', '初級', '中級', '上級',
            '教科書', '参考書', '問題集', '演習',
            'Mathematics', 'Physics', 'Chemistry', 'Biology',
            'Introduction', 'Beginner', 'Intermediate', 'Advanced'
        ];
        
        foreach ($eduTerms as $term) {
            if (stripos($title, $term) !== false) {
                $keywords[] = $term;
            }
        }
        
        return array_slice($keywords, 0, 3);
    }
    
    /**
     * ビジネス書の類似本を検索
     */
    private function findSimilarBusinessBooks(int $userId, array $baseBooks, int $limit): array {
        $recommendations = [];
        
        foreach ($baseBooks as $baseBook) {
            $businessKeywords = ['経営', 'マネジメント', 'リーダー', '戦略', 'マーケティング'];
            
            $sql = "
                SELECT DISTINCT
                    br.asin,
                    br.title,
                    br.author,
                    br.image_url
                FROM b_book_repository br
                LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                INNER JOIN b_book_list bl_pop ON bl_pop.amazon_id = br.asin
                WHERE bl.book_id IS NULL
                AND br.asin != ?
                AND (
            ";
            
            $params = [$userId, $baseBook['asin']];
            $conditions = [];
            
            foreach ($businessKeywords as $keyword) {
                $conditions[] = "br.title LIKE ?";
                $params[] = '%' . $keyword . '%';
            }
            
            $sql .= implode(' OR ', $conditions) . ")
                GROUP BY br.asin
                HAVING COUNT(DISTINCT bl_pop.user_id) >= 3
                ORDER BY COUNT(DISTINCT bl_pop.user_id) DESC
                LIMIT 5";
            
            $bizBooks = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($bizBooks) && $bizBooks) {
                foreach ($bizBooks as &$book) {
                    $book['reason'] = "『{$baseBook['title']}』と同じビジネス分野の人気書籍";
                    $book['score'] = 75;
                    $book['similar_to'] = $baseBook['title'];
                }
                $recommendations = array_merge($recommendations, $bizBooks);
            }
        }
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * 料理本の類似本を検索
     */
    private function findSimilarCookingBooks(int $userId, array $baseBooks, int $limit): array {
        $recommendations = [];
        
        foreach ($baseBooks as $baseBook) {
            $sql = "
                SELECT DISTINCT
                    br.asin,
                    br.title,
                    br.author,
                    br.image_url
                FROM b_book_repository br
                LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
                WHERE bl.book_id IS NULL
                AND br.asin != ?
                AND (
                    br.title LIKE '%料理%'
                    OR br.title LIKE '%レシピ%'
                    OR br.title LIKE '%クッキング%'
                    OR br.title LIKE '%食%'
                    OR br.title LIKE '%おかず%'
                    OR br.title LIKE '%弁当%'
                )
                ORDER BY RAND()
                LIMIT 5
            ";
            
            $cookBooks = $this->db->getAll($sql, [$userId, $baseBook['asin']], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($cookBooks) && $cookBooks) {
                foreach ($cookBooks as &$book) {
                    $book['reason'] = "『{$baseBook['title']}』と同じ料理・レシピ本";
                    $book['score'] = 80;
                    $book['similar_to'] = $baseBook['title'];
                }
                $recommendations = array_merge($recommendations, $cookBooks);
            }
        }
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * キーワードベースの類似本検索（フォールバック）
     */
    private function findSimilarByKeywords(int $userId, array $baseBooks, int $limit): array {
        // 既存のキーワードマッチング処理
        return [];
    }
    
    /**
     * 著者で検索
     */
    private function findByAuthor(int $userId, string $author, string $excludeAsin, int $limit): array {
        if (empty($author)) {
            return [];
        }
        
        $sql = "
            SELECT DISTINCT
                br.asin,
                br.title,
                br.author,
                br.image_url
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
            WHERE bl.book_id IS NULL
            AND br.author = ?
            AND br.asin != ?
            ORDER BY br.title
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$userId, $author, $excludeAsin, $limit], DB_FETCHMODE_ASSOC);
        
        return DB::isError($books) ? [] : $books;
    }
    
    /**
     * 出版社レーベルで検索
     */
    private function findByLabel(int $userId, string $label, string $excludeAsin, int $limit): array {
        $sql = "
            SELECT DISTINCT
                br.asin,
                br.title,
                br.author,
                br.image_url
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
            INNER JOIN b_book_list bl_pop ON bl_pop.amazon_id = br.asin
            WHERE bl.book_id IS NULL
            AND br.title LIKE ?
            AND br.asin != ?
            GROUP BY br.asin
            HAVING COUNT(DISTINCT bl_pop.user_id) >= 3
            ORDER BY COUNT(DISTINCT bl_pop.user_id) DESC
            LIMIT ?
        ";
        
        $books = $this->db->getAll($sql, [$userId, '%' . $label . '%', $excludeAsin, $limit], DB_FETCHMODE_ASSOC);
        
        return DB::isError($books) ? [] : $books;
    }
    
    /**
     * 技術キーワードを抽出
     */
    private function extractTechnicalKeywords(string $title): array {
        $keywords = [];
        
        // 一般的な技術用語
        $techTerms = [
            'Python', 'Java', 'JavaScript', 'PHP', 'Ruby', 'Go', 'Rust',
            'SQL', 'NoSQL', 'Database', 'データベース',
            'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes',
            'Machine Learning', '機械学習', 'AI', 'Deep Learning',
            'Architecture', 'アーキテクチャ', 'Design', '設計',
            'Lakehouse', 'Data', 'Analytics'
        ];
        
        foreach ($techTerms as $term) {
            if (stripos($title, $term) !== false) {
                $keywords[] = $term;
            }
        }
        
        return array_slice($keywords, 0, 3); // 最大3キーワード
    }
    
    /**
     * 除外句を生成（前回使用したベース本を除外）
     */
    private function getExclusionClause(array $excludeAsins): string {
        if (empty($excludeAsins)) {
            return "";
        }
        
        // 安全なSQLのため、ASINをエスケープ
        $escapedAsins = array_map(function($asin) {
            return "'" . addslashes($asin) . "'";
        }, $excludeAsins);
        
        return " AND br.asin NOT IN (" . implode(',', $escapedAsins) . ")";
    }
}