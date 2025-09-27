<?php
/**
 * AI書評アシスタントAPIエンドポイント
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// エラーログを有効化（デバッグ用）
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once('config.php');
    require_once('library/session.php');
    require_once('library/ai_review_assistant.php');
    
    // セッション管理
    $con = new SessionClass();
    $con->Session();
    
    // ログインチェック
    $login_flag = false;
    if (isset($_SESSION['AUTH']) && $_SESSION['AUTH'] == 1 && isset($_SESSION['AUTH_USER'])) {
        $login_flag = true;
    }
    
    if (!$login_flag) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => 'ログインが必要です'
        ]);
        exit;
    }
} catch (Exception $e) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'error' => 'サーバーエラー: ' . $e->getMessage()
    ]);
    exit;
}

// POSTリクエストのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

// リクエストデータを取得
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

header('Content-Type: application/json; charset=UTF-8');

$assistant = new AIReviewAssistant();

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
        
    case 'improve_review':
        // 書評改善
        $originalReview = $input['review'] ?? '';
        $direction = $input['direction'] ?? '';
        
        if (empty($originalReview)) {
            echo json_encode([
                'success' => false,
                'error' => '書評を入力してください'
            ]);
            exit;
        }
        
        $result = $assistant->improveReview($originalReview, $direction);
        echo json_encode($result);
        break;
        
    case 'generate_tags':
        // タグ生成
        $review = $input['review'] ?? '';
        $bookInfo = [
            'title' => $input['title'] ?? '',
            'author' => $input['author'] ?? ''
        ];
        
        if (empty($review)) {
            echo json_encode([
                'success' => false,
                'error' => '書評を入力してください'
            ]);
            exit;
        }
        
        $result = $assistant->generateTags($review, $bookInfo);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
}