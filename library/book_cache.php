<?php
/**
 * 本詳細ページ用のキャッシュ機能
 * 人気の本へのアクセス時のパフォーマンス改善
 */

class BookCache {
    private static $cache_dir = '/tmp/readnest_cache/books/';
    private static $cache_ttl = 300; // 5分間のキャッシュ
    
    /**
     * キャッシュディレクトリを初期化
     */
    public static function init() {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0777, true);
        }
    }
    
    /**
     * キャッシュキーを生成
     */
    private static function getCacheKey($book_id, $user_id = null) {
        $key = 'book_' . $book_id;
        if ($user_id) {
            $key .= '_user_' . $user_id;
        }
        return $key;
    }
    
    /**
     * キャッシュファイルのパスを取得
     */
    private static function getCachePath($key) {
        return self::$cache_dir . $key . '.cache';
    }
    
    /**
     * キャッシュから本の基本情報を取得
     */
    public static function getBookInfo($book_id) {
        $key = 'book_info_' . $book_id;
        $cache_path = self::getCachePath($key);
        
        if (file_exists($cache_path)) {
            $cache_time = filemtime($cache_path);
            if (time() - $cache_time < self::$cache_ttl) {
                $data = file_get_contents($cache_path);
                return unserialize($data);
            }
        }
        
        return null;
    }
    
    /**
     * 本の基本情報をキャッシュに保存
     */
    public static function setBookInfo($book_id, $data) {
        self::init();
        $key = 'book_info_' . $book_id;
        $cache_path = self::getCachePath($key);
        
        $serialized = serialize($data);
        file_put_contents($cache_path, $serialized, LOCK_EX);
    }
    
    /**
     * 読者統計情報を取得（キャッシュ対応）
     */
    public static function getReaderStats($book_id) {
        $key = 'reader_stats_' . $book_id;
        $cache_path = self::getCachePath($key);
        
        if (file_exists($cache_path)) {
            $cache_time = filemtime($cache_path);
            if (time() - $cache_time < self::$cache_ttl) {
                $data = file_get_contents($cache_path);
                return unserialize($data);
            }
        }
        
        return null;
    }
    
    /**
     * 読者統計情報をキャッシュに保存
     */
    public static function setReaderStats($book_id, $data) {
        self::init();
        $key = 'reader_stats_' . $book_id;
        $cache_path = self::getCachePath($key);
        
        $serialized = serialize($data);
        file_put_contents($cache_path, $serialized, LOCK_EX);
    }
    
    /**
     * 類似本リストを取得（キャッシュ対応）
     */
    public static function getSimilarBooks($book_id) {
        $key = 'similar_books_' . $book_id;
        $cache_path = self::getCachePath($key);
        
        if (file_exists($cache_path)) {
            $cache_time = filemtime($cache_path);
            // 類似本は1時間キャッシュ
            if (time() - $cache_time < 3600) {
                $data = file_get_contents($cache_path);
                return unserialize($data);
            }
        }
        
        return null;
    }
    
    /**
     * 類似本リストをキャッシュに保存
     */
    public static function setSimilarBooks($book_id, $data) {
        self::init();
        $key = 'similar_books_' . $book_id;
        $cache_path = self::getCachePath($key);
        
        $serialized = serialize($data);
        file_put_contents($cache_path, $serialized, LOCK_EX);
    }
    
    /**
     * 特定の本のキャッシュをクリア
     */
    public static function clearBookCache($book_id) {
        $patterns = [
            'book_info_' . $book_id,
            'reader_stats_' . $book_id,
            'similar_books_' . $book_id
        ];
        
        foreach ($patterns as $pattern) {
            $cache_path = self::getCachePath($pattern);
            if (file_exists($cache_path)) {
                unlink($cache_path);
            }
        }
    }
    
    /**
     * 古いキャッシュをクリーンアップ
     */
    public static function cleanup() {
        if (!is_dir(self::$cache_dir)) {
            return;
        }
        
        $files = glob(self::$cache_dir . '*.cache');
        $now = time();
        
        foreach ($files as $file) {
            // 1日以上古いキャッシュは削除
            if ($now - filemtime($file) > 86400) {
                unlink($file);
            }
        }
    }
}

/**
 * Memcachedが利用可能な場合の拡張キャッシュクラス
 */
class BookMemcache {
    private static $memcache = null;
    private static $enabled = false;
    
    public static function init() {
        if (class_exists('Memcached')) {
            self::$memcache = new Memcached();
            self::$memcache->addServer('localhost', 11211);
            self::$enabled = true;
        }
    }
    
    public static function get($key) {
        if (!self::$enabled) return null;
        return self::$memcache->get($key);
    }
    
    public static function set($key, $value, $ttl = 300) {
        if (!self::$enabled) return false;
        return self::$memcache->set($key, $value, $ttl);
    }
    
    public static function delete($key) {
        if (!self::$enabled) return false;
        return self::$memcache->delete($key);
    }
}

// 初期化
BookMemcache::init();
?>