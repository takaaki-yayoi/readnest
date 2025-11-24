<?php
/**
 * OAuth 2.0 Token Endpoint
 *
 * RFC 6749準拠のトークンエンドポイント
 * PKCE検証対応
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once(dirname(__DIR__) . '/config.php');

// POSTのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'invalid_request']);
    exit;
}

// クライアント認証（Basic認証またはPOSTパラメータ）
$client_id = null;
$client_secret = null;

// Basic認証をチェック
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    $client_id = $_SERVER['PHP_AUTH_USER'];
    $client_secret = $_SERVER['PHP_AUTH_PW'];
} else {
    // POSTパラメータから取得
    $client_id = $_POST['client_id'] ?? '';
    $client_secret = $_POST['client_secret'] ?? '';
}

if (empty($client_id) || empty($client_secret)) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client']);
    exit;
}

// クライアント認証
$client_sql = "SELECT client_id FROM b_oauth_clients WHERE client_id = ? AND client_secret = ?";
$valid_client = $g_db->getOne($client_sql, [$client_id, $client_secret]);

if (DB::isError($valid_client) || !$valid_client) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client']);
    exit;
}

$grant_type = $_POST['grant_type'] ?? '';

// authorization_codeグラント
if ($grant_type === 'authorization_code') {
    $code = $_POST['code'] ?? '';
    $redirect_uri = $_POST['redirect_uri'] ?? '';
    $code_verifier = $_POST['code_verifier'] ?? '';

    if (empty($code) || empty($redirect_uri)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request']);
        exit;
    }

    // 認可コードを取得
    $code_sql = "SELECT * FROM b_oauth_authorization_codes
                 WHERE code = ? AND client_id = ? AND redirect_uri = ?";
    $auth_code = $g_db->getRow($code_sql, [$code, $client_id, $redirect_uri], DB_FETCHMODE_ASSOC);

    if (DB::isError($auth_code) || !$auth_code) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant']);
        exit;
    }

    // 有効期限チェック
    if (strtotime($auth_code['expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Code expired']);
        exit;
    }

    // PKCE検証
    if (!empty($auth_code['code_challenge'])) {
        if (empty($code_verifier)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request', 'error_description' => 'code_verifier required']);
            exit;
        }

        $calculated_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');

        if ($calculated_challenge !== $auth_code['code_challenge']) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Invalid code_verifier']);
            exit;
        }
    }

    // アクセストークンを生成
    $access_token = bin2hex(random_bytes(32));
    $refresh_token = bin2hex(random_bytes(32));
    $token_expires_at = date('Y-m-d H:i:s', time() + 3600); // 1時間
    $refresh_expires_at = date('Y-m-d H:i:s', time() + 86400 * 30); // 30日

    // トークンを保存
    $insert_access = "INSERT INTO b_oauth_access_tokens
                      (access_token, client_id, user_id, scope, expires_at)
                      VALUES (?, ?, ?, ?, ?)";

    $result = $g_db->query($insert_access, [
        $access_token,
        $client_id,
        $auth_code['user_id'],
        $auth_code['scope'],
        $token_expires_at
    ]);

    if (DB::isError($result)) {
        http_response_code(500);
        echo json_encode(['error' => 'server_error']);
        exit;
    }

    // リフレッシュトークンを保存
    $insert_refresh = "INSERT INTO b_oauth_refresh_tokens
                       (refresh_token, access_token, client_id, user_id, scope, expires_at)
                       VALUES (?, ?, ?, ?, ?, ?)";

    $g_db->query($insert_refresh, [
        $refresh_token,
        $access_token,
        $client_id,
        $auth_code['user_id'],
        $auth_code['scope'],
        $refresh_expires_at
    ]);

    // 認可コードを削除（使い捨て）
    $g_db->query("DELETE FROM b_oauth_authorization_codes WHERE code = ?", [$code]);

    // トークンレスポンス
    echo json_encode([
        'access_token' => $access_token,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'refresh_token' => $refresh_token,
        'scope' => $auth_code['scope']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// refresh_tokenグラント
if ($grant_type === 'refresh_token') {
    $refresh_token = $_POST['refresh_token'] ?? '';

    if (empty($refresh_token)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request']);
        exit;
    }

    // リフレッシュトークンを取得
    $refresh_sql = "SELECT * FROM b_oauth_refresh_tokens
                    WHERE refresh_token = ? AND client_id = ?";
    $refresh_data = $g_db->getRow($refresh_sql, [$refresh_token, $client_id], DB_FETCHMODE_ASSOC);

    if (DB::isError($refresh_data) || !$refresh_data) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant']);
        exit;
    }

    // 有効期限チェック
    if (strtotime($refresh_data['expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Refresh token expired']);
        exit;
    }

    // 新しいアクセストークンを生成
    $new_access_token = bin2hex(random_bytes(32));
    $token_expires_at = date('Y-m-d H:i:s', time() + 3600);

    // 古いアクセストークンを削除
    $g_db->query("DELETE FROM b_oauth_access_tokens WHERE access_token = ?", [$refresh_data['access_token']]);

    // 新しいアクセストークンを保存
    $insert_access = "INSERT INTO b_oauth_access_tokens
                      (access_token, client_id, user_id, scope, expires_at)
                      VALUES (?, ?, ?, ?, ?)";

    $g_db->query($insert_access, [
        $new_access_token,
        $client_id,
        $refresh_data['user_id'],
        $refresh_data['scope'],
        $token_expires_at
    ]);

    // リフレッシュトークンのaccess_tokenを更新
    $g_db->query("UPDATE b_oauth_refresh_tokens SET access_token = ? WHERE refresh_token = ?",
                 [$new_access_token, $refresh_token]);

    // トークンレスポンス
    echo json_encode([
        'access_token' => $new_access_token,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'refresh_token' => $refresh_token,
        'scope' => $refresh_data['scope']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 未対応のgrant_type
http_response_code(400);
echo json_encode(['error' => 'unsupported_grant_type']);
?>
