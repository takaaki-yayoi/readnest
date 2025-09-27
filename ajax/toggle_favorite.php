<?php
/**
 * お気に入り登録・解除のAJAXエンドポイント
 */

require_once('../modern_config.php');

// JSONレスポンスを返す
header('Content-Type: application/json; charset=utf-8');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    echo json_encode(['success' => false, 'message' => 'ログインが必要です']);
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// POSTデータの取得
$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$book_id) {
    echo json_encode(['success' => false, 'message' => '不正なリクエストです']);
    exit;
}

// 本の所有者チェック
$book_sql = "SELECT user_id FROM b_book_list WHERE book_id = ?";
$book_owner = $g_db->getOne($book_sql, [$book_id]);

if (DB::isError($book_owner) || !$book_owner) {
    echo json_encode(['success' => false, 'message' => '本が見つかりません']);
    exit;
}

// 自分の本棚の本のみお気に入り登録可能
if ($book_owner != $user_id) {
    echo json_encode(['success' => false, 'message' => 'この本はお気に入りに登録できません']);
    exit;
}

try {
    if ($action === 'add') {
        // お気に入りに追加（sprintf使用）
        $insert_sql = sprintf(
            "INSERT INTO b_book_favorites (user_id, book_id) VALUES (%d, %d) 
             ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP",
            (int)$user_id,
            (int)$book_id
        );
        $result = $g_db->query($insert_sql);
        
        if (DB::isError($result)) {
            error_log('Favorite add error: ' . $result->getMessage());
            throw new Exception('お気に入り登録に失敗しました: ' . $result->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'お気に入りに追加しました',
            'is_favorite' => true
        ]);
        
    } elseif ($action === 'remove') {
        // お気に入りから削除（sprintf使用）
        $delete_sql = sprintf(
            "DELETE FROM b_book_favorites WHERE user_id = %d AND book_id = %d",
            (int)$user_id,
            (int)$book_id
        );
        $result = $g_db->query($delete_sql);
        
        if (DB::isError($result)) {
            error_log('Favorite remove error: ' . $result->getMessage());
            throw new Exception('お気に入り解除に失敗しました: ' . $result->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'お気に入りを解除しました',
            'is_favorite' => false
        ]);
        
    } elseif ($action === 'toggle') {
        // トグル（現在の状態を確認して切り替え）
        $check_sql = sprintf(
            "SELECT 1 FROM b_book_favorites WHERE user_id = %d AND book_id = %d",
            (int)$user_id,
            (int)$book_id
        );
        $exists = $g_db->getOne($check_sql);
        
        if (DB::isError($exists)) {
            error_log('Favorite check error: ' . $exists->getMessage());
            throw new Exception('状態確認に失敗しました');
        }
        
        if ($exists) {
            // 削除（sprintf使用）
            $delete_sql = sprintf(
                "DELETE FROM b_book_favorites WHERE user_id = %d AND book_id = %d",
                (int)$user_id,
                (int)$book_id
            );
            $result = $g_db->query($delete_sql);
            
            if (DB::isError($result)) {
                error_log('Favorite toggle remove error: ' . $result->getMessage());
                throw new Exception('お気に入り解除に失敗しました');
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'お気に入りを解除しました',
                'is_favorite' => false
            ]);
        } else {
            // 追加（sprintf使用）
            $insert_sql = sprintf(
                "INSERT INTO b_book_favorites (user_id, book_id) VALUES (%d, %d)",
                (int)$user_id,
                (int)$book_id
            );
            $result = $g_db->query($insert_sql);
            
            if (DB::isError($result)) {
                error_log('Favorite toggle add error: ' . $result->getMessage());
                throw new Exception('お気に入り登録に失敗しました');
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'お気に入りに追加しました',
                'is_favorite' => true
            ]);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => '不正なアクションです']);
    }
    
} catch (Exception $e) {
    error_log('Favorite toggle error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>