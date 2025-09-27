<?php
/**
 * AIタグ生成AJAX処理
 * OpenAI APIを使用したインテリジェントなタグ生成
 */

// 必要なファイルを読み込み
require_once('config.php');
require_once('library/session.php');
require_once('library/openai_client.php');

// JSONレスポンスを設定
header('Content-Type: application/json; charset=utf-8');

// セッションチェック
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ログインチェック
if (!isset($_SESSION['AUTH_USER'])) {
    echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
    exit;
}

// POSTデータを取得
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$book_title = $input['book_title'] ?? '';
$book_author = $input['book_author'] ?? '';
$user_review = $input['user_review'] ?? '';

// 入力チェック
if (empty($book_title) && empty($book_author)) {
    echo json_encode(['success' => false, 'error' => 'Book title or author is required']);
    exit;
}

// OpenAI APIを使用したインテリジェントなタグ生成
$tags = [];

try {
    // OpenAIクライアントを取得
    $openaiClient = getOpenAIClient();
    
    if (!$openaiClient) {
        throw new Exception('OpenAI API が利用できません');
    }
    
    // プロンプトを構築
    $systemPrompt = "あなたは読書管理サービスのタグ生成AIです。本のタイトル、著者、レビューから適切なタグを3-5個生成してください。

重要: 必ず以下の形式で回答してください
[\"タグ1\", \"タグ2\", \"タグ3\"]

例: 
- 「生成AIのプロンプトエンジニアリング」→ [\"生成AI\", \"プロンプト\", \"技術書\", \"AI\"]
- 「ノルウェイの森」→ [\"文学\", \"恋愛\", \"青春\"]
- 「ChatGPT APIを活用したPythonプログラミング」→ [\"ChatGPT\", \"Python\", \"API\", \"技術書\"]

ルール:
1. 日本語のタグのみ生成
2. タイトルに含まれる重要なキーワード（生成AI、プロンプト、ChatGPT、Python等）は優先的にタグ化
3. ジャンル（技術書、文学、ビジネスなど）も含める
4. 1-10文字程度のタグ（具体的な技術用語は長くても可）
5. 必ずJSON配列形式で回答
6. 説明文は不要、配列のみ回答

適切なタグ例: ミステリー, 恋愛, SF, ビジネス, 感動, 技術書, 歴史, 青春, 家族, 戦争, 料理, 旅行, 成長, 心理学, 哲学, 芸術, 漫画, エッセイ, 自己啓発, AI, 機械学習, ChatGPT, 生成AI, プロンプト, Python, JavaScript, データサイエンス";

    $userPrompt = "以下の本のタグを生成してください:\n\n";
    $userPrompt .= "タイトル: " . $book_title . "\n";
    if (!empty($book_author)) {
        $userPrompt .= "著者: " . $book_author . "\n";
    }
    if (!empty($user_review) && $user_review !== '本のタイトルと著者から推測してタグを生成してください') {
        $userPrompt .= "レビュー: " . $user_review . "\n";
    }
    
    // OpenAI APIを呼び出し
    $response = $openaiClient->chatWithSystem(
        $systemPrompt,
        $userPrompt,
        'gpt-4o-mini',
        0.3,  // 低い温度で一貫性を保つ
        150   // 短いレスポンスでコスト削減
    );
    
    $generatedText = OpenAIClient::extractText($response);
    
    // テキストをクリーンアップ
    $cleanedText = trim($generatedText);
    $cleanedText = preg_replace('/```json|```/', '', $cleanedText); // コードブロック削除
    $cleanedText = preg_replace('/^[^[\{]*/', '', $cleanedText); // 最初の[または{まで削除
    $cleanedText = preg_replace('/[^}\]]*$/', '', $cleanedText); // 最後の}または]以降削除
    
    // JSONパースを試行
    $decodedTags = json_decode($cleanedText, true);
    
    if (is_array($decodedTags)) {
        $tags = array_slice($decodedTags, 0, 5); // 最大5個に制限
    } else {
        // 方法1: 日本語の単語を抽出（改良版）
        preg_match_all('/[「『"\'"]([^「『"\'"\]]+)[」』"\'"]/', $generatedText, $quotedMatches);
        if (!empty($quotedMatches[1])) {
            $tags = array_slice($quotedMatches[1], 0, 5);
        } else {
            // 方法2: 配列風の文字列から抽出
            preg_match_all('/[\[",\s]+([^\],\s"]+)/', $cleanedText, $arrayMatches);
            if (!empty($arrayMatches[1])) {
                $tags = array_filter(array_map('trim', $arrayMatches[1]));
                $tags = array_slice($tags, 0, 5);
            } else {
                // 方法3: カンマ区切りのテキストを試行
                $possibleTags = preg_split('/[,、\n]/', $generatedText);
                $tags = array_filter(array_map('trim', $possibleTags));
                $tags = array_slice($tags, 0, 5);
            }
        }
    }
    
    // フォールバック処理
    if (empty($tags)) {
        throw new Exception('AIからのタグ生成に失敗しました');
    }
    
} catch (Exception $e) {
    // フォールバック: 簡単なルールベースでタグ生成
    $fallbackTags = [];
    
    // タイトルから重要なキーワードを抽出
    // AI/技術関連
    if (preg_match('/(生成AI|generative\s*ai)/iu', $book_title, $matches)) {
        $fallbackTags[] = '生成AI';
    }
    if (preg_match('/(プロンプト|prompt)/iu', $book_title, $matches)) {
        $fallbackTags[] = 'プロンプト';
    }
    if (preg_match('/(ChatGPT|GPT-4|GPT)/iu', $book_title, $matches)) {
        $fallbackTags[] = 'ChatGPT';
    }
    if (preg_match('/(AI|人工知能|artificial\s*intelligence)/iu', $book_title, $matches)) {
        $fallbackTags[] = 'AI';
    }
    if (preg_match('/(機械学習|machine\s*learning|深層学習|deep\s*learning)/iu', $book_title, $matches)) {
        $fallbackTags[] = '機械学習';
    }
    if (preg_match('/(Python|パイソン)/iu', $book_title, $matches)) {
        $fallbackTags[] = 'Python';
    }
    if (preg_match('/(JavaScript|JS|TypeScript|TS)/iu', $book_title, $matches)) {
        $fallbackTags[] = 'JavaScript';
    }
    if (preg_match('/(データサイエンス|data\s*science)/iu', $book_title, $matches)) {
        $fallbackTags[] = 'データサイエンス';
    }
    
    // ジャンル判定
    if (preg_match('/(ミステリー|推理|探偵|殺人|犯罪|謎|事件)/u', $book_title)) {
        $fallbackTags[] = 'ミステリー';
    }
    if (preg_match('/(恋|愛|ラブ|恋愛|ロマンス)/u', $book_title)) {
        $fallbackTags[] = '恋愛';
    }
    if (preg_match('/(ビジネス|経営|仕事|起業)/u', $book_title)) {
        $fallbackTags[] = 'ビジネス';
    }
    if (preg_match('/(技術|プログラム|IT|システム|エンジニア)/u', $book_title)) {
        $fallbackTags[] = '技術書';
    }
    if (preg_match('/(歴史|時代|戦争)/u', $book_title)) {
        $fallbackTags[] = '歴史';
    }
    
    // 著者判定
    if (strpos($book_author, '村上春樹') !== false) {
        $fallbackTags[] = '文学';
    }
    if (strpos($book_author, '東野圭吾') !== false) {
        $fallbackTags[] = 'ミステリー';
    }
    
    $tags = !empty($fallbackTags) ? $fallbackTags : ['読書'];
}

// 重複を削除してユニークなタグのみを返す
$tags = array_unique($tags);
$tags = array_values($tags);

// 最大5個のタグに制限
$tags = array_slice($tags, 0, 5);

// レスポンスを返す
echo json_encode([
    'success' => true,
    'tags' => $tags,
    'message' => count($tags) . '個のタグを生成しました'
]);
?>