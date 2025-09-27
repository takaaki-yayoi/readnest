<?php
require_once('../modern_config.php');

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
$user_context = isset($input['user_context']) ? $input['user_context'] : [];

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

// ユーザーの読書傾向を取得
$context = [];

// ユーザーコンテキストが送信されている場合は優先的に使用
if (!empty($user_context)) {
    if (isset($user_context['recent_books']) && !empty($user_context['recent_books'])) {
        $books_text = [];
        foreach ($user_context['recent_books'] as $book) {
            $rating = isset($book['rating']) && $book['rating'] ? "（評価: {$book['rating']}★）" : "";
            $books_text[] = "・{$book['title']} - {$book['author']}{$rating}";
        }
        $context[] = "最近読んだ本:\n" . implode("\n", $books_text);
    }
    
    if (isset($user_context['favorite_genres']) && !empty($user_context['favorite_genres'])) {
        $genre_names = array_column($user_context['favorite_genres'], 'tag_name');
        $context[] = "よく読むジャンル: " . implode("、", $genre_names);
    }
    
    if (isset($user_context['reading_stats']) && !empty($user_context['reading_stats'])) {
        $stats = $user_context['reading_stats'];
        $stats_text = [];
        if (isset($stats['finished_count'])) $stats_text[] = "読了: {$stats['finished_count']}冊";
        if (isset($stats['reading_count'])) $stats_text[] = "読書中: {$stats['reading_count']}冊";
        if (isset($stats['avg_rating']) && $stats['avg_rating']) {
            $avg = round($stats['avg_rating'], 1);
            $stats_text[] = "平均評価: {$avg}★";
        }
        if (!empty($stats_text)) {
            $context[] = "読書統計: " . implode("、", $stats_text);
        }
    }
}

// DBからも取得（フォールバック用）
if (empty($context)) {
    // 最近読んだ本
$sql = "SELECT bl.name as title, bl.author, bl.rating 
        FROM b_book_list bl 
        WHERE bl.user_id = ? AND bl.status IN (2, 3, 4)
        ORDER BY bl.update_date DESC 
        LIMIT 5";
$recent_books = $g_db->getAll($sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
if (!DB::isError($recent_books) && count($recent_books) > 0) {
    $books_text = [];
    foreach ($recent_books as $book) {
        $rating = $book['rating'] ? "（評価: {$book['rating']}★）" : "";
        $books_text[] = "・{$book['title']} - {$book['author']}{$rating}";
    }
        $context[] = "最近読んだ本:\n" . implode("\n", $books_text);
    }

    // よく読むジャンル
    $sql = "SELECT bt.tag_name, COUNT(*) as count 
            FROM b_book_tags bt 
            WHERE bt.user_id = ? 
            GROUP BY bt.tag_name 
            ORDER BY count DESC 
            LIMIT 3";
    $genres = $g_db->getAll($sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
    if (!DB::isError($genres) && count($genres) > 0) {
        $genre_names = array_column($genres, 'tag_name');
        $context[] = "よく読むジャンル: " . implode("、", $genre_names);
    }
}

// システムプロンプトを構築
$system_prompt = "あなたはReadNestの読書アシスタントです。ユーザーの読書相談に親切に応じ、適切な本を推薦してください。\n\n";
if (!empty($context)) {
    $system_prompt .= "ユーザーの読書傾向:\n" . implode("\n\n", $context) . "\n\n";
}
$system_prompt .= "回答は簡潔で具体的にし、必要に応じて本のタイトルと著者名を含めてください。";

try {
    // OpenAI API呼び出し
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $message]
        ],
        'max_tokens' => 500,
        'temperature' => 0.7
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            echo json_encode([
                'success' => true,
                'response' => $result['choices'][0]['message']['content']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'response' => '申し訳ございません。適切な回答を生成できませんでした。'
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'response' => '申し訳ございません。現在混雑しています。しばらくしてからお試しください。'
        ]);
    }
} catch (Exception $e) {
    error_log('AI Assistant Error: ' . $e->getMessage());
    echo json_encode([
        'success' => true,
        'response' => '申し訳ございません。エラーが発生しました。'
    ]);
}
?>