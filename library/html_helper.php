<?php
/**
 * HTMLヘルパー関数
 * h()関数を共通化
 */

// security.phpが読み込まれていることを確認
if (!function_exists('html')) {
    require_once(dirname(__FILE__) . '/security.php');
}

// h()関数を定義（html()のショートカット）
if (!function_exists('h')) {
    function h($str) {
        return html($str);
    }
}
?>