<?php
/**
 * 読書傾向分析ライブラリ
 * レビューembeddingを使用してユーザーの読書傾向を分析
 */

declare(strict_types=1);

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/database.php');

class ReadingTrendAnalyzer {
    private $db;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
    }
    
    /**
     * ユーザーの読書傾向サマリーを取得
     */
    public function getUserReadingSummary(int $userId): array {
        $summary = [];
        
        // 基本統計
        $sql = "
            SELECT 
                COUNT(DISTINCT bl.amazon_id) as total_books,
                COUNT(DISTINCT CASE WHEN bl.status = 3 THEN bl.amazon_id END) as finished_books,
                COUNT(DISTINCT CASE WHEN bl.memo IS NOT NULL AND bl.memo != '' THEN bl.amazon_id END) as reviewed_books,
                AVG(bl.rating) as avg_rating,
                COUNT(DISTINCT br.author) as unique_authors
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
        ";
        
        $stats = $this->db->getRow($sql, array($userId), DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($stats)) {
            $summary['stats'] = $stats;
        } else {
            // エラーの場合はデフォルト値を設定
            $summary['stats'] = [
                'total_books' => 0,
                'finished_books' => 0,
                'reviewed_books' => 0,
                'avg_rating' => 0,
                'unique_authors' => 0
            ];
        }
        
        // ジャンル分布
        $summary['genres'] = $this->getUserGenreDistribution($userId);
        
        // 読書ペース
        $summary['reading_pace'] = $this->getReadingPace($userId);
        
        // レビューの特徴
        $summary['review_characteristics'] = $this->getReviewCharacteristics($userId);
        
        return $summary;
    }
    
    /**
     * ユーザーのジャンル分布を取得（embeddingクラスタベース）
     */
    private function getUserGenreDistribution(int $userId): array {
        // EmbeddingAnalyzerを使用してクラスタリング
        require_once(__DIR__ . '/embedding_analyzer.php');
        $analyzer = new EmbeddingAnalyzer();
        
        // embeddingベースでクラスタリング
        $clusters = $analyzer->analyzeUserReadingClusters($userId, 8);
        
        if (empty($clusters)) {
            // クラスタリングできない場合は評価ベースで分類
            $sql = "
                SELECT 
                    CASE 
                        WHEN rating >= 4.5 THEN '★5 最高評価'
                        WHEN rating >= 3.5 THEN '★4 高評価'
                        WHEN rating >= 2.5 THEN '★3 普通'
                        WHEN rating >= 1.5 THEN '★2 低評価'
                        ELSE '★1 最低評価'
                    END as genre,
                    COUNT(*) as count,
                    AVG(rating) as avg_rating
                FROM b_book_list
                WHERE user_id = ? AND rating > 0
                GROUP BY genre
                ORDER BY avg_rating DESC
            ";
            $result = $this->db->getAll($sql, array($userId), DB_FETCHMODE_ASSOC);
            return DB::isError($result) ? [] : $result;
        }
        
        // クラスタを「ジャンル」として返す
        $genres = [];
        foreach ($clusters as $cluster) {
            $genres[] = [
                'genre' => $cluster['name'],
                'count' => $cluster['size'],
                'avg_rating' => $cluster['avg_rating']
            ];
        }
        
        return $genres;
    }
    
    /**
     * 読書ペースを分析
     */
    private function getReadingPace(int $userId): array {
        $sql = "
            SELECT 
                YEAR(bl.update_date) as year,
                MONTH(bl.update_date) as month,
                COUNT(*) as books_read
            FROM b_book_list bl
            WHERE bl.user_id = ? 
                AND bl.status IN (3, 5)
                AND bl.update_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY YEAR(bl.update_date), MONTH(bl.update_date)
            ORDER BY year DESC, month DESC
        ";
        
        $result = $this->db->getAll($sql, array($userId), DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result)) {
            return [];
        }
        
        // 月平均を計算
        $total_books = array_sum(array_column($result, 'books_read'));
        $months = count($result);
        
        return [
            'monthly_data' => $result,
            'avg_per_month' => $months > 0 ? round($total_books / $months, 1) : 0,
            'total_last_year' => $total_books
        ];
    }
    
    /**
     * レビューの特徴を分析
     */
    private function getReviewCharacteristics(int $userId): array {
        $sql = "
            SELECT 
                COUNT(*) as total_reviews,
                AVG(LENGTH(memo)) as avg_review_length,
                MIN(LENGTH(memo)) as min_review_length,
                MAX(LENGTH(memo)) as max_review_length,
                COUNT(CASE WHEN LENGTH(memo) > 500 THEN 1 END) as long_reviews,
                COUNT(CASE WHEN LENGTH(memo) <= 100 THEN 1 END) as short_reviews
            FROM b_book_list
            WHERE user_id = ? AND memo IS NOT NULL AND memo != ''
        ";
        
        $result = $this->db->getRow($sql, array($userId), DB_FETCHMODE_ASSOC);
        return DB::isError($result) ? [] : $result;
    }
    
    /**
     * レビューembeddingからクラスタリング
     */
    public function getReviewClusters(int $userId, int $numClusters = 5): array {
        // EmbeddingAnalyzerを使用
        require_once(__DIR__ . '/embedding_analyzer.php');
        $analyzer = new EmbeddingAnalyzer();
        
        return $analyzer->analyzeUserReadingClusters($userId, $numClusters);
    }
    
    /**
     * レビューembeddingからクラスタリング（旧版・廃止予定）
     */
    private function getReviewClustersOld(int $userId, int $numClusters = 5): array {
        // ユーザーのレビューembeddingを取得
        $sql = "
            SELECT 
                re.book_id,
                re.review_embedding,
                br.title,
                br.author,
                bl.rating,
                LEFT(bl.memo, 100) as review_snippet
            FROM review_embeddings re
            JOIN b_book_repository br ON re.book_id = br.book_id
            JOIN b_book_list bl ON bl.amazon_id = br.asin AND re.user_id = bl.user_id
            WHERE re.user_id = ? 
                AND re.review_embedding IS NOT NULL
            ORDER BY bl.update_date DESC
            LIMIT 100
        ";
        
        $reviews = $this->db->getAll($sql, array($userId), DB_FETCHMODE_ASSOC);
        
        if (DB::isError($reviews) || count($reviews) === 0) {
            return [];
        }
        
        // embeddingをデコード
        $embeddings = [];
        foreach ($reviews as $index => $review) {
            $embedding = json_decode($review['review_embedding'], true);
            if ($embedding) {
                $embeddings[$index] = $embedding;
            }
        }
        
        // 簡易的なクラスタリング（k-means的な処理）
        // 実際にはPythonスクリプトを呼び出すか、PHPのMLライブラリを使用
        $clusters = $this->performSimpleClustering($embeddings, $reviews, $numClusters);
        
        return $clusters;
    }
    
    /**
     * 簡易的なクラスタリング処理
     */
    private function performSimpleClustering(array $embeddings, array $reviews, int $k): array {
        // ここでは簡易的にランダムに振り分け
        // 実際にはk-meansアルゴリズムを実装するか、外部ライブラリを使用
        $clusters = [];
        
        foreach ($reviews as $index => $review) {
            $clusterId = $index % $k;
            if (!isset($clusters[$clusterId])) {
                $clusters[$clusterId] = [
                    'id' => $clusterId,
                    'books' => [],
                    'characteristics' => []
                ];
            }
            
            $clusters[$clusterId]['books'][] = [
                'title' => $review['title'],
                'author' => $review['author'],
                'rating' => $review['rating'],
                'snippet' => $review['review_snippet']
            ];
        }
        
        // クラスタの特徴を分析（仮）
        foreach ($clusters as &$cluster) {
            $cluster['characteristics'] = [
                'size' => count($cluster['books']),
                'avg_rating' => array_sum(array_column($cluster['books'], 'rating')) / count($cluster['books'])
            ];
        }
        
        return array_values($clusters);
    }
    
    /**
     * 読書の多様性スコアを計算
     */
    public function calculateDiversityScore(int $userId): float {
        // EmbeddingAnalyzerを使用
        require_once(__DIR__ . '/embedding_analyzer.php');
        $analyzer = new EmbeddingAnalyzer();
        
        // embeddingベースの多様性を計算
        $embeddingDiversity = $analyzer->calculateEmbeddingDiversity($userId);
        
        if ($embeddingDiversity > 0) {
            return round($embeddingDiversity, 1);
        }
        
        // embeddingがない場合は従来の方法
        $genreDiversity = $this->calculateGenreDiversity($userId);
        $authorDiversity = $this->calculateAuthorDiversity($userId);
        $ratingVariance = $this->calculateRatingVariance($userId);
        
        $score = ($genreDiversity * 0.4 + $authorDiversity * 0.4 + $ratingVariance * 0.2) * 100;
        
        return round($score, 1);
    }
    
    /**
     * ジャンル（タグ）の多様性を計算（Shannon entropy）
     */
    private function calculateGenreDiversity(int $userId): float {
        // タグベースで多様性を計算
        $sql = "
            SELECT COUNT(*) as count
            FROM b_book_list bl
            LEFT JOIN b_book_tags bt ON bl.book_id = bt.book_id AND bl.user_id = bt.user_id
            WHERE bl.user_id = ?
            GROUP BY bt.tag_name
        ";
        
        $result = $this->db->getAll($sql, array($userId), DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result) || count($result) === 0) {
            // タグがない場合は、レビューの長さの多様性で代替
            return $this->calculateReviewDiversity($userId);
        }
        
        $counts = array_column($result, 'count');
        $total = array_sum($counts);
        
        if ($total === 0) {
            return 0;
        }
        
        $entropy = 0;
        foreach ($counts as $count) {
            $p = $count / $total;
            if ($p > 0) {
                $entropy -= $p * log($p);
            }
        }
        
        // 正規化（0-1の範囲に）
        $maxEntropy = log(count($counts));
        return $maxEntropy > 0 ? $entropy / $maxEntropy : 0;
    }
    
    /**
     * レビューの多様性を計算（レビュー長の分散ベース）
     */
    private function calculateReviewDiversity(int $userId): float {
        $sql = "
            SELECT 
                VARIANCE(LENGTH(memo)) / POW(AVG(LENGTH(memo)), 2) as cv_squared
            FROM b_book_list
            WHERE user_id = ? AND memo IS NOT NULL AND memo != ''
        ";
        
        $result = $this->db->getOne($sql, array($userId));
        
        if (DB::isError($result) || !$result) {
            return 0;
        }
        
        // 変動係数の二乗を0-1に正規化
        return min(1.0, sqrt($result));
    }
    
    /**
     * 著者の多様性を計算
     */
    private function calculateAuthorDiversity(int $userId): float {
        $sql = "
            SELECT 
                COUNT(DISTINCT br.author) as unique_authors,
                COUNT(*) as total_books
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
        ";
        
        $result = $this->db->getRow($sql, array($userId), DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result) || $result['total_books'] == 0) {
            return 0;
        }
        
        // 著者数 / 本の数の比率（最大1.0）
        return min(1.0, $result['unique_authors'] / $result['total_books']);
    }
    
    /**
     * 評価の分散を計算
     */
    private function calculateRatingVariance(int $userId): float {
        $sql = "
            SELECT 
                VARIANCE(rating) as rating_variance,
                COUNT(*) as count
            FROM b_book_list
            WHERE user_id = ? AND rating > 0
        ";
        
        $result = $this->db->getRow($sql, array($userId), DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result) || $result['count'] == 0) {
            return 0;
        }
        
        // 分散を0-1の範囲に正規化（最大分散は2.5^2 = 6.25）
        return min(1.0, $result['rating_variance'] / 6.25);
    }
    
    /**
     * 類似した読書傾向を持つユーザーを探す
     */
    public function findSimilarReaders(int $userId, int $limit = 10): array {
        // ユーザーの平均embedding vectorを計算
        $sql = "
            SELECT AVG(JSON_EXTRACT(review_embedding, '$[*]')) as avg_embedding
            FROM review_embeddings
            WHERE user_id = ? AND review_embedding IS NOT NULL
        ";
        
        // 簡易版：同じジャンルを読んでいるユーザーを探す
        $sql = "
            SELECT 
                other.user_id,
                u.nickname,
                COUNT(DISTINCT other.amazon_id) as common_books,
                AVG(other.rating) as avg_rating
            FROM b_book_list me
            JOIN b_book_list other ON me.amazon_id = other.amazon_id
            JOIN u_users u ON other.user_id = u.user_id
            WHERE me.user_id = ? 
                AND other.user_id != ?
                AND other.user_id IN (
                    SELECT user_id FROM b_book_list 
                    GROUP BY user_id 
                    HAVING COUNT(*) >= 10
                )
            GROUP BY other.user_id, u.nickname
            ORDER BY common_books DESC
            LIMIT ?
        ";
        
        $result = $this->db->getAll($sql, array($userId, $userId, $limit), DB_FETCHMODE_ASSOC);
        
        return DB::isError($result) ? [] : $result;
    }
}
?>