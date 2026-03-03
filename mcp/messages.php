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
- limit (optional): 取得件数 (デフォルト: 500、最大: 5000)
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
                                        'description' => '取得件数（最大5000）',
                                        'default' => 500
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
                        ],
                        [
                            'name' => 'search_books',
                            'description' => '本を検索します。

パラメータ:
- query (required): 検索キーワード（タイトル、著者名、ISBN）
- limit (optional): 取得件数 (デフォルト: 50、最大: 500)',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'query' => [
                                        'type' => 'string',
                                        'description' => '検索キーワード'
                                    ],
                                    'limit' => [
                                        'type' => 'integer',
                                        'description' => '取得件数（最大500）',
                                        'default' => 50
                                    ]
                                ],
                                'required' => ['query']
                            ]
                        ],
                        [
                            'name' => 'get_book_detail',
                            'description' => '特定の本の詳細情報を取得します。

パラメータ:
- book_id (required): 本のID',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'book_id' => [
                                        'type' => 'integer',
                                        'description' => '本のID'
                                    ]
                                ],
                                'required' => ['book_id']
                            ]
                        ],
                        [
                            'name' => 'get_reading_history',
                            'description' => '読書履歴を取得します。

パラメータ:
- year (optional): 年を指定（例: 2024）
- month (optional): 月を指定（例: 11）
- limit (optional): 取得件数 (デフォルト: 100、最大: 1000)',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'year' => [
                                        'type' => 'integer',
                                        'description' => '年'
                                    ],
                                    'month' => [
                                        'type' => 'integer',
                                        'description' => '月'
                                    ],
                                    'limit' => [
                                        'type' => 'integer',
                                        'description' => '取得件数（最大1000）',
                                        'default' => 100
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_favorite_genres',
                            'description' => 'よく読むジャンル（タグ）を取得します。

パラメータ:
- limit (optional): 取得件数 (デフォルト: 20)',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'limit' => [
                                        'type' => 'integer',
                                        'description' => '取得件数',
                                        'default' => 20
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_reviews',
                            'description' => 'レビューを取得します。

パラメータ:
- book_id (optional): 特定の本のレビューのみ取得
- limit (optional): 取得件数 (デフォルト: 50)',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'book_id' => [
                                        'type' => 'integer',
                                        'description' => '本のID'
                                    ],
                                    'limit' => [
                                        'type' => 'integer',
                                        'description' => '取得件数',
                                        'default' => 50
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_high_rated_books',
                            'description' => '高評価の本をレビュー付きで一括取得します。プロファイル生成の入力データとして使用します。

パラメータ:
- min_rating (required): 最低評価（例: 4）
- limit (optional): 取得件数 (デフォルト: 50)',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'min_rating' => [
                                        'type' => 'integer',
                                        'description' => '最低評価（1-5）'
                                    ],
                                    'limit' => [
                                        'type' => 'integer',
                                        'description' => '取得件数（最大200）',
                                        'default' => 50
                                    ]
                                ],
                                'required' => ['min_rating']
                            ]
                        ],
                        [
                            'name' => 'is_book_read',
                            'description' => 'ユーザーが特定の本を読んだかどうかをあいまい検索で確認します。全角/半角、中黒の有無、副題の有無などの表記ゆれを吸収します。

パラメータ:
- title (required): 書籍タイトル
- author (optional): 著者名（指定するとマッチ精度が向上）',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => [
                                        'type' => 'string',
                                        'description' => '書籍タイトル'
                                    ],
                                    'author' => [
                                        'type' => 'string',
                                        'description' => '著者名（任意）'
                                    ]
                                ],
                                'required' => ['title']
                            ]
                        ],
                        [
                            'name' => 'get_books_by_tag',
                            'description' => 'タグ（ジャンル）別の本一覧を取得します。

パラメータ:
- tag (required): タグ名（例: "SF", "小説", "戦争"）
- limit (optional): 取得件数 (デフォルト: 50)',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'tag' => [
                                        'type' => 'string',
                                        'description' => 'タグ名'
                                    ],
                                    'limit' => [
                                        'type' => 'integer',
                                        'description' => '取得件数（最大200）',
                                        'default' => 50
                                    ]
                                ],
                                'required' => ['tag']
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
            } elseif ($tool_name === 'search_books') {
                return handleSearchBooks($tool_args, $user_id, $id);
            } elseif ($tool_name === 'get_book_detail') {
                return handleGetBookDetail($tool_args, $user_id, $id);
            } elseif ($tool_name === 'get_reading_history') {
                return handleGetReadingHistory($tool_args, $user_id, $id);
            } elseif ($tool_name === 'get_favorite_genres') {
                return handleGetFavoriteGenres($tool_args, $user_id, $id);
            } elseif ($tool_name === 'get_reviews') {
                return handleGetReviews($tool_args, $user_id, $id);
            } elseif ($tool_name === 'get_high_rated_books') {
                return handleGetHighRatedBooks($tool_args, $user_id, $id);
            } elseif ($tool_name === 'is_book_read') {
                return handleIsBookRead($tool_args, $user_id, $id);
            } elseif ($tool_name === 'get_books_by_tag') {
                return handleGetBooksByTag($tool_args, $user_id, $id);
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
    $limit = min((int)($args['limit'] ?? 500), 5000);
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

/**
 * 本を検索
 */
function handleSearchBooks($args, $user_id, $id) {
    global $g_db;

    $query = $args['query'] ?? '';
    $limit = min((int)($args['limit'] ?? 50), 500);

    if (empty($query)) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32602,
                'message' => 'query parameter is required'
            ]
        ];
    }

    $sql = "SELECT bl.book_id, bl.user_id, bl.amazon_id, bl.isbn, bl.name,
            bl.image_url, bl.status, bl.rating, bl.total_page, bl.current_page,
            bl.finished_date, bl.update_date,
            COALESCE(bl.author, br.author, '') as author
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND (bl.name LIKE ? OR COALESCE(bl.author, br.author, '') LIKE ? OR bl.isbn LIKE ?)
            ORDER BY bl.update_date DESC
            LIMIT ?";

    $search_term = '%' . $query . '%';
    $results = $g_db->getAll($sql, [$user_id, $search_term, $search_term, $search_term, $limit], DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        error_log("handleSearchBooks DB error: " . $results->getMessage() . " | Query: " . $query);
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Database error: ' . $results->getMessage()
            ]
        ];
    }

    $status_name = [1 => '積読', 2 => '読書中', 3 => '読了', 4 => '既読'];

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
        $line .= " [ID: {$book['book_id']}]";
        $output_lines[] = $line;
    }

    $text = count($output_lines) > 0
        ? "検索結果: " . count($output_lines) . "件\n\n" . implode("\n", $output_lines)
        : "「{$query}」に一致する本が見つかりませんでした";

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
 * 本の詳細情報を取得
 */
function handleGetBookDetail($args, $user_id, $id) {
    global $g_db;

    $book_id = (int)($args['book_id'] ?? 0);

    if ($book_id <= 0) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32602,
                'message' => 'book_id parameter is required'
            ]
        ];
    }

    $sql = "SELECT bl.book_id, bl.user_id, bl.amazon_id, bl.isbn, bl.name,
            bl.image_url, bl.status, bl.rating, bl.total_page, bl.current_page,
            bl.finished_date, bl.update_date, bl.create_date,
            COALESCE(bl.author, br.author, '') as author,
            br.description
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ? AND bl.book_id = ?";

    $book = $g_db->getRow($sql, [$user_id, $book_id], DB_FETCHMODE_ASSOC);

    if (DB::isError($book)) {
        error_log("handleGetBookDetail DB error: " . $book->getMessage() . " | book_id: " . $book_id);
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Database error: ' . $book->getMessage()
            ]
        ];
    }

    if (!$book) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Book not found'
            ]
        ];
    }

    // レビューを取得
    $review_sql = "SELECT memo FROM b_book_list WHERE book_id = ?";
    $review = $g_db->getOne($review_sql, [$book_id]);

    $status_name = [1 => '積読', 2 => '読書中', 3 => '読了', 4 => '既読'];

    $output = "📚 {$book['name']}\n\n";
    $output .= "著者: {$book['author']}\n";
    $output .= "ステータス: {$status_name[(int)$book['status']]}\n";
    if ($book['rating']) {
        $output .= "評価: ⭐️ {$book['rating']}\n";
    }
    if ($book['current_page'] && $book['total_page']) {
        $progress = (int)(($book['current_page'] / $book['total_page']) * 100);
        $output .= "進捗: {$book['current_page']}/{$book['total_page']}ページ ({$progress}%)\n";
    }
    if ($book['finished_date'] && $book['finished_date'] !== '0000-00-00') {
        $output .= "読了日: {$book['finished_date']}\n";
    }
    $output .= "登録日: {$book['create_date']}\n";

    if (!empty($review) && !DB::isError($review)) {
        $output .= "\nレビュー:\n{$review}\n";
    }

    if ($book['description']) {
        $output .= "\n説明:\n{$book['description']}\n";
    }

    return [
        'jsonrpc' => '2.0',
        'id' => $id,
        'result' => [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $output
                ]
            ]
        ]
    ];
}

/**
 * 読書履歴を取得
 */
function handleGetReadingHistory($args, $user_id, $id) {
    global $g_db;

    $year = (int)($args['year'] ?? 0);
    $month = (int)($args['month'] ?? 0);
    $limit = min((int)($args['limit'] ?? 100), 1000);

    $where_conditions = ["bl.user_id = ?"];
    $params = [$user_id];

    if ($year > 0) {
        $where_conditions[] = "YEAR(bl.finished_date) = ?";
        $params[] = $year;
    }
    if ($month > 0) {
        $where_conditions[] = "MONTH(bl.finished_date) = ?";
        $params[] = $month;
    }

    $where_clause = implode(" AND ", $where_conditions);

    $sql = "SELECT bl.book_id, bl.name,
            COALESCE(bl.author, br.author, '') as author,
            bl.status, bl.rating, bl.finished_date, bl.total_page
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE {$where_clause}
            AND bl.finished_date IS NOT NULL
            AND bl.finished_date != '0000-00-00'
            ORDER BY bl.finished_date DESC
            LIMIT ?";

    $params[] = $limit;
    $results = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        error_log("handleGetReadingHistory DB error: " . $results->getMessage() . " | Year: " . $year . " | Month: " . $month);
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Database error: ' . $results->getMessage()
            ]
        ];
    }

    $output_lines = [];
    $total_pages = 0;

    foreach ($results as $book) {
        $line = "{$book['finished_date']} - 📖 {$book['name']}";
        if ($book['author']) {
            $line .= " / {$book['author']}";
        }
        if ($book['rating']) {
            $line .= " ⭐️ {$book['rating']}";
        }
        if ($book['total_page']) {
            $line .= " ({$book['total_page']}ページ)";
            $total_pages += (int)$book['total_page'];
        }
        $output_lines[] = $line;
    }

    $header = "📅 読書履歴";
    if ($year > 0) {
        $header .= " ({$year}年";
        if ($month > 0) {
            $header .= "{$month}月";
        }
        $header .= ")";
    }
    $header .= "\n\n";
    $header .= "読了冊数: " . count($output_lines) . "冊\n";
    $header .= "総ページ数: " . number_format($total_pages) . "ページ\n\n";

    $text = count($output_lines) > 0
        ? $header . implode("\n", $output_lines)
        : "指定された期間の読書履歴がありません";

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
 * お気に入りジャンルを取得
 */
function handleGetFavoriteGenres($args, $user_id, $id) {
    global $g_db;

    $limit = min((int)($args['limit'] ?? 20), 100);

    $sql = "SELECT tag_name, COUNT(*) as count
            FROM b_book_tags
            WHERE user_id = ?
            GROUP BY tag_name
            ORDER BY count DESC
            LIMIT ?";

    $results = $g_db->getAll($sql, [$user_id, $limit], DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        error_log("handleGetFavoriteGenres DB error: " . $results->getMessage() . " | SQL: " . $sql);
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Database error: ' . $results->getMessage()
            ]
        ];
    }

    $output_lines = [];
    foreach ($results as $row) {
        $output_lines[] = "🏷️ {$row['tag_name']} ({$row['count']}冊)";
    }

    $text = count($output_lines) > 0
        ? "よく読むジャンル:\n\n" . implode("\n", $output_lines)
        : "タグが登録されていません";

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
 * レビューを取得
 */
function handleGetReviews($args, $user_id, $id) {
    global $g_db;

    $book_id = (int)($args['book_id'] ?? 0);
    $limit = min((int)($args['limit'] ?? 50), 500);

    if ($book_id > 0) {
        // 特定の本のレビュー
        $sql = "SELECT bl.book_id, bl.name,
                COALESCE(bl.author, br.author, '') as author,
                bl.memo as review, bl.rating, bl.memo_updated as update_date
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ? AND bl.book_id = ?
                AND bl.memo IS NOT NULL AND bl.memo != ''";
        $params = [$user_id, $book_id];
    } else {
        // 全てのレビュー
        $sql = "SELECT bl.book_id, bl.name,
                COALESCE(bl.author, br.author, '') as author,
                bl.memo as review, bl.rating, bl.memo_updated as update_date
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND bl.memo IS NOT NULL AND bl.memo != ''
                ORDER BY bl.memo_updated DESC
                LIMIT ?";
        $params = [$user_id, $limit];
    }

    $results = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        error_log("handleGetReviews DB error: " . $results->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Database error: ' . $results->getMessage()
            ]
        ];
    }

    $output_lines = [];
    foreach ($results as $row) {
        $output = "📖 {$row['name']}";
        if ($row['author']) {
            $output .= " / {$row['author']}";
        }
        if ($row['rating']) {
            $output .= " ⭐️ {$row['rating']}";
        }
        $output .= "\n";
        $output .= $row['review'];
        $output .= "\n({$row['update_date']})";
        $output_lines[] = $output;
    }

    $text = count($output_lines) > 0
        ? implode("\n\n---\n\n", $output_lines)
        : "レビューがありません";

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
 * 高評価の本をレビュー付きで取得
 */
function handleGetHighRatedBooks($args, $user_id, $id) {
    global $g_db;

    $min_rating = (int)($args['min_rating'] ?? 4);
    $limit = min((int)($args['limit'] ?? 50), 200);

    if ($min_rating < 1 || $min_rating > 5) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32602,
                'message' => 'min_rating must be between 1 and 5'
            ]
        ];
    }

    $sql = "SELECT bl.book_id, bl.name,
            COALESCE(bl.author, br.author, '') as author,
            bl.rating, bl.memo as review, bl.finished_date, bl.total_page
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ? AND bl.rating >= ?
            ORDER BY bl.rating DESC, bl.finished_date DESC
            LIMIT ?";

    $results = $g_db->getAll($sql, [$user_id, $min_rating, $limit], DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        error_log("handleGetHighRatedBooks DB error: " . $results->getMessage());
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Database error'
            ]
        ];
    }

    $output_lines = [];
    foreach ($results as $book) {
        $line = "📖 {$book['name']}";
        if ($book['author']) {
            $line .= " / {$book['author']}";
        }
        $line .= " ⭐️ {$book['rating']}";
        if ($book['total_page']) {
            $line .= " ({$book['total_page']}ページ)";
        }
        if ($book['finished_date'] && $book['finished_date'] !== '0000-00-00') {
            $line .= " 読了: {$book['finished_date']}";
        }
        if (!empty($book['review'])) {
            $review_preview = mb_substr($book['review'], 0, 200, 'UTF-8');
            if (mb_strlen($book['review'], 'UTF-8') > 200) {
                $review_preview .= '...';
            }
            $line .= "\n  レビュー: {$review_preview}";
        }
        $line .= " [ID: {$book['book_id']}]";
        $output_lines[] = $line;
    }

    $text = count($output_lines) > 0
        ? "高評価本 (⭐️{$min_rating}以上): " . count($output_lines) . "件\n\n" . implode("\n\n", $output_lines)
        : "評価{$min_rating}以上の本が見つかりませんでした";

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
 * 既読チェック（あいまい検索）
 */
function handleIsBookRead($args, $user_id, $id) {
    require_once(dirname(__DIR__) . '/library/book_fuzzy_matcher.php');

    $title = $args['title'] ?? '';
    $author = $args['author'] ?? null;

    if (empty($title)) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32602,
                'message' => 'title parameter is required'
            ]
        ];
    }

    $match = findMatchingBook($title, $author, $user_id);

    if ($match) {
        $result_json = json_encode([
            'is_read' => true,
            'matched_book' => [
                'id' => $match['book_id'],
                'title' => $match['name'],
                'author' => $match['author'],
                'rating' => $match['rating']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $result_json = json_encode([
            'is_read' => false,
            'matched_book' => null
        ], JSON_UNESCAPED_UNICODE);
    }

    return [
        'jsonrpc' => '2.0',
        'id' => $id,
        'result' => [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $result_json
                ]
            ]
        ]
    ];
}

/**
 * タグ別の本一覧を取得
 */
function handleGetBooksByTag($args, $user_id, $id) {
    global $g_db;

    $tag = $args['tag'] ?? '';
    $limit = min((int)($args['limit'] ?? 50), 200);

    if (empty($tag)) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32602,
                'message' => 'tag parameter is required'
            ]
        ];
    }

    $sql = "SELECT bl.book_id, bl.name,
            COALESCE(bl.author, br.author, '') as author,
            bl.rating, bl.total_page
            FROM b_book_tags bt
            JOIN b_book_list bl ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bt.user_id = ? AND bt.tag_name = ?
            ORDER BY bl.rating DESC, bl.update_date DESC
            LIMIT ?";

    $results = $g_db->getAll($sql, [$user_id, $tag, $limit], DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        error_log("handleGetBooksByTag DB error: " . $results->getMessage());
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Database error'
            ]
        ];
    }

    $output_lines = [];
    foreach ($results as $book) {
        $line = "📖 {$book['name']}";
        if ($book['author']) {
            $line .= " / {$book['author']}";
        }
        if ($book['rating']) {
            $line .= " ⭐️ {$book['rating']}";
        }
        if ($book['total_page']) {
            $line .= " ({$book['total_page']}ページ)";
        }
        $line .= " [ID: {$book['book_id']}]";
        $output_lines[] = $line;
    }

    $text = count($output_lines) > 0
        ? "🏷️ タグ「{$tag}」の本: " . count($output_lines) . "件\n\n" . implode("\n", $output_lines)
        : "タグ「{$tag}」に該当する本が見つかりませんでした";

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
?>
