<?php
/**
 * ベース本のローテーション管理クラス
 * セッションとデータベースを使用して推薦のベース本の重複を防ぐ
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/database.php');

class BaseBookRotation {
    private $db;
    private $userId;
    private $sessionKey;
    private $dbTableExists = false;
    
    public function __construct(int $userId) {
        global $g_db;
        $this->db = $g_db;
        $this->userId = $userId;
        $this->sessionKey = 'base_books_' . $userId;
        
        // セッション開始
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // テーブルの存在確認
        $this->checkOrCreateTable();
    }
    
    /**
     * 使用履歴テーブルの作成
     */
    private function checkOrCreateTable(): void {
        $checkSql = "SHOW TABLES LIKE 'b_recommendation_history'";
        $exists = $this->db->getOne($checkSql);
        
        if (!$exists) {
            $createSql = "
                CREATE TABLE IF NOT EXISTS b_recommendation_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    asin VARCHAR(20) NOT NULL,
                    used_as_base TINYINT(1) DEFAULT 1,
                    usage_count INT DEFAULT 1,
                    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_asin (user_id, asin),
                    INDEX idx_last_used (last_used)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            
            $result = $this->db->query($createSql);
            if (!DB::isError($result)) {
                $this->dbTableExists = true;
            }
        } else {
            $this->dbTableExists = true;
        }
    }
    
    /**
     * 前回使用したベース本を取得
     */
    public function getPreviousBaseBooks(int $hoursAgo = 24): array {
        $baseBooks = [];
        
        // 1. セッションから取得（短期）
        if (isset($_SESSION[$this->sessionKey])) {
            $sessionBooks = $_SESSION[$this->sessionKey];
            if (isset($sessionBooks['timestamp']) && 
                (time() - $sessionBooks['timestamp']) < 3600) { // 1時間以内
                $baseBooks = array_merge($baseBooks, $sessionBooks['asins'] ?? []);
            }
        }
        
        // 2. データベースから取得（長期）
        if ($this->dbTableExists) {
            $sql = "
                SELECT DISTINCT asin
                FROM b_recommendation_history
                WHERE user_id = ?
                AND used_as_base = 1
                AND last_used > DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY last_used DESC
                LIMIT 50
            ";
            
            $dbBooks = $this->db->getAll($sql, [$this->userId, $hoursAgo], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($dbBooks) && $dbBooks) {
                foreach ($dbBooks as $book) {
                    $baseBooks[] = $book['asin'];
                }
            }
        }
        
        return array_unique($baseBooks);
    }
    
    /**
     * 使用頻度の高い本を取得（これらは避ける）
     */
    public function getFrequentlyUsedBooks(int $threshold = 3): array {
        if (!$this->dbTableExists) {
            return [];
        }
        
        $sql = "
            SELECT asin, usage_count
            FROM b_recommendation_history
            WHERE user_id = ?
            AND usage_count >= ?
            ORDER BY usage_count DESC
            LIMIT 100
        ";
        
        $books = $this->db->getAll($sql, [$this->userId, $threshold], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books) || !$books) {
            return [];
        }
        
        return array_column($books, 'asin');
    }
    
    /**
     * 今回使用したベース本を記録
     */
    public function recordBaseBooks(array $asins): void {
        if (empty($asins)) {
            return;
        }
        
        // 1. セッションに記録
        $_SESSION[$this->sessionKey] = [
            'asins' => $asins,
            'timestamp' => time()
        ];
        
        // 2. データベースに記録
        if ($this->dbTableExists) {
            foreach ($asins as $asin) {
                // UPSERT操作
                $sql = "
                    INSERT INTO b_recommendation_history 
                    (user_id, asin, used_as_base, usage_count)
                    VALUES (?, ?, 1, 1)
                    ON DUPLICATE KEY UPDATE
                    usage_count = usage_count + 1,
                    last_used = CURRENT_TIMESTAMP
                ";
                
                $this->db->query($sql, [$this->userId, $asin]);
            }
        }
    }
    
    /**
     * 古い履歴をクリーンアップ
     */
    public function cleanupOldHistory(int $daysOld = 30): void {
        if (!$this->dbTableExists) {
            return;
        }
        
        // 30日以上前の履歴を削除
        $sql = "
            DELETE FROM b_recommendation_history
            WHERE user_id = ?
            AND last_used < DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        $this->db->query($sql, [$this->userId, $daysOld]);
        
        // 使用回数をリセット（ローテーション）
        $resetSql = "
            UPDATE b_recommendation_history
            SET usage_count = GREATEST(1, usage_count - 1)
            WHERE user_id = ?
            AND usage_count > 5
            AND last_used < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";
        
        $this->db->query($resetSql, [$this->userId]);
    }
    
    /**
     * 推薦用の除外ASINリストを生成
     */
    public function getExclusionList(): array {
        $exclusions = [];
        
        // 24時間以内に使用したベース本
        $recentBase = $this->getPreviousBaseBooks(24);
        $exclusions = array_merge($exclusions, $recentBase);
        
        // 頻繁に使用される本（使用回数5回以上）
        $frequent = $this->getFrequentlyUsedBooks(5);
        $exclusions = array_merge($exclusions, $frequent);
        
        return array_unique($exclusions);
    }
    
    /**
     * ベース本選択の最適化
     * 多様性スコアを計算して最適な組み合わせを選ぶ
     */
    public function optimizeBaseBookSelection(array $candidates): array {
        $scored = [];
        
        // 除外リストを取得
        $exclusions = $this->getExclusionList();
        
        foreach ($candidates as $book) {
            if (in_array($book['asin'], $exclusions)) {
                continue; // 除外リストにある本はスキップ
            }
            
            $score = 100; // 基本スコア
            
            // 使用履歴がある場合はスコアを下げる
            if ($this->dbTableExists) {
                $sql = "
                    SELECT usage_count, 
                           DATEDIFF(NOW(), last_used) as days_ago
                    FROM b_recommendation_history
                    WHERE user_id = ? AND asin = ?
                ";
                
                $history = $this->db->getRow($sql, [$this->userId, $book['asin']], DB_FETCHMODE_ASSOC);
                
                if (!DB::isError($history) && $history) {
                    // 使用回数に応じてペナルティ
                    $score -= $history['usage_count'] * 10;
                    
                    // 最近使用した場合は大きくペナルティ
                    if ($history['days_ago'] < 7) {
                        $score -= (7 - $history['days_ago']) * 5;
                    }
                }
            }
            
            // 評価が高い本は優先
            $score += ($book['rating'] ?? 3) * 5;
            
            // 最近読んだ本は少し優先
            $daysOld = (time() - strtotime($book['update_date'])) / 86400;
            if ($daysOld < 30) {
                $score += 10;
            }
            
            $book['diversity_score'] = $score;
            $scored[] = $book;
        }
        
        // スコアでソート
        usort($scored, function($a, $b) {
            return $b['diversity_score'] - $a['diversity_score'];
        });
        
        return $scored;
    }
}