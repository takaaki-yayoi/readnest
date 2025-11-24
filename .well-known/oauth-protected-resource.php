<?php
/**
 * MCP Protected Resource Metadata (RFC 9728)
 * https://datatracker.ietf.org/doc/html/rfc9728
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$base_url = 'https://readnest.jp';

$metadata = [
    'resource' => $base_url . '/mcp/messages',
    'authorization_servers' => [
        $base_url
    ],
    'scopes_supported' => ['mcp:read'],
    'bearer_methods_supported' => ['header']
];

echo json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
