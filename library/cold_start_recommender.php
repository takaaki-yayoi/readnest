<?php
/**
 * コールドスタート推薦システム
 * 新規ユーザーや読書履歴が少ないユーザー向けの推薦
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');

class ColdStartRecommender {
    private $db;
    private int $userId;
    
    public function __construct(int $userId) {
        global $g_db;
        $this->db = $g_db;
        $this->userId = $userId;
    }
    
    /**
     * コールドスタート推薦を取得
     */
    public function getRecommendations(int $limit = 30): array {
        $recommendations = [];
        
        // 1. 人気の本（全ユーザーで最も読まれている本）
        $popularBooks = $this->getPopularBooks(15);
        error_log("ColdStart: Popular books fetched: " . count($popularBooks));
        foreach ($popularBooks as $book) {
            $book['reason'] = "{$book['reader_count']}人が読んでいる人気作品";
            $book['category'] = 'popular';
            $recommendations[] = $book;
        }
        
        // 2. 高評価の本（全ユーザーで評価が高い本）
        $highRatedBooks = $this->getHighRatedBooks(15);
        error_log("ColdStart: High rated books fetched: " . count($highRatedBooks));
        foreach ($highRatedBooks as $book) {
            $book['reason'] = "平均★{$book['avg_rating']}の高評価作品";
            $book['category'] = 'highly_rated';
            $recommendations[] = $book;
        }
        
        error_log("ColdStart: Total recommendations before dedup: " . count($recommendations));
        
        // スコアでソート
        usort($recommendations, function($a, $b) {
            return ($b['score'] ?? 0) - ($a['score'] ?? 0);
        });
        
        // 重複を除去
        $uniqueBooks = [];
        $seenAsins = [];
        foreach ($recommendations as $book) {
            if (!in_array($book['asin'], $seenAsins)) {
                $uniqueBooks[] = $book;
                $seenAsins[] = $book['asin'];
            }
        }
        
        error_log("ColdStart: Total unique books: " . count($uniqueBooks) . " for user " . $this->userId);
        
        return array_slice($uniqueBooks, 0, $limit);
    }
    
    /**
     * 人気の本を取得（シンプル版）
     */
    private function getPopularBooks(int $limit): array {
        // まずユーザーが既に持っている本を取得
        $userBooksSql = "SELECT amazon_id FROM b_book_list WHERE user_id = ?";
        $userBooksResult = $this->db->getAll($userBooksSql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        $userBooks = [];
        if (!DB::isError($userBooksResult) && $userBooksResult) {
            foreach ($userBooksResult as $row) {
                $userBooks[] = $row['amazon_id'];
            }
        }
        
        // 人気の本を取得（ユーザーが持っていない本のみ）
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.image_url,
                COUNT(*) as reader_count,
                ROUND(90 + LOG(COUNT(*)) * 10) as score
            FROM b_book_repository br
            INNER JOIN b_book_list bl ON bl.amazon_id = br.asin
            WHERE br.author IS NOT NULL
            AND br.author != ''
        ";
        
        // ユーザーが本を持っている場合は除外
        $params = [];
        if (!empty($userBooks)) {
            $placeholders = array_fill(0, count($userBooks), '?');
            $sql .= " AND br.asin NOT IN (" . implode(',', $placeholders) . ")";
            $params = array_merge($params, $userBooks);
        }
        
        $sql .= "
            GROUP BY br.asin, br.title, br.author, br.image_url
            ORDER BY reader_count DESC
            LIMIT ?
        ";
        
        $params[] = $limit;
        
        $result = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result)) {
            error_log('Popular books error: ' . $result->getMessage());
            return [];
        }
        
        return $result ?: [];
    }
    
    /**
     * 高評価の本を取得（シンプル版）
     */
    private function getHighRatedBooks(int $limit): array {
        // まずユーザーが既に持っている本を取得
        $userBooksSql = "SELECT amazon_id FROM b_book_list WHERE user_id = ?";
        $userBooksResult = $this->db->getAll($userBooksSql, [$this->userId], DB_FETCHMODE_ASSOC);
        
        $userBooks = [];
        if (!DB::isError($userBooksResult) && $userBooksResult) {
            foreach ($userBooksResult as $row) {
                $userBooks[] = $row['amazon_id'];
            }
        }
        
        // 高評価の本を取得
        $sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.image_url,
                ROUND(AVG(bl.rating), 1) as avg_rating,
                COUNT(*) as reviewer_count,
                ROUND(85 + AVG(bl.rating) * 10) as score
            FROM b_book_repository br
            INNER JOIN b_book_list bl ON bl.amazon_id = br.asin
            WHERE bl.rating > 0
            AND br.author IS NOT NULL
            AND br.author != ''
        ";
        
        // ユーザーが本を持っている場合は除外
        $params = [];
        if (!empty($userBooks)) {
            $placeholders = array_fill(0, count($userBooks), '?');
            $sql .= " AND br.asin NOT IN (" . implode(',', $placeholders) . ")";
            $params = array_merge($params, $userBooks);
        }
        
        $sql .= "
            GROUP BY br.asin, br.title, br.author, br.image_url
            HAVING AVG(bl.rating) >= 3.5
            ORDER BY avg_rating DESC, reviewer_count DESC
            LIMIT ?
        ";
        
        $params[] = $limit;
        
        $result = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result)) {
            error_log('High rated books error: ' . $result->getMessage());
            return [];
        }
        
        return $result ?: [];
    }
    
    /**
     * ユーザーの読書数を取得
     */
    public function getUserBookCount(): int {
        $sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status IN (3, 4)";
        $count = $this->db->getOne($sql, [$this->userId]);
        
        if (DB::isError($count)) {
            return 0;
        }
        
        return intval($count);
    }
}