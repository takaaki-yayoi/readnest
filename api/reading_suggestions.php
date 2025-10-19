<?php
/**
 * AI読書提案API
 * ユーザーの読書履歴を分析してパーソナライズされた提案を生成
 */

// エラー出力を無効化
error_reporting(0);
ini_set('display_errors', 0);

// 出力バッファリングを開始
ob_start();

// JSONヘッダーを最初に設定
header('Content-Type: application/json; charset=utf-8');

try {
    // ファイルパスを絶対パスで指定
    $base_dir = dirname(__FILE__) . '/../';
    require_once $base_dir . 'config.php';
    require_once $base_dir . 'library/database.php';
    
    // セッション確認
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $g_login_id = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : null;

    if (!$g_login_id) {
        ob_clean();
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // ユーザーID取得
    $user_id = isset($_GET['user']) ? $_GET['user'] : $g_login_id;

    // 他人のデータの場合は公開設定を確認
    if ($user_id != $g_login_id) {
        $target_user = getUserInformation($user_id);
        if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
            ob_clean();
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
    }

    // データベース接続
    global $g_db;
    
    // データベース接続確認
    if (!isset($g_db) || !$g_db) {
        throw new Exception('Database connection failed');
    }
    
    // ユーザーの読書データを詳細分析
    
    // 1. ジャンル別読書傾向分析（著者ベース）
    $genre_sql = "SELECT 
        CASE 
            WHEN author LIKE '%夏目%' OR author LIKE '%芥川%' OR author LIKE '%太宰%' THEN '文学・小説'
            WHEN author LIKE '%ドラッカー%' OR name LIKE '%経営%' OR name LIKE '%マネジメント%' THEN 'ビジネス・経済'
            WHEN name LIKE '%プログラミング%' OR name LIKE '%技術%' OR name LIKE '%IT%' THEN '技術・IT'
            WHEN name LIKE '%歴史%' OR author LIKE '%司馬%' THEN '歴史'
            WHEN name LIKE '%科学%' OR name LIKE '%物理%' THEN '科学'
            WHEN name LIKE '%心理%' OR name LIKE '%哲学%' THEN '心理・哲学'
            WHEN name LIKE '%健康%' OR name LIKE '%医学%' THEN '健康・医学'
            WHEN name LIKE '%ミステリ%' OR name LIKE '%推理%' OR author LIKE '%東野%' THEN 'ミステリー'
            WHEN name LIKE '%SF%' OR name LIKE '%ファンタジー%' THEN 'SF・ファンタジー'
            WHEN name LIKE '%自己啓発%' THEN '自己啓発'
            ELSE '一般書'
        END as genre,
        COUNT(*) as count,
        AVG(rating) as avg_rating,
        MAX(update_date) as last_read_date
    FROM b_book_list
    WHERE user_id = ? AND status = 3
    GROUP BY genre
    ORDER BY count DESC";
    
    $genre_data = $g_db->getAll($genre_sql, [$user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($genre_data)) {
        throw new Exception('Genre data query failed: ' . $genre_data->getMessage());
    }
    
    // 2. 最近の読書活動分析
    $recent_sql = "SELECT 
        name as title, author,
        rating, update_date,
        DATEDIFF(NOW(), update_date) as days_ago
    FROM b_book_list
    WHERE user_id = ? AND status = 3
    ORDER BY update_date DESC
    LIMIT 10";
    
    $recent_books = $g_db->getAll($recent_sql, [$user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($recent_books)) {
        $recent_books = []; // エラーの場合は空配列
    }
    
    // 3. 評価の高い本の分析
    $high_rated_sql = "SELECT 
        author,
        COUNT(*) as count
    FROM b_book_list
    WHERE user_id = ? AND status = 3 AND rating >= 4
    GROUP BY author
    ORDER BY count DESC
    LIMIT 5";
    
    $high_rated_data = $g_db->getAll($high_rated_sql, [$user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($high_rated_data)) {
        $high_rated_data = [];
    }
    
    // 4. 読書ペース分析
    $pace_sql = "SELECT 
        DATE_FORMAT(update_date, '%Y-%m') as month,
        COUNT(*) as books_count
    FROM b_book_list
    WHERE user_id = ? AND status = 3 
    AND update_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month DESC";
    
    $pace_data = $g_db->getAll($pace_sql, [$user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($pace_data)) {
        $pace_data = [];
    }
    
    // 5. 未読・積読本の分析
    $unread_sql = "SELECT 
        COUNT(*) as unread_count,
        CASE 
            WHEN name LIKE '%小説%' OR name LIKE '%文学%' THEN '文学・小説'
            WHEN name LIKE '%ビジネス%' OR name LIKE '%経済%' OR name LIKE '%経営%' THEN 'ビジネス・経済'
            WHEN name LIKE '%技術%' OR name LIKE '%プログラミング%' OR name LIKE '%IT%' THEN '技術・IT'
            ELSE 'その他'
        END as genre
    FROM b_book_list
    WHERE user_id = ? AND status IN (1, 2)
    GROUP BY genre
    ORDER BY unread_count DESC";
    
    $unread_data = $g_db->getAll($unread_sql, [$user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($unread_data)) {
        $unread_data = [];
    }
    
    // データが不足している場合はフォールバック提案を返す
    if (empty($genre_data) && empty($recent_books) && empty($high_rated_data)) {
        ob_clean();
        echo json_encode([
            'suggestions' => [
                [
                    'type' => 'genre_exploration',
                    'title' => '読書を始めてみませんか？',
                    'description' => '新しい本を探して読書の世界を広げましょう。',
                    'action_text' => 'おすすめ本を探す',
                    'action_url' => '/add_book.php'
                ]
            ],
            'analysis' => [
                'total_genres' => 0,
                'most_read_genre' => null,
                'recent_activity' => 0,
                'reading_pace' => 0,
                'unread_books' => 0
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // AIプロンプトを構築
    $systemPrompt = "あなたは読書アドバイザーです。ユーザーの読書履歴を分析して、パーソナライズされた読書提案を3つ生成してください。

提案は以下の形式のJSONで返してください：
{
  \"suggestions\": [
    {
      \"type\": \"genre_exploration\", // genre_exploration, author_deep_dive, reading_pace, unread_focus のいずれか
      \"title\": \"提案のタイトル\",
      \"description\": \"具体的な説明（100文字以内）\",
      \"action_text\": \"ボタンのテキスト\",
      \"action_url\": \"/add_book.php?keyword=推奨キーワード\"
    }
  ]
}

提案タイプの説明：
- genre_exploration: 新しいジャンルの探索
- author_deep_dive: 好きな著者の他作品
- reading_pace: 読書ペースの改善
- unread_focus: 積読本の消化";

    $userPrompt = "読書履歴分析結果：

【ジャンル別読書傾向】
" . json_encode($genre_data, JSON_UNESCAPED_UNICODE) . "

【最近読んだ本（10冊）】
" . json_encode($recent_books, JSON_UNESCAPED_UNICODE) . "

【高評価本の傾向】
" . json_encode($high_rated_data, JSON_UNESCAPED_UNICODE) . "

【月別読書ペース（直近6ヶ月）】
" . json_encode($pace_data, JSON_UNESCAPED_UNICODE) . "

【未読本の状況】
" . json_encode($unread_data, JSON_UNESCAPED_UNICODE) . "

この情報に基づいて、ユーザーにとって価値のある読書提案を3つ生成してください。";

    // OpenAI APIを使用して提案を生成
    try {
        // OpenAIClientクラスの存在確認
        $openai_class_file = dirname(__FILE__) . '/../library/openai_client.php';
        if (file_exists($openai_class_file)) {
            require_once $openai_class_file;
        }
        
        if (!class_exists('OpenAIClient')) {
            throw new Exception('OpenAI client not available');
        }
        
        // OpenAI APIキーが必要
        if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $openaiClient = new OpenAIClient(OPENAI_API_KEY);
        $response = $openaiClient->chatWithSystem(
            $systemPrompt,
            $userPrompt,
            'gpt-4o-mini',
            0.7,  // 創造性を重視
            1000  // 十分な長さ
        );
    } catch (Exception $aiError) {
        // AI APIが利用できない場合はルールベースの提案を生成
        $suggestions = generateFallbackSuggestions($genre_data, $recent_books, $high_rated_data, $unread_data);
        
        ob_clean(); // 出力バッファをクリア
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'suggestions' => $suggestions,
            'analysis' => [
                'total_genres' => count($genre_data),
                'most_read_genre' => count($genre_data) > 0 ? $genre_data[0]['genre'] : null,
                'recent_activity' => count($recent_books),
                'reading_pace' => count($pace_data),
                'unread_books' => array_sum(array_column($unread_data, 'unread_count'))
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // レスポンスから内容を取得
    if (!isset($response['choices'][0]['message']['content'])) {
        throw new Exception('Invalid AI response format');
    }
    
    $cleanResponse = trim($response['choices'][0]['message']['content']);
    
    // JSONブロックをクリーンアップ
    if (strpos($cleanResponse, '```json') !== false) {
        $cleanResponse = preg_replace('/```json\s*/', '', $cleanResponse);
        $cleanResponse = preg_replace('/\s*```/', '', $cleanResponse);
    }
    
    $aiSuggestions = json_decode($cleanResponse, true);
    
    if (!$aiSuggestions || !isset($aiSuggestions['suggestions'])) {
        throw new Exception('AI response parsing failed');
    }
    
    // レスポンスを返す
    ob_clean(); // 出力バッファをクリア
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'suggestions' => $aiSuggestions['suggestions'],
        'analysis' => [
            'total_genres' => count($genre_data),
            'most_read_genre' => count($genre_data) > 0 ? $genre_data[0]['genre'] : null,
            'recent_activity' => count($recent_books),
            'reading_pace' => count($pace_data),
            'unread_books' => array_sum(array_column($unread_data, 'unread_count'))
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_clean(); // 出力バッファをクリア
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Suggestion generation failed: ' . $e->getMessage(),
        'fallback_suggestions' => [
            [
                'type' => 'genre_exploration',
                'title' => '新しいジャンルを探索してみませんか？',
                'description' => '読書の幅を広げて新たな発見をしましょう。',
                'action_text' => 'おすすめ本を探す',
                'action_url' => '/add_book.php'
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * ルールベースのフォールバック提案を生成
 */
function generateFallbackSuggestions($genre_data, $recent_books, $high_rated_data, $unread_data) {
    $suggestions = [];
    
    // 1. 最も読んでいるジャンルがある場合
    if (!empty($genre_data)) {
        $topGenre = $genre_data[0]['genre'];
        $suggestions[] = [
            'type' => 'genre_exploration',
            'title' => $topGenre . 'の新しい作品を探してみませんか？',
            'description' => 'よく読まれているジャンルの新作や話題作をチェックしてみましょう。',
            'action_text' => $topGenre . 'の本を探す',
            'action_url' => '/add_book.php?keyword=' . urlencode($topGenre)
        ];
    }
    
    // 2. 高評価の著者がいる場合
    if (!empty($high_rated_data)) {
        $topAuthor = $high_rated_data[0]['author'];
        $suggestions[] = [
            'type' => 'author_deep_dive',
            'title' => $topAuthor . 'さんの他の作品はいかがですか？',
            'description' => 'お気に入りの著者の未読作品を発見しましょう。',
            'action_text' => $topAuthor . 'の本を探す',
            'action_url' => '/add_book.php?keyword=' . urlencode($topAuthor)
        ];
    }
    
    // 3. 積読本がある場合
    if (!empty($unread_data)) {
        $suggestions[] = [
            'type' => 'unread_focus',
            'title' => '積読本を読み始めませんか？',
            'description' => '本棚にある未読の本から選んで読書を再開しましょう。',
            'action_text' => '未読本を確認',
            'action_url' => '/bookshelf.php?status=1,2'
        ];
    }
    
    // デフォルト提案（データが不足している場合）
    if (empty($suggestions)) {
        $suggestions[] = [
            'type' => 'genre_exploration',
            'title' => '新しい読書の冒険を始めませんか？',
            'description' => '話題の本やおすすめ作品から気になる一冊を見つけましょう。',
            'action_text' => 'おすすめ本を探す',
            'action_url' => '/add_book.php'
        ];
    }
    
    return array_slice($suggestions, 0, 3); // 最大3つまで
}
?>