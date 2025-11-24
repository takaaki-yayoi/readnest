<?php
/**
 * OAuthクライアント管理ページ
 */

declare(strict_types=1);

require_once('modern_config.php');

// ログインチェック
if (!checkLogin()) {
    header('Location: https://readnest.jp/');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];
$d_nickname = getNickname($mine_user_id);

// POSTリクエスト処理
$message = '';
$error = '';
$new_client = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        // OAuthクライアント生成
        $client_name = trim($_POST['client_name'] ?? '');
        $redirect_uris = trim($_POST['redirect_uris'] ?? '');

        if (empty($client_name)) {
            $error = 'クライアント名を入力してください';
        } elseif (empty($redirect_uris)) {
            $error = 'リダイレクトURIを入力してください';
        } else {
            // Client IDとSecretを生成
            $client_id = bin2hex(random_bytes(16));
            $client_secret = bin2hex(random_bytes(32));

            $sql = "INSERT INTO b_oauth_clients (client_id, user_id, client_secret, client_name, redirect_uris)
                    VALUES (?, ?, ?, ?, ?)";

            $result = $g_db->query($sql, [$client_id, $mine_user_id, $client_secret, $client_name, $redirect_uris]);

            if (DB::isError($result)) {
                error_log("OAuth client generation error: " . $result->getMessage());
                $error = 'OAuthクライアントの生成に失敗しました: ' . $result->getMessage();
            } else {
                $message = 'OAuthクライアントを生成しました';
                $new_client = [
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'client_name' => $client_name
                ];
            }
        }
    } elseif ($action === 'delete') {
        // OAuthクライアント削除
        $client_id = $_POST['client_id'] ?? '';

        $sql = "DELETE FROM b_oauth_clients WHERE client_id = ? AND user_id = ?";
        $result = $g_db->query($sql, [$client_id, $mine_user_id]);

        if (DB::isError($result)) {
            error_log("OAuth client deletion error: " . $result->getMessage());
            $error = 'OAuthクライアントの削除に失敗しました';
        } else {
            $message = 'OAuthクライアントを削除しました';
        }
    }
}

// OAuthクライアント一覧を取得
$sql = "SELECT client_id, client_name, redirect_uris, created_at
        FROM b_oauth_clients
        WHERE user_id = ?
        ORDER BY created_at DESC";

$oauth_clients = $g_db->getAll($sql, [$mine_user_id], DB_FETCHMODE_ASSOC);

if (DB::isError($oauth_clients)) {
    error_log("OAuth clients fetch error: " . $oauth_clients->getMessage());
    $oauth_clients = [];
}

// ページメタ情報
$d_site_title = 'OAuthクライアント管理 - ReadNest';
$g_meta_description = 'OAuthクライアントの管理';
$g_meta_keyword = 'ReadNest,OAuth,設定';

// テンプレートを読み込み
include(getTemplatePath('t_oauth_clients.php'));
?>
