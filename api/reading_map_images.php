<?php
/**
 * 読書マップ画像データAPI
 * 著者やタグの画像を遅延読み込みするためのエンドポイント
 */

// エラー出力を抑制
error_reporting(0);
ini_set('display_errors', 0);

// 既存の出力バッファをクリア
while (ob_get_level()) {
    ob_end_clean();
}

try {
    header('Content-Type: application/json; charset=utf-8');
    
    // 設定ファイルの読み込み
    require_once dirname(__FILE__) . '/../config.php';
    require_once dirname(__FILE__) . '/../library/database.php';
    
    // セッション処理
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $g_login_id = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : null;
    $user_id = isset($_GET['user']) ? $_GET['user'] : null;
    $type = isset($_GET['type']) ? $_GET['type'] : 'author';  // 'author' or 'tag'
    
    if (!$user_id && $g_login_id) {
        $user_id = $g_login_id;
    }
    
    if (!$user_id) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // 他人のデータの場合は公開設定を確認
    if ($user_id != $g_login_id) {
        $target_user = getUserInformation($user_id);
        if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
    }

    // データベース接続
    global $g_db;
    
    $result = [];
    
    if ($type === 'author') {
        // 著者別の画像を取得
        $sql = "SELECT 
            author,
            book_id,
            image_url,
            name as title
        FROM b_book_list
        WHERE user_id = ? 
            AND status = 3
            AND author IS NOT NULL 
            AND author != ''
            AND image_url IS NOT NULL 
            AND image_url != ''
        ORDER BY author, update_date DESC";
        
        $image_data = $g_db->getAll($sql, [$user_id], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($image_data)) {
            $author_images = [];
            foreach ($image_data as $row) {
                $author = $row['author'];
                if (!isset($author_images[$author])) {
                    $author_images[$author] = [];
                }
                if (count($author_images[$author]) < 3) {
                    $author_images[$author][] = [
                        'url' => $row['image_url'],
                        'title' => $row['title'],
                        'book_id' => $row['book_id']
                    ];
                }
            }
            $result = $author_images;
        }
        
    } else if ($type === 'tag') {
        // タグ別の画像を取得
        $sql = "SELECT 
            bt.tag_name,
            bl.book_id,
            bl.image_url,
            bl.name as title,
            COUNT(be.event_id) as event_count
        FROM b_book_tags bt
        JOIN b_book_list bl ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
        LEFT JOIN b_book_event be ON bl.book_id = be.book_id AND bl.user_id = be.user_id
        WHERE bt.user_id = ? 
            AND bl.image_url IS NOT NULL 
            AND bl.image_url != ''
        GROUP BY bt.tag_name, bl.book_id, bl.image_url, bl.name
        ORDER BY bt.tag_name, event_count DESC, bl.update_date DESC
        LIMIT 300";
        
        $tag_image_data = $g_db->getAll($sql, [$user_id], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($tag_image_data)) {
            $tag_images = [];
            foreach ($tag_image_data as $row) {
                $tag_name = $row['tag_name'];
                if (!isset($tag_images[$tag_name])) {
                    $tag_images[$tag_name] = [];
                }
                if (count($tag_images[$tag_name]) < 3) {
                    $tag_images[$tag_name][] = [
                        'url' => $row['image_url'],
                        'title' => $row['title'],
                        'book_id' => $row['book_id']
                    ];
                }
            }
            $result = $tag_images;
        }
    }
    
    // キャッシュヘッダーを追加（30分間）
    header('Cache-Control: public, max-age=1800');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 1800) . ' GMT');
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'type' => $type
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>