<?php
/**
 * Googleログイン開始ページ
 * Google OAuth認証URLにリダイレクトする
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/google_oauth.php');

// セッション開始（既にconfig.phpで開始されている場合もあるため確認）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 既にログイン済みの場合は本棚へリダイレクト
if (isset($_SESSION['AUTH_USER']) && $_SESSION['AUTH_USER'] != '') {
    header('Location: /bookshelf.php');
    exit;
}

try {
    // Google OAuth設定が存在しない場合はエラー
    if (!file_exists(dirname(__DIR__) . '/config/google_oauth.php')) {
        throw new Exception('Google OAuth設定が見つかりません');
    }
    
    // Google OAuthインスタンスを作成
    $google = new GoogleOAuth();
    
    // CSRF対策用のstateを生成
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;
    
    // リダイレクト元URLを保存（ログイン後に戻るため）
    if (isset($_SERVER['HTTP_REFERER'])) {
        $_SESSION['google_oauth_redirect'] = $_SERVER['HTTP_REFERER'];
    }
    
    // Google認証URLを生成してリダイレクト
    $authUrl = $google->getAuthUrl($state);
    header('Location: ' . $authUrl);
    exit;
    
} catch (Exception $e) {
    // エラー時は通常のログインページへ
    $_SESSION['error_message'] = 'Googleログインの開始に失敗しました: ' . $e->getMessage();
    header('Location: /');
    exit;
}