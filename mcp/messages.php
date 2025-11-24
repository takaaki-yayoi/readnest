<?php
/**
 * ReadNest MCP Server (PHP Implementation)
 *
 * JSON-RPC over HTTPS endpoint for Claude Desktop
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS リクエスト（プリフライト）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/api_auth.php');
require_once(dirname(__DIR__) . '/library/database.php');

// POSTのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 認証：Authorizationヘッダーからトークンを取得
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? '';

// .htaccessで設定した環境変数からも取得を試みる
if (empty($auth_header) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
}
if (empty($auth_header) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

error_log("MCP auth check: Authorization header = " . ($auth_header ? 'present' : 'missing'));
error_log("All headers: " . json_encode($headers));
if (!empty($auth_header)) {
    error_log("Authorization header value: " . substr($auth_header, 0, 20) . "...");
}

if (!$auth_header || !preg_match('/^Bearer\s+(.+)$/i', $auth_header, $matches)) {
    // 認証ヘッダーがない場合は401を返す
    error_log("MCP auth failed: Invalid or missing Authorization header");
    http_response_code(401);
    header('WWW-Authenticate: Bearer realm="ReadNest MCP Server"');
    echo json_encode([
        'jsonrpc' => '2.0',
        'error' => [
            'code' => -32001,
            'message' => 'Authentication required'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = $matches[1];

// トークンを検証してuser_idを取得
global $g_db;

// OAuthアクセストークンを検証
$sql = "SELECT user_id, expires_at FROM b_oauth_access_tokens WHERE access_token = ?";
$token_data = $g_db->getRow($sql, [$token], DB_FETCHMODE_ASSOC);

if (DB::isError($token_data) || !$token_data) {
    http_response_code(401);
    header('WWW-Authenticate: Bearer realm="ReadNest MCP Server", error="invalid_token"');
    echo json_encode([
        'jsonrpc' => '2.0',
        'error' => [
            'code' => -32001,
            'message' => 'Invalid token'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 有効期限チェック
if (strtotime($token_data['expires_at']) < time()) {
    http_response_code(401);
    header('WWW-Authenticate: Bearer realm="ReadNest MCP Server", error="invalid_token", error_description="Token expired"');
    echo json_encode([
        'jsonrpc' => '2.0',
        'error' => [
            'code' => -32001,
            'message' => 'Token expired'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = $token_data['user_id'];

// リクエストボディを取得
$input = file_get_contents('php://input');
$message = json_decode($input, true);

if (!$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

// MCPメッセージを処理
error_log("MCP message received: method=" . ($message['method'] ?? 'none') . ", user_id=$user_id");
$response = handleMcpMessage($message, $user_id);

// notificationの場合はレスポンスを返さない
if ($response === null) {
    error_log("MCP notification processed (no response)");
    http_response_code(204); // No Content
    exit;
}

error_log("MCP response: " . json_encode($response));
echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * MCPメッセージを処理
 */
function handleMcpMessage($message, $user_id) {
    $method = $message['method'] ?? '';
    $params = $message['params'] ?? [];
    $id = $message['id'] ?? null;

    switch ($method) {
        case 'initialize':
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'protocolVersion' => '2024-11-05',
                    'serverInfo' => [
                        'name' => 'readnest-mcp',
                        'version' => '1.0.0'
                    ],
                    'capabilities' => [
                        'tools' => (object)[]
                    ]
                ]
            ];

        case 'notifications/initialized':
            // クライアントの初期化完了通知（レスポンス不要）
            return null;

        case 'tools/list':
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'tools' => [
                        [
                            'name' => 'get_bookshelf',
                            'description' => '本棚のデータを取得します。

パラメータ:
- status (optional): 本のステータス
  - tsundoku: 積読
  - reading: 読書中
  - finished: 読了
  - read: 既読
- limit (optional): 取得件数 (デフォルト: 100)
- offset (optional): オフセット (デフォルト: 0)',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'status' => [
                                        'type' => 'string',
                                        'enum' => ['tsundoku', 'reading', 'finished', 'read'],
                                        'description' => '本のステータス'
                                    ],
                                    'limit' => [
                                        'type' => 'integer',
                                        'description' => '取得件数',
                                        'default' => 100
                                    ],
                                    'offset' => [
                                        'type' => 'integer',
                                        'description' => 'オフセット',
                                        'default' => 0
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_reading_stats',
                            'description' => '読書統計情報を取得します。

取得できる情報:
- 総書籍数
- ステータス別の冊数
- 今年の読了冊数とページ数
- 今月の読了冊数とページ数
- 平均評価',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => (object)[]
                            ]
                        ]
                    ]
                ]
            ];

        case 'tools/call':
            $tool_name = $params['name'] ?? '';
            $tool_args = $params['arguments'] ?? [];

            if ($tool_name === 'get_bookshelf') {
                return handleGetBookshelf($tool_args, $user_id, $id);
            } elseif ($tool_name === 'get_reading_stats') {
                return handleGetReadingStats($user_id, $id);
            } else {
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => -32601,
                        'message' => 'Unknown tool: ' . $tool_name
                    ]
                ];
            }

        default:
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32601,
                    'message' => 'Method not found: ' . $method
                ]
            ];
    }
}

/**
 * 本棚データを取得
 */
function handleGetBookshelf($args, $user_id, $id) {
    global $g_db;

    $status = $args['status'] ?? null;
    $limit = min((int)($args['limit'] ?? 100), 1000);
    $offset = (int)($args['offset'] ?? 0);

    $status_map = [
        'tsundoku' => 1,
        'reading' => 2,
        'finished' => 3,
        'read' => 4
    ];

    // SQL構築
    $status_where = '';
    $params = [$user_id];

    if ($status && isset($status_map[$status])) {
        $status_where = ' AND bl.status = ?';
        $params[] = $status_map[$status];
    }

    $sql = "SELECT bl.book_id, bl.user_id, bl.amazon_id, bl.isbn, bl.name,
            bl.image_url, bl.status, bl.rating, bl.total_page, bl.current_page,
            bl.finished_date, bl.update_date,
            COALESCE(bl.author, br.author, '') as author
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ? $status_where
            ORDER BY bl.update_date DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $results = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Database error'
            ]
        ];
    }

    // フォーマット
    $status_name = [
        1 => '積読',
        2 => '読書中',
        3 => '読了',
        4 => '既読'
    ];

    $output_lines = [];
    foreach ($results as $book) {
        $line = "📖 {$book['name']}";
        if ($book['author']) {
            $line .= " / {$book['author']}";
        }
        $line .= " ({$status_name[(int)$book['status']]})";

        if ($book['rating']) {
            $line .= " ⭐️ {$book['rating']}";
        }

        if ($book['current_page'] && $book['total_page']) {
            $progress = (int)(($book['current_page'] / $book['total_page']) * 100);
            $line .= " | {$book['current_page']}/{$book['total_page']}ページ ({$progress}%)";
        }

        $output_lines[] = $line;
    }

    $text = count($output_lines) > 0
        ? implode("\n", $output_lines)
        : "該当する本が見つかりませんでした";

    return [
        'jsonrpc' => '2.0',
        'id' => $id,
        'result' => [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $text
                ]
            ]
        ]
    ];
}

/**
 * 読書統計を取得
 */
function handleGetReadingStats($user_id, $id) {
    global $g_db;

    // 総書籍数
    $total_sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ?";
    $total_books = (int)$g_db->getOne($total_sql, [$user_id]);

    // ステータス別
    $status_sql = "SELECT status, COUNT(*) as count
                   FROM b_book_list WHERE user_id = ?
                   GROUP BY status";
    $status_results = $g_db->getAll($status_sql, [$user_id], DB_FETCHMODE_ASSOC);

    $by_status = [
        'tsundoku' => 0,
        'reading' => 0,
        'finished' => 0,
        'read' => 0
    ];

    if (!DB::isError($status_results)) {
        $status_map = [1 => 'tsundoku', 2 => 'reading', 3 => 'finished', 4 => 'read'];
        foreach ($status_results as $row) {
            $key = $status_map[(int)$row['status']] ?? null;
            if ($key) {
                $by_status[$key] = (int)$row['count'];
            }
        }
    }

    // 今年の実績
    $this_year_sql = "SELECT COUNT(*) as count, SUM(total_page) as pages
                      FROM b_book_list
                      WHERE user_id = ? AND status = 3
                      AND YEAR(finished_date) = YEAR(NOW())";
    $this_year = $g_db->getRow($this_year_sql, [$user_id], DB_FETCHMODE_ASSOC);

    // 今月の実績
    $this_month_sql = "SELECT COUNT(*) as count, SUM(total_page) as pages
                       FROM b_book_list
                       WHERE user_id = ? AND status = 3
                       AND YEAR(finished_date) = YEAR(NOW())
                       AND MONTH(finished_date) = MONTH(NOW())";
    $this_month = $g_db->getRow($this_month_sql, [$user_id], DB_FETCHMODE_ASSOC);

    // 平均評価
    $rating_sql = "SELECT AVG(rating) as avg_rating
                   FROM b_book_list
                   WHERE user_id = ? AND rating IS NOT NULL";
    $avg_rating = $g_db->getOne($rating_sql, [$user_id]);

    // 出力
    $output_lines = [
        "📊 読書統計\n",
        "総書籍数: {$total_books}冊",
        "  - 積読: {$by_status['tsundoku']}冊",
        "  - 読書中: {$by_status['reading']}冊",
        "  - 読了: {$by_status['finished']}冊",
        "  - 既読: {$by_status['read']}冊",
        "",
        "今年の実績:",
        "  - 読了: " . (int)$this_year['count'] . "冊",
        "  - ページ数: " . number_format((int)$this_year['pages']) . "ページ",
        "",
        "今月の実績:",
        "  - 読了: " . (int)$this_month['count'] . "冊",
        "  - ページ数: " . number_format((int)$this_month['pages']) . "ページ"
    ];

    if (!DB::isError($avg_rating) && $avg_rating) {
        $output_lines[] = "";
        $output_lines[] = "平均評価: ⭐️ " . round($avg_rating, 2);
    }

    return [
        'jsonrpc' => '2.0',
        'id' => $id,
        'result' => [
            'content' => [
                [
                    'type' => 'text',
                    'text' => implode("\n", $output_lines)
                ]
            ]
        ]
    ];
}
?>
