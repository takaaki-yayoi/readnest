<?php
/**
 * キャッシュ制御ヘッダーの設定
 * 動的コンテンツのブラウザキャッシュを防ぐ
 */

function setCacheControlHeaders() {
    // キャッシュを完全に無効化
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
    
    // コンテンツが動的であることを明示
    header('X-Content-Type-Options: nosniff');
    header('Vary: Accept-Encoding, Cookie');
}

/**
 * 静的リソース用のキャッシュヘッダー
 */
function setStaticCacheHeaders($days = 30) {
    $seconds = $days * 24 * 60 * 60;
    header('Cache-Control: public, max-age=' . $seconds);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
}

/**
 * APIレスポンス用のキャッシュヘッダー
 */
function setAPICacheHeaders() {
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
    header('Content-Type: application/json; charset=utf-8');
}
?>