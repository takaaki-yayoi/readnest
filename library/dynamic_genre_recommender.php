<?php
/**
 * 動的ジャンル推薦システム
 * ユーザーの読書履歴から動的にジャンルを特定し、それに応じた推薦を行う
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');
require_once(dirname(__FILE__) . '/book_genre_detector.php');

class DynamicGenreRecommender {
    private $db;
    private $genreDetector;
    private $userId;
    
    public function __construct(int $userId) {
        global $g_db;
        $this->db = $g_db;
        $this->userId = $userId;
        $this->genreDetector = new BookGenreDetector();
    }
    
    /**
     * ユーザーの読書履歴からジャンルプロファイルを動的に生成
     */
    public function analyzeUserGenreProfile(): array {
        // ユーザーの全読書履歴を取得
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                bl.rating,
                bl.status,
                bl.update_date
            FROM b_book_list bl
            INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.status IN (3, 4)
            ORDER BY bl.update_date DESC
            LIMIT 100
        ";
        
        $books = $this->db->getAll($sql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books) || empty($books)) {
            return [];
        }
        
        // ジャンル統計を収集
        $genreStats = [];
        $genreKeywords = [];
        $genreAuthors = [];
        $genreExamples = [];
        
        foreach ($books as $book) {
            // タイトルと著者からジャンルを推定
            $genres = $this->detectDetailedGenres($book['title'], $book['author']);
            
            foreach ($genres as $genre) {
                if (!isset($genreStats[$genre])) {
                    $genreStats[$genre] = [
                        'count' => 0,
                        'total_rating' => 0,
                        'avg_rating' => 0,
                        'keywords' => [],
                        'authors' => [],
                        'examples' => [],
                        'recent_count' => 0
                    ];
                }
                
                $genreStats[$genre]['count']++;
                $genreStats[$genre]['total_rating'] += $book['rating'] ?? 3;
                
                // 最近読んだ本（3ヶ月以内）
                if (strtotime($book['update_date']) > strtotime('-3 months')) {
                    $genreStats[$genre]['recent_count']++;
                }
                
                // 著者を記録
                if (!empty($book['author'])) {
                    if (!isset($genreStats[$genre]['authors'][$book['author']])) {
                        $genreStats[$genre]['authors'][$book['author']] = 0;
                    }
                    $genreStats[$genre]['authors'][$book['author']]++;
                }
                
                // サンプル本を記録（最大5冊）
                if (count($genreStats[$genre]['examples']) < 5) {
                    $genreStats[$genre]['examples'][] = [
                        'title' => $book['title'],
                        'author' => $book['author'],
                        'rating' => $book['rating']
                    ];
                }
                
                // キーワードを抽出
                $keywords = $this->extractGenreKeywords($book['title'], $genre);
                foreach ($keywords as $keyword) {
                    if (!isset($genreStats[$genre]['keywords'][$keyword])) {
                        $genreStats[$genre]['keywords'][$keyword] = 0;
                    }
                    $genreStats[$genre]['keywords'][$keyword]++;
                }
            }
        }
        
        // 平均評価を計算し、重要度スコアを算出
        foreach ($genreStats as $genre => &$stats) {
            $stats['avg_rating'] = $stats['total_rating'] / $stats['count'];
            
            // ジャンルの重要度スコア（複合指標）
            $stats['importance_score'] = 
                ($stats['count'] * 2) +                    // 読書数の重み
                ($stats['avg_rating'] * 10) +              // 評価の重み
                ($stats['recent_count'] * 3) +             // 最近の興味の重み
                (count($stats['authors']) * 1);            // 著者の多様性
            
            // 上位キーワードを抽出
            arsort($stats['keywords']);
            $stats['top_keywords'] = array_slice(array_keys($stats['keywords']), 0, 10);
            
            // 上位著者を抽出
            arsort($stats['authors']);
            $stats['top_authors'] = array_slice(array_keys($stats['authors']), 0, 5);
        }
        
        // 重要度でソート
        uasort($genreStats, function($a, $b) {
            return $b['importance_score'] - $a['importance_score'];
        });
        
        return $genreStats;
    }
    
    /**
     * 詳細なジャンル検出（動的カテゴリ対応）
     */
    private function detectDetailedGenres(string $title, string $author): array {
        $genres = [];
        $titleLower = mb_strtolower($title);
        
        // 動的ジャンルマッピング（拡張可能）
        $genrePatterns = [
            // 小説系
            'mystery' => ['ミステリ', '推理', '探偵', '事件', '殺人'],
            'fantasy' => ['ファンタジー', '魔法', '異世界', '冒険', '勇者'],
            'romance' => ['恋愛', 'ロマンス', '恋', '愛'],
            'sf' => ['SF', '宇宙', 'ロボット', '未来', 'サイエンスフィクション'],
            'horror' => ['ホラー', '恐怖', '怪談', 'ゾンビ'],
            'literary' => ['文学', '純文学', '芥川賞', '直木賞'],
            
            // 実用書系
            'business' => ['ビジネス', '経営', 'マネジメント', 'リーダー', '起業', 'マーケティング'],
            'self-help' => ['自己啓発', '成功', '習慣', '人生', '生き方'],
            'finance' => ['投資', '金融', '株', 'FX', '資産運用'],
            'technology' => ['プログラミング', 'エンジニア', 'AI', '機械学習', 'データ'],
            'design' => ['デザイン', 'UI', 'UX', 'グラフィック', 'Web'],
            
            // 学習系
            'language' => ['英語', 'TOEIC', '語学', '中国語', '韓国語', 'English'],
            'math' => ['数学', '算数', '統計', '微分', '積分'],
            'science' => ['科学', '物理', '化学', '生物', '宇宙'],
            'history' => ['歴史', '日本史', '世界史', '戦国', '明治'],
            
            // 趣味系
            'cooking' => ['料理', 'レシピ', 'クッキング', '弁当', 'スイーツ'],
            'travel' => ['旅行', '旅', '紀行', 'ガイド'],
            'sports' => ['スポーツ', '野球', 'サッカー', 'ゴルフ', 'ランニング'],
            'art' => ['芸術', 'アート', '美術', '絵画', '写真'],
            'music' => ['音楽', '楽譜', 'ギター', 'ピアノ', 'ジャズ'],
            
            // その他
            'psychology' => ['心理', 'メンタル', 'カウンセリング', '発達'],
            'philosophy' => ['哲学', '思想', '倫理', '宗教'],
            'health' => ['健康', '医学', '病気', 'ダイエット', 'フィットネス'],
            'parenting' => ['育児', '子育て', '教育', '親子'],
            'essay' => ['エッセイ', '随筆', 'コラム', '日記'],
            'biography' => ['伝記', '自伝', '評伝', '人物'],
            'manga' => ['漫画', 'マンガ', 'コミック', 'まんが']
        ];
        
        // パターンマッチング
        foreach ($genrePatterns as $genre => $patterns) {
            foreach ($patterns as $pattern) {
                if (mb_stripos($titleLower, $pattern) !== false) {
                    $genres[] = $genre;
                    break;
                }
            }
        }
        
        // 著者ベースのジャンル推定
        if (!empty($author)) {
            $authorGenres = $this->getAuthorGenres($author);
            $genres = array_merge($genres, $authorGenres);
        }
        
        // ジャンルが特定できない場合
        if (empty($genres)) {
            // 出版社レーベルから推測
            if (preg_match('/(文庫|新書)/', $title)) {
                $genres[] = 'literary';
            } else {
                $genres[] = 'general';
            }
        }
        
        return array_unique($genres);
    }
    
    /**
     * 著者からジャンルを推定
     */
    private function getAuthorGenres(string $author): array {
        $authorGenreMap = [
            // 小説家
            '湊かなえ' => ['mystery'],
            '東野圭吾' => ['mystery'],
            '村上春樹' => ['literary'],
            '宮部みゆき' => ['mystery'],
            '伊坂幸太郎' => ['mystery', 'literary'],
            
            // ビジネス書著者
            'ドラッカー' => ['business'],
            '大前研一' => ['business'],
            
            // その他
            // 動的に追加可能
        ];
        
        $genres = [];
        foreach ($authorGenreMap as $knownAuthor => $authorGenres) {
            if (mb_stripos($author, $knownAuthor) !== false) {
                $genres = array_merge($genres, $authorGenres);
            }
        }
        
        return $genres;
    }
    
    /**
     * ジャンル固有のキーワードを抽出
     */
    private function extractGenreKeywords(string $title, string $genre): array {
        $keywords = [];
        
        // タイトルを形態素解析的に分割（簡易版）
        $words = preg_split('/[\s　\[\]「」『』（）\(\)]+/u', $title);
        
        foreach ($words as $word) {
            // 2文字以上の実質的な単語
            if (mb_strlen($word) >= 2 && !in_array($word, ['です', 'ます', 'する', 'なる', 'ある'])) {
                $keywords[] = $word;
            }
        }
        
        return array_slice($keywords, 0, 5);
    }
    
    /**
     * 動的ジャンルに基づいた推薦を生成
     */
    public function generateRecommendations(array $genreProfile, int $limit = 20): array {
        $recommendations = [];
        $seenAsins = [];
        
        // 上位ジャンルから推薦を生成
        $topGenres = array_slice($genreProfile, 0, 5);
        $perGenreLimit = (int)ceil($limit / count($topGenres));
        
        foreach ($topGenres as $genre => $stats) {
            error_log("Processing genre: $genre with importance score: {$stats['importance_score']}");
            
            // ジャンル固有の推薦戦略を動的に選択
            $genreRecs = $this->findBooksForGenre(
                $genre,
                $stats['top_keywords'],
                $stats['top_authors'],
                $stats['examples'],
                $perGenreLimit
            );
            
            foreach ($genreRecs as &$rec) {
                if (!in_array($rec['asin'], $seenAsins)) {
                    $rec['genre'] = $genre;
                    $rec['genre_importance'] = $stats['importance_score'];
                    $recommendations[] = $rec;
                    $seenAsins[] = $rec['asin'];
                }
            }
        }
        
        // スコアでソート
        usort($recommendations, function($a, $b) {
            // ジャンル重要度も考慮
            $scoreA = ($a['score'] ?? 0) + ($a['genre_importance'] ?? 0) / 10;
            $scoreB = ($b['score'] ?? 0) + ($b['genre_importance'] ?? 0) / 10;
            return $scoreB - $scoreA;
        });
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * 特定ジャンルの本を検索
     */
    private function findBooksForGenre(
        string $genre,
        array $keywords,
        array $authors,
        array $examples,
        int $limit
    ): array {
        $recommendations = [];
        
        // 1. 同じ著者の本を検索
        if (!empty($authors)) {
            $authorBooks = $this->findByAuthors($authors, ceil($limit / 3));
            foreach ($authorBooks as &$book) {
                $book['reason'] = "よく読む著者の作品";
                $book['score'] = 85;
            }
            $recommendations = array_merge($recommendations, $authorBooks);
        }
        
        // 2. キーワードベースの検索
        if (!empty($keywords)) {
            $keywordBooks = $this->findByKeywords($keywords, ceil($limit / 3));
            foreach ($keywordBooks as &$book) {
                $book['reason'] = "関連キーワード: " . implode('、', array_slice($keywords, 0, 3));
                $book['score'] = 75;
            }
            $recommendations = array_merge($recommendations, $keywordBooks);
        }
        
        // 3. 人気本から検索
        $popularBooks = $this->findPopularInGenre($genre, $keywords, ceil($limit / 3));
        foreach ($popularBooks as &$book) {
            $book['reason'] = "{$genre}ジャンルの人気作品";
            $book['score'] = 70;
        }
        $recommendations = array_merge($recommendations, $popularBooks);
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * 著者リストから本を検索
     */
    private function findByAuthors(array $authors, int $limit): array {
        if (empty($authors)) {
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
            AND br.author IN (" . implode(',', array_fill(0, count($authors), '?')) . ")
            ORDER BY RAND()
            LIMIT ?
        ";
        
        $params = array_merge([$this->userId], $authors, [$limit]);
        $books = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
        
        return DB::isError($books) ? [] : $books;
    }
    
    /**
     * キーワードリストから本を検索
     */
    private function findByKeywords(array $keywords, int $limit): array {
        if (empty($keywords)) {
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
            AND (
        ";
        
        $params = [$this->userId];
        $conditions = [];
        
        foreach (array_slice($keywords, 0, 5) as $keyword) {
            $conditions[] = "br.title LIKE ?";
            $params[] = '%' . $keyword . '%';
        }
        
        $sql .= implode(' OR ', $conditions) . ")
            ORDER BY RAND()
            LIMIT ?";
        
        $params[] = $limit;
        
        $books = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
        
        return DB::isError($books) ? [] : $books;
    }
    
    /**
     * ジャンル内の人気本を検索
     */
    private function findPopularInGenre(string $genre, array $keywords, int $limit): array {
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.image_url,
                COUNT(DISTINCT bl_all.user_id) as reader_count
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin AND bl.user_id = ?
            INNER JOIN b_book_list bl_all ON bl_all.amazon_id = br.asin
            WHERE bl.book_id IS NULL
        ";
        
        $params = [$this->userId];
        
        // キーワードフィルタ
        if (!empty($keywords)) {
            $sql .= " AND (";
            $conditions = [];
            foreach (array_slice($keywords, 0, 3) as $keyword) {
                $conditions[] = "br.title LIKE ?";
                $params[] = '%' . $keyword . '%';
            }
            $sql .= implode(' OR ', $conditions) . ")";
        }
        
        $sql .= "
            GROUP BY br.asin
            HAVING reader_count >= 3
            ORDER BY reader_count DESC
            LIMIT ?
        ";
        
        $params[] = $limit;
        
        $books = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
        
        return DB::isError($books) ? [] : $books;
    }
}