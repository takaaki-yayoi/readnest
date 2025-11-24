<?php
/**
 * OAuth 2.0 Authorization Server Metadata (RFC 8414)
 * https://datatracker.ietf.org/doc/html/rfc8414
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$base_url = 'https://readnest.jp';

$metadata = [
    'issuer' => $base_url,
    'authorization_endpoint' => $base_url . '/oauth/authorize.php',
    'token_endpoint' => $base_url . '/oauth/token.php',
    'response_types_supported' => ['code'],
    'grant_types_supported' => ['authorization_code', 'refresh_token'],
    'code_challenge_methods_supported' => ['S256'],
    'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
    'scopes_supported' => ['mcp:read'],
];

echo json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
