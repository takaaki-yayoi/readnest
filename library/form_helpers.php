<?php
/**
 * フォーム関連のヘルパー関数
 * CSRF対策やフォーム要素の生成を簡潔に行う
 */

declare(strict_types=1);

require_once(dirname(__FILE__) . '/csrf.php');

/**
 * CSRFトークンフィールドを含むフォーム開始タグを生成
 */
function formOpen(string $action = '', string $method = 'post', array $attributes = []): string {
    $html = '<form action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '" method="' . htmlspecialchars($method, ENT_QUOTES, 'UTF-8') . '"';
    
    foreach ($attributes as $key => $value) {
        $html .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    $html .= '>';
    
    // POSTメソッドの場合はCSRFトークンを自動的に追加
    if (strtolower($method) === 'post') {
        $html .= "\n" . csrfField();
    }
    
    return $html;
}

/**
 * フォーム終了タグ
 */
function formClose(): string {
    return '</form>';
}

/**
 * 既存のフォームに手動でCSRFフィールドを追加する場合
 */
function csrfFieldTag(): void {
    echo csrfField();
}

/**
 * Ajax用のCSRFメタタグを出力
 */
function csrfMetaTag(): void {
    echo csrfMeta();
}

/**
 * JavaScript用のCSRFトークン情報を出力
 */
function csrfJsSetup(): void {
    ?>
    <script>
    // CSRF token setup for AJAX requests
    (function() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            // jQuery AJAX設定（jQueryが存在する場合）
            if (typeof jQuery !== 'undefined') {
                jQuery.ajaxSetup({
                    headers: {
                        'X-CSRF-Token': token.content
                    }
                });
            }
            
            // Fetch API用のヘルパー関数
            window.fetchWithCSRF = function(url, options = {}) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-Token'] = token.content;
                return fetch(url, options);
            };
        }
    })();
    </script>
    <?php
}

/**
 * セキュアな隠しフィールド生成
 */
function hiddenField(string $name, string $value): string {
    return '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . 
           '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * セキュアなテキストフィールド生成
 */
function textField(string $name, string $value = '', array $attributes = []): string {
    $html = '<input type="text" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . 
            '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    
    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . 
                 '="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    $html .= '>';
    return $html;
}

/**
 * セキュアなテキストエリア生成
 */
function textArea(string $name, string $value = '', array $attributes = []): string {
    $html = '<textarea name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';
    
    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . 
                 '="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    $html .= '>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</textarea>';
    return $html;
}

/**
 * セレクトボックス生成
 */
function selectField(string $name, array $options, string $selected = '', array $attributes = []): string {
    $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';
    
    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . 
                 '="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    $html .= '>';
    
    foreach ($options as $value => $label) {
        $html .= '<option value="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
        if ((string)$value === (string)$selected) {
            $html .= ' selected';
        }
        $html .= '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    
    $html .= '</select>';
    return $html;
}
?>