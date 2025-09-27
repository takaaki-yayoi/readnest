<?php
/**
 * 作家クラウドデータ生成クラス
 */

class SakkaCloudGenerator {
    private $db;
    private $cache;
    
    public function __construct() {
        global $g_db;
        $this->db = $g_db;
        
        // キャッシュシステムを初期化
        require_once(dirname(__FILE__) . '/cache.php');
        $this->cache = getCache();
    }
    
    /**
     * 作家クラウドデータを生成
     */
    public function generate() {
        try {
            // 既存データをクリア
            $this->clearOldData();
            
            // 新しいデータを生成
            $authors = $this->collectAuthorData();
            
            // データベースに保存
            $count = $this->saveToDatabase($authors);
            
            // キャッシュをクリア
            $this->clearCache();
            
            return [
                'success' => true,
                'count' => $count,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log('SakkaCloudGenerator Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 作家データを収集
     */
    private function collectAuthorData() {
        // b_book_repositoryとb_book_listを結合して作家データを収集（シンプル版）
        $sql = "
            SELECT 
                br.author,
                COUNT(DISTINCT bl.book_id) as book_count,
                COUNT(DISTINCT bl.user_id) as reader_count,
                0 as review_count,
                NULL as average_rating,
                MAX(bl.update_date) as last_read_date
            FROM b_book_repository br
            INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
            INNER JOIN b_user bu ON bl.user_id = bu.user_id
            WHERE 
                bu.diary_policy = 1 
                AND bu.status = 1
                AND br.author != ''
                AND br.author IS NOT NULL
                AND br.author != '-'
            GROUP BY br.author
            HAVING book_count > 0
            ORDER BY last_read_date DESC, reader_count DESC
        ";
        
        $result = $this->db->getAll($sql, null, DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result)) {
            throw new Exception('データ収集エラー: ' . $result->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 簡易版作家データ収集（b_sakka_cloud用）
     */
    public function generateSimple() {
        try {
            // b_sakka_cloud用のシンプルなデータ収集
            $sql = "
                SELECT 
                    br.author,
                    COUNT(*) as author_count,
                    MAX(bl.update_date) as newest_date
                FROM b_book_repository br
                INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
                INNER JOIN b_user bu ON bl.user_id = bu.user_id
                WHERE 
                    bu.diary_policy = 1
                    AND bu.status = 1
                    AND br.author != ''
                    AND br.author IS NOT NULL
                    AND br.author != '-'
                GROUP BY br.author
                ORDER BY newest_date DESC
            ";
            
            $result = $this->db->getAll($sql, null, DB_FETCHMODE_ASSOC);
            
            if (DB::isError($result)) {
                throw new Exception('データ収集エラー: ' . $result->getMessage());
            }
            
            // b_sakka_cloudテーブルをクリア
            $this->db->query("TRUNCATE TABLE b_sakka_cloud");
            
            // データを挿入
            $count = 0;
            foreach ($result as $row) {
                $insert_sql = "INSERT INTO b_sakka_cloud (author, author_count, updated) VALUES (?, ?, ?)";
                $params = [
                    $row['author'],
                    $row['author_count'],
                    $row['newest_date'] ?? date('Y-m-d H:i:s')
                ];
                
                $insert_result = $this->db->query($insert_sql, $params);
                if (!DB::isError($insert_result)) {
                    $count++;
                }
            }
            
            return [
                'success' => true,
                'count' => $count
            ];
            
        } catch (Exception $e) {
            error_log('SakkaCloudGenerator Simple Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * データベースに保存
     */
    private function saveToDatabase($authors) {
        $count = 0;
        
        // b_author_stats_cacheに保存
        foreach ($authors as $author) {
            $sql = "
                INSERT INTO b_author_stats_cache 
                (author, book_count, reader_count, review_count, average_rating, last_read_date)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    book_count = VALUES(book_count),
                    reader_count = VALUES(reader_count),
                    review_count = VALUES(review_count),
                    average_rating = VALUES(average_rating),
                    last_read_date = VALUES(last_read_date),
                    updated_at = NOW()
            ";
            
            $params = [
                $author['author'],
                $author['book_count'],
                $author['reader_count'],
                $author['review_count'] ?? 0,
                $author['average_rating'],
                $author['last_read_date']
            ];
            
            $result = $this->db->query($sql, $params);
            if (!DB::isError($result)) {
                $count++;
            }
        }
        
        // b_sakka_cloudにも簡易データを保存
        $this->db->query("TRUNCATE TABLE b_sakka_cloud");
        
        foreach ($authors as $author) {
            $sql = "INSERT INTO b_sakka_cloud (author, author_count, updated) VALUES (?, ?, ?)";
            $params = [
                $author['author'],
                $author['reader_count'], // 読者数を使用
                $author['last_read_date'] ?? date('Y-m-d H:i:s')
            ];
            
            $this->db->query($sql, $params);
        }
        
        return $count;
    }
    
    /**
     * 古いデータをクリア
     */
    private function clearOldData() {
        // 30日以上更新されていないデータを削除
        $sql = "DELETE FROM b_author_stats_cache WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $this->db->query($sql);
    }
    
    /**
     * キャッシュをクリア
     */
    public function clearCache() {
        $cache_keys = [
            'sakka_cloud_data',
            'sakka_cloud_html',
            'author_stats_cache'
        ];
        
        foreach ($cache_keys as $key) {
            $this->cache->delete($key);
        }
    }
    
    /**
     * 人気の作家を取得（表示用）
     */
    public function getPopularAuthors($limit = 100) {
        // キャッシュから取得を試みる
        $cacheKey = 'sakka_cloud_data_' . $limit;
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // データベースから取得
        $sql = "
            SELECT * FROM b_author_stats_cache
            ORDER BY last_read_date DESC, reader_count DESC
            LIMIT ?
        ";
        
        $result = $this->db->getAll($sql, [$limit], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($result)) {
            // 1時間キャッシュ
            $this->cache->set($cacheKey, $result, 3600);
            return $result;
        }
        
        return [];
    }
    
    /**
     * 自動更新が必要かチェック
     */
    public function needsUpdate() {
        // 最終更新時刻を取得
        $sql = "SELECT MAX(updated_at) as last_update FROM b_author_stats_cache";
        $last_update = $this->db->getOne($sql);
        
        if (DB::isError($last_update) || !$last_update) {
            // データがない場合は更新が必要
            return true;
        }
        
        // 最終更新から6時間以上経過していたら更新
        $hours_passed = (time() - strtotime($last_update)) / 3600;
        return $hours_passed >= 6;
    }
    
    /**
     * バックグラウンド更新用の軽量チェック
     */
    public function shouldUpdateInBackground() {
        // 更新フラグをチェック（頻繁なチェックを避ける）
        $flagKey = 'sakka_cloud_update_check';
        $lastCheck = $this->cache->get($flagKey);
        
        if ($lastCheck !== false) {
            // 5分以内にチェック済みならスキップ
            return false;
        }
        
        // フラグを設定（5分間有効）
        $this->cache->set($flagKey, time(), 300);
        
        // 実際の更新チェック
        return $this->needsUpdate();
    }
    
    /**
     * 作家クラウドHTMLを生成
     */
    public function generateCloudHtml($limit = 100, $minSize = 12, $maxSize = 36) {
        $authors = $this->getPopularAuthors($limit);
        
        if (empty($authors)) {
            return '<p class="text-gray-500">作家データがありません</p>';
        }
        
        // 最大値と最小値を取得
        $maxCount = max(array_column($authors, 'reader_count'));
        $minCount = min(array_column($authors, 'reader_count'));
        
        // HTMLを生成
        $html = '<div class="author-cloud">';
        
        // シャッフルして表示順をランダムに
        shuffle($authors);
        
        foreach ($authors as $author) {
            $count = $author['reader_count'];
            
            // フォントサイズを計算（対数スケール）
            if ($maxCount > $minCount) {
                $ratio = ($count - $minCount) / ($maxCount - $minCount);
                $size = $minSize + ($maxSize - $minSize) * sqrt($ratio);
            } else {
                $size = $minSize;
            }
            
            $authorName = htmlspecialchars($author['author']);
            $bookCount = $author['book_count'];
            $readerCount = $author['reader_count'];
            
            $html .= sprintf(
                '<a href="/author/%s" class="inline-block px-2 py-1 m-1 hover:opacity-70 transition-opacity" 
                 style="font-size: %dpx" 
                 title="%d人が読書中 / %d作品">%s</a>',
                urlencode($author['author']),
                $size,
                $readerCount,
                $bookCount,
                $authorName
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }
}