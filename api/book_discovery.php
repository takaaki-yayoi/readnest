<?php
/**
 * 書籍ディスカバリーAPI
 *
 * ユーザーの読書プロファイルとLLMの知識を組み合わせて
 * 対話型の書籍推薦を行う。
 *
 * POST /api/book_discovery.php
 * Body: { "query": "泣ける小説が読みたい" }
 */

// エラー出力制御
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// OpenAI API呼び出しが複数あるため実行時間を延長
set_time_limit(120);

ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once(dirname(__DIR__) . '/modern_config.php');
    require_once(dirname(__DIR__) . '/library/book_fuzzy_matcher.php');
    require_once(dirname(__DIR__) . '/library/csrf.php');
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'システムエラー']);
    exit;
}

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'ログインが必要です']);
    exit;
}

// CSRF検証
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verifyCSRFToken($csrf_token)) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '不正なリクエストです']);
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];

// POSTデータ取得
$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? '');

if (empty($query)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => '入力が必要です']);
    exit;
}

// OpenAI APIキーチェック
if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'AI機能は現在利用できません']);
    exit;
}

global $g_db;

// ============================================================
// Step 1: データ収集
// ============================================================

// 高評価本+レビュー（rating >= 4）
$high_rated_sql = "SELECT bl.book_id, bl.name,
        COALESCE(bl.author, br.author, '') as author,
        bl.rating, bl.memo as review, bl.finished_date, bl.total_page
        FROM b_book_list bl
        LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
        WHERE bl.user_id = ? AND bl.rating >= 4
        AND bl.memo IS NOT NULL AND bl.memo != ''
        ORDER BY bl.rating DESC, bl.finished_date DESC
        LIMIT 30";
$high_rated_books = $g_db->getAll($high_rated_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
if (DB::isError($high_rated_books)) {
    $high_rated_books = [];
}

// レビューなしの高評価本も追加（レビューありの本が少ない場合）
if (count($high_rated_books) < 10) {
    $additional_sql = "SELECT bl.book_id, bl.name,
            COALESCE(bl.author, br.author, '') as author,
            bl.rating, '' as review, bl.finished_date, bl.total_page
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ? AND bl.rating >= 4
            AND (bl.memo IS NULL OR bl.memo = '')
            ORDER BY bl.rating DESC, bl.finished_date DESC
            LIMIT ?";
    $additional = $g_db->getAll($additional_sql, [$mine_user_id, 30 - count($high_rated_books)], DB_FETCHMODE_ASSOC);
    if (!DB::isError($additional)) {
        $high_rated_books = array_merge($high_rated_books, $additional);
    }
}

if (empty($high_rated_books)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => '推薦を生成するには、本を評価してください。★4以上の評価がある本が必要です。'
    ]);
    exit;
}

// よく読むジャンル
$genres_sql = "SELECT tag_name, COUNT(*) as count
        FROM b_book_tags
        WHERE user_id = ?
        GROUP BY tag_name
        ORDER BY count DESC
        LIMIT 15";
$genres = $g_db->getAll($genres_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
if (DB::isError($genres)) {
    $genres = [];
}

// 読書統計
$stats_sql = "SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN status IN (3, 4) THEN 1 END) as finished,
        COUNT(CASE WHEN rating >= 4 THEN 1 END) as highly_rated,
        AVG(CASE WHEN rating > 0 THEN rating END) as avg_rating
        FROM b_book_list
        WHERE user_id = ?";
$stats = $g_db->getRow($stats_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
if (DB::isError($stats)) {
    $stats = ['total' => 0, 'finished' => 0, 'highly_rated' => 0, 'avg_rating' => 0];
}

// ============================================================
// Step 2: プロファイル生成
// ============================================================

$books_for_profile = [];
foreach ($high_rated_books as $book) {
    $entry = "「{$book['name']}」({$book['author']}) ⭐{$book['rating']}";
    if (!empty($book['review'])) {
        // 短い手書きレビューを重視（200字以下）
        $review_text = mb_substr($book['review'], 0, 200, 'UTF-8');
        $entry .= " レビュー: {$review_text}";
    }
    $books_for_profile[] = $entry;
}

$genre_text = '';
if (!empty($genres)) {
    $genre_items = [];
    foreach ($genres as $g) {
        $genre_items[] = "{$g['tag_name']}({$g['count']}冊)";
    }
    $genre_text = "よく読むジャンル: " . implode(', ', $genre_items);
}

$profile_prompt = "あなたは読書プロフィール分析のエキスパートです。
以下のユーザーの高評価本（★4以上）とレビュー、ジャンル分布を分析し、読書プロフィールをJSON形式で出力してください。

【重要ルール】
- 短い手書きレビュー（具体的な感想が書かれたもの）を長文の定型的なレビューより重視すること
- ジャンル分布と読書冊数も考慮すること

【読書統計】
総蔵書: {$stats['total']}冊、読了: {$stats['finished']}冊、高評価: {$stats['highly_rated']}冊、平均評価: " . round((float)($stats['avg_rating'] ?? 0), 1) . "
{$genre_text}

【高評価本とレビュー】
" . implode("\n", $books_for_profile) . "

以下のJSON形式のみ出力してください（説明文不要）:
{
  \"summary\": \"2-3文の読書傾向の要約\",
  \"preferred_themes\": [\"好きなテーマ1\", \"テーマ2\", ...],
  \"preferred_styles\": [\"好きな文体/スタイル1\", ...],
  \"dislikes\": [\"苦手そうなパターン\"],
  \"favorite_authors_with_reasons\": {\"著者名\": \"好きな理由\"},
  \"blind_spots\": [\"まだ読んでいないジャンル\"],
  \"languages\": [\"ja\", \"en\"]
}";

$profile_result = callOpenAI($profile_prompt, null, 0.3, 800);
if (!$profile_result['success']) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'プロファイル生成に失敗しました。再度お試しください。(' . ($profile_result['error_detail'] ?? 'unknown') . ')']);
    exit;
}

$profile = json_decode($profile_result['content'], true);
if (!$profile) {
    // JSONパース失敗。マークダウンコードブロックで囲まれている場合を処理
    $content = $profile_result['content'];
    if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $content, $matches)) {
        $profile = json_decode($matches[1], true);
    }
    if (!$profile) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'プロファイルの解析に失敗しました。再度お試しください。']);
        exit;
    }
}

// ============================================================
// Step 3: 候補生成
// ============================================================

$read_books_text = [];
foreach ($high_rated_books as $book) {
    $read_books_text[] = "「{$book['name']}」({$book['author']}) ⭐{$book['rating']}";
}

$candidates_prompt = "あなたは世界中の書籍に精通する書籍推薦のエキスパートです。
以下のユーザープロフィールとリクエストに基づいて、ユーザーがまだ読んでいない可能性が高い本を15冊推薦してください。

【ユーザープロフィール】
" . json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

【ユーザーのリクエスト】
{$query}

【ユーザーが既に読んで高評価をつけた本】
" . implode("\n", $read_books_text) . "

【推薦ルール】
1. 上記の既読本とは異なる本を推薦すること（同じ本を推薦しない）
2. 同じ著者からの推薦は最大1冊に制限し、多様性を確保すること
3. ユーザーの好みにぴったり合う本（8割）と、意外だが刺さりそうな本（2割）を混ぜること
4. 各推薦には、ユーザーの既読本のどれと繋がりがあるかを明示すること
5. 日本語の本と英語の本を適切に混ぜること（ユーザーの言語プロフィールに基づく）
6. 実在する書籍のみを推薦すること

以下のJSON配列のみ出力してください（説明文不要）:
[
  {
    \"title\": \"本のタイトル\",
    \"author\": \"著者名\",
    \"language\": \"ja または en\",
    \"connection_book\": \"接続元の既読本タイトル\",
    \"connection_author\": \"接続元の著者名\",
    \"reasoning\": \"なぜこの本がおすすめなのか（既読本との具体的な接点を含む、2-3文）\",
    \"match_themes\": [\"マッチするテーマ1\", \"テーマ2\"],
    \"surprise_factor\": \"予想外だが刺さる理由（1文）\",
    \"genre\": \"ジャンル\"
  }
]";

$candidates_result = callOpenAI($candidates_prompt, null, 0.8, 4000);
if (!$candidates_result['success']) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => '推薦候補の生成に失敗しました。再度お試しください。(' . ($candidates_result['error_detail'] ?? 'unknown') . ')',
        'profile' => $profile
    ]);
    exit;
}

$candidates = json_decode($candidates_result['content'], true);
if (!$candidates) {
    $content = $candidates_result['content'];
    if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $content, $matches)) {
        $candidates = json_decode($matches[1], true);
    }
    if (!$candidates || !is_array($candidates)) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => '推薦候補の解析に失敗しました。再度お試しください。',
            'profile' => $profile
        ]);
        exit;
    }
}

// ============================================================
// Step 4: 既読フィルタ
// ============================================================

$filtered = [];
foreach ($candidates as $candidate) {
    $title = $candidate['title'] ?? '';
    $author = $candidate['author'] ?? null;

    if (empty($title)) continue;

    $match = findMatchingBook($title, $author, $mine_user_id);
    if ($match === null) {
        // 未読 → 推薦リストに追加
        $candidate['is_read'] = false;
        $filtered[] = $candidate;
    }
    // 既読は除外
}

// 上位10件を残す
$filtered = array_slice($filtered, 0, 10);

if (empty($filtered)) {
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'profile' => $profile,
        'recommendations' => [],
        'query' => $query,
        'message' => '推薦候補がすべて既読でした。別の気分で再度お試しください。'
    ]);
    exit;
}

// ============================================================
// Step 5: 推薦カード整形
// ============================================================

// 高評価本のマップ（接続元情報の補完用）
$read_books_map = [];
foreach ($high_rated_books as $book) {
    $read_books_map[$book['name']] = $book;
}

$recommendations = [];
foreach ($filtered as $candidate) {
    // 接続元の本の評価を取得
    $connection_rating = null;
    $connection_book = $candidate['connection_book'] ?? '';
    if (!empty($connection_book)) {
        // 完全一致で探す
        if (isset($read_books_map[$connection_book])) {
            $connection_rating = (int)$read_books_map[$connection_book]['rating'];
        } else {
            // あいまい検索でフォールバック
            foreach ($read_books_map as $name => $book) {
                if (mb_strpos($name, $connection_book) !== false || mb_strpos($connection_book, $name) !== false) {
                    $connection_rating = (int)$book['rating'];
                    $connection_book = $name;
                    break;
                }
            }
        }
    }

    // マッチ要素をスコア付きで整形
    $match_factors = [];
    $themes = $candidate['match_themes'] ?? [];
    $profile_themes = $profile['preferred_themes'] ?? [];
    foreach ($themes as $i => $theme) {
        // スコアは降順で設定（最初のテーマが最もマッチ）
        $strength = max(0.5, 1.0 - ($i * 0.1));
        $match_factors[] = [
            'label' => $theme,
            'strength' => round($strength, 2)
        ];
    }

    $card = [
        'title' => $candidate['title'] ?? '',
        'author' => $candidate['author'] ?? '',
        'language' => $candidate['language'] ?? 'ja',
        'genre' => $candidate['genre'] ?? '',
        'connection' => [
            'from_book' => $connection_book,
            'from_rating' => $connection_rating,
            'from_author' => $candidate['connection_author'] ?? '',
            'reasoning' => $candidate['reasoning'] ?? ''
        ],
        'match_factors' => $match_factors,
        'surprise_factor' => $candidate['surprise_factor'] ?? '',
        'tags' => $themes
    ];

    $recommendations[] = $card;
}

// 出力バッファをクリア
ob_end_clean();

echo json_encode([
    'success' => true,
    'profile' => $profile,
    'recommendations' => $recommendations,
    'query' => $query,
    'stats' => [
        'total_candidates' => count($candidates),
        'filtered_out' => count($candidates) - count($filtered),
        'final_count' => count($recommendations)
    ]
], JSON_UNESCAPED_UNICODE);


// ============================================================
// ヘルパー関数
// ============================================================

/**
 * OpenAI Chat Completions APIを呼び出す
 *
 * @param string $system_prompt システムプロンプト
 * @param string|null $user_message ユーザーメッセージ（nullの場合system_promptのみ使用）
 * @param float $temperature
 * @param int $max_tokens
 * @return array ['success' => bool, 'content' => string]
 */
function callOpenAI(string $system_prompt, ?string $user_message = null, float $temperature = 0.5, int $max_tokens = 1500): array {
    $messages = [
        ['role' => 'system', 'content' => $system_prompt]
    ];

    if ($user_message !== null) {
        $messages[] = ['role' => 'user', 'content' => $user_message];
    } else {
        // system_promptにすべて含まれる場合、userメッセージとしてプロンプトを送る
        $messages = [
            ['role' => 'user', 'content' => $system_prompt]
        ];
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'max_tokens' => $max_tokens,
        'temperature' => $temperature
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("book_discovery OpenAI curl error: " . $curl_error);
        return ['success' => false, 'content' => '', 'error_detail' => 'curl: ' . $curl_error];
    }

    if ($http_code !== 200) {
        error_log("book_discovery OpenAI HTTP error: {$http_code} - {$response}");
        return ['success' => false, 'content' => '', 'error_detail' => "HTTP {$http_code}"];
    }

    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        error_log("book_discovery OpenAI unexpected response: " . $response);
        return ['success' => false, 'content' => '', 'error_detail' => 'unexpected response format'];
    }

    return [
        'success' => true,
        'content' => $result['choices'][0]['message']['content']
    ];
}
