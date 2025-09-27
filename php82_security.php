<?php
/**
 * PHP 8.2.28 セキュリティ強化設定
 */

declare(strict_types=1);

// セキュリティヘッダーの設定
if (!function_exists('setSecurityHeaders')) {
    function setSecurityHeaders(): void {
    // XSS保護
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // HSTS (HTTPS環境でのみ)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // 混在コンテンツのブロック（HTTPSページでHTTPリソースを自動的にHTTPSに変換）
    header('Content-Security-Policy: upgrade-insecure-requests');
    
    // コンテンツセキュリティポリシー
    $csp = "default-src 'self' https:; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
           "font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self' https:; " .
           "frame-src 'none'; " .
           "object-src 'none'; " .
           "base-uri 'self'";
    header("Content-Security-Policy: $csp");
    
    // リファラーポリシー
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // パーミッションポリシー
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}

// 入力値のサニタイゼーション（PHP 8.2対応）
function sanitizeInput(mixed $input): string {
    return match (true) {
        is_string($input) => htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        is_numeric($input) => (string)$input,
        is_bool($input) => $input ? '1' : '0',
        is_null($input) => '',
        default => ''
    };
}

// CSRFトークンの生成と検証は library/csrf.php の実装を使用
// generateCSRFToken() は library/csrf.php で定義
// verifyCSRFToken() は library/csrf.php で定義

// レート制限チェック（シンプル版）
// rate_limiter.phpでより高機能な実装が提供されているため、この関数は使用しない
// function checkRateLimit は rate_limiter.php で定義

// パスワードハッシュ化（PHP 8.2推奨方式）
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64MB
        'time_cost' => 4,       // 4回の反復
        'threads' => 3          // 3つのスレッド
    ]);
}

// パスワード検証
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// セキュアなセッション設定
function initSecureSession(): void {
    // セッションが既に開始されている場合は設定のみ適用可能な項目を設定
    if (session_status() !== PHP_SESSION_NONE) {
        // セッションが既に開始されている場合は何もしない
        return;
    }
    
    $sessionName = 'DOKUSHO_SESSID';
    
    // セッション設定（セッション開始前のみ有効）
    ini_set('session.name', $sessionName);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', '7200'); // 2時間
    
    // セッション開始
    session_start();
}

// セッションのセキュリティチェック（セッション開始後に実行）
function applySessionSecurity(): void {
    // セッションが開始されていない場合は何もしない
    if (session_status() === PHP_SESSION_NONE) {
        return;
    }
    
    // セッションハイジャック対策
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
    
    // セッション有効期限チェック
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > 7200) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

// ログ記録（セキュリティイベント）
function logSecurityEvent(string $event, array $context = []): void {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'context' => $context
    ];
    
    error_log('SECURITY: ' . json_encode($logEntry, JSON_UNESCAPED_UNICODE));
}

// ファイルアップロードの検証
function validateFileUpload(array $file, array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']): array {
    $errors = [];
    
    // ファイルエラーチェック
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'ファイルアップロードエラー: ' . $file['error'];
        return $errors;
    }
    
    // ファイルサイズチェック
    if ($file['size'] > MAX_PHOTO_FILE_SIZE) {
        $errors[] = 'ファイルサイズが大きすぎます';
    }
    
    // MIMEタイプチェック
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes, true)) {
        $errors[] = '許可されていないファイル形式です';
    }
    
    // 拡張子チェック
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $allowedExtensions, true)) {
        $errors[] = '許可されていない拡張子です';
    }
    
    return $errors;
}

// データベース接続のセキュリティ強化
function getSecureDBOptions(): array {
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    ];
}

// モダンテンプレート使用時にセキュリティ機能を有効化
if (function_exists('isModernTemplateEnabled') && isModernTemplateEnabled()) {
    setSecurityHeaders();
    initSecureSession();
    applySessionSecurity();
}