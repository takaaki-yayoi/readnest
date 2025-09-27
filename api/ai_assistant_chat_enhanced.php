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

// RAG用の拡張コンテキスト取得関数
function getEnhancedContext($user_id, $message, $user_context = []) {
    global $g_db;
    $context_parts = [];
    
    // 1. ユーザーの基本読書傾向
    if (!empty($user_context['recent_books'])) {
        $books_text = [];
        foreach ($user_context['recent_books'] as $book) {
            $rating = isset($book['rating']) && $book['rating'] ? "（評価: {$book['rating']}★）" : "";
            $books_text[] = "・{$book['title']} - {$book['author']}{$rating}";
        }
        $context_parts[] = "最近読んだ本:\n" . implode("\n", $books_text);
    }
    
    if (!empty($user_context['favorite_genres'])) {
        $genre_names = array_column($user_context['favorite_genres'], 'tag_name');
        $context_parts[] = "よく読むジャンル: " . implode("、", $genre_names);
    }
    
    // 2. メッセージから関連する本を検索（RAG）
    if (preg_match('/「([^」]+)」/u', $message, $matches)) {
        $book_title = $matches[1];
        
        // 本の詳細情報を取得
        $sql = "SELECT bl.*, 
                (SELECT COUNT(*) FROM b_book_list WHERE title = bl.title) as reader_count
                FROM b_book_list bl 
                WHERE bl.title LIKE ? 
                LIMIT 5";
        $related_books = $g_db->getAll($sql, ['%' . $book_title . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($related_books) && count($related_books) > 0) {
            $books_info = [];
            foreach ($related_books as $book) {
                $info = "・{$book['title']} ({$book['author']})";
                if ($book['rating']) $info .= " - 評価: {$book['rating']}★";
                if ($book['reader_count'] > 1) $info .= " - {$book['reader_count']}人が読書";
                $books_info[] = $info;
            }
            $context_parts[] = "関連する本の情報:\n" . implode("\n", $books_info);
        }
        
        // レビューを検索
        $sql = "SELECT review, rating FROM b_book_list 
                WHERE title LIKE ? AND review IS NOT NULL AND review != ''
                ORDER BY rating DESC 
                LIMIT 3";
        $reviews = $g_db->getAll($sql, ['%' . $book_title . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($reviews) && count($reviews) > 0) {
            $review_texts = [];
            foreach ($reviews as $review) {
                $review_text = mb_substr($review['review'], 0, 100);
                $review_texts[] = "・{$review['rating']}★: {$review_text}...";
            }
            $context_parts[] = "他のユーザーのレビュー:\n" . implode("\n", $review_texts);
        }
    }
    
    // 3. ジャンル検索
    if (preg_match('/(?:小説|ミステリー|SF|ファンタジー|ビジネス|自己啓発|歴史|哲学|科学|技術|アート|料理|旅行)/u', $message, $matches)) {
        $genre = $matches[0];
        
        // ジャンルの人気本を取得
        $sql = "SELECT bl.title, bl.author, AVG(bl.rating) as avg_rating, COUNT(*) as reader_count
                FROM b_book_list bl
                JOIN b_book_tags bt ON bl.id = bt.book_id
                WHERE bt.tag_name LIKE ? AND bl.rating > 0
                GROUP BY bl.title, bl.author
                ORDER BY avg_rating DESC, reader_count DESC
                LIMIT 5";
        $popular_books = $g_db->getAll($sql, ['%' . $genre . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($popular_books) && count($popular_books) > 0) {
            $books_list = [];
            foreach ($popular_books as $book) {
                $avg_rating = round($book['avg_rating'], 1);
                $books_list[] = "・{$book['title']} ({$book['author']}) - 平均評価: {$avg_rating}★ ({$book['reader_count']}人)";
            }
            $context_parts[] = "{$genre}ジャンルの人気本:\n" . implode("\n", $books_list);
        }
    }
    
    // 4. 著者検索
    if (preg_match('/([ぁ-んァ-ヶー一-龠]+(?:\s+[ぁ-んァ-ヶー一-龠]+)?)\s*(?:の|さん|氏|先生)/u', $message, $matches)) {
        $author = $matches[1];
        
        $sql = "SELECT title, rating, COUNT(*) OVER() as total_books
                FROM b_book_list
                WHERE author LIKE ?
                ORDER BY rating DESC
                LIMIT 5";
        $author_books = $g_db->getAll($sql, ['%' . $author . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($author_books) && count($author_books) > 0) {
            $books_list = [];
            foreach ($author_books as $book) {
                $rating_text = $book['rating'] ? " - {$book['rating']}★" : "";
                $books_list[] = "・{$book['title']}{$rating_text}";
            }
            $context_parts[] = "{$author}の作品:\n" . implode("\n", $books_list);
        }
    }
    
    // 5. ユーザーの読書統計
    if (!empty($user_context['reading_stats'])) {
        $stats = $user_context['reading_stats'];
        $stats_text = [];
        if (isset($stats['finished_count'])) $stats_text[] = "読了: {$stats['finished_count']}冊";
        if (isset($stats['reading_count'])) $stats_text[] = "読書中: {$stats['reading_count']}冊";
        if (isset($stats['avg_rating']) && $stats['avg_rating']) {
            $avg = round($stats['avg_rating'], 1);
            $stats_text[] = "平均評価: {$avg}★";
        }
        if (!empty($stats_text)) {
            $context_parts[] = "読書統計: " . implode("、", $stats_text);
        }
    }
    
    return $context_parts;
}

// 拡張コンテキストを取得
$context_parts = getEnhancedContext($mine_user_id, $message, $user_context);

// システムプロンプトを構築
$system_prompt = "あなたはReadNestの読書アシスタントです。ユーザーの読書相談に親切に応じ、適切な本を推薦してください。\n\n";

if (!empty($context_parts)) {
    $system_prompt .= "ユーザーと本のコンテキスト情報:\n" . implode("\n\n", $context_parts) . "\n\n";
}

$system_prompt .= "以下の点に注意して回答してください:\n";
$system_prompt .= "- 具体的な本のタイトルと著者名を含める\n";
$system_prompt .= "- ユーザーの読書傾向を考慮する\n";
$system_prompt .= "- 簡潔で分かりやすい説明を心がける\n";
$system_prompt .= "- 必要に応じて読書のコツやアドバイスも提供する\n";
$system_prompt .= "- 本のタイトルは「」で囲む";

// 会話履歴をメッセージ配列に変換
$messages = [
    ['role' => 'system', 'content' => $system_prompt]
];

// 直近の会話履歴を追加（コンテキスト維持）
if (!empty($user_context['conversation_history'])) {
    foreach (array_slice($user_context['conversation_history'], -5) as $hist) {
        if (isset($hist['role']) && isset($hist['content'])) {
            $role = $hist['role'] === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $hist['content']];
        }
    }
}

// 現在のメッセージを追加
$messages[] = ['role' => 'user', 'content' => $message];

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
        'messages' => $messages,
        'max_tokens' => 800,
        'temperature' => 0.7,
        'presence_penalty' => 0.3,
        'frequency_penalty' => 0.3
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