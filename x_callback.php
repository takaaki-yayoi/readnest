<?php
/**
 * X (Twitter) OAuth Callback Handler
 * Handles the OAuth callback and stores user's X credentials
 */

declare(strict_types=1);

require_once('config.php');
require_once('library/session.php');
require_once('library/database.php');
require_once('library/security.php');

// Initialize session
// session.php handles session start directly

// Check if user is logged in
if (!checkLogin()) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// Get OAuth parameters from callback
$oauth_token = $_GET['oauth_token'] ?? '';
$oauth_verifier = $_GET['oauth_verifier'] ?? '';

// Validate callback parameters
if (empty($oauth_token) || empty($oauth_verifier)) {
    error_log('[X Callback] Missing OAuth parameters');
    header('Location: /account.php?x_error=missing_params');
    exit;
}

// Verify token matches session
if (!isset($_SESSION['x_oauth_token']) || $_SESSION['x_oauth_token'] !== $oauth_token) {
    error_log('[X Callback] OAuth token mismatch');
    header('Location: /account.php?x_error=token_mismatch');
    exit;
}

$request_token_secret = $_SESSION['x_oauth_token_secret'] ?? '';

// Exchange request token for access token
$base_url = 'https://api.twitter.com/oauth/access_token';
$http_method = 'POST';

// Build OAuth parameters
$oauth_params = [
    'oauth_consumer_key' => X_API_KEY,
    'oauth_token' => $oauth_token,
    'oauth_nonce' => bin2hex(random_bytes(16)),
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_timestamp' => time(),
    'oauth_version' => '1.0',
    'oauth_verifier' => $oauth_verifier
];

// Sort parameters
ksort($oauth_params);

// Build parameter string
$param_string = '';
foreach ($oauth_params as $key => $value) {
    $param_string .= rawurlencode($key) . '=' . rawurlencode((string)$value) . '&';
}
$param_string = rtrim($param_string, '&');

// Build signature base
$signature_base = $http_method . '&' . rawurlencode($base_url) . '&' . rawurlencode($param_string);

// Build signing key
$signing_key = rawurlencode(X_API_SECRET) . '&' . rawurlencode($request_token_secret);

// Generate signature
$oauth_signature = base64_encode(hash_hmac('sha1', $signature_base, $signing_key, true));
$oauth_params['oauth_signature'] = $oauth_signature;

// Build Authorization header
$auth_header = 'OAuth ';
foreach ($oauth_params as $key => $value) {
    $auth_header .= rawurlencode($key) . '="' . rawurlencode((string)$value) . '", ';
}
$auth_header = rtrim($auth_header, ', ');

// Make request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'oauth_verifier=' . urlencode($oauth_verifier));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $auth_header,
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    error_log('[X Callback] Failed to get access token. HTTP: ' . $http_code . ', Response: ' . $response);
    header('Location: /account.php?x_error=access_token_failed');
    exit;
}

// Parse response
parse_str($response, $access_token_info);

if (!isset($access_token_info['oauth_token']) || !isset($access_token_info['oauth_token_secret'])) {
    error_log('[X Callback] Invalid access token response: ' . $response);
    header('Location: /account.php?x_error=invalid_access_token');
    exit;
}

// Get user info from X to verify connection
require_once('library/x_oauth_v2.php');

$x_oauth = new XOAuthV2(
    X_API_KEY,
    X_API_SECRET,
    $access_token_info['oauth_token'],
    $access_token_info['oauth_token_secret']
);

// Store X credentials in database
global $g_db;

// データベース接続を確認
if (!$g_db) {
    error_log('[X Callback] Database connection not found, attempting to connect');
    $g_db = DB_Connect();
    if (DB::isError($g_db)) {
        error_log('[X Callback] Failed to connect to database: ' . $g_db->getMessage());
        header('Location: /account.php?x_error=storage_failed');
        exit;
    }
}

$update_sql = "UPDATE b_user SET 
    x_oauth_token = ?,
    x_oauth_token_secret = ?,
    x_screen_name = ?,
    x_user_id = ?,
    x_connected_at = NOW(),
    x_post_enabled = 1
    WHERE user_id = ?";

$params = [
    $access_token_info['oauth_token'],
    $access_token_info['oauth_token_secret'],
    $access_token_info['screen_name'] ?? null,
    $access_token_info['user_id'] ?? null,
    $user_id
];

// デバッグ情報

$result = $g_db->query($update_sql, $params);

if (DB::isError($result)) {
    error_log('[X Callback] Failed to store credentials: ' . $result->getMessage());
    error_log('[X Callback] SQL: ' . $update_sql);
    error_log('[X Callback] Params: ' . print_r($params, true));
    
    // より詳細なエラーメッセージを表示（デバッグ用）
    if (isset($_GET['debug'])) {
        echo "<pre>";
        echo "Error: " . $result->getMessage() . "\n";
        echo "SQL: " . $update_sql . "\n";
        echo "Params: " . print_r($params, true);
        echo "</pre>";
        exit;
    }
    
    header('Location: /account.php?x_error=storage_failed');
    exit;
}

// Clean up session
unset($_SESSION['x_oauth_token']);
unset($_SESSION['x_oauth_token_secret']);

// Redirect to account page with success message
header('Location: /account.php?x_connected=success');
exit;