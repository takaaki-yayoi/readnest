<?php
/**
 * AI自然言語検索API
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// エラーハンドリング設定
error_reporting(0);
ini_set('display_errors', '0');

// エラーハンドラー設定
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("AI Search API Error: $errstr in $errfile on line $errline");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
    exit;
});

// 設定読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/ai_search_engine.php');

// データベース接続を初期化
global $g_db;
if (!isset($g_db) || !$g_db) {
    $g_db = DB_Connect();
}

// レスポンスヘッダー設定
header('Content-Type: application/json; charset=utf-8');

// CORSヘッダー（必要に応じて設定）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// レート制限チェック
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$rate_limit_key = 'ai_search_rate_' . md5($client_ip);
$rate_limit = 30; // 1分間に30回まで

// キャッシュでレート制限を管理
if (function_exists('apcu_fetch')) {
    $current_count = apcu_fetch($rate_limit_key) ?: 0;
    if ($current_count >= $rate_limit) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Rate limit exceeded. Please try again later.'
        ]);
        exit;
    }
    apcu_store($rate_limit_key, $current_count + 1, 60);
}

// パラメータ取得
$query = $_GET['q'] ?? $_GET['query'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$progress_only = isset($_GET['progress']) && $_GET['progress'] == '1';
$autocomplete_mode = isset($_GET['autocomplete']) && $_GET['autocomplete'] == '1';

// バリデーション
if (empty($query)) {
    echo json_encode([
        'success' => false,
        'error' => 'Query parameter is required'
    ]);
    exit;
}

if (mb_strlen($query) > 200) {
    echo json_encode([
        'success' => false,
        'error' => 'Query is too long (max 200 characters)'
    ]);
    exit;
}

try {
    // キャッシュキー生成
    $cache_key = 'ai_search_' . md5($query . '_' . $page . '_' . $limit);
    
    // キャッシュチェック（関数が存在する場合のみ）
    if (function_exists('getCacheData')) {
        $cached_result = getCacheData($cache_key);
        if ($cached_result !== false) {
            echo json_encode($cached_result);
            exit;
        }
    }
    
    // AI検索エンジンを使用
    $searchEngine = new AISearchEngine();
    
    // オートコンプリートモードの場合は、自然言語クエリの候補を返す
    if ($autocomplete_mode) {
        $suggestions = generateAISearchSuggestions($query);
        $response = [
            'success' => true,
            'suggestions' => $suggestions
        ];
        echo json_encode($response);
        exit;
    }
    
    // 進捗情報のみ返す場合は、クエリ解析結果のみ取得
    if ($progress_only) {
        $analysis = $searchEngine->analyzeQuery($query);
        $response = [
            'success' => true,
            'query' => $query,
            'detected_intent' => $analysis['intent'] ?? [],
            'expanded_keywords' => $analysis['keywords'] ?? []
        ];
        echo json_encode($response);
        exit;
    }
    
    $results = $searchEngine->search($query, $page, $limit);
    
    // 結果を整形
    $response = [
        'success' => true,
        'query' => $query,
        'natural_language_query' => $query,
        'detected_intent' => $results['intent'] ?? [],
        'expanded_keywords' => $results['keywords'] ?? [],
        'page' => $page,
        'limit' => $limit,
        'total' => $results['total'] ?? 0,
        'books' => []
    ];
    
    // 本のデータを整形
    if (!empty($results['results'])) {
        foreach ($results['results'] as $book) {
            $response['books'][] = [
                'book_id' => $book['ASIN'] ?? null,
                'title' => $book['Title'] ?? '',
                'author' => $book['Author'] ?? '',
                'isbn' => $book['ISBN'] ?? '',
                'image_url' => $book['LargeImage'] ?? '/img/no-image-book.png',
                'description' => $book['Description'] ?? '',
                'page_count' => $book['NumberOfPages'] ?? 0,
                'categories' => $book['Categories'] ?? [],
                'ai_relevance_score' => $book['ai_score'] ?? 0,
                'source' => 'google_books'
            ];
        }
    }
    
    // 検索履歴を保存（ログイン中のユーザーのみ）
    if (checkLogin()) {
        $user_id = $_SESSION['AUTH_USER'] ?? 0;
        saveSearchHistory($user_id, $query, 'ai_search');
    }
    
    // キャッシュに保存（15分間）
    if (function_exists('setCacheData')) {
        setCacheData($cache_key, $response, 900);
    }
    
    // レスポンス送信
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('AI Search API Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'debug' => [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * 検索履歴を保存
 */
function saveSearchHistory(int $user_id, string $query, string $search_type): void {
    global $g_db;
    
    try {
        $sql = "INSERT INTO b_search_history (user_id, query, search_type, created_at) 
                VALUES (?, ?, ?, NOW())";
        $g_db->query($sql, [$user_id, $query, $search_type]);
    } catch (Exception $e) {
        // エラーは記録するが処理は継続
        error_log('Failed to save search history: ' . $e->getMessage());
    }
}

/**
 * AI検索用の自然言語クエリ候補を生成
 */
function generateAISearchSuggestions(string $query): array {
    $suggestions = [];
    $query_lower = mb_strtolower($query);
    
    // 自然言語クエリのパターン
    $patterns = [
        '泣ける' => [
            ['title' => '泣ける恋愛小説', 'type' => 'ai_suggestion'],
            ['title' => '泣ける感動の実話', 'type' => 'ai_suggestion'],
            ['title' => '泣けるペットの物語', 'type' => 'ai_suggestion']
        ],
        '元気' => [
            ['title' => '元気が出るビジネス書', 'type' => 'ai_suggestion'],
            ['title' => '元気になれる自己啓発本', 'type' => 'ai_suggestion'],
            ['title' => '元気をもらえるエッセイ', 'type' => 'ai_suggestion']
        ],
        '夏' => [
            ['title' => '夏に読みたい小説', 'type' => 'ai_suggestion'],
            ['title' => '夏の冒険物語', 'type' => 'ai_suggestion'],
            ['title' => '夏を感じる青春小説', 'type' => 'ai_suggestion']
        ],
        '恋愛' => [
            ['title' => '恋愛小説の名作', 'type' => 'ai_suggestion'],
            ['title' => '恋愛がうまくいく本', 'type' => 'ai_suggestion'],
            ['title' => '恋愛エッセイ', 'type' => 'ai_suggestion']
        ],
        '怖い' => [
            ['title' => '怖いホラー小説', 'type' => 'ai_suggestion'],
            ['title' => '怖い都市伝説の本', 'type' => 'ai_suggestion'],
            ['title' => '怖いけど面白いミステリー', 'type' => 'ai_suggestion']
        ],
        '心' => [
            ['title' => '心が温まる家族の物語', 'type' => 'ai_suggestion'],
            ['title' => '心に響く名言集', 'type' => 'ai_suggestion'],
            ['title' => '心が軽くなる本', 'type' => 'ai_suggestion']
        ],
        '初心者' => [
            ['title' => '初心者向けプログラミング本', 'type' => 'ai_suggestion'],
            ['title' => '初心者でも読みやすい小説', 'type' => 'ai_suggestion'],
            ['title' => '初心者向け投資入門書', 'type' => 'ai_suggestion']
        ]
    ];
    
    // マッチするパターンを探す
    foreach ($patterns as $keyword => $pattern_suggestions) {
        if (mb_strpos($query_lower, $keyword) !== false) {
            $suggestions = array_merge($suggestions, $pattern_suggestions);
            break;
        }
    }
    
    // マッチしない場合は一般的な候補を返す
    if (empty($suggestions)) {
        $suggestions = [
            ['title' => $query . 'に関する本', 'type' => 'ai_suggestion'],
            ['title' => $query . 'がテーマの小説', 'type' => 'ai_suggestion'],
            ['title' => $query . 'について学べる本', 'type' => 'ai_suggestion']
        ];
    }
    
    return array_slice($suggestions, 0, 5);
}
?>