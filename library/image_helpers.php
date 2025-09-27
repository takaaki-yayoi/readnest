<?php
/**
 * 画像関連のヘルパー関数
 * 画像URLの正規化、フォールバック処理
 */

if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
    die('reference for this file is not allowed.');
}

/**
 * 本の画像URLを正規化
 * 無効な画像URLをデフォルト画像に置き換える
 * 
 * @param string|null $image_url 画像URL
 * @return string 正規化された画像URL
 */
function normalizeBookImageUrl($image_url) {
    // 空の場合や無効な値の場合
    if (empty($image_url) || $image_url === null || $image_url === '0') {
        return '/img/no-image-book.png';
    }
    
    // "noimage"を含む場合
    if (strpos($image_url, 'noimage') !== false) {
        return '/img/no-image-book.png';
    }
    
    // dokusho.noteigi.comのnoimage.jpgの場合
    if (strpos($image_url, 'dokusho.noteigi.com/img/noimage.jpg') !== false) {
        return '/img/no-image-book.png';
    }
    
    // "no-image"を含む場合  
    if (strpos($image_url, 'no-image') !== false) {
        return '/img/no-image-book.png';
    }
    
    // プレースホルダー的なURLの場合
    $placeholder_patterns = [
        'placeholder',
        'default',
        'missing',
        'unavailable',
        'error'
    ];
    
    foreach ($placeholder_patterns as $pattern) {
        if (strpos(strtolower($image_url), $pattern) !== false) {
            return '/img/no-image-book.png';
        }
    }
    
    // 有効な画像URLとして返す
    return $image_url;
}

/**
 * ユーザーの画像URLを正規化
 * 無効な画像URLをデフォルト画像に置き換える
 * 
 * @param string|null $image_url 画像URL
 * @return string 正規化された画像URL
 */
function normalizeUserImageUrl($image_url) {
    // 空の場合や無効な値の場合
    if (empty($image_url) || $image_url === null || $image_url === '0') {
        return '/img/no-image-user.png';
    }
    
    // "noimage"を含む場合
    if (strpos($image_url, 'noimage') !== false) {
        return '/img/no-image-user.png';
    }
    
    // "no-image"を含む場合
    if (strpos($image_url, 'no-image') !== false) {
        return '/img/no-image-user.png';
    }
    
    // 有効な画像URLとして返す
    return $image_url;
}

/**
 * HTMLimg要素を生成（エラーハンドリング付き）
 * 
 * @param string $src 画像URL
 * @param string $alt altテキスト
 * @param string $class CSSクラス
 * @param string $type 'book' または 'user'
 * @return string HTML img要素
 */
function generateImageTag($src, $alt = '', $class = '', $type = 'book') {
    // 画像URLを正規化
    if ($type === 'user') {
        $normalized_src = normalizeUserImageUrl($src);
        $fallback_src = '/img/no-image-user.png';
    } else {
        $normalized_src = normalizeBookImageUrl($src);
        $fallback_src = '/img/no-image-book.png';
    }
    
    $html = '<img src="' . htmlspecialchars($normalized_src) . '"';
    $html .= ' alt="' . htmlspecialchars($alt) . '"';
    
    if (!empty($class)) {
        $html .= ' class="' . htmlspecialchars($class) . '"';
    }
    
    $html .= ' onerror="this.src=\'' . $fallback_src . '\'"';
    $html .= '>';
    
    return $html;
}

/**
 * 書籍の表紙画像をリサイズ
 * アスペクト比を維持しつつ、指定された最大幅・高さに収まるようにリサイズ
 * 
 * @param string $file_path 画像ファイルのパス
 * @param int $max_width 最大幅
 * @param int $max_height 最大高さ
 * @return bool 成功した場合true
 */
function resizeBookCoverImage($file_path, $max_width = 400, $max_height = 600) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    // 画像情報を取得
    $image_info = getimagesize($file_path);
    if ($image_info === false) {
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // リサイズが必要かどうかチェック
    if ($width <= $max_width && $height <= $max_height) {
        return true; // リサイズ不要
    }
    
    // アスペクト比を維持しつつリサイズ
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // 元画像を読み込み
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($file_path);
            break;
        case 'image/png':
            $source = imagecreatefrompng($file_path);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($file_path);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($file_path);
            break;
        default:
            return false;
    }
    
    if ($source === false) {
        return false;
    }
    
    // リサイズ後の画像を作成
    $resized = imagecreatetruecolor($new_width, $new_height);
    
    // PNGとGIFの透明性を保持
    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // リサイズ実行
    imagecopyresampled(
        $resized, $source,
        0, 0, 0, 0,
        $new_width, $new_height,
        $width, $height
    );
    
    // リサイズ後の画像を保存
    $result = false;
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $result = imagejpeg($resized, $file_path, 85); // 品質85
            break;
        case 'image/png':
            $result = imagepng($resized, $file_path, 9); // 圧縮レベル9
            break;
        case 'image/gif':
            $result = imagegif($resized, $file_path);
            break;
        case 'image/webp':
            $result = imagewebp($resized, $file_path, 85); // 品質85
            break;
    }
    
    // メモリを解放
    imagedestroy($source);
    imagedestroy($resized);
    
    return $result;
}
?>