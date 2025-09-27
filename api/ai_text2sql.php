<?php
/**
 * Text2SQL機能 - 自然言語からSQLクエリを生成して実行
 * セキュリティ: ユーザー自身のデータのみアクセス可能
 */

require_once('../modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];

// POSTデータ取得
$input = json_decode(file_get_contents('php://input'), true);
$question = $input['question'] ?? '';

if (empty($question)) {
    echo json_encode(['success' => false, 'error' => 'No question provided']);
    exit;
}

/**
 * データベーススキーマ情報を取得
 */
function getDatabaseSchema() {
    return "
    テーブル構造:
    
    1. b_bookshelf (ユーザーの本棚)
    - bookshelf_id: INT PRIMARY KEY
    - user_id: INT (ユーザーID)
    - asin: VARCHAR(20) (Amazon ASIN)
    - isbn: VARCHAR(20) (ISBN)
    - title: VARCHAR(255) (書籍タイトル)
    - author: VARCHAR(255) (著者名)
    - status: INT (1:読了, 2:読書中, 3:積読, 4:読みたい)
    - rating: INT (1-5の評価, NULLも可)
    - current_page: INT (現在のページ)
    - total_page: INT (総ページ数)
    - finished_date: DATE (読了日)
    - update_date: DATETIME (更新日時)
    - reg_date: DATETIME (登録日時)
    
    2. b_review (レビュー)
    - review_id: INT PRIMARY KEY
    - bookshelf_id: INT (本棚ID)
    - user_id: INT (ユーザーID)
    - review_text: TEXT (レビュー内容)
    - reg_date: DATETIME (投稿日時)
    
    3. b_tag (タグマスタ)
    - tag_id: INT PRIMARY KEY
    - tag_name: VARCHAR(100) (タグ名/ジャンル名)
    
    4. b_bookshelf_tag (本とタグの関連)
    - bookshelf_id: INT
    - tag_id: INT
    - user_id: INT
    
    5. b_book_repository (書籍情報リポジトリ)
    - asin: VARCHAR(20) PRIMARY KEY
    - isbn: VARCHAR(20)
    - title: VARCHAR(255)
    - author: VARCHAR(255)
    - publisher: VARCHAR(255)
    - description: TEXT
    - image_url: VARCHAR(500)
    
    ステータスの意味:
    - 1: 読了
    - 2: 読書中
    - 3: 積読
    - 4: 読みたい
    ";
}

/**
 * SQLクエリを生成（OpenAI APIを使用）
 */
function generateSQL($question, $user_id) {
    $schema = getDatabaseSchema();
    
    $system_prompt = "あなたはMySQLのエキスパートです。
    ユーザーの質問を分析して、適切なSELECT文を生成してください。
    
    重要な制約:
    - 必ずuser_id = {$user_id}の条件を含めること（他のユーザーのデータにアクセスしない）
    - SELECT文のみ生成すること（INSERT, UPDATE, DELETE, DROPなどは禁止）
    - サブクエリは使用可能
    - JOINは必要に応じて使用可能
    - 集計関数（COUNT, SUM, AVG等）は使用可能
    - ORDER BY, GROUP BY, LIMIT句は適切に使用
    - 日付関数は使用可能（NOW(), DATE_SUB等）
    
    {$schema}
    
    回答形式:
    1. SQLクエリのみを返す（説明は不要）
    2. セミコロンは含めない
    3. 実行可能な正しいMySQLクエリを生成する
    
    例:
    質問: 読了した本の数は？
    SQL: SELECT COUNT(*) as count FROM b_bookshelf WHERE user_id = {$user_id} AND status = 1
    
    質問: 評価が4以上の本を新しい順に10冊
    SQL: SELECT title, author, rating, finished_date FROM b_bookshelf WHERE user_id = {$user_id} AND rating >= 4 ORDER BY finished_date DESC LIMIT 10";
    
    $messages = [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => "質問: " . $question]
    ];
    
    try {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'max_tokens' => 500,
            'temperature' => 0.1  // 低温度で正確性を重視
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                $sql = trim($result['choices'][0]['message']['content']);
                // SQLインジェクション対策: 危険なキーワードをチェック
                $dangerous_keywords = ['DROP', 'DELETE', 'INSERT', 'UPDATE', 'ALTER', 'CREATE', 'TRUNCATE', 'REPLACE', 'GRANT', 'REVOKE'];
                foreach ($dangerous_keywords as $keyword) {
                    if (stripos($sql, $keyword) !== false) {
                        return ['success' => false, 'error' => 'Unsafe SQL detected'];
                    }
                }
                
                // user_idの確認
                if (strpos($sql, "user_id = {$user_id}") === false && 
                    strpos($sql, "user_id={$user_id}") === false) {
                    // user_id条件が含まれていない場合は追加
                    if (stripos($sql, 'WHERE') !== false) {
                        $sql = str_ireplace('WHERE', "WHERE user_id = {$user_id} AND ", $sql);
                    } else if (stripos($sql, 'FROM b_bookshelf') !== false) {
                        $sql = str_ireplace('FROM b_bookshelf', "FROM b_bookshelf WHERE user_id = {$user_id}", $sql);
                    }
                }
                
                return ['success' => true, 'sql' => $sql];
            }
        }
        
        return ['success' => false, 'error' => 'Failed to generate SQL'];
        
    } catch (Exception $e) {
        error_log('Text2SQL Error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'API error'];
    }
}

/**
 * SQLを実行して結果を取得
 */
function executeSQL($sql, $user_id) {
    global $g_db;
    
    try {
        // 最終的なセキュリティチェック
        if (!preg_match('/^SELECT/i', $sql)) {
            return ['success' => false, 'error' => 'Only SELECT queries allowed'];
        }
        
        // クエリ実行（PEAR DB形式）
        $results = $g_db->getAll($sql, null, DB_FETCHMODE_ASSOC);
        
        if (DB::isError($results)) {
            error_log('SQL Execution Error: ' . $results->getMessage());
            return [
                'success' => false,
                'error' => 'クエリの実行に失敗しました',
                'sql' => $sql
            ];
        }
        
        // 結果を整形
        if (empty($results)) {
            return [
                'success' => true,
                'data' => [],
                'message' => 'データが見つかりませんでした'
            ];
        }
        
        return [
            'success' => true,
            'data' => $results,
            'count' => count($results),
            'sql' => $sql  // デバッグ用（本番環境では削除可能）
        ];
        
    } catch (Exception $e) {
        error_log('SQL Execution Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'クエリの実行に失敗しました',
            'sql' => $sql  // デバッグ用
        ];
    }
}

// メイン処理
$sql_result = generateSQL($question, $mine_user_id);

if (!$sql_result['success']) {
    echo json_encode($sql_result);
    exit;
}

$execution_result = executeSQL($sql_result['sql'], $mine_user_id);

// 結果を自然言語で説明するための処理
if ($execution_result['success'] && !empty($execution_result['data'])) {
    $explanation = generateExplanation($question, $execution_result['data']);
    $execution_result['explanation'] = $explanation;
}

// デバッグ情報を含める（SQLクエリを表示）
$execution_result['debug'] = [
    'mode' => 'text2sql',
    'query' => $sql_result['sql']
];

echo json_encode($execution_result);

/**
 * 結果を自然言語で説明
 */
function generateExplanation($question, $data) {
    // 簡単な説明を生成
    $count = count($data);
    
    if ($count === 0) {
        return "該当するデータが見つかりませんでした。";
    }
    
    if ($count === 1 && isset($data[0]['count'])) {
        return "結果: " . $data[0]['count'] . "件";
    }
    
    if ($count === 1 && isset($data[0]['avg_rating'])) {
        return "平均評価: " . number_format($data[0]['avg_rating'], 1) . "点";
    }
    
    return "{$count}件のデータが見つかりました。";
}
?>