<?php
/**
 * アセットバージョン管理
 * JavaScriptやCSSファイルのキャッシュバスティング
 */

declare(strict_types=1);

/**
 * アセットのバージョン番号
 * 更新時はこの値を変更する
 */
define('ASSET_VERSION', '1.0.' . date('YmdHis'));

/**
 * アセットURLにバージョンパラメータを追加
 * 
 * @param string $url アセットのURL
 * @param bool $timestamp タイムスタンプを使用するか
 * @return string バージョン付きURL
 */
function assetUrl(string $url, bool $timestamp = false): string {
    $separator = strpos($url, '?') !== false ? '&' : '?';
    
    if ($timestamp) {
        // ファイルの更新時刻を使用（より正確）
        $filePath = $_SERVER['DOCUMENT_ROOT'] . parse_url($url, PHP_URL_PATH);
        if (file_exists($filePath)) {
            $version = filemtime($filePath);
        } else {
            $version = ASSET_VERSION;
        }
    } else {
        // 定義されたバージョンを使用
        $version = ASSET_VERSION;
    }
    
    return $url . $separator . 'v=' . $version;
}

/**
 * JavaScriptタグを生成（バージョン付き）
 */
function jsTag(string $url, array $attributes = []): string {
    $versionedUrl = assetUrl($url, true);
    $attrString = '';
    
    foreach ($attributes as $key => $value) {
        $attrString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    return '<script src="' . htmlspecialchars($versionedUrl) . '"' . $attrString . '></script>';
}

/**
 * CSSタグを生成（バージョン付き）
 */
function cssTag(string $url, array $attributes = []): string {
    $versionedUrl = assetUrl($url, true);
    $attrString = '';
    
    // デフォルト属性
    if (!isset($attributes['rel'])) {
        $attributes['rel'] = 'stylesheet';
    }
    
    foreach ($attributes as $key => $value) {
        $attrString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    return '<link href="' . htmlspecialchars($versionedUrl) . '"' . $attrString . '>';
}

/**
 * 複数のJSファイルを一括で出力
 */
function jsBundle(array $urls, array $globalAttributes = []): string {
    $output = '';
    foreach ($urls as $url) {
        $output .= jsTag($url, $globalAttributes) . "\n";
    }
    return $output;
}

/**
 * 複数のCSSファイルを一括で出力
 */
function cssBundle(array $urls, array $globalAttributes = []): string {
    $output = '';
    foreach ($urls as $url) {
        $output .= cssTag($url, $globalAttributes) . "\n";
    }
    return $output;
}

/**
 * Service Workerを使用したキャッシュ制御スクリプト
 */
function cacheControlScript(): string {
    return <<<'SCRIPT'
<script>
// キャッシュバージョン管理
(function() {
    const CACHE_VERSION = 'readnest-v' + new Date().getTime();
    
    // Service Workerがサポートされている場合
    if ('serviceWorker' in navigator) {
        // 古いキャッシュをクリア
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.filter(function(cacheName) {
                    return cacheName.startsWith('readnest-') && cacheName !== CACHE_VERSION;
                }).map(function(cacheName) {
                    return caches.delete(cacheName);
                })
            );
        });
    }
    
    // 強制リロード用のメタタグを追加
    if (window.location.search.includes('force_refresh=1')) {
        const meta = document.createElement('meta');
        meta.httpEquiv = 'Cache-Control';
        meta.content = 'no-cache, no-store, must-revalidate';
        document.head.appendChild(meta);
        
        // URLからパラメータを削除
        const url = new URL(window.location);
        url.searchParams.delete('force_refresh');
        window.history.replaceState({}, document.title, url.toString());
    }
})();
</script>
SCRIPT;
}
?>