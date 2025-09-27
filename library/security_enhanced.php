<?php
/**
 * 強化されたセキュリティ関数
 * XSS対策、SQLインジェクション対策の改善版
 */

declare(strict_types=1);

/**
 * HTMLエスケープ（コンテキスト対応）
 */
class XSS {
    /**
     * HTML本文用エスケープ
     */
    public static function escape(string $string): string {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * HTML属性用エスケープ
     */
    public static function attr(string $string): string {
        // 属性値は必ずダブルクォートで囲むこと
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * JavaScript文字列用エスケープ
     */
    public static function js(string $string): string {
        // JSON encodeを使用してJavaScript安全な文字列に
        return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    /**
     * URL用エスケープ
     */
    public static function url(string $string): string {
        return rawurlencode($string);
    }
    
    /**
     * CSS用エスケープ
     */
    public static function css(string $string): string {
        // CSSコンテキストでの危険な文字をエスケープ
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $string);
    }
    
    /**
     * ファイル名用サニタイズ
     */
    public static function filename(string $string): string {
        // 危険な文字を除去
        $string = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $string);
        // ディレクトリトラバーサル対策
        $string = str_replace(['..', '/', '\\'], '', $string);
        return $string;
    }
    
    /**
     * テキスト内のURLを自動的にリンクに変換（安全版）
     * 
     * @param string $text 変換対象のテキスト
     * @return string URLがリンクに変換されたテキスト
     */
    public static function autoLink(string $text): string {
        // まずHTMLエスケープ
        $text = self::escape($text);
        
        // URL正規表現パターン（日本語URLにも対応）
        $pattern = '/(https?:\/\/[a-zA-Z0-9\-\._~:\/\?#\[\]@!$&\'()*+,;=%\p{L}\p{N}]+)/u';
        
        // URLをリンクに変換（エスケープ済みのテキストに対して実行）
        $text = preg_replace_callback($pattern, function($matches) {
            $url = $matches[1];
            
            // URLの検証（日本語URLも許可）
            if (filter_var($url, FILTER_VALIDATE_URL) !== false || 
                preg_match('/^https?:\/\/[^\s<>"{}|\\\\^`\[\]]+$/u', $url)) {
                // 安全なリンクを生成
                return sprintf(
                    '<a href="%s" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline break-all">%s</a>',
                    self::attr($url),
                    self::escape($url)
                );
            }
            
            // 無効なURLの場合はそのまま返す
            return $url;
        }, $text);
        
        return $text;
    }
    
    /**
     * nl2brとautoLinkを組み合わせた関数
     * レビューなどのテキスト表示に使用
     * 
     * @param string $text 変換対象のテキスト
     * @return string 改行がbrタグに、URLがリンクに変換されたテキスト
     */
    public static function nl2brAutoLink(string $text): string {
        // URLを先にリンクに変換
        $text = self::autoLink($text);
        // その後改行を<br>に変換
        return nl2br($text);
    }
}

/**
 * SQLインジェクション対策
 */
class SQLSecurity {
    /**
     * LIKE句用のエスケープ
     */
    public static function escapeLike(string $string): string {
        // LIKE句で特別な意味を持つ文字をエスケープ
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $string);
    }
    
    /**
     * 識別子（テーブル名、カラム名）の検証
     */
    public static function validateIdentifier(string $identifier): bool {
        // 英数字とアンダースコアのみ許可
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier) === 1;
    }
    
    /**
     * IN句用の値の準備
     */
    public static function prepareInClause(array $values): array {
        // 数値配列の場合
        if (count($values) > 0 && is_numeric($values[0])) {
            return array_map('intval', $values);
        }
        // 文字列配列の場合（プリペアドステートメントで使用）
        return $values;
    }
    
    /**
     * ORDER BY句の検証
     */
    public static function validateOrderBy(string $column, array $allowedColumns): ?string {
        $column = strtolower(trim($column));
        $allowedColumns = array_map('strtolower', $allowedColumns);
        
        if (in_array($column, $allowedColumns, true)) {
            return $column;
        }
        
        return null;
    }
    
    /**
     * LIMIT句の検証
     */
    public static function validateLimit($limit): ?int {
        if (!is_numeric($limit)) {
            return null;
        }
        
        $limit = (int)$limit;
        
        // 妥当な範囲内かチェック
        if ($limit > 0 && $limit <= 1000) {
            return $limit;
        }
        
        return null;
    }
}

/**
 * 入力検証
 */
class InputValidation {
    /**
     * メールアドレスの検証
     */
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * URLの検証
     */
    public static function url(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * 整数の検証
     */
    public static function int($value, ?int $min = null, ?int $max = null): bool {
        if (!is_numeric($value)) {
            return false;
        }
        
        $value = (int)$value;
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 文字列長の検証
     */
    public static function stringLength(string $string, int $min = 0, ?int $max = null): bool {
        $length = mb_strlen($string, 'UTF-8');
        
        if ($length < $min) {
            return false;
        }
        
        if ($max !== null && $length > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 日付の検証
     */
    public static function date(string $date, string $format = 'Y-m-d'): bool {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * 電話番号の検証（日本）
     */
    public static function phoneJP(string $phone): bool {
        // ハイフンあり・なし両対応
        $pattern = '/^0\d{1,4}-?\d{1,4}-?\d{3,4}$/';
        return preg_match($pattern, $phone) === 1;
    }
    
    /**
     * 郵便番号の検証（日本）
     */
    public static function postalCodeJP(string $code): bool {
        // ハイフンあり・なし両対応
        $pattern = '/^\d{3}-?\d{4}$/';
        return preg_match($pattern, $code) === 1;
    }
}

/**
 * セキュアなランダム文字列生成
 */
class SecureRandom {
    /**
     * ランダム文字列生成
     */
    public static function string(int $length = 32): string {
        return bin2hex(random_bytes((int)ceil($length / 2)));
    }
    
    /**
     * ランダムトークン生成（URL安全）
     */
    public static function token(int $length = 32): string {
        $bytes = random_bytes($length);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
    
    /**
     * 数字のみのランダム文字列
     */
    public static function numeric(int $length = 6): string {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= random_int(0, 9);
        }
        return $result;
    }
}

/**
 * セキュリティヘッダーの設定
 */
if (!function_exists('setEnhancedSecurityHeaders')) {
    function setEnhancedSecurityHeaders(): void {
        // XSS対策
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        
        // クリックジャッキング対策
        header('X-Frame-Options: SAMEORIGIN');
        
        // HTTPS強制
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // リファラー制御
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // 機能制限
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    }
}

/**
 * コンテンツセキュリティポリシーの設定
 */
if (!function_exists('setCSPHeader')) {
    function setCSPHeader(array $directives = []): void {
    $defaultDirectives = [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
        'style-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
        'img-src' => "'self' data: https:",
        'font-src' => "'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
        'connect-src' => "'self'",
        'frame-ancestors' => "'self'",
        'base-uri' => "'self'",
        'form-action' => "'self'"
    ];
    
    $directives = array_merge($defaultDirectives, $directives);
    
    $csp = [];
    foreach ($directives as $directive => $value) {
        $csp[] = $directive . ' ' . $value;
    }
    
    header('Content-Security-Policy: ' . implode('; ', $csp));
    }
}
?>