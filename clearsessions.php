<?php
/**
 * ログアウト処理
 * ReadNest - PHP 8.2対応版
 */

declare(strict_types=1);

// 出力バッファリングを有効にしてヘッダー送信前に処理を完了
ob_start();

// config.phpを読み込み（セッション管理を含む）
require_once('config.php');

// デバッグ情報をログに記録
error_log("Logout process started for session: " . session_id());

// ログアウト前にユーザーIDを保存
$logout_user_id = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : null;

// データベースから自動ログインキーを削除
if ($logout_user_id && isset($_COOKIE['AUTOLOGIN'])) {
    global $g_db;
    $delete_sql = "DELETE FROM b_autologin WHERE user_id = ? AND autologin_key = ?";
    $g_db->query($delete_sql, array($logout_user_id, $_COOKIE['AUTOLOGIN']));
}

// セッション変数をすべて削除
$_SESSION = array();

// セッションクッキーも削除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 86400,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ReadNest固有のクッキーも削除
setcookie('AUTOLOGIN', '', time() - 86400, '/', '', false, true);
setcookie('autologin', '', time() - 86400, '/', '', false, true);
setcookie('use_modern_template', '', time() - 86400, '/', '', false, true);
setcookie('AUTH_USER_ID', '', time() - 86400, '/', '', false, true);
setcookie('PHPSESSID', '', time() - 86400, '/', '', false, true);

// 認証関連のセッション変数を確実に削除
if (isset($_SESSION['AUTH_USER'])) {
    unset($_SESSION['AUTH_USER']);
}
if (isset($_SESSION['AUTH_AUTHENTICATED'])) {
    unset($_SESSION['AUTH_AUTHENTICATED']);
}

// セッションを完全に破棄
session_destroy();

error_log("Logout process completed, redirecting to index");

// 出力バッファをクリア
ob_end_clean();

// キャッシュ無効化ヘッダー
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// トップページにリダイレクト（セッションパラメータなし）
header('Location: https://readnest.jp/');
exit;
