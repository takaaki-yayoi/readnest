<?php
/**
 * Text2SQL共通関数ライブラリ
 */

/**
 * データベーススキーマ情報を取得
 */
function getDatabaseSchema() {
    return "
    テーブル構造:
    
    1. b_book_list (ユーザーの本棚)
    - book_id: INT PRIMARY KEY
    - user_id: INT (ユーザーID)
    - amazon_id: VARCHAR(100) (Amazon ASIN)
    - isbn: VARCHAR(20) (ISBN)
    - name: VARCHAR(255) (書籍タイトル)
    - author: VARCHAR(255) (著者名)
    - status: INT (1:積読, 2:読書中, 3:読了, 4:既読)
    - rating: INT (1-5の評価, NULLも可)
    - current_page: INT (現在のページ)
    - total_page: INT (総ページ数)
    - finished_date: DATE (読了日)
    - update_date: DATETIME (更新日時)
    - reg_date: DATETIME (登録日時)
    - image_url: VARCHAR(500) (画像URL)
    
    2. b_book_review (レビュー)
    - review_id: INT PRIMARY KEY
    - book_id: INT (本のID)
    - user_id: INT (ユーザーID)
    - review_text: TEXT (レビュー内容)
    - reg_date: DATETIME (投稿日時)
    
    3. b_tag (タグマスタ)
    - tag_id: INT PRIMARY KEY
    - tag_name: VARCHAR(100) (タグ名/ジャンル名)
    
    4. b_book_tags (本とタグの関連)
    - book_id: INT
    - tag_id: INT
    - user_id: INT
    - tag_name: VARCHAR(100)
    
    5. b_book_repository (書籍情報リポジトリ)
    - asin: VARCHAR(20) PRIMARY KEY
    - isbn: VARCHAR(20)
    - title: VARCHAR(255)
    - author: VARCHAR(255)
    - publisher: VARCHAR(255)
    - description: TEXT
    - image_url: VARCHAR(500)
    
    ステータスの意味:
    - 1: 積読
    - 2: 読書中
    - 3: 読了
    - 4: 既読
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
    SQL: SELECT COUNT(*) as count FROM b_book_list WHERE user_id = {$user_id} AND status = 3
    
    質問: 評価が4以上の本を新しい順に10冊
    SQL: SELECT name as title, author, rating, finished_date FROM b_book_list WHERE user_id = {$user_id} AND rating >= 4 ORDER BY finished_date DESC LIMIT 10
    
    質問: 今年読了した本の一覧
    SQL: SELECT name as title, author, rating, finished_date FROM b_book_list WHERE user_id = {$user_id} AND status = 3 AND YEAR(finished_date) = YEAR(NOW()) ORDER BY finished_date DESC
    
    注意: 
    - 「今年」は YEAR(finished_date) = YEAR(NOW()) を使用
    - 「今月」は MONTH(finished_date) = MONTH(NOW()) AND YEAR(finished_date) = YEAR(NOW()) を使用
    - 「読了」は必ず status = 3 の条件を含める";
    
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
                    } else if (stripos($sql, 'FROM b_book_list') !== false) {
                        $sql = str_ireplace('FROM b_book_list', "FROM b_book_list WHERE user_id = {$user_id}", $sql);
                    }
                }
                
                // LIMIT句の追加（安全のため最大100件に制限）
                if (stripos($sql, 'LIMIT') === false && stripos($sql, 'COUNT') === false && stripos($sql, 'AVG') === false && stripos($sql, 'SUM') === false) {
                    $sql .= ' LIMIT 100';
                } else if (preg_match('/LIMIT\s+(\d+)/i', $sql, $matches)) {
                    $limit = intval($matches[1]);
                    if ($limit > 100) {
                        $sql = preg_replace('/LIMIT\s+\d+/i', 'LIMIT 100', $sql);
                    }
                }
                
                return ['success' => true, 'sql' => $sql];
            }
        }
        
        return ['success' => false, 'error' => 'Failed to generate SQL'];
        
    } catch (Exception $e) {
        // Text2SQLエラー
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
            // SQL実行エラー
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
        // SQL実行エラー
        return [
            'success' => false,
            'error' => 'クエリの実行に失敗しました',
            'sql' => $sql  // デバッグ用
        ];
    }
}
?>