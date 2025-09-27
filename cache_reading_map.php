<?php
/**
 * 読書マップデータキャッシュ機能
 * パフォーマンス最適化のためにAPIレスポンスをキャッシュ
 */

// config.phpが既に読み込まれているかチェック
if (!defined('CONFIG')) {
    // APIディレクトリから呼ばれた場合とルートから呼ばれた場合の両方に対応
    $config_paths = [
        __DIR__ . '/config.php',
        __DIR__ . '/../config.php',
        'config.php'
    ];
    
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

class ReadingMapCache {
    private $cache_dir;
    private $cache_duration = 3600; // 1時間
    
    public function __construct() {
        // システムのテンポラリディレクトリを使用
        $this->cache_dir = sys_get_temp_dir() . '/readnest_cache';
        
        // ディレクトリ作成を試みる
        if (!is_dir($this->cache_dir)) {
            try {
                if (!@mkdir($this->cache_dir, 0755, true)) {
                    // 作成に失敗した場合は、プロジェクト内のcacheディレクトリを使用
                    $this->cache_dir = dirname(__FILE__) . '/cache';
                    if (!is_dir($this->cache_dir)) {
                        @mkdir($this->cache_dir, 0755, true);
                    }
                }
            } catch (Exception $e) {
                // エラーを無視してプロジェクト内のcacheディレクトリを使用
                $this->cache_dir = dirname(__FILE__) . '/cache';
            }
        }
    }
    
    /**
     * キャッシュキーを生成
     */
    private function getCacheKey($user_id) {
        return 'reading_map_' . md5($user_id);
    }
    
    /**
     * キャッシュファイルパスを取得
     */
    private function getCacheFilePath($user_id) {
        return $this->cache_dir . '/' . $this->getCacheKey($user_id) . '.json';
    }
    
    /**
     * キャッシュからデータを取得
     */
    public function get($user_id) {
        try {
            $file_path = $this->getCacheFilePath($user_id);
            
            if (!file_exists($file_path)) {
                return null;
            }
            
            $modified_time = @filemtime($file_path);
            if ($modified_time === false || time() - $modified_time > $this->cache_duration) {
                // キャッシュが期限切れ
                @unlink($file_path);
                return null;
            }
            
            $content = @file_get_contents($file_path);
            if ($content === false) {
                return null;
            }
            
            return json_decode($content, true);
        } catch (Exception $e) {
            // エラーが発生した場合はnullを返す
            return null;
        }
    }
    
    /**
     * データをキャッシュに保存
     */
    public function set($user_id, $data) {
        try {
            $file_path = $this->getCacheFilePath($user_id);
            
            // ディレクトリが存在することを確認
            $dir = dirname($file_path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            
            return @file_put_contents($file_path, json_encode($data, JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            // エラーが発生した場合はfalseを返す
            return false;
        }
    }
    
    /**
     * ユーザーのキャッシュを削除
     */
    public function delete($user_id) {
        $file_path = $this->getCacheFilePath($user_id);
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return true;
    }
    
    /**
     * 期限切れキャッシュを削除
     */
    public function cleanup() {
        $files = glob($this->cache_dir . '/reading_map_*.json');
        $deleted = 0;
        
        foreach ($files as $file) {
            $modified_time = filemtime($file);
            if (time() - $modified_time > $this->cache_duration) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}

/**
 * ユーザーのブックリストが更新された際にキャッシュを無効化
 */
function invalidateReadingMapCache($user_id) {
    $cache = new ReadingMapCache();
    $cache->delete($user_id);
}
?>