<?php
/**
 * CSRF対策ライブラリ
 * トークンの生成、検証、フォームヘルパー
 */

declare(strict_types=1);

/**
 * CSRFトークンを生成
 */
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken(): string {
        // セッション開始の前にセッション名を設定
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.name', 'DOKUSHO');
            session_start();
        }
        
        // 既存のトークンがあれば再利用（同一セッション内）
        // タイムスタンプをスライドさせ、アクティブな閲覧中は失効しないようにする
        if (isset($_SESSION['csrf_token']) && !empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token_time'] = time();
            return $_SESSION['csrf_token'];
        }

        // 新しいトークンを生成
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
}

/**
 * CSRFトークンを検証
 * 
 * @param string|null $token 検証するトークン
 * @param int $expiry トークンの有効期限（秒）
 * @return bool 検証成功ならtrue
 */
if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken(?string $token, int $expiry = 7200): bool {
        // セッション開始の前にセッション名を設定
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.name', 'DOKUSHO');
            session_start();
        }
        
        // トークンが存在しない
        if (empty($token) || !isset($_SESSION['csrf_token'])) {
            error_log('CSRF verify: Token not found in session or empty input token');
            return false;
        }
        
        // トークンが一致しない
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            // トークンの値はログに残さない（ログ経由で漏れると偽装に使える）
            error_log('CSRF verify: Token mismatch');
            return false;
        }
        
        // トークンの有効期限をチェック
        if (isset($_SESSION['csrf_token_time'])) {
            $tokenAge = time() - $_SESSION['csrf_token_time'];
            if ($tokenAge > $expiry) {
                // 期限切れのトークンを削除
                error_log('CSRF verify: Token expired - Age: ' . $tokenAge . ' seconds');
                unset($_SESSION['csrf_token']);
                unset($_SESSION['csrf_token_time']);
                return false;
            }
        }
        
        return true;
    }
}

/**
 * POSTリクエストでCSRFトークンを検証
 * 失敗時は403エラーを返して終了
 */
if (!function_exists('requireCSRFToken')) {
    function requireCSRFToken(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!verifyCSRFToken($token)) {
                http_response_code(403);
                // デバッグ情報をログ出力
                // セッションID・トークン・セッション内容はログに残さない（漏れるとなりすましに使える）
                error_log('CSRF token validation failed. IP: ' . $_SERVER['REMOTE_ADDR']
                    . ' path: ' . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
                die('不正なリクエストです。ページを再読み込みしてください。');
            }
        }
    }
}

/**
 * CSRFトークンの隠しフィールドを生成
 */
if (!function_exists('csrfField')) {
    function csrfField(): string {
        $token = generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

/**
 * CSRFトークンのメタタグを生成（AJAX用）
 */
if (!function_exists('csrfMeta')) {
    function csrfMeta(): string {
        $token = generateCSRFToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

/**
 * トークンをリフレッシュ
 */
if (!function_exists('refreshCSRFToken')) {
    function refreshCSRFToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // 既存のトークンを削除
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        
        // 新しいトークンを生成
        return generateCSRFToken();
    }
}

/**
 * AJAXリクエスト用のCSRFトークン検証
 */
if (!function_exists('verifyAjaxCSRFToken')) {
    function verifyAjaxCSRFToken(): bool {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
        return verifyCSRFToken($token);
    }
}

/**
 * JavaScript用のCSRFトークン取得関数
 */
if (!function_exists('getCSRFTokenForJS')) {
    function getCSRFTokenForJS(): string {
        return json_encode([
            'token' => generateCSRFToken(),
            'header' => 'X-CSRF-Token'
        ]);
    }
}

/**
 * CSRFトークンを取得する関数（エイリアス）
 */
if (!function_exists('getCSRFToken')) {
    function getCSRFToken(): string {
        return generateCSRFToken();
    }
}
?>