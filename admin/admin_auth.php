<?php
/**
 * 管理者認証ミドルウェア
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/session.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者メールアドレスのリスト
define('ADMIN_EMAILS', [
    'admin@readnest.jp',
    'icotfeels@gmail.com'
]);

// 後方互換性のため単一のEMAILも定義
define('ADMIN_EMAIL', 'admin@readnest.jp');

/**
 * 管理者権限をチェック
 * @return bool 管理者の場合true
 */
function isAdmin(): bool {
    if (!isset($_SESSION['AUTH_USER'])) {
        return false;
    }
    
    $user_id = $_SESSION['AUTH_USER'];
    $user_info = getUserInformation($user_id);
    
    if (!$user_info) {
        return false;
    }
    
    // 複数の管理者メールアドレスをチェック
    return in_array($user_info['email'], ADMIN_EMAILS, true);
}

/**
 * 管理者認証を要求
 * 管理者でない場合はログインページにリダイレクト
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        // ログインしていない場合
        if (!isset($_SESSION['AUTH_USER'])) {
            $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'];
            header('Location: /admin/login.php');
            exit;
        }
        
        // ログインしているが管理者でない場合
        header('HTTP/1.1 403 Forbidden');
        include(dirname(__DIR__) . '/admin/403.php');
        exit;
    }
}

/**
 * 管理者情報を取得
 * @return array|null 管理者情報
 */
function getAdminInfo(): ?array {
    if (!isAdmin()) {
        return null;
    }
    
    $user_id = $_SESSION['AUTH_USER'];
    return getUserInformation($user_id);
}
?>