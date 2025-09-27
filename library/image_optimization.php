<?php
/**
 * Image Optimization Helper Functions
 * 
 * This file contains functions to optimize image loading
 * for better performance and SEO.
 */

declare(strict_types=1);

/**
 * Generate an optimized img tag with lazy loading
 * 
 * @param string $src Image source URL
 * @param string $alt Alternative text
 * @param int|null $width Image width
 * @param int|null $height Image height
 * @param string $class CSS classes
 * @param array $attributes Additional HTML attributes
 * @return string HTML img tag
 */
function generateOptimizedImage(
    string $src, 
    string $alt, 
    ?int $width = null, 
    ?int $height = null, 
    string $class = '', 
    array $attributes = []
): string {
    // デフォルトの属性
    $default_attributes = [
        'loading' => 'lazy',
        'decoding' => 'async'
    ];
    
    // 属性をマージ
    $attributes = array_merge($default_attributes, $attributes);
    
    // 基本属性を設定
    $img_attributes = [
        'src' => htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
        'alt' => htmlspecialchars($alt, ENT_QUOTES, 'UTF-8')
    ];
    
    // 幅と高さを設定（レイアウトシフト防止）
    if ($width !== null) {
        $img_attributes['width'] = $width;
    }
    if ($height !== null) {
        $img_attributes['height'] = $height;
    }
    
    // クラスを設定
    if (!empty($class)) {
        $img_attributes['class'] = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    }
    
    // その他の属性を追加
    foreach ($attributes as $key => $value) {
        $img_attributes[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    // HTML生成
    $html = '<img';
    foreach ($img_attributes as $key => $value) {
        $html .= sprintf(' %s="%s"', $key, $value);
    }
    $html .= '>';
    
    return $html;
}

/**
 * Generate a responsive picture element with multiple sources
 * 
 * @param array $sources Array of source configurations
 * @param string $fallback_src Fallback image source
 * @param string $alt Alternative text
 * @param string $class CSS classes
 * @return string HTML picture element
 */
function generateResponsivePicture(
    array $sources, 
    string $fallback_src, 
    string $alt, 
    string $class = ''
): string {
    $html = '<picture>';
    
    // 各ソースを追加
    foreach ($sources as $source) {
        $source_attributes = [];
        
        if (isset($source['srcset'])) {
            $source_attributes['srcset'] = htmlspecialchars($source['srcset'], ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($source['media'])) {
            $source_attributes['media'] = htmlspecialchars($source['media'], ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($source['type'])) {
            $source_attributes['type'] = htmlspecialchars($source['type'], ENT_QUOTES, 'UTF-8');
        }
        
        $html .= '<source';
        foreach ($source_attributes as $key => $value) {
            $html .= sprintf(' %s="%s"', $key, $value);
        }
        $html .= '>';
    }
    
    // フォールバック画像
    $html .= generateOptimizedImage($fallback_src, $alt, null, null, $class);
    $html .= '</picture>';
    
    return $html;
}

/**
 * Get optimized book cover image URL
 * 
 * @param string $original_url Original image URL
 * @param string $size Size preset (small, medium, large)
 * @return string Optimized image URL
 */
function getOptimizedBookCoverUrl(string $original_url, string $size = 'medium'): string {
    // noimage の場合はデフォルト画像を返す
    if (empty($original_url) || strpos($original_url, 'noimage') !== false) {
        return '/img/no-image-book.png';
    }
    
    // Amazon画像の場合はサイズ指定を変更
    if (strpos($original_url, 'images-na.ssl-images-amazon.com') !== false || 
        strpos($original_url, 'ecx.images-amazon.com') !== false) {
        
        $size_map = [
            'small' => '_SL160_',
            'medium' => '_SL300_',
            'large' => '_SL500_'
        ];
        
        $target_size = $size_map[$size] ?? '_SL300_';
        
        // 既存のサイズ指定を置換
        $pattern = '/_SL\d+_/';
        if (preg_match($pattern, $original_url)) {
            return preg_replace($pattern, $target_size, $original_url);
        } else {
            // サイズ指定がない場合は追加
            return str_replace('.jpg', $target_size . '.jpg', $original_url);
        }
    }
    
    // Google Books APIの場合
    if (strpos($original_url, 'books.google.com') !== false) {
        $zoom_map = [
            'small' => 'zoom=1',
            'medium' => 'zoom=2',
            'large' => 'zoom=3'
        ];
        
        $target_zoom = $zoom_map[$size] ?? 'zoom=2';
        
        // zoom パラメータを置換または追加
        if (strpos($original_url, 'zoom=') !== false) {
            return preg_replace('/zoom=\d/', $target_zoom, $original_url);
        } else {
            $separator = strpos($original_url, '?') !== false ? '&' : '?';
            return $original_url . $separator . $target_zoom;
        }
    }
    
    return $original_url;
}

/**
 * Generate book cover image HTML with optimization
 * 
 * @param array $book Book data array
 * @param string $size Size preset
 * @param array $options Additional options
 * @return string HTML img tag
 */
function generateBookCoverImage(array $book, string $size = 'medium', array $options = []): string {
    $title = $book['title'] ?? $book['name'] ?? '本の画像';
    $author = $book['author'] ?? '';
    $image_url = $book['image_url'] ?? '';
    
    // Alt テキストの生成
    $alt = $title;
    if (!empty($author)) {
        $alt .= ' - ' . $author;
    }
    
    // 画像URLの最適化
    $optimized_url = getOptimizedBookCoverUrl($image_url, $size);
    
    // サイズに応じた幅と高さ
    $dimensions = [
        'small' => ['width' => 80, 'height' => 120],
        'medium' => ['width' => 120, 'height' => 180],
        'large' => ['width' => 200, 'height' => 300]
    ];
    
    $dimension = $dimensions[$size] ?? $dimensions['medium'];
    
    // クラスの設定
    $class = $options['class'] ?? 'book-cover';
    
    // 追加属性
    $attributes = $options['attributes'] ?? [];
    
    return generateOptimizedImage(
        $optimized_url,
        $alt,
        $dimension['width'],
        $dimension['height'],
        $class,
        $attributes
    );
}

/**
 * Generate user profile photo HTML with optimization
 * 
 * @param string $user_id User ID
 * @param string $photo_url Profile photo URL
 * @param string $nickname User nickname
 * @param int $size Size in pixels
 * @return string HTML img tag
 */
function generateUserProfilePhoto(
    string $user_id, 
    string $photo_url, 
    string $nickname, 
    int $size = 50
): string {
    // デフォルト画像の処理
    if (empty($photo_url) || strpos($photo_url, 'no-image-user') !== false) {
        $photo_url = '/img/no-image-user.png';
    }
    
    $alt = $nickname . 'さんのプロフィール写真';
    
    return generateOptimizedImage(
        $photo_url,
        $alt,
        $size,
        $size,
        'user-photo',
        ['referrerpolicy' => 'no-referrer']
    );
}

/**
 * Preload critical images
 * 
 * @param array $images Array of image URLs to preload
 * @return string HTML link tags for preloading
 */
function generateImagePreloadTags(array $images): string {
    $tags = [];
    
    foreach ($images as $image) {
        if (is_string($image)) {
            $tags[] = sprintf(
                '<link rel="preload" as="image" href="%s">',
                htmlspecialchars($image, ENT_QUOTES, 'UTF-8')
            );
        } elseif (is_array($image) && isset($image['href'])) {
            $tag = sprintf(
                '<link rel="preload" as="image" href="%s"',
                htmlspecialchars($image['href'], ENT_QUOTES, 'UTF-8')
            );
            
            if (isset($image['type'])) {
                $tag .= sprintf(' type="%s"', htmlspecialchars($image['type'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($image['media'])) {
                $tag .= sprintf(' media="%s"', htmlspecialchars($image['media'], ENT_QUOTES, 'UTF-8'));
            }
            
            $tags[] = $tag . '>';
        }
    }
    
    return implode("\n", $tags);
}