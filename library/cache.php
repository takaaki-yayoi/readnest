<?php
/**
 * シンプルなファイルベースキャッシュシステム
 * PHP 5.6以上対応版
 */

class SimpleCache {
    private $cacheDir;
    private $defaultTtl;

    public function __construct($cacheDir = null, $defaultTtl = 300) {
        $this->cacheDir = $cacheDir ? $cacheDir : dirname(__DIR__) . '/cache';
        $this->defaultTtl = $defaultTtl; // デフォルト5分
        
        // キャッシュディレクトリが存在しない場合は作成
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * キャッシュからデータを取得
     * 
     * @param string $key キャッシュキー
     * @return mixed|false キャッシュデータまたはfalse
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        $content = file_get_contents($filename);
        if ($content === false) {
            return false;
        }
        
        $data = unserialize($content);
        
        // 有効期限チェック
        if ($data['expires'] < time()) {
            // ファイルが存在する場合のみ削除
            if (file_exists($filename)) {
                @unlink($filename);
            }
            return false;
        }
        
        return $data['value'];
    }

    /**
     * キャッシュにデータを保存
     * 
     * @param string $key キャッシュキー
     * @param mixed $value 保存するデータ
     * @param int|null $ttl 有効期限（秒）
     * @return bool
     */
    public function set($key, $value, $ttl = null) {
        $filename = $this->getCacheFilename($key);
        $ttl = $ttl ? $ttl : $this->defaultTtl;
        
        $data = [
            'expires' => time() + $ttl,
            'value' => $value
        ];
        
        return file_put_contents($filename, serialize($data)) !== false;
    }

    /**
     * キャッシュを削除
     * 
     * @param string $key キャッシュキー
     * @return bool
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return @unlink($filename);
        }
        
        return true;
    }

    /**
     * すべてのキャッシュをクリア
     * 
     * @return bool
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        
        return true;
    }

    /**
     * キャッシュが存在し、有効かチェック
     * 
     * @param string $key キャッシュキー
     * @return bool
     */
    public function has($key) {
        return $this->get($key) !== false;
    }

    /**
     * キャッシュファイル名を生成
     * 
     * @param string $key
     * @return string
     */
    private function getCacheFilename($key) {
        // キーをハッシュ化してファイル名として使用
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * キャッシュ統計情報を取得
     * 
     * @return array
     */
    public function getStats() {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        $validCount = 0;
        $expiredCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $content = file_get_contents($file);
            if ($content !== false) {
                $data = unserialize($content);
                if ($data['expires'] < time()) {
                    $expiredCount++;
                } else {
                    $validCount++;
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_count' => $validCount,
            'expired_count' => $expiredCount,
            'total_size' => $totalSize,
            'cache_dir' => $this->cacheDir
        ];
    }
}

/**
 * グローバルキャッシュインスタンスを取得
 * 
 * @return SimpleCache
 */
function getCache() {
    static $cache = null;
    
    if ($cache === null) {
        $cache = new SimpleCache();
    }
    
    return $cache;
}