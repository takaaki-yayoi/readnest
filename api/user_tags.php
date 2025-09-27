<?php
/**
 * ユーザータグAPI
 * タグクラウドデータを高速に取得
 */

declare(strict_types=1);

// エラー出力を抑制
error_reporting(0);
ini_set('display_errors', 0);

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
    require_once dirname(__FILE__) . '/../library/cache.php';
    
    // セッション処理
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $g_login_id = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : null;
    $user_id = isset($_GET['user']) ? $_GET['user'] : null;
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'cloud'; // cloud, popular, recent, all
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    
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
    
    // 対象ユーザーのプライバシー設定を確認
    if ($user_id != $g_login_id) {
        $target_user = getUserInformation($user_id);
        if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
            // 出力バッファをクリア
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'User profile is private']);
            exit;
        }
    }
    
    // キャッシュを取得
    $cache = getCache();
    $cacheKey = 'user_tags_api_' . md5($user_id . '_' . $mode . '_' . $limit . '_' . $status_filter);
    $cacheTime = 1800; // 30分キャッシュ
    
    // キャッシュチェック
    $cachedData = $cache->get($cacheKey);
    if ($cachedData !== false) {
        // 出力バッファをクリア
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('X-Cache: HIT');
        echo json_encode($cachedData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    // データベース接続
    global $g_db;
    
    // タグデータを取得（パフォーマンス最適化版）
    if ($mode === 'recent') {
        // 最新タグ：最近使用されたタグ（本の更新日時順）
        $sql = "SELECT 
            bt.tag_name,
            COUNT(DISTINCT bt.book_id) as tag_count,
            GROUP_CONCAT(DISTINCT bl.status) as statuses,
            MAX(bl.update_date) as latest_use
        FROM b_book_tags bt
        INNER JOIN b_book_list bl ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
        WHERE bt.user_id = ?";
        
        $params = [$user_id];
        
        if ($status_filter !== '') {
            $sql .= " AND bl.status = ?";
            $params[] = $status_filter;
        }
        
        $sql .= " GROUP BY bt.tag_name
                  ORDER BY latest_use DESC, bt.tag_name ASC
                  LIMIT " . $limit;
                  
    } elseif ($mode === 'popular') {
        // popular: 人気タグのみ（3冊以上）
        $sql = "SELECT 
            bt.tag_name,
            COUNT(DISTINCT bt.book_id) as tag_count,
            GROUP_CONCAT(DISTINCT bl.status) as statuses
        FROM b_book_tags bt
        INNER JOIN b_book_list bl ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
        WHERE bt.user_id = ?
        GROUP BY bt.tag_name
        HAVING tag_count >= 3
        ORDER BY tag_count DESC, bt.tag_name ASC
        LIMIT " . $limit;
        
        $params = [$user_id];
    } else {
        // cloud/all: タグクラウド用：すべてのタグ（ステータスフィルタ可能）
        $sql = "SELECT 
            bt.tag_name,
            COUNT(DISTINCT bt.book_id) as tag_count,
            GROUP_CONCAT(DISTINCT bl.status) as statuses
        FROM b_book_tags bt
        INNER JOIN b_book_list bl ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
        WHERE bt.user_id = ?";
        
        $params = [$user_id];
        
        if ($status_filter !== '') {
            $sql .= " AND bl.status = ?";
            $params[] = $status_filter;
        }
        
        $sql .= " GROUP BY bt.tag_name
                  ORDER BY tag_count DESC, bt.tag_name ASC";
        
        if ($mode === 'cloud' && $limit > 0) {
            $sql .= " LIMIT " . $limit;
        }
    }
    
    $tags = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
    
    if (DB::isError($tags)) {
        throw new Exception($tags->getMessage());
    }
    
    // タグの統計情報を計算
    $total_tags = count($tags);
    $max_count = 0;
    $min_count = PHP_INT_MAX;
    $total_books_with_tags = 0;
    
    foreach ($tags as &$tag) {
        $count = intval($tag['tag_count']);
        $max_count = max($max_count, $count);
        $min_count = min($min_count, $count);
        $total_books_with_tags += $count;
        
        // ステータス情報を配列に変換
        $tag['statuses'] = !empty($tag['statuses']) ? explode(',', $tag['statuses']) : [];
    }
    
    if ($total_tags === 0) {
        $min_count = 0;
    }
    
    // タグカテゴリ分類
    $categories = [
        'popular' => [],      // 5冊以上
        'frequent' => [],     // 3-4冊
        'occasional' => [],   // 2冊
        'rare' => []         // 1冊
    ];
    
    foreach ($tags as $tag) {
        $count = intval($tag['tag_count']);
        if ($count >= 5) {
            $categories['popular'][] = $tag;
        } elseif ($count >= 3) {
            $categories['frequent'][] = $tag;
        } elseif ($count >= 2) {
            $categories['occasional'][] = $tag;
        } else {
            $categories['rare'][] = $tag;
        }
    }
    
    // 結果を構築
    $result = [
        'success' => true,
        'tags' => $tags,
        'stats' => [
            'total_tags' => $total_tags,
            'max_count' => $max_count,
            'min_count' => $min_count,
            'total_books_with_tags' => $total_books_with_tags,
            'categories' => [
                'popular' => count($categories['popular']),
                'frequent' => count($categories['frequent']),
                'occasional' => count($categories['occasional']),
                'rare' => count($categories['rare'])
            ]
        ],
        'categories' => $categories,
        'mode' => $mode,
        'user_id' => $user_id
    ];
    
    // キャッシュに保存
    $cache->set($cacheKey, $result, $cacheTime);
    
    // 出力バッファをクリア
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('X-Cache: MISS');
    header('Cache-Control: public, max-age=300'); // 5分間ブラウザキャッシュ
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