<?php
/**
 * お気に入りの並び順を更新するAjaxエンドポイント
 */

require_once(dirname(__DIR__) . '/modern_config.php');

// レスポンスヘッダー設定
header('Content-Type: application/json');

// ログインチェック
if (!isset($_SESSION['AUTH_USER']) || empty($_SESSION['AUTH_USER'])) {
    echo json_encode(['success' => false, 'error' => 'ログインしてください']);
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// POSTデータの取得
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['book_ids']) || !is_array($input['book_ids'])) {
    echo json_encode(['success' => false, 'error' => '無効なデータです']);
    exit;
}

$book_ids = $input['book_ids'];

try {
    // トランザクション開始
    $g_db->query('START TRANSACTION');
    
    $sort_order = 1;
    $success = true;
    
    foreach ($book_ids as $book_id) {
        $book_id = (int)$book_id;
        
        // ユーザーのお気に入りであることを確認しつつ、sort_orderを更新
        $update_sql = sprintf(
            "UPDATE b_book_favorites SET sort_order = %d WHERE user_id = %d AND book_id = %d",
            $sort_order,
            (int)$user_id,
            $book_id
        );
        
        $result = $g_db->query($update_sql);
        
        if (DB::isError($result)) {
            $success = false;
            break;
        }
        
        $sort_order++;
    }
    
    if ($success) {
        $g_db->query('COMMIT');
        echo json_encode(['success' => true]);
    } else {
        $g_db->query('ROLLBACK');
        echo json_encode(['success' => false, 'error' => 'データベースエラー']);
    }
    
} catch (Exception $e) {
    $g_db->query('ROLLBACK');
    error_log('Error updating favorites order: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'エラーが発生しました']);
}
?>