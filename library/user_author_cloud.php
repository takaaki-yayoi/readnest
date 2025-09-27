<?php
/**
 * ユーザー向け作家クラウド生成クラス
 * リアルタイム更新対応版
 */

class UserAuthorCloud {
    private $db;
    private $cache;
    private $user_id;
    
    public function __construct($user_id = null) {
        global $g_db;
        $this->db = $g_db;
        $this->user_id = $user_id;
        
        // キャッシュシステムを初期化
        require_once(dirname(__FILE__) . '/cache.php');
        $this->cache = getCache();
    }
    
    /**
     * ユーザーの作家クラウドデータを取得（リアルタイム）
     */
    public function getUserAuthorCloud($user_id = null, $limit = 50) {
        if ($user_id === null) {
            $user_id = $this->user_id;
        }
        
        if (!$user_id) {
            return [];
        }
        
        
        // キャッシュキー（短時間キャッシュ for リアルタイム性）
        $cache_key = 'user_author_cloud_' . $user_id . '_' . $limit;
        
        // 短時間キャッシュから取得（30秒）
        $cached = $this->cache->get($cache_key);
        if ($cached !== false && !isset($_GET['force_refresh'])) {
            return $cached;
        }
        
        // データベースから取得
        // まず基本的なクエリで作家を取得（ステータス関係なく）
        // LIMITは直接SQLに埋め込む（PEAR DBではLIMITのバインドがサポートされていない）
        $sql = sprintf("
            SELECT 
                bl.author,
                COUNT(DISTINCT bl.book_id) as book_count,
                MAX(bl.update_date) as last_read_date,
                SUM(CASE WHEN bl.status = %d THEN 1 ELSE 0 END) as reading_count,
                SUM(CASE WHEN bl.status = %d THEN 1 ELSE 0 END) as finished_count,
                AVG(CASE WHEN bl.rating > 0 THEN bl.rating ELSE NULL END) as avg_rating
            FROM b_book_list bl
            WHERE bl.user_id = ?
            AND bl.author IS NOT NULL
            AND bl.author != ''
            AND bl.author != '-'
            GROUP BY bl.author
            ORDER BY last_read_date DESC, book_count DESC
            LIMIT %d
        ", READING_NOW, READING_FINISH, intval($limit));
        
        $params = [$user_id];
        // デバッグ出力削除
        
        $result = $this->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
        
        if (DB::isError($result)) {
            error_log('UserAuthorCloud Error: ' . $result->getMessage());
            return [];
        }
        
        
        // If no results, try a simpler query without status filtering
        if (empty($result)) {
            
            $simple_sql = sprintf("
                SELECT 
                    bl.author,
                    COUNT(DISTINCT bl.book_id) as book_count,
                    MAX(bl.update_date) as last_read_date,
                    0 as reading_count,
                    0 as finished_count,
                    AVG(CASE WHEN bl.rating > 0 THEN bl.rating ELSE NULL END) as avg_rating
                FROM b_book_list bl
                WHERE bl.user_id = ?
                AND bl.author IS NOT NULL
                AND bl.author != ''
                AND bl.author != '-'
                GROUP BY bl.author
                ORDER BY last_read_date DESC, book_count DESC
                LIMIT %d
            ", intval($limit));
            
            $simple_params = [$user_id];
            $result = $this->db->getAll($simple_sql, $simple_params, DB_FETCHMODE_ASSOC);
            
            if (DB::isError($result)) {
                return [];
            }
            
        }
        
        // if (is_array($result)) {
        // Debug loop removed
        
        // データを処理
        $authors = $this->processAuthorData($result);
        
        
        // 短時間キャッシュに保存（30秒）
        $this->cache->set($cache_key, $authors, 30);
        
        return $authors;
    }
    
    /**
     * 作家データを処理してクラウド表示用に整形
     */
    private function processAuthorData($data) {
        if (empty($data)) {
            return [];
        }
        
        // 最大値と最小値を取得
        $max_count = max(array_column($data, 'book_count'));
        $min_count = min(array_column($data, 'book_count'));
        
        // サイズ計算用の係数
        $min_size = 12;
        $max_size = 32;
        
        foreach ($data as &$author) {
            // フォントサイズを計算
            if ($max_count > $min_count) {
                $ratio = ($author['book_count'] - $min_count) / ($max_count - $min_count);
                $author['font_size'] = round($min_size + ($max_size - $min_size) * sqrt($ratio));
            } else {
                $author['font_size'] = $min_size;
            }
            
            // 読書状態に応じた色を設定
            if ($author['finished_count'] > $author['reading_count']) {
                $author['color_class'] = 'from-green-500 to-emerald-600'; // 読了が多い
            } elseif ($author['reading_count'] > 0) {
                $author['color_class'] = 'from-blue-500 to-indigo-600'; // 読書中
            } else {
                $author['color_class'] = 'from-gray-500 to-gray-600'; // その他
            }
            
            // 評価による強調
            if ($author['avg_rating'] >= 4.0) {
                $author['is_favorite'] = true;
            } else {
                $author['is_favorite'] = false;
            }
        }
        
        return $data;
    }
    
    /**
     * ユーザーの作家統計を取得
     */
    public function getUserAuthorStats($user_id = null) {
        if ($user_id === null) {
            $user_id = $this->user_id;
        }
        
        if (!$user_id) {
            return null;
        }
        
        $cache_key = 'user_author_stats_' . $user_id;
        
        // 短時間キャッシュから取得（1分）
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $sql = "
            SELECT 
                COUNT(DISTINCT author) as total_authors,
                COUNT(*) as total_books,
                AVG(CASE WHEN rating > 0 THEN rating ELSE NULL END) as avg_rating
            FROM b_book_list
            WHERE user_id = ?
            AND author IS NOT NULL
            AND author != ''
        ";
        
        $stats = $this->db->getRow($sql, [$user_id], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($stats)) {
            $this->cache->set($cache_key, $stats, 60);
            return $stats;
        }
        
        return null;
    }
    
    /**
     * 本の更新時にキャッシュをクリア（リアルタイム更新用）
     */
    public function clearUserCache($user_id) {
        // ユーザー関連のキャッシュをすべてクリア
        $patterns = [
            'user_author_cloud_' . $user_id . '_*',
            'user_author_stats_' . $user_id
        ];
        
        foreach ($patterns as $pattern) {
            // 簡易的なパターンマッチング
            for ($limit = 10; $limit <= 200; $limit += 10) {
                $this->cache->delete('user_author_cloud_' . $user_id . '_' . $limit);
            }
        }
        
        $this->cache->delete('user_author_stats_' . $user_id);
        
        return true;
    }
    
    /**
     * HTMLクラウドを生成
     */
    public function generateCloudHtml($user_id = null, $limit = 30, $compact = false) {
        
        $authors = $this->getUserAuthorCloud($user_id, $limit);
        
        
        if (empty($authors)) {
            return '<p class="text-gray-500 text-center">まだ作家データがありません</p>';
        }
        
        // コンパクト表示の場合は件数を制限（シャッフルはしない - 最新順を維持）
        if ($compact) {
            // shuffle($authors); // 最新順を維持するためコメントアウト
            $authors = array_slice($authors, 0, 20);
        }
        
        $html = '<div class="author-cloud-user ' . ($compact ? 'compact' : '') . '">';
        
        foreach ($authors as $author) {
            $html .= sprintf(
                '<a href="/bookshelf.php?search_word=%s&search_type=author" 
                   class="inline-block px-2 py-1 m-1 rounded-lg transition-all duration-300 hover:scale-110 bg-gradient-to-r %s text-white %s"
                   style="font-size: %dpx;"
                   title="%s (%d冊)">
                    %s%s
                </a>',
                urlencode($author['author']),
                $author['color_class'],
                $author['is_favorite'] ? 'ring-2 ring-yellow-400' : '',
                $author['font_size'],
                htmlspecialchars($author['author']),
                $author['book_count'],
                htmlspecialchars($author['author']),
                $author['is_favorite'] ? ' ⭐' : ''
            );
        }
        
        $html .= '</div>';
        
        
        return $html;
    }
    
    /**
     * リアルタイム更新のトリガー
     * 本の追加・更新・削除時に呼び出す
     */
    public static function triggerUpdate($user_id, $book_id = null) {
        $instance = new self($user_id);
        
        // キャッシュをクリア
        $instance->clearUserCache($user_id);
        
        // イベントログ（必要に応じて）
        error_log("UserAuthorCloud updated for user: $user_id" . ($book_id ? " (book: $book_id)" : ""));
        
        return true;
    }
}

/**
 * ヘルパー関数: ユーザーの作家クラウドを取得
 */
function getUserAuthorCloud($user_id, $limit = 50) {
    $cloud = new UserAuthorCloud($user_id);
    return $cloud->getUserAuthorCloud($user_id, $limit);
}

/**
 * ヘルパー関数: ユーザーの作家クラウドHTMLを生成
 */
function generateUserAuthorCloudHtml($user_id, $limit = 30, $compact = false) {
    $cloud = new UserAuthorCloud($user_id);
    return $cloud->generateCloudHtml($user_id, $limit, $compact);
}
?>