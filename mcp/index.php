<?php
/**
 * ReadNest MCP Server - Health Check
 */

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'ok',
    'service' => 'ReadNest MCP Server',
    'version' => '1.0.0',
    'endpoints' => [
        'messages' => '/mcp/messages.php'
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
