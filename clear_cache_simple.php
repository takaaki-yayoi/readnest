<?php
/**
 * シンプルなキャッシュクリアスクリプト
 */

$cache_dir = dirname(__FILE__) . '/cache';

if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '/*.cache');
    $count = count($files);
    
    foreach ($files as $file) {
        @unlink($file);
    }
    
    echo "Cleared $count cache files.\n";
} else {
    echo "Cache directory not found.\n";
}
?>