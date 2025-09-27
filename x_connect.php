<?php
/**
 * X (Twitter) OAuth Connection Page
 * Initiates the OAuth flow for connecting user's X account
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
$user_info = getUserInformation($user_id);

// Check if already connected
if (!empty($user_info['x_oauth_token']) && !empty($user_info['x_oauth_token_secret'])) {
    // Already connected, redirect to account page
    header('Location: /account.php');
    exit;
}

// OAuth 1.0a flow for X API
// Step 1: Get request token
$oauth_callback = 'https://readnest.jp/x_callback.php';

// Build OAuth parameters for request token
$oauth_params = [
    'oauth_callback' => $oauth_callback,
    'oauth_consumer_key' => X_API_KEY,
    'oauth_nonce' => bin2hex(random_bytes(16)),
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_timestamp' => time(),
    'oauth_version' => '1.0'
];

// Build signature base string
$base_url = 'https://api.twitter.com/oauth/request_token';
$http_method = 'POST';

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
$signing_key = rawurlencode(X_API_SECRET) . '&'; // No token secret for request token

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
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $auth_header,
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Debug logging
error_log('[X Connect] Request URL: ' . $base_url);
error_log('[X Connect] Auth Header: ' . $auth_header);
error_log('[X Connect] HTTP Code: ' . $http_code);
error_log('[X Connect] Response: ' . $response);
if ($curl_error) {
    error_log('[X Connect] CURL Error: ' . $curl_error);
}

if ($http_code !== 200) {
    error_log('[X Connect] Failed to get request token. HTTP: ' . $http_code . ', Response: ' . $response);
    // デバッグ用に詳細エラーを表示（本番環境では削除してください）
    if (isset($_GET['debug'])) {
        echo "<pre>";
        echo "HTTP Code: " . $http_code . "\n";
        echo "Response: " . htmlspecialchars($response) . "\n";
        echo "Auth Header: " . htmlspecialchars($auth_header) . "\n";
        echo "</pre>";
        exit;
    }
    header('Location: /account.php?x_error=request_token_failed');
    exit;
}

// Parse response
parse_str($response, $request_token_info);

if (!isset($request_token_info['oauth_token']) || !isset($request_token_info['oauth_token_secret'])) {
    error_log('[X Connect] Invalid request token response: ' . $response);
    header('Location: /account.php?x_error=invalid_token');
    exit;
}

// Store request token in session for callback
$_SESSION['x_oauth_token'] = $request_token_info['oauth_token'];
$_SESSION['x_oauth_token_secret'] = $request_token_info['oauth_token_secret'];

// Redirect to X authorization page
$auth_url = 'https://api.twitter.com/oauth/authorize?oauth_token=' . urlencode($request_token_info['oauth_token']);
header('Location: ' . $auth_url);
exit;