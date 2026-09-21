<?php
/**
 * MCP統合型AIアシスタント
 * MCPツールを使用して読書データにアクセス
 */

require_once('../modern_config.php');
require_once(dirname(__DIR__) . '/library/openai_models.php');

// APIレスポンスヘッダー
header('Content-Type: application/json; charset=utf-8');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    echo json_encode(['success' => false, 'message' => 'ログインが必要です']);
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];

// POSTデータを取得
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim($input['message']) : '';
$conversation_history = isset($input['conversation_history']) ? $input['conversation_history'] : [];

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'メッセージが入力されていません']);
    exit;
}

// OpenAI APIキーチェック
if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
    echo json_encode([
        'success' => true,
        'response' => '申し訳ございません。現在AIアシスタント機能はメンテナンス中です。'
    ]);
    exit;
}

// MCPツールを実行（mcp/messages.phpからコピーして内部実装）
function executeMcpTool($tool_name, $arguments, $user_id) {
    global $g_db;

    try {
        switch ($tool_name) {
            case 'get_bookshelf':
                return getBookshelfData($arguments, $user_id);
            case 'get_reading_stats':
                return getReadingStatsData($user_id);
            case 'search_books':
                return searchBooksData($arguments, $user_id);
            case 'get_book_detail':
                return getBookDetailData($arguments, $user_id);
            case 'get_reading_history':
                return getReadingHistoryData($arguments, $user_id);
            case 'get_favorite_genres':
                return getFavoriteGenresData($user_id);
            case 'get_reviews':
                return getReviewsData($arguments, $user_id);
            default:
                return 'Unknown tool: ' . $tool_name;
        }
    } catch (Exception $e) {
        error_log("MCP tool execution error: " . $e->getMessage());
        return 'エラーが発生しました: ' . $e->getMessage();
    }
}

// 本棚データ取得
function getBookshelfData($args, $user_id) {
    global $g_db;

    $status = $args['status'] ?? '';
    $limit = min((int)($args['limit'] ?? 500), 5000);

    $status_map = [
        'tsundoku' => 1,
        'reading' => 2,
        'finished' => 3,
        'read' => 4
    ];

    $where = "bl.user_id = ?";
    $params = [$user_id];

    if ($status && isset($status_map[$status])) {
        $where .= " AND bl.status = ?";
        $params[] = $status_map[$status];
    }

    $sql = "SELECT bl.book_id, bl.name, COALESCE(bl.author, br.author, '') as author,
            bl.status, bl.rating, bl.update_date
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE {$where}
            ORDER BY bl.update_date DESC
            LIMIT ?";
    $params[] = $limit;

    $results = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        return 'データベースエラー';
    }

    $status_name = [1 => '積読', 2 => '読書中', 3 => '読了', 4 => '既読'];
    $lines = [];
    foreach ($results as $book) {
        $line = "📖 {$book['name']}";
        if ($book['author']) $line .= " / {$book['author']}";
        $line .= " ({$status_name[(int)$book['status']]})";
        if ($book['rating']) $line .= " ⭐️ {$book['rating']}";
        $line .= " [ID: {$book['book_id']}]";
        $lines[] = $line;
    }

    return count($lines) > 0 ? implode("\n", $lines) : '本棚に本がありません';
}

// 読書統計取得
function getReadingStatsData($user_id) {
    global $g_db;

    $sql = "SELECT
            COUNT(*) as total,
            COUNT(CASE WHEN status = 1 THEN 1 END) as tsundoku,
            COUNT(CASE WHEN status = 2 THEN 1 END) as reading,
            COUNT(CASE WHEN status = 3 THEN 1 END) as finished,
            COUNT(CASE WHEN status = 4 THEN 1 END) as already_read,
            AVG(CASE WHEN rating > 0 THEN rating END) as avg_rating,
            COUNT(CASE WHEN YEAR(finished_date) = YEAR(CURDATE()) THEN 1 END) as finished_this_year,
            COUNT(CASE WHEN YEAR(finished_date) = YEAR(CURDATE()) AND MONTH(finished_date) = MONTH(CURDATE()) THEN 1 END) as finished_this_month
            FROM b_book_list
            WHERE user_id = ?";

    $stats = $g_db->getRow($sql, [$user_id], DB_FETCHMODE_ASSOC);

    if (DB::isError($stats)) {
        return 'データベースエラー';
    }

    $output = "📊 読書統計\n\n";
    $output .= "総冊数: {$stats['total']}冊\n";
    $output .= "積読: {$stats['tsundoku']}冊\n";
    $output .= "読書中: {$stats['reading']}冊\n";
    $output .= "読了: {$stats['finished']}冊\n";
    $output .= "既読: {$stats['already_read']}冊\n";
    if ($stats['avg_rating']) {
        $output .= "平均評価: " . number_format($stats['avg_rating'], 1) . "\n";
    }
    $output .= "今年読了: {$stats['finished_this_year']}冊\n";
    $output .= "今月読了: {$stats['finished_this_month']}冊";

    return $output;
}

// 本を検索
function searchBooksData($args, $user_id) {
    global $g_db;

    $query = $args['query'] ?? '';
    $limit = min((int)($args['limit'] ?? 50), 500);

    if (empty($query)) {
        return 'クエリが必要です';
    }

    $sql = "SELECT bl.book_id, bl.name, COALESCE(bl.author, br.author, '') as author,
            bl.status, bl.rating
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND (bl.name LIKE ? OR COALESCE(bl.author, br.author, '') LIKE ? OR bl.isbn LIKE ?)
            ORDER BY bl.update_date DESC
            LIMIT ?";

    $search_term = '%' . $query . '%';
    $results = $g_db->getAll($sql, [$user_id, $search_term, $search_term, $search_term, $limit], DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        return 'データベースエラー';
    }

    $status_name = [1 => '積読', 2 => '読書中', 3 => '読了', 4 => '既読'];
    $lines = [];
    foreach ($results as $book) {
        $line = "📖 {$book['name']}";
        if ($book['author']) $line .= " / {$book['author']}";
        $line .= " ({$status_name[(int)$book['status']]})";
        if ($book['rating']) $line .= " ⭐️ {$book['rating']}";
        $line .= " [ID: {$book['book_id']}]";
        $lines[] = $line;
    }

    return count($lines) > 0
        ? "検索結果: " . count($lines) . "件\n\n" . implode("\n", $lines)
        : "「{$query}」に一致する本が見つかりませんでした";
}

// 本の詳細取得
function getBookDetailData($args, $user_id) {
    global $g_db;

    $book_id = (int)($args['book_id'] ?? 0);

    if ($book_id <= 0) {
        return 'book_idが必要です';
    }

    $sql = "SELECT bl.book_id, bl.name, COALESCE(bl.author, br.author, '') as author,
            bl.status, bl.rating, bl.total_page, bl.current_page, bl.finished_date,
            bl.create_date, bl.memo, br.description
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ? AND bl.book_id = ?";

    $book = $g_db->getRow($sql, [$user_id, $book_id], DB_FETCHMODE_ASSOC);

    if (DB::isError($book) || !$book) {
        return '本が見つかりませんでした';
    }

    $status_name = [1 => '積読', 2 => '読書中', 3 => '読了', 4 => '既読'];

    $output = "📚 {$book['name']}\n\n";
    $output .= "著者: {$book['author']}\n";
    $output .= "ステータス: {$status_name[(int)$book['status']]}\n";
    if ($book['rating']) $output .= "評価: ⭐️ {$book['rating']}\n";
    if ($book['current_page'] && $book['total_page']) {
        $progress = (int)(($book['current_page'] / $book['total_page']) * 100);
        $output .= "進捗: {$book['current_page']}/{$book['total_page']}ページ ({$progress}%)\n";
    }
    if ($book['finished_date'] && $book['finished_date'] !== '0000-00-00') {
        $output .= "読了日: {$book['finished_date']}\n";
    }
    $output .= "登録日: {$book['create_date']}\n";

    if (!empty($book['memo'])) {
        $output .= "\nレビュー:\n{$book['memo']}\n";
    }
    if ($book['description']) {
        $output .= "\n説明:\n{$book['description']}\n";
    }

    return $output;
}

// 読書履歴取得
function getReadingHistoryData($args, $user_id) {
    global $g_db;

    $year = (int)($args['year'] ?? 0);
    $month = (int)($args['month'] ?? 0);
    $limit = min((int)($args['limit'] ?? 100), 1000);

    $where = ["bl.user_id = ?", "bl.finished_date IS NOT NULL", "bl.finished_date != '0000-00-00'"];
    $params = [$user_id];

    if ($year > 0) {
        $where[] = "YEAR(bl.finished_date) = ?";
        $params[] = $year;
    }
    if ($month > 0) {
        $where[] = "MONTH(bl.finished_date) = ?";
        $params[] = $month;
    }

    $sql = "SELECT bl.book_id, bl.name, COALESCE(bl.author, br.author, '') as author,
            bl.rating, bl.finished_date, bl.total_page
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE " . implode(" AND ", $where) . "
            ORDER BY bl.finished_date DESC
            LIMIT ?";
    $params[] = $limit;

    $results = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        return 'データベースエラー';
    }

    $lines = [];
    $total_pages = 0;

    foreach ($results as $book) {
        $line = "{$book['finished_date']} - 📖 {$book['name']}";
        if ($book['author']) $line .= " / {$book['author']}";
        if ($book['rating']) $line .= " ⭐️ {$book['rating']}";
        if ($book['total_page']) {
            $line .= " ({$book['total_page']}ページ)";
            $total_pages += (int)$book['total_page'];
        }
        $lines[] = $line;
    }

    $header = "📅 読書履歴: " . count($lines) . "冊";
    if ($total_pages > 0) {
        $header .= " (合計 " . number_format($total_pages) . "ページ)";
    }

    return count($lines) > 0
        ? $header . "\n\n" . implode("\n", $lines)
        : '読書履歴がありません';
}

// よく読むジャンル取得
function getFavoriteGenresData($user_id) {
    global $g_db;

    $sql = "SELECT tag_name, COUNT(*) as count
            FROM b_book_tags
            WHERE user_id = ?
            GROUP BY tag_name
            ORDER BY count DESC
            LIMIT 20";

    $results = $g_db->getAll($sql, [$user_id], DB_FETCHMODE_ASSOC);

    if (DB::isError($results)) {
        return 'データベースエラー';
    }

    $lines = [];
    foreach ($results as $row) {
        $lines[] = "🏷️ {$row['tag_name']} ({$row['count']}冊)";
    }

    return count($lines) > 0
        ? "よく読むジャンル:\n\n" . implode("\n", $lines)
        : 'タグが登録されていません';
}

// レビュー取得
function getReviewsData($args, $user_id) {
    global $g_db;

    $book_id = (int)($args['book_id'] ?? 0);
    $limit = min((int)($args['limit'] ?? 50), 500);

    if ($book_id > 0) {
        $sql = "SELECT bl.book_id, bl.name, COALESCE(bl.author, br.author, '') as author,
                bl.memo as review, bl.rating, bl.memo_updated as update_date
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ? AND bl.book_id = ?
                AND bl.memo IS NOT NULL AND bl.memo != ''";
        $params = [$user_id, $book_id];
    } else {
        $sql = "SELECT bl.book_id, bl.name, COALESCE(bl.author, br.author, '') as author,
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
        return 'データベースエラー';
    }

    $lines = [];
    foreach ($results as $row) {
        $output = "📖 {$row['name']}";
        if ($row['author']) $output .= " / {$row['author']}";
        if ($row['rating']) $output .= " ⭐️ {$row['rating']}";
        $output .= "\n" . $row['review'];
        $output .= "\n({$row['update_date']})";
        $lines[] = $output;
    }

    return count($lines) > 0
        ? implode("\n\n---\n\n", $lines)
        : 'レビューがありません';
}

// MCPツール定義
$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'get_bookshelf',
            'description' => 'ユーザーの本棚データを取得します。status（tsundoku/reading/finished/read）でフィルタ可能。',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'status' => [
                        'type' => 'string',
                        'enum' => ['tsundoku', 'reading', 'finished', 'read'],
                        'description' => '本のステータス'
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => '取得件数'
                    ]
                ]
            ]
        ]
    ],
    [
        'type' => 'function',
        'function' => [
            'name' => 'get_reading_stats',
            'description' => '読書統計情報（総冊数、ステータス別冊数、今年/今月の読了数など）を取得します。',
            'parameters' => [
                'type' => 'object',
                'properties' => (object)[]
            ]
        ]
    ],
    [
        'type' => 'function',
        'function' => [
            'name' => 'search_books',
            'description' => 'タイトル、著者名、ISBNで本を検索します。',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => '検索キーワード'
                    ]
                ],
                'required' => ['query']
            ]
        ]
    ],
    [
        'type' => 'function',
        'function' => [
            'name' => 'get_book_detail',
            'description' => '特定の本の詳細情報を取得します。',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'book_id' => [
                        'type' => 'integer',
                        'description' => '本のID'
                    ]
                ],
                'required' => ['book_id']
            ]
        ]
    ],
    [
        'type' => 'function',
        'function' => [
            'name' => 'get_reading_history',
            'description' => '読書履歴を取得します。年月でフィルタ可能。',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'year' => [
                        'type' => 'integer',
                        'description' => '年'
                    ],
                    'month' => [
                        'type' => 'integer',
                        'description' => '月'
                    ]
                ]
            ]
        ]
    ],
    [
        'type' => 'function',
        'function' => [
            'name' => 'get_favorite_genres',
            'description' => 'よく読むジャンル（タグ）を取得します。',
            'parameters' => [
                'type' => 'object',
                'properties' => (object)[]
            ]
        ]
    ],
    [
        'type' => 'function',
        'function' => [
            'name' => 'get_reviews',
            'description' => 'レビューを取得します。book_idを指定すると特定の本のレビューのみ取得。',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'book_id' => [
                        'type' => 'integer',
                        'description' => '本のID（オプション）'
                    ]
                ]
            ]
        ]
    ]
];

// システムプロンプト
$system_prompt = "あなたはReadNestの読書アシスタントです。ユーザーの読書に関する質問に答えてください。

利用可能なツール:
- get_bookshelf: 本棚の本を取得
- get_reading_stats: 読書統計を取得
- search_books: 本を検索
- get_book_detail: 本の詳細を取得
- get_reading_history: 読書履歴を取得
- get_favorite_genres: よく読むジャンルを取得
- get_reviews: レビューを取得

必要に応じてこれらのツールを使用して、ユーザーの質問に答えてください。
フレンドリーで親しみやすい口調で対応してください。";

// 会話履歴を構築
$messages = [];
$messages[] = ['role' => 'system', 'content' => $system_prompt];

// 過去の会話履歴を追加
foreach ($conversation_history as $item) {
    $messages[] = [
        'role' => $item['role'],
        'content' => $item['content']
    ];
}

// 新しいメッセージを追加
$messages[] = ['role' => 'user', 'content' => $message];

// OpenAI API呼び出し（最大3回のツールループ）
$max_iterations = 3;
$iteration = 0;

while ($iteration < $max_iterations) {
    $iteration++;

    $request_body = openaiChatParams([
        'model' => openaiChatModel(),
        'messages' => $messages,
        'tools' => $tools,
        'tool_choice' => 'auto'
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        echo json_encode([
            'success' => false,
            'message' => 'OpenAI APIエラー: ' . $http_code,
            'debug' => $response
        ]);
        exit;
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid API response',
            'debug' => $result
        ]);
        exit;
    }

    $assistant_message = $result['choices'][0]['message'];
    $messages[] = $assistant_message;

    // ツール呼び出しがない場合は終了
    if (!isset($assistant_message['tool_calls'])) {
        echo json_encode([
            'success' => true,
            'response' => $assistant_message['content']
        ]);
        exit;
    }

    // ツールを実行
    foreach ($assistant_message['tool_calls'] as $tool_call) {
        $tool_name = $tool_call['function']['name'];
        $arguments = json_decode($tool_call['function']['arguments'], true);

        $tool_result = executeMcpTool($tool_name, $arguments, $mine_user_id);

        $messages[] = [
            'role' => 'tool',
            'tool_call_id' => $tool_call['id'],
            'name' => $tool_name,
            'content' => $tool_result ?? 'エラーが発生しました'
        ];
    }
}

// 最大反復に達した場合
echo json_encode([
    'success' => true,
    'response' => '申し訳ございません。処理に時間がかかりすぎています。もう一度お試しください。'
]);
?>
