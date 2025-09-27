<?php
/**
 * AIアシスタントAPI
 * コンテキストに応じた会話を提供
 */

declare(strict_types=1);

// エラー出力を抑制
error_reporting(E_ALL);
ini_set('display_errors', '0');

// 出力バッファリングを開始
ob_start();

// JSONヘッダーを設定
header('Content-Type: application/json; charset=utf-8');

try {
    require_once(__DIR__ . '/../config.php');
    require_once(__DIR__ . '/../library/database.php');
    require_once(__DIR__ . '/../library/session.php');
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error: ' . $e->getMessage()]);
    exit;
}

// セッション処理を簡略化
try {
    // セッションが開始されていない場合のみ開始
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // デバッグ: セッション情報を確認
    error_log('AI Assistant - Session ID: ' . session_id());
    error_log('AI Assistant - AUTH_USER: ' . (isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : 'not set'));
    
} catch (Exception $e) {
    ob_clean();
    error_log('AI Assistant - Session Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Session initialization failed']);
    exit;
}

// ログインチェック
if (!isset($_SESSION['AUTH_USER'])) {
    ob_clean();
    
    // デバッグモードの場合は仮ユーザーを設定（本番では削除）
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        $_SESSION['AUTH_USER'] = 'debug_user';
        // Debug mode enabled
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - Please login first']);
        exit;
    }
}

$user_id = $_SESSION['AUTH_USER'];

// リクエストメソッドチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. POST required.']);
    exit;
}

// リクエストボディを取得
$input_raw = file_get_contents('php://input');
$input = json_decode($input_raw, true);

// JSONデコードエラーチェック
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

// アクションをチェック
$action = $input['action'] ?? null;

// アクション処理
if ($action) {
    switch ($action) {
        case 'clear_context':
            // セッションの会話履歴をクリア
            $_SESSION['ai_chat_history'] = [];
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Context cleared']);
            exit;
            
        case 'generate_questions':
            // サンプル質問を生成
            $last_response = $input['last_response'] ?? '';
            $context = $input['context'] ?? 'general';
            $page_data = $input['page_data'] ?? [];
            
            // データベース接続（オプショナル）
            $db = null;
            try {
                if (function_exists('DB_Connect')) {
                    $db = @DB_Connect();
                    if (!$db || DB::isError($db)) {
                        $db = null;
                    }
                }
            } catch (Exception $e) {
                $db = null;
            }
            
            $result = generateSampleQuestions($last_response, $context, $page_data, $user_id, $db);
            ob_clean();
            echo json_encode([
                'status' => 'success', 
                'questions' => $result['questions'],
                'is_fallback' => $result['is_fallback']
            ]);
            exit;
    }
}

$message = $input['message'] ?? '';
$context = $input['context'] ?? 'general';
$page_data = $input['page_data'] ?? [];
$conversation_history = $input['conversation_history'] ?? [];

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// データベース接続（オプショナル）
$db = null;
try {
    if (function_exists('DB_Connect')) {
        $db = @DB_Connect();
        if (!$db || DB::isError($db)) {
            throw new Exception('Database connection failed');
        }
        error_log('AI Assistant - Database connected');
    } else {
        error_log('AI Assistant - DB_Connect function not found, using fallback mode');
    }
} catch (Exception $e) {
    error_log('AI Assistant - Database Error: ' . $e->getMessage());
    // データベースなしでも動作を継続（フォールバックモード）
    $db = null;
}

try {
    // セッションに会話履歴を保持
    if (!isset($_SESSION['ai_chat_history'])) {
        $_SESSION['ai_chat_history'] = [];
    }

    // 初回ユーザーチェック
    $is_first_time = false;
    if (!isset($_SESSION['ai_onboarding_shown'])) {
        $is_first_time = true;
        $_SESSION['ai_onboarding_shown'] = true;
    }

    // コンテキストに応じたシステムプロンプトを生成
    $system_prompt = getSystemPrompt($context, $user_id, $page_data, $db);

    // 会話履歴を含めたメッセージ配列を構築
    $messages = [
        ['role' => 'system', 'content' => $system_prompt]
    ];

    // JavaScriptから送信された会話履歴を使用（現在のコンテキストに限定）
    if (!empty($conversation_history)) {
        foreach ($conversation_history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                // システムメッセージをスキップ
                if ($msg['role'] !== 'system') {
                    $messages[] = [
                        'role' => $msg['role'],
                        'content' => $msg['content']
                    ];
                }
            }
        }
    } else {
        // フォールバック：セッションの会話履歴（最新5件まで）
        $history = array_slice($_SESSION['ai_chat_history'], -5);
        foreach ($history as $h) {
            $messages[] = ['role' => 'user', 'content' => $h['user']];
            $messages[] = ['role' => 'assistant', 'content' => $h['assistant']];
        }
    }

    // 現在のメッセージを追加
    $messages[] = ['role' => 'user', 'content' => $message];

    // OpenAI API呼び出し
    $response = callOpenAI($messages);

    if ($response['success']) {
        // 会話履歴に追加
        $_SESSION['ai_chat_history'][] = [
            'user' => $message,
            'assistant' => $response['content'],
            'timestamp' => time()
        ];
        
        // バッファをクリア
        ob_clean();
        
        // レスポンスを返す
        echo json_encode([
            'response' => $response['content'],
            'is_first_time' => $is_first_time,
            'context' => $context
        ]);
    } else {
        // APIエラーの場合、代替メッセージを返す
        $fallback_response = getFallbackResponse($message, $context);
        
        // エラーログに記録
        error_log('AI Assistant API Error: ' . $response['error']);
        
        // 会話履歴に追加（フォールバック）
        $_SESSION['ai_chat_history'][] = [
            'user' => $message,
            'assistant' => $fallback_response,
            'timestamp' => time()
        ];
        
        // バッファをクリア
        ob_clean();
        
        // フォールバック応答を返す
        echo json_encode([
            'response' => $fallback_response,
            'is_first_time' => $is_first_time,
            'context' => $context,
            'fallback' => true
        ]);
    }
} catch (Exception $e) {
    // エラーログ
    error_log('AI Assistant Error: ' . $e->getMessage());
    
    // バッファをクリア
    ob_clean();
    
    // エラー応答
    $error_response = "申し訳ございません。エラーが発生しました。\n\nReadNestの使い方については以下をご確認ください：\n• 本の追加：ヘッダーの検索ボックスから\n• 読書管理：本の詳細ページで\n• レビュー：読了後に記録\n\nヘルプページもご活用ください。";
    
    echo json_encode([
        'response' => $error_response,
        'is_first_time' => false,
        'context' => $context,
        'fallback' => true,
        'error' => true
    ]);
}

// 出力バッファを終了（バッファがある場合のみ）
if (ob_get_level() > 0) {
    ob_end_flush();
}

/**
 * コンテキストに応じたシステムプロンプトを生成
 */
function getSystemPrompt($context, $user_id, $page_data, $db) {
    $nickname = 'ユーザー';
    $is_new_user = false;
    
    // データベースが利用可能な場合のみユーザー情報を取得
    if ($db && function_exists('getUserInformation')) {
        $user_info = @getUserInformation($user_id);
        if ($user_info && isset($user_info['nickname'])) {
            $nickname = $user_info['nickname'];
        }
        // 新規ユーザーかチェック（本の登録がまだない場合）
        $book_count_sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ?";
        $book_count = @$db->getOne($book_count_sql, [$user_id]);
        if ($book_count == 0) {
            $is_new_user = true;
        }
    }
    
    $base_prompt = "あなたはReadNestのAIアシスタントです。{$nickname}さんの読書体験をサポートします。";
    
    // 新規ユーザーの場合は特別な案内を追加
    if ($is_new_user) {
        $base_prompt .= "\n\n【重要】このユーザーは新規登録したばかりで、まだ本を1冊も登録していません。";
        $base_prompt .= "\n積極的に以下の点をサポートしてください：";
        $base_prompt .= "\n- 最初の本の登録方法を丁寧に説明";
        $base_prompt .= "\n- 人気の本や話題の本を提案";
        $base_prompt .= "\n- ReadNestの基本的な使い方を順を追って説明";
        $base_prompt .= "\n- 読書の楽しさを伝え、モチベーションを高める";
    }
    $base_prompt .= "\n\n【基本的な振る舞い】\n";
    $base_prompt .= "- 親しみやすく、でも丁寧な口調で対応\n";
    $base_prompt .= "- 読書の楽しさを共有し、新しい発見を促す\n";
    $base_prompt .= "- 具体的で実用的なアドバイスを提供\n";
    $base_prompt .= "- ReadNestの機能については、実際の機能のみを案内（存在しない機能は提案しない）\n";
    $base_prompt .= "- 機能の使い方を聞かれたら、具体的な手順を案内\n\n";
    
    // ReadNestの主要機能
    $base_prompt .= "【ReadNestの主要機能】\n";
    $base_prompt .= "- 本の追加・管理（検索、手動追加）\n";
    $base_prompt .= "- 読書ステータス管理（いつか買う、未読、読書中、読了、昔読んだ）\n";
    $base_prompt .= "- 読書進捗記録（ページ数）\n";
    $base_prompt .= "- レビュー・評価機能\n";
    $base_prompt .= "- タグ管理（AIタグ生成機能あり）\n";
    $base_prompt .= "- 読書インサイト（AI分析・視覚的な読書傾向分析）\n";
    $base_prompt .= "- AI書評アシスタント\n";
    $base_prompt .= "- AI本の推薦\n";
    $base_prompt .= "- X（Twitter）連携\n";
    $base_prompt .= "- Googleログイン対応\n\n";
    
    switch ($context) {
        case 'home':
            $prompt = $base_prompt;
            $prompt .= "【現在の状況】\n";
            $prompt .= "ユーザーはトップページにいます。本に関する一般的な相談や、ReadNestの使い方について案内してください。\n";
            
            // データベースが利用可能な場合のみ最近の読書活動を取得
            if ($db) {
                $recent_books = getRecentBooks($user_id, $db, 5);
                if (!empty($recent_books)) {
                    $prompt .= "\n【最近の読書活動】\n";
                    foreach ($recent_books as $book) {
                        $prompt .= "- 「{$book['title']}」（{$book['author']}）- {$book['status_name']}\n";
                    }
                }
            }
            break;
            
        case 'bookshelf':
            $prompt = $base_prompt;
            $prompt .= "【現在の状況】\n";
            $prompt .= "ユーザーは本棚ページにいます。本棚にある本についての質問や、読書管理のアドバイスを提供してください。\n";
            
            // データベースが利用可能な場合のみ本棚の統計情報を取得
            if ($db) {
                $stats = getBookshelfStats($user_id, $db);
                $prompt .= "\n【本棚の統計】\n";
                $prompt .= "- いつか買う: {$stats['buy_someday']}冊\n";
                $prompt .= "- 未読: {$stats['not_started']}冊\n";
                $prompt .= "- 読書中: {$stats['reading_now']}冊\n";
                $prompt .= "- 読了: {$stats['reading_finish']}冊\n";
                $prompt .= "- 昔読んだ: {$stats['read_before']}冊\n";
            }
            
            // 検索中の場合
            if (!empty($page_data['search_word'])) {
                $prompt .= "\nユーザーは「{$page_data['search_word']}」で検索中です。\n";
            }
            break;
            
        case 'book_detail':
            $prompt = $base_prompt;
            $prompt .= "【現在の状況】\n";
            $prompt .= "ユーザーは特定の本の詳細ページにいます。この本についての質問や読書のアドバイスを提供してください。\n";
            
            // JavaScriptから送信された本の情報を優先的に使用
            if (!empty($page_data['title'])) {
                $prompt .= "\n【表示中の本】\n";
                $prompt .= "- タイトル: {$page_data['title']}\n";
                if (!empty($page_data['author'])) {
                    $prompt .= "- 著者: {$page_data['author']}\n";
                }
                if (!empty($page_data['publisher'])) {
                    $prompt .= "- 出版社: {$page_data['publisher']}\n";
                }
                if (!empty($page_data['isbn'])) {
                    $prompt .= "- ISBN: {$page_data['isbn']}\n";
                }
                if (!empty($page_data['pages'])) {
                    $prompt .= "- ページ数: {$page_data['pages']}ページ\n";
                }
                
                // ユーザーの読書状態（JavaScriptから取得）
                if (!empty($page_data['status']) || !empty($page_data['rating']) || !empty($page_data['current_page'])) {
                    $prompt .= "\n【ユーザーの読書状態】\n";
                    if (!empty($page_data['status'])) {
                        $prompt .= "- ステータス: {$page_data['status']}\n";
                    }
                    if (!empty($page_data['rating'])) {
                        $prompt .= "- 評価: {$page_data['rating']}点\n";
                    }
                    if (!empty($page_data['current_page'])) {
                        $prompt .= "- 読書進捗: {$page_data['current_page']}ページ\n";
                    }
                    if (!empty($page_data['memo'])) {
                        $prompt .= "- メモ: {$page_data['memo']}\n";
                    }
                    if (!empty($page_data['user_review'])) {
                        $prompt .= "- レビュー: {$page_data['user_review']}\n";
                    }
                }
                
                if (!empty($page_data['tags']) && is_array($page_data['tags'])) {
                    $prompt .= "- タグ: " . implode(', ', $page_data['tags']) . "\n";
                }
            } elseif (!empty($page_data['book_id']) && $db) {
                // JavaScriptから情報が取得できなかった場合のフォールバック
                $book = getBookDetails($page_data['book_id'], $db);
                if ($book) {
                    $prompt .= "\n【表示中の本】\n";
                    $prompt .= "- タイトル: {$book['title']}\n";
                    $prompt .= "- 著者: {$book['author']}\n";
                    $prompt .= "- 出版社: {$book['publisher']}\n";
                    $prompt .= "- ISBN: {$book['isbn10']}\n";
                    
                    // ユーザーの読書状態
                    $user_book = getUserBookInfo($user_id, $page_data['book_id'], $db);
                    if ($user_book) {
                        $prompt .= "\n【ユーザーの読書状態】\n";
                        $prompt .= "- ステータス: {$user_book['status_name']}\n";
                        $prompt .= "- 評価: {$user_book['rating']}点\n";
                        if (!empty($user_book['current_page'])) {
                            $prompt .= "- 読書進捗: {$user_book['current_page']}ページ\n";
                        }
                        if (!empty($user_book['memo'])) {
                            $prompt .= "- レビュー: {$user_book['memo']}\n";
                        }
                    }
                }
            }
            break;
            
        default:
            $prompt = $base_prompt;
            $prompt .= "【現在の状況】\n";
            $prompt .= "ユーザーはReadNestを利用中です。読書に関する相談や、機能の使い方について案内してください。\n";
    }
    
    return $prompt;
}

/**
 * OpenAI APIを呼び出す
 */
function callOpenAI($messages) {
    // APIが無効化されている場合
    if (defined('OPENAI_ENABLED') && !OPENAI_ENABLED) {
        error_log('OpenAI API is disabled');
        return ['success' => false, 'error' => 'API is disabled'];
    }
    
    // APIキーの確認
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY) || OPENAI_API_KEY === 'sk-YOUR_API_KEY_HERE') {
        error_log('OpenAI API key is not configured');
        return ['success' => false, 'error' => 'API configuration error'];
    }
    
    $api_key = OPENAI_API_KEY;
    $api_url = 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini',
        'messages' => $messages,
        'temperature' => defined('OPENAI_TEMPERATURE') ? OPENAI_TEMPERATURE : 0.7,
        'max_tokens' => defined('OPENAI_MAX_TOKENS') ? OPENAI_MAX_TOKENS : 800
    ];
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, defined('OPENAI_TIMEOUT') ? OPENAI_TIMEOUT : 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        error_log('CURL error: ' . $curl_error);
        return ['success' => false, 'error' => 'Network error: ' . $curl_error];
    }
    
    if ($http_code !== 200) {
        error_log('OpenAI API HTTP error: ' . $http_code . ' Response: ' . $response);
        $error_data = json_decode($response, true);
        $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'API request failed';
        return ['success' => false, 'error' => 'API error (' . $http_code . '): ' . $error_message];
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        return ['success' => false, 'error' => 'Invalid API response format'];
    }
    
    if (isset($result['choices'][0]['message']['content'])) {
        return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
    }
    
    // Unexpected API response structure
    return ['success' => false, 'error' => 'Unexpected API response'];
}

/**
 * 最近の読書活動を取得
 */
function getRecentBooks($user_id, $db, $limit = 5) {
    if (!$db) return [];
    
    $sql = "SELECT b.title, b.author, bl.status,
            CASE bl.status
                WHEN 0 THEN 'いつか買う'
                WHEN 1 THEN '未読'
                WHEN 2 THEN '読書中'
                WHEN 3 THEN '読了'
                WHEN 4 THEN '昔読んだ'
            END as status_name
            FROM b_book_list bl
            JOIN b_book b ON bl.book_id = b.book_id
            WHERE bl.user_id = ?
            ORDER BY bl.update_date DESC
            LIMIT ?";
    
    $result = $db->getAll($sql, [$user_id, $limit]);
    return DB::isError($result) ? [] : $result;
}

/**
 * 本棚の統計情報を取得
 */
function getBookshelfStats($user_id, $db) {
    if (!$db) {
        return [
            'buy_someday' => 0,
            'not_started' => 0,
            'reading_now' => 0,
            'reading_finish' => 0,
            'read_before' => 0
        ];
    }
    
    $sql = "SELECT status, COUNT(*) as count
            FROM b_book_list
            WHERE user_id = ?
            GROUP BY status";
    
    $result = $db->getAll($sql, [$user_id]);
    $stats = [
        'buy_someday' => 0,
        'not_started' => 0,
        'reading_now' => 0,
        'reading_finish' => 0,
        'read_before' => 0
    ];
    
    if (!DB::isError($result)) {
        foreach ($result as $row) {
            switch ($row['status']) {
                case 0: $stats['buy_someday'] = $row['count']; break;
                case 1: $stats['not_started'] = $row['count']; break;
                case 2: $stats['reading_now'] = $row['count']; break;
                case 3: $stats['reading_finish'] = $row['count']; break;
                case 4: $stats['read_before'] = $row['count']; break;
            }
        }
    }
    
    return $stats;
}

/**
 * 本の詳細情報を取得
 */
function getBookDetails($book_id, $db) {
    if (!$db) return null;
    
    $sql = "SELECT * FROM b_book WHERE book_id = ?";
    $result = $db->getRow($sql, [$book_id], DB_FETCHMODE_ASSOC);
    return DB::isError($result) ? null : $result;
}

/**
 * ユーザーの本情報を取得
 */
function getUserBookInfo($user_id, $book_id, $db) {
    if (!$db) return null;
    
    $sql = "SELECT bl.*,
            CASE bl.status
                WHEN 0 THEN 'いつか買う'
                WHEN 1 THEN '未読'
                WHEN 2 THEN '読書中'
                WHEN 3 THEN '読了'
                WHEN 4 THEN '昔読んだ'
            END as status_name
            FROM b_book_list bl
            WHERE bl.user_id = ? AND bl.book_id = ?";
    
    $result = $db->getRow($sql, [$user_id, $book_id], DB_FETCHMODE_ASSOC);
    return DB::isError($result) ? null : $result;
}

/**
 * フォールバック応答を生成
 */
function getFallbackResponse($message, $context) {
    $responses = [
        'general' => [
            'default' => "申し訳ございません。現在、AIアシスタントが一時的に利用できません。\n\nReadNestの使い方については以下をご確認ください：\n• 本の追加：ヘッダーの検索ボックスまたは「本を追加」ページから\n• 読書管理：本の詳細ページでステータスや進捗を更新\n• レビュー：読了後に感想を記録\n\nヘルプページもご活用ください。",
            'greeting' => "こんにちは！ReadNest AIアシスタントです。\n\n現在、一時的にAI機能が制限されていますが、以下のお手伝いができます：\n• 本の検索・追加方法の案内\n• 読書記録の管理方法\n• 基本的な使い方の説明\n\n何かお困りのことがあれば、お聞きください。",
            'help' => "ReadNestの主要機能：\n\n📚 本の管理\n• 検索して追加、または手動で追加\n• 読書ステータスの管理\n• 進捗記録\n\n⭐ レビュー・評価\n• 5段階評価\n• 感想の記録\n\n🏷️ タグ機能\n• 本の分類\n• タグで検索\n\n🧠 読書インサイト\n• AIによる本のクラスタリング\n• 著者別・タグ別表示\n• 読書ペース分析"
        ],
        'bookshelf' => [
            'default' => "本棚の管理についてお答えします。\n\n本棚では以下のことができます：\n• ステータスで絞り込み（未読、読書中など）\n• キーワードで検索\n• タグで分類\n• 読書インサイトで全体を把握\n\n効率的な管理のコツ：\n• タグを活用して整理\n• 定期的に進捗を更新\n• レビューを残して記録"
        ],
        'book_detail' => [
            'default' => "この本について何かお手伝いできることがあれば教えてください。\n\n本の詳細ページでは：\n• 読書ステータスの変更\n• 進捗の記録（ページ数）\n• レビューの投稿\n• タグの追加・編集\n• 評価（5段階）\n\nが可能です。"
        ]
    ];
    
    // メッセージに応じた応答を選択
    $message_lower = mb_strtolower($message);
    $context_type = is_array($context) ? ($context['type'] ?? 'general') : $context;
    
    if (strpos($message_lower, 'こんにちは') !== false || strpos($message_lower, 'はじめまして') !== false) {
        return $responses['general']['greeting'];
    } elseif (strpos($message_lower, '使い方') !== false || strpos($message_lower, 'ヘルプ') !== false) {
        return $responses['general']['help'];
    } elseif (isset($responses[$context_type])) {
        return $responses[$context_type]['default'];
    } else {
        return $responses['general']['default'];
    }
}

/**
 * サンプル質問を生成
 */
function generateSampleQuestions($last_response, $context, $page_data, $user_id, $db = null) {
    // APIが無効化されている場合はフォールバック
    if (defined('OPENAI_ENABLED') && !OPENAI_ENABLED) {
        return [
            'questions' => getFallbackQuestions($context),
            'is_fallback' => true
        ];
    }
    
    // APIキーの確認
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY) || OPENAI_API_KEY === 'sk-YOUR_API_KEY_HERE') {
        return [
            'questions' => getFallbackQuestions($context),
            'is_fallback' => true
        ];
    }
    
    // プロンプトを構築
    $prompt = "以下のAIアシスタントの回答に基づいて、ユーザーが次に質問しそうな内容を3つ提案してください。\n\n";
    $prompt .= "【コンテキスト】\n";
    $prompt .= "- 現在のページ: ";
    
    switch ($context) {
        case 'home':
            $prompt .= "トップページ（ホーム）\n";
            break;
        case 'bookshelf':
            $prompt .= "本棚ページ\n";
            break;
        case 'book_detail':
            $prompt .= "本の詳細ページ\n";
            if (!empty($page_data['title'])) {
                $prompt .= "- 表示中の本: 「{$page_data['title']}」\n";
                if (!empty($page_data['author'])) {
                    $prompt .= "- 著者: {$page_data['author']}\n";
                }
            }
            break;
        default:
            $prompt .= "その他のページ\n";
    }
    
    $prompt .= "\n【AIアシスタントの最後の回答】\n";
    $prompt .= $last_response . "\n\n";
    
    $prompt .= "【要件】\n";
    $prompt .= "- 会話の流れに自然につながる質問を提案\n";
    $prompt .= "- 各質問は短く（20文字以内）\n";
    $prompt .= "- 絵文字を1つ含める\n";
    $prompt .= "- 質問のみを返す（説明不要）\n";
    $prompt .= "- 3つの質問を改行で区切って返す\n";
    
    // OpenAI API呼び出し
    $messages = [
        ['role' => 'system', 'content' => 'あなたは読書管理アプリReadNestのアシスタントです。ユーザーの次の質問を予測して提案してください。'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    $response = callOpenAI($messages);
    
    if ($response['success']) {
        // レスポンスを行ごとに分割
        $questions = array_filter(array_map('trim', explode("\n", $response['content'])));
        // 最大3つまでに制限
        $questions = array_slice($questions, 0, 3);
        
        // 質問が少ない場合はフォールバックを追加
        if (count($questions) < 3) {
            $fallback = getFallbackQuestions($context);
            $questions = array_merge($questions, array_slice($fallback, 0, 3 - count($questions)));
            return [
                'questions' => $questions,
                'is_fallback' => true  // 部分的にフォールバックを使用
            ];
        }
        
        return [
            'questions' => $questions,
            'is_fallback' => false
        ];
    } else {
        // API呼び出しが失敗した場合はフォールバック
        return [
            'questions' => getFallbackQuestions($context),
            'is_fallback' => true
        ];
    }
}

/**
 * フォールバック質問を返す
 */
function getFallbackQuestions($context) {
    switch ($context) {
        case 'home':
            return [
                '📚 おすすめの本を教えて',
                '📋 今月の読書目標を立てたい',
                '🔍 特定のジャンルで探したい'
            ];
        case 'bookshelf':
            return [
                '📊 読書進捗を管理したい',
                '🏷️ タグで本を整理したい',
                '📅 読書計画を作りたい'
            ];
        case 'book_detail':
            return [
                'この本について教えて',
                '似たような本を探したい',
                'レビューを書きたい'
            ];
        default:
            return [
                '📚 ReadNestの使い方',
                '❓ よくある質問',
                '📈 読書統計の見方'
            ];
    }
}
?>