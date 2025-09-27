<?php
/**
 * 既存ユーザーとGoogleアカウントの連携確認ページ
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/google_oauth.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/page_design.php');

// セッション開始（既にconfig.phpで開始されている場合もあるため確認）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 必要なセッション情報がない場合はトップページへ
if (!isset($_SESSION['google_link_user']) || !isset($_SESSION['google_user_info']) || !isset($_SESSION['google_token_data'])) {
    // デバッグ用：直接アクセスの場合はダミーデータを表示
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        $existingUser = ['user_id' => 'test', 'nickname' => 'テストユーザー'];
        $googleUserInfo = [
            'id' => '12345',
            'email' => 'test@example.com',
            'name' => 'テスト太郎',
            'picture' => null
        ];
        $tokenData = [];
    } else {
        header('Location: /');
        exit;
    }
} else {
    $existingUser = $_SESSION['google_link_user'];
    $googleUserInfo = $_SESSION['google_user_info'];
    $tokenData = $_SESSION['google_token_data'];
}

// POSTの場合は連携処理を実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['link_account']) && $_POST['link_account'] === 'yes') {
        // パスワード確認
        if (!isset($_POST['password']) || empty($_POST['password'])) {
            $error_message = 'パスワードを入力してください';
        } else {
            // パスワード検証
            if (checkPasswordById($existingUser['user_id'], $_POST['password'])) {
                try {
                    // Google OAuthインスタンスを作成
                    $google = new GoogleOAuth();
                    
                    // Google認証情報を保存
                    $google->saveGoogleAuth(
                        $existingUser['user_id'],
                        $googleUserInfo['id'],
                        $googleUserInfo['email'],
                        $googleUserInfo['name'],
                        $googleUserInfo['picture'] ?? null,
                        $tokenData['access_token'],
                        $tokenData['refresh_token'] ?? null,
                        $tokenData['expires_in']
                    );
                    
                    // ログイン処理
                    $_SESSION['AUTH_USER'] = $existingUser['user_id'];
                    $_SESSION['USER_NAME'] = $existingUser['nickname'];
                    
                    // 連携時も自動ログイン設定（15日間）
                    require_once(dirname(__DIR__) . '/library/session.php');
                    setAutoLogin($existingUser['user_id']);
                    
                    // セッションクリーンアップ
                    unset($_SESSION['google_link_user']);
                    unset($_SESSION['google_user_info']);
                    unset($_SESSION['google_token_data']);
                    
                    // リダイレクト
                    header('Location: /bookshelf.php');
                    exit;
                    
                } catch (Exception $e) {
                    $error_message = 'アカウント連携に失敗しました: ' . $e->getMessage();
                }
            } else {
                $error_message = 'パスワードが正しくありません';
            }
        }
    } else {
        // 連携をキャンセル
        unset($_SESSION['google_link_user']);
        unset($_SESSION['google_user_info']);
        unset($_SESSION['google_token_data']);
        header('Location: /');
        exit;
    }
}

// モダン設定を読み込み（必要な定数や関数を定義）
require_once(dirname(__DIR__) . '/modern_config.php');

// ページタイトル
$page_title = 'Googleアカウント連携';

// テンプレート用の変数を設定
$google_link_data = [
    'existing_user' => $existingUser,
    'google_user_info' => $googleUserInfo,
    'error_message' => $error_message ?? null
];

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_google_link.php'));