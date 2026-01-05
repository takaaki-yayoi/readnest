<?php
/**
 * AI書評アシスタント シンプル版APIエンドポイント
 */

// 出力バッファリングを開始（エラー出力を制御）
ob_start();

// エラー表示を無効化
error_reporting(0);
ini_set('display_errors', 0);

// カスタムエラーハンドラ（HTMLを出力しない）
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("AI Review Simple Error: $errstr in $errfile on line $errline");
    return true;
});

// 必要最小限の設定
define('ROOT_PATH', dirname(__FILE__));

// OpenAI APIキーを定義
if (!defined('OPENAI_API_KEY')) {
    // config.phpから読み込む
    require_once(ROOT_PATH . '/config.php');
}

// 出力バッファをクリア
ob_clean();

// Fatal error handler to ensure JSON response
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error occurred: ' . $error['message']
        ]);
    }
});

// CORS対応
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONSリクエスト（プリフライト）の処理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

// レスポンスヘッダー
header('Content-Type: application/json; charset=UTF-8');

// POSTリクエストのみ処理
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// APIキーが設定されていない場合はエラー
if (empty(OPENAI_API_KEY)) {
    echo json_encode([
        'success' => false, 
        'error' => 'OpenAI APIキーが設定されていません。'
    ]);
    exit;
}

// ライブラリを読み込み
try {
    require_once(ROOT_PATH . '/library/openai_client.php');
    require_once(ROOT_PATH . '/library/ai_review_assistant.php');
    require_once(ROOT_PATH . '/library/ai_book_recommender.php');
} catch (Exception $e) {
    error_log('AI Review Simple: Failed to load libraries: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'ライブラリの読み込みに失敗しました: ' . $e->getMessage()
    ]);
    exit;
}

// リクエストデータを取得
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (empty($input)) {
    echo json_encode([
        'success' => false,
        'error' => 'リクエストデータが空です'
    ]);
    exit;
}

// アクションを取得
$action = $input['action'] ?? '';

// AIアシスタントを初期化
$assistant = new AIReviewAssistant();
$recommender = new AIBookRecommender();

try {
    switch ($action) {
        case 'generate_review':
            // 書評生成
            $bookInfo = [
                'title' => $input['title'] ?? '',
                'author' => $input['author'] ?? ''
            ];
            $userInput = $input['user_input'] ?? '';
            $rating = intval($input['rating'] ?? 3);
            
            if (empty($userInput)) {
                echo json_encode([
                    'success' => false,
                    'error' => '感想やキーワードを入力してください'
                ]);
                exit;
            }
            
            $result = $assistant->generateReview($bookInfo, $userInput, $rating);
            echo json_encode($result);
            break;
            
        case 'suggest_tags':
            // タグ提案
            $bookInfo = [
                'title' => $input['title'] ?? '',
                'author' => $input['author'] ?? ''
            ];
            $review = $input['review'] ?? '';
            
            if (empty($review)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'レビューテキストがありません'
                ]);
                exit;
            }
            
            $result = $assistant->suggestTags($bookInfo, $review);
            echo json_encode($result);
            break;
            
        case 'summarize_review':
            // レビュー要約
            $review = $input['review'] ?? '';
            
            if (empty($review)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'レビューテキストがありません'
                ]);
                exit;
            }
            
            $result = $assistant->summarizeReview($review);
            echo json_encode($result);
            break;
            
        case 'recommend_books':
            // 本の推薦
            $readingHistory = $input['reading_history'] ?? [];
            
            if (empty($readingHistory)) {
                echo json_encode([
                    'success' => false,
                    'error' => '読書履歴がありません'
                ]);
                exit;
            }
            
            // 読書履歴を最大20冊に制限
            $limitedHistory = array_slice($readingHistory, 0, 20);
            
            // 各本のデータを圧縮
            $compressedHistory = [];
            foreach ($limitedHistory as $book) {
                $compressedHistory[] = [
                    'title' => mb_substr($book['title'] ?? '', 0, 50),
                    'author' => mb_substr($book['author'] ?? '', 0, 30),
                    'rating' => $book['rating'] ?? 0
                ];
            }
            
            $result = $recommender->recommendBooks($compressedHistory);
            echo json_encode($result);
            break;
            
        case 'analyze_trends':
            // 読書傾向分析
            $readingHistory = $input['reading_history'] ?? [];
            
            if (empty($readingHistory)) {
                echo json_encode([
                    'success' => false,
                    'error' => '読書履歴がありません'
                ]);
                exit;
            }
            
            // 読書履歴を最大30冊に制限
            $limitedHistory = array_slice($readingHistory, 0, 30);
            
            // 各本のデータを圧縮
            $compressedHistory = [];
            foreach ($limitedHistory as $book) {
                $compressedHistory[] = [
                    'title' => mb_substr($book['title'] ?? '', 0, 50),
                    'author' => mb_substr($book['author'] ?? '', 0, 30),
                    'rating' => $book['rating'] ?? 0
                ];
            }
            
            $result = $recommender->analyzeReadingTrends($compressedHistory);

            // 保存オプションは別のエンドポイントで処理するため、ここでは無視

            echo json_encode($result);
            break;

        case 'generate_monthly_summary':
            // 月間レポート要約生成
            $year = isset($input['year']) ? (int)$input['year'] : 0;
            $month = isset($input['month']) ? (int)$input['month'] : 0;
            $reportData = $input['report_data'] ?? [];

            if ($year < 2015 || $month < 1 || $month > 12) {
                echo json_encode([
                    'success' => false,
                    'error' => '無効な年月です'
                ]);
                exit;
            }

            if (empty($reportData)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'レポートデータがありません'
                ]);
                exit;
            }

            $result = $recommender->generateMonthlySummary($year, $month, $reportData);
            echo json_encode($result);
            break;

        case 'suggest_challenge':
            // 読書チャレンジ提案
            $readingHistory = $input['reading_history'] ?? [];
            $challenge = $input['challenge'] ?? '新しいジャンル';
            
            if (empty($readingHistory)) {
                echo json_encode([
                    'success' => false,
                    'error' => '読書履歴がありません'
                ]);
                exit;
            }
            
            // 読書履歴を最大20冊に制限
            $limitedHistory = array_slice($readingHistory, 0, 20);
            
            // 各本のデータを圧縮
            $compressedHistory = [];
            foreach ($limitedHistory as $book) {
                $compressedHistory[] = [
                    'title' => mb_substr($book['title'] ?? '', 0, 50),
                    'author' => mb_substr($book['author'] ?? '', 0, 30),
                    'rating' => $book['rating'] ?? 0
                ];
            }
            
            $result = $recommender->suggestReadingChallenge($compressedHistory, $challenge);
            
            // パースされた提案がある場合は、フロントエンド用に整形
            if (isset($result['parsed_suggestions']) && !empty($result['parsed_suggestions'])) {
                $result['recommendations'] = $result['parsed_suggestions'];
            }
            
            echo json_encode($result);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    error_log('AI Review Simple Exception: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'エラー: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    error_log('AI Review Simple Fatal Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Fatal Error: ' . $e->getMessage()
    ]);
}

// 出力バッファを終了
ob_end_flush();
?>