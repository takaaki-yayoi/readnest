<?php
/**
 * MCP API認証ライブラリ
 *
 * API Key認証を提供
 * - Bearer Token形式
 * - user_id の取得
 */

/**
 * API Keyを検証してuser_idを取得
 *
 * @return array ['success' => bool, 'user_id' => int|null, 'error' => string|null]
 */
function authenticateApiKey() {
    // Authorization ヘッダーを取得
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    // Bearer トークン形式をチェック
    if (!preg_match('/^Bearer\s+(.+)$/i', $auth_header, $matches)) {
        return [
            'success' => false,
            'user_id' => null,
            'error' => 'Invalid authorization header format'
        ];
    }

    $api_key = $matches[1];

    // API Keyを検証（データベースから取得）
    global $g_db;

    $sql = "SELECT user_id, expires_at
            FROM b_api_keys
            WHERE api_key = ? AND is_active = 1";

    $result = $g_db->getRow($sql, [$api_key], DB_FETCHMODE_ASSOC);

    if (DB::isError($result)) {
        error_log("API Key validation error: " . $result->getMessage());
        return [
            'success' => false,
            'user_id' => null,
            'error' => 'Database error'
        ];
    }

    if (!$result) {
        return [
            'success' => false,
            'user_id' => null,
            'error' => 'Invalid API key'
        ];
    }

    // 有効期限をチェック
    if ($result['expires_at'] && strtotime($result['expires_at']) < time()) {
        return [
            'success' => false,
            'user_id' => null,
            'error' => 'API key expired'
        ];
    }

    return [
        'success' => true,
        'user_id' => (int)$result['user_id'],
        'error' => null
    ];
}

/**
 * API認証ミドルウェア
 * 認証に失敗した場合はJSONエラーを返して終了
 *
 * @return int user_id
 */
function requireApiAuth() {
    $auth = authenticateApiKey();

    if (!$auth['success']) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $auth['error']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $auth['user_id'];
}

/**
 * JSON レスポンスを返す
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
?>
