<?php
/**
 * 読書マップデータAPI（軽量版）
 * 初回ロードを高速化するため、画像なしで基本データのみ返す
 */

// デバッグ時はエラー表示を有効化
if (isset($_GET['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // エラー出力を完全に抑制
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 既存の出力バッファをクリア
while (ob_get_level()) {
    ob_end_clean();
}

// 出力バッファリング開始
ob_start();

try {
    header('Content-Type: application/json; charset=utf-8');
    
    // 設定ファイルの読み込み
    require_once dirname(__FILE__) . '/../config.php';
    require_once dirname(__FILE__) . '/../library/database.php';
    require_once dirname(__FILE__) . '/../cache_reading_map.php';
    
    // セッション処理
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $g_login_id = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : null;
    $user_id = isset($_GET['user']) ? $_GET['user'] : null;
    
    if (!$user_id && $g_login_id) {
        $user_id = $g_login_id;
    }
    
    if (!$user_id) {
        // 出力バッファをクリア
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // 他人のデータの場合は公開設定を確認
    if ($user_id != $g_login_id) {
        $target_user = getUserInformation($user_id);
        if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
            // 出力バッファをクリア
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
    }

    // データベース接続
    global $g_db;
    
    // 読了本を著者別に集計（画像なし）
    $sql = "SELECT 
        author,
        COUNT(*) as book_count
    FROM b_book_list
    WHERE user_id = ? 
        AND status = 3
        AND author IS NOT NULL 
        AND author != ''
    GROUP BY author
    ORDER BY book_count DESC";
    
    $author_data = $g_db->getAll($sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($author_data)) {
        throw new Exception($author_data->getMessage());
    }
    
    // データを整形
    $formatted_data = [
        'name' => '読書マップ',
        'children' => []
    ];
    
    // カラーパレット
    $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57'];
    
    // パフォーマンス最適化: 最大表示数を制限
    $max_display_nodes = 500;
    
    // 著者をカテゴリ別に分類
    $categories = [
        '10冊以上読了した著者' => ['authors' => [], 'color' => '#FF6B6B'],
        '5〜9冊読了した著者' => ['authors' => [], 'color' => '#4ECDC4'],
        '3〜4冊読了した著者' => ['authors' => [], 'color' => '#45B7D1'],
        '1〜2冊読了した著者' => ['authors' => [], 'color' => '#96CEB4']
    ];
    
    // 著者を分類
    $total_author_nodes = 0;
    foreach ($author_data as $author) {
        if ($total_author_nodes >= $max_display_nodes) break;
        
        $count = (int)$author['book_count'];
        $author_name = $author['author'];
        
        // 画像情報は空配列に
        $author['images'] = [];
        
        if ($count >= 10) {
            $categories['10冊以上読了した著者']['authors'][] = $author;
        } elseif ($count >= 5) {
            $categories['5〜9冊読了した著者']['authors'][] = $author;
        } elseif ($count >= 3) {
            $categories['3〜4冊読了した著者']['authors'][] = $author;
        } else {
            $categories['1〜2冊読了した著者']['authors'][] = $author;
        }
        $total_author_nodes++;
    }
    
    // カテゴリごとにデータを作成
    foreach ($categories as $category_name => $category_data) {
        if (count($category_data['authors']) > 0) {
            $category_total = 0;
            $children = [];
            
            foreach ($category_data['authors'] as $author) {
                $category_total += $author['book_count'];
                $children[] = [
                    'name' => $author['author'] . ' (' . $author['book_count'] . '冊)',
                    'value' => $author['book_count'],
                    'finished' => $author['book_count'],
                    'reading' => 0,
                    'unread' => 0,
                    'images' => [],  // 空配列
                    'author_name' => $author['author']  // 後で画像を取得するため
                ];
            }
            
            $formatted_data['children'][] = [
                'name' => $category_name,
                'color' => $category_data['color'],
                'value' => $category_total,
                'finished' => $category_total,
                'reading' => 0,
                'unread' => 0,
                'children' => $children
            ];
        }
    }
    
    // タグ情報も追加（画像なし）
    $tag_sql = "SELECT 
        tag_name,
        COUNT(DISTINCT book_id) as book_count
    FROM b_book_tags
    WHERE user_id = ?
    GROUP BY tag_name
    ORDER BY book_count DESC
    LIMIT 50";
    
    $tag_data = $g_db->getAll($tag_sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($tag_data) && count($tag_data) > 0) {
        $tag_total = 0;
        $tag_children = [];
        
        foreach ($tag_data as $tag) {
            $tag_name = $tag['tag_name'];
            $tag_total += $tag['book_count'];
            
            $tag_children[] = [
                'name' => $tag_name . ' (' . $tag['book_count'] . '冊)',
                'value' => $tag['book_count'],
                'finished' => 0,
                'reading' => 0,
                'unread' => 0,
                'images' => [],  // 空配列
                'tag_name' => $tag_name  // 後で画像を取得するため
            ];
        }
        
        if (count($tag_children) > 0) {
            $formatted_data['children'][] = [
                'name' => 'よく使うタグ',
                'color' => '#FECA57',
                'value' => $tag_total,
                'finished' => 0,
                'reading' => 0,
                'unread' => 0,
                'children' => $tag_children
            ];
        }
    }
    
    // 統計情報を計算
    $total_books = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE user_id = ?", [$user_id]);
    $finished_books = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status = 3", [$user_id]);
    $reading_books = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status = 2", [$user_id]);
    $genres_count = count($formatted_data['children']);
    
    $stats = [
        'total_books' => (int)$total_books,
        'finished_books' => (int)$finished_books,
        'reading_books' => (int)$reading_books,
        'genres_explored' => $genres_count,
        'total_authors' => count($author_data),
        'displayed_authors' => $total_author_nodes,
        'total_tags' => count($tag_data),
        'displayed_tags' => count($tag_children ?? []),
        'performance_optimized' => ($total_author_nodes < count($author_data))
    ];
    
    $result = [
        'success' => true,
        'data' => $formatted_data,
        'stats' => $stats,
        'version' => 'v3-lite'  // 軽量版を示す
    ];
    
    // 出力バッファをクリア
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=300'); // 5分間キャッシュ
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
    
} catch (Exception $e) {
    // エラー出力
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>