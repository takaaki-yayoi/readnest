<?php
/**
 * 本追加API
 */

header('Content-Type: application/json; charset=utf-8');

// エラー出力を抑制
error_reporting(0);
ini_set('display_errors', 0);

// エラーハンドラー設定
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean();
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'PHP Error',
        'details' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]));
});

ob_start();

try {
    require_once(dirname(__DIR__) . '/config.php');
    require_once(dirname(__DIR__) . '/library/database.php');
    
    // ログインチェック（config.phpでセッションは開始される）
    $login_flag = checkLogin();
    if (!$login_flag || !isset($_SESSION['AUTH_USER'])) {
        ob_clean();
        http_response_code(401);
        die(json_encode([
            'success' => false,
            'error' => 'Not logged in'
        ]));
    }
    
    $user_id = $_SESSION['AUTH_USER'];
    
    // POSTデータを取得
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['asin'])) {
        ob_clean();
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'error' => 'ASIN is required'
        ]));
    }
    
    $asin = $input['asin'];
    $status = $input['status'] ?? 1; // デフォルトは積読
    
    // AI推薦の場合は処理しない
    if (strpos($asin, 'ai_') === 0) {
        ob_clean();
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'error' => 'この本は自動追加できません。タイトルをクリックして検索してください。'
        ]));
    }
    
    // まずb_book_repositoryから本の情報を取得
    $book_sql = "SELECT * FROM b_book_repository WHERE asin = ?";
    $book_info = $g_db->getRow($book_sql, [$asin], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($book_info)) {
        throw new Exception('Database error: ' . $book_info->getMessage());
    }
    
    if (!$book_info) {
        // b_book_repositoryに存在しない場合はエラー
        ob_clean();
        http_response_code(404);
        die(json_encode([
            'success' => false,
            'error' => '本が見つかりません'
        ]));
    }
    
    // 既に本棚に存在するかチェック
    $check_sql = "SELECT book_id FROM b_book_list WHERE user_id = ? AND amazon_id = ?";
    $existing = $g_db->getOne($check_sql, [$user_id, $asin]);
    
    if ($existing) {
        ob_clean();
        http_response_code(409);
        die(json_encode([
            'success' => false,
            'error' => 'この本は既に本棚に追加されています'
        ]));
    }
    
    // 本を追加（b_book_repositoryの情報も含める）
    $insert_sql = "
        INSERT INTO b_book_list (
            user_id, amazon_id, name, author, image_url, status, create_date, update_date
        ) VALUES (
            ?, ?, ?, ?, ?, ?, NOW(), NOW()
        )
    ";
    
    $result = $g_db->query($insert_sql, [
        $user_id, 
        $asin, 
        $book_info['title'],
        $book_info['author'],
        $book_info['image_url'],
        $status
    ]);
    
    if (DB::isError($result)) {
        throw new Exception('Failed to add book: ' . $result->getMessage());
    }
    
    // 挿入したbook_idを取得
    $book_id = $g_db->getOne("SELECT LAST_INSERT_ID()");
    
    // 成功レスポンス
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => '本を追加しました',
        'book' => [
            'book_id' => $book_id,
            'asin' => $asin,
            'title' => $book_info['title'],
            'author' => $book_info['author']
        ],
        'redirect' => '/book_detail.php?book_id=' . $book_id . '&t=' . time()
    ]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'details' => $e->getMessage()
    ]);
}

ob_end_flush();
?>