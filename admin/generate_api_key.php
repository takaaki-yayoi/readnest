<?php
/**
 * API Key生成スクリプト（CLI専用）
 *
 * Usage:
 *   php generate_api_key.php <user_id> <name>
 *
 * Example:
 *   php generate_api_key.php 1 "MCP Server"
 */

// CLI専用チェック
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

// 引数チェック
if ($argc < 3) {
    echo "Usage: php generate_api_key.php <user_id> <name>\n";
    echo "Example: php generate_api_key.php 1 \"MCP Server\"\n";
    exit(1);
}

$user_id = (int)$argv[1];
$name = $argv[2];

if ($user_id <= 0) {
    echo "Error: Invalid user_id\n";
    exit(1);
}

// ReadNestの設定を読み込み
require_once(dirname(__DIR__) . '/config.php');

// API Keyを生成（64文字のランダムな文字列）
$api_key = bin2hex(random_bytes(32));

// データベースに保存
$sql = "INSERT INTO b_api_keys (user_id, api_key, name, is_active)
        VALUES (?, ?, ?, 1)";

$result = $g_db->query($sql, [$user_id, $api_key, $name]);

if (DB::isError($result)) {
    echo "Error: Failed to save API key\n";
    echo $result->getMessage() . "\n";
    exit(1);
}

echo "API Key generated successfully!\n\n";
echo "User ID: $user_id\n";
echo "Name: $name\n";
echo "API Key: $api_key\n\n";
echo "IMPORTANT: Save this API key securely. It will not be shown again.\n";
echo "\nTo use this API key, add it to your .env file:\n";
echo "READNEST_API_KEY=$api_key\n";

exit(0);
?>
