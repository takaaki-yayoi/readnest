<?php
/**
 * Performance Helper Functions
 * 
 * This file contains functions to improve page loading performance
 * and optimize Core Web Vitals scores.
 * 
 * Note: Some functions have been moved to page_speed_config.php to avoid duplication:
 * - generatePreloadTags()
 * - getCriticalCSS()
 * - deferScript()
 * - generateResourceHints()
 */

declare(strict_types=1);

// generatePreloadTags() function has been moved to page_speed_config.php

/**
 * Generate prefetch tags for likely next pages
 * 
 * @param array $urls Array of URLs to prefetch
 * @return string HTML link tags
 */
function generatePrefetchTags(array $urls): string {
    $tags = [];
    
    foreach ($urls as $url) {
        $tags[] = sprintf(
            '<link rel="prefetch" href="%s">',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        );
    }
    
    return implode("\n", $tags);
}

/**
 * Optimize image tag with lazy loading and proper sizing
 * 
 * @param string $src Image source URL
 * @param string $alt Alt text
 * @param array $options Additional options
 * @return string Optimized img tag
 */
function optimizedImage(string $src, string $alt, array $options = []): string {
    $defaults = [
        'loading' => 'lazy',
        'decoding' => 'async',
        'class' => '',
        'width' => '',
        'height' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    // 画像URLの最適化（no-imageチェック）
    if (strpos($src, 'noimage') !== false || empty($src)) {
        $src = '/img/no-image-book.png';
    }
    
    $attributes = [
        'src' => htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
        'alt' => htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'),
        'loading' => $options['loading'],
        'decoding' => $options['decoding']
    ];
    
    if (!empty($options['class'])) {
        $attributes['class'] = htmlspecialchars($options['class'], ENT_QUOTES, 'UTF-8');
    }
    
    if (!empty($options['width'])) {
        $attributes['width'] = htmlspecialchars($options['width'], ENT_QUOTES, 'UTF-8');
    }
    
    if (!empty($options['height'])) {
        $attributes['height'] = htmlspecialchars($options['height'], ENT_QUOTES, 'UTF-8');
    }
    
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $attr_string .= sprintf(' %s="%s"', $key, $value);
    }
    
    return sprintf('<img%s>', $attr_string);
}

// getCriticalCSS() function has been moved to page_speed_config.php

// deferScript() function has been moved to page_speed_config.php

// generateResourceHints() function has been moved to page_speed_config.php

/**
 * Inline small CSS files to reduce requests
 * 
 * @param string $css_file Path to CSS file
 * @param int $max_size Maximum file size to inline (bytes)
 * @return string Inline CSS or link tag
 */
function inlineSmallCSS(string $css_file, int $max_size = 2048): string {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $css_file;
    
    if (file_exists($full_path) && filesize($full_path) <= $max_size) {
        $css_content = file_get_contents($full_path);
        return '<style>' . $css_content . '</style>';
    }
    
    return sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($css_file, ENT_QUOTES, 'UTF-8'));
}

/**
 * Generate responsive image srcset
 * 
 * @param string $base_url Base image URL
 * @param array $sizes Array of sizes
 * @return string srcset attribute value
 */
function generateSrcset(string $base_url, array $sizes = []): string {
    if (empty($sizes)) {
        $sizes = ['small' => '300w', 'medium' => '600w', 'large' => '900w'];
    }
    
    $srcset_parts = [];
    foreach ($sizes as $suffix => $descriptor) {
        // 画像URLの拡張子を取得
        $path_info = pathinfo($base_url);
        $url = $path_info['dirname'] . '/' . $path_info['filename'] . '_' . $suffix . '.' . $path_info['extension'];
        $srcset_parts[] = $url . ' ' . $descriptor;
    }
    
    return implode(', ', $srcset_parts);
}

/**
 * Optimize database query results for JSON response
 * 
 * @param array $data Query results
 * @return string JSON encoded data with proper headers
 */
function optimizedJsonResponse(array $data): string {
    // gzip圧縮を有効化
    if (!headers_sent() && extension_loaded('zlib')) {
        ob_start('ob_gzhandler');
    }
    
    // 適切なヘッダーを設定
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=300'); // 5分間キャッシュ
    
    // 最小化されたJSONを返す
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}