<?php
/**
 * グローバル検索のキャッシュをクリア
 */

require_once('modern_config.php');
require_once('library/cache.php');

$cache = getCache();

// キャッシュディレクトリ内のファイルを確認
$cache_dir = dirname(__FILE__) . '/cache';
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '/*.cache');
    $count = 0;
    
    foreach ($files as $file) {
        // ファイル名をMD5ハッシュから推測
        $content = @file_get_contents($file);
        if ($content !== false) {
            $data = @unserialize($content);
            if ($data !== false) {
                // global_searchに関連するキャッシュを削除
                @unlink($file);
                $count++;
            }
        }
    }
    
    echo "Cleared $count cache files.\n";
} else {
    echo "Cache directory not found.\n";
}

// 代替方法：すべてのキャッシュをクリア
$cache->clear();
echo "All cache cleared.\n";
?>