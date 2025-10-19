<?php
/**
 * 読書マップデータAPI（実データ版）
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

    // キャッシュのチェック（no_cacheパラメータがない場合のみ）
    $use_cache = !isset($_GET['no_cache']);
    $cache = new ReadingMapCache();
    
    if ($use_cache) {
        $cached_data = $cache->get($user_id);
        
        if ($cached_data !== null) {
            // 出力バッファをクリア
            while (ob_get_level()) {
                ob_end_clean();
            }
            // キャッシュヘッダーを追加
            header('Content-Type: application/json; charset=utf-8');
            header('X-Cache: HIT');
            echo json_encode($cached_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
    }
    
    // キャッシュミスのヘッダー
    header('X-Cache: MISS');
    
    // データベース接続
    global $g_db;
    
    // 読了本を著者別に集計
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
    
    // 著者別に本の画像を取得（シンプル版）
    $image_sql = "SELECT 
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
    ORDER BY author, update_date DESC
    LIMIT 1000";
    
    $image_data = $g_db->getAll($image_sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    // 著者ごとの代表画像を作成
    $author_images = [];
    if (!DB::isError($image_data)) {
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
    }
    
    // 著者を分類
    $total_author_nodes = 0;
    foreach ($author_data as $author) {
        if ($total_author_nodes >= $max_display_nodes) break;
        
        $count = (int)$author['book_count'];
        $author_name = $author['author'];
        
        // 画像情報を追加
        $author['images'] = isset($author_images[$author_name]) ? $author_images[$author_name] : [];
        
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
                    'images' => $author['images']
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
    
    // タグ情報も追加
    $tag_sql = "SELECT 
        tag_name,
        COUNT(DISTINCT book_id) as book_count
    FROM b_book_tags
    WHERE user_id = ?
    GROUP BY tag_name
    ORDER BY book_count DESC
    LIMIT 100";
    
    $tag_data = $g_db->getAll($tag_sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($tag_data) && count($tag_data) > 0) {
        // タグ別の画像を取得（読書イベント数でソート）
        $tag_image_sql = "SELECT 
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
        LIMIT 500";
        
        $tag_image_data = $g_db->getAll($tag_image_sql, [$user_id], DB_FETCHMODE_ASSOC);
        
        
        // タグごとの代表画像を作成
        $tag_images = [];
        if (!DB::isError($tag_image_data)) {
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
            
        }
        
        $tag_total = 0;
        $tag_children = [];
        $tag_count = 0;
        $max_tag_nodes = 50; // タグの最大表示数
        
        foreach ($tag_data as $tag) {
            if ($tag_count >= $max_tag_nodes) break;
            
            $tag_name = $tag['tag_name'];
            $tag_total += $tag['book_count'];
            
            
            // タグの画像を設定（ない場合はデフォルト画像を1つ設定）
            $images = isset($tag_images[$tag_name]) ? $tag_images[$tag_name] : [];
            if (empty($images)) {
                // 画像がない場合はデフォルト画像を設定
                $images = [[
                    'url' => '/img/no-image-book.png',
                    'title' => 'No Image',
                    'book_id' => 0
                ]];
            }
            
            $tag_children[] = [
                'name' => $tag_name . ' (' . $tag['book_count'] . '冊)',
                'value' => $tag['book_count'],
                'finished' => 0,
                'reading' => 0,
                'unread' => 0,
                'images' => $images
            ];
            $tag_count++;
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
    
    // 統計情報
    $stats_sql = "SELECT 
        COUNT(*) as total_books,
        SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as finished_books,
        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as reading_books
    FROM b_book_list 
    WHERE user_id = ?";
    
    $stats_result = $g_db->getRow($stats_sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    $stats = [
        'total_books' => $stats_result['total_books'] ?? 0,
        'finished_books' => $stats_result['finished_books'] ?? 0,
        'reading_books' => $stats_result['reading_books'] ?? 0,
        'genres_explored' => count($formatted_data['children']),
        'most_read_genre' => count($formatted_data['children']) > 0 ? $formatted_data['children'][0]['name'] : '',
        'most_read_author' => count($author_data) > 0 ? $author_data[0]['author'] : '',
        'least_explored_genres' => ['SF・ファンタジー', 'ミステリー', 'ビジネス書'],
        'total_authors' => count($author_data),
        'displayed_authors' => $total_author_nodes,
        'total_tags' => is_array($tag_data) ? count($tag_data) : 0,
        'displayed_tags' => isset($tag_count) ? $tag_count : 0,
        'performance_optimized' => $total_author_nodes >= $max_display_nodes,
        'authors_with_images' => count($author_images),
        'total_image_records' => is_array($image_data) ? count($image_data) : 0
    ];
    
    // レスポンスデータ
    $response_data = [
        'data' => $formatted_data,
        'stats' => $stats,
        'user_id' => $user_id
    ];
    
    // JSONを生成
    $json_response = json_encode($response_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    // キャッシュに保存
    $cache->set($user_id, $response_data);
    
    // 出力バッファをクリアしてJSONを出力
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    header('X-Cache: MISS');
    echo $json_response;
    
} catch (Exception $e) {
    // 出力バッファをクリア
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Error: ' . $e->getMessage(),
        'data' => ['name' => 'エラー', 'children' => []],
        'stats' => [
            'total_books' => 0,
            'finished_books' => 0,
            'reading_books' => 0,
            'genres_explored' => 0,
            'most_read_genre' => '',
            'most_read_author' => '',
            'least_explored_genres' => []
        ],
        'user_id' => 0
    ]);
}
?>