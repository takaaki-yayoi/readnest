<?php
/**
 * 人気の本一覧ページ
 * 多くのユーザーがブックマークしている本を表示
 * トップページと同じロジックを使用
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// グローバル変数
global $g_db;

// キャッシュライブラリを読み込み
require_once(dirname(__FILE__) . '/library/cache.php');
$cache = getCache();

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = $login_flag ? $_SESSION['AUTH_USER'] : '';
$d_nickname = $login_flag ? getNickname($mine_user_id) : '';

// ページネーション設定
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 48;
$offset = ($page - 1) * $per_page;

// 期間フィルター
$period = isset($_GET['period']) ? $_GET['period'] : 'all'; // all, month, year

// 人気の本の総数を取得（キャッシュ対応）
$countCacheKey = 'popular_books_count_v3_' . $period;
$countCacheTime = 7200; // 2時間キャッシュ

$cachedCount = $cache->get($countCacheKey);
if ($cachedCount !== false) {
    $total_count = $cachedCount;
} else {
    // トップページと同じロジックでカウント
    if ($period === 'month' || $period === 'year') {
        $time_limit = $period === 'month' ? date('Y-m-d H:i:s', strtotime('-1 month')) : date('Y-m-d H:i:s', strtotime('-1 year'));
        $count_sql = "SELECT COUNT(DISTINCT bl.name, bl.image_url) as total 
                      FROM b_book_list bl
                      INNER JOIN b_user u ON bl.user_id = u.user_id
                      WHERE u.diary_policy = 1
                      AND u.status = 1
                      AND bl.update_date >= ?
                      AND bl.name IS NOT NULL 
                      AND bl.name != ''
                      AND bl.image_url IS NOT NULL
                      AND bl.image_url != ''
                      AND bl.image_url NOT LIKE '%noimage%'";
        $params = array($time_limit);
    } else {
        // 全期間
        $count_sql = "SELECT COUNT(DISTINCT bl.name, bl.image_url) as total 
                      FROM b_book_list bl
                      INNER JOIN b_user u ON bl.user_id = u.user_id
                      WHERE u.diary_policy = 1
                      AND u.status = 1
                      AND bl.name IS NOT NULL 
                      AND bl.name != ''
                      AND bl.image_url IS NOT NULL
                      AND bl.image_url != ''
                      AND bl.image_url NOT LIKE '%noimage%'";
        $params = array();
    }

    $total_count = $g_db->getOne($count_sql, $params);
    if (DB::isError($total_count)) {
        error_log('Database error in popular_book.php count query: ' . $total_count->getMessage());
        $total_count = 0;
    } else {
        // キャッシュに保存
        $cache->set($countCacheKey, $total_count, $countCacheTime);
    }
}
$total_pages = ceil($total_count / $per_page);

// 人気の本を取得（キャッシュ対応）
$booksCacheKey = 'popular_books_v3_' . md5($period . '_' . $page);
$booksCacheTime = 3600; // 1時間キャッシュ

$cachedBooks = $cache->get($booksCacheKey);
if ($cachedBooks !== false) {
    $books = $cachedBooks;
} else {
    // トップページと同じロジックで取得
    if ($period === 'month' || $period === 'year') {
        $time_limit = $period === 'month' ? date('Y-m-d H:i:s', strtotime('-1 month')) : date('Y-m-d H:i:s', strtotime('-1 year'));
        $books_sql = "SELECT 
            MIN(bl.book_id) as book_id,
            bl.name as title,
            bl.author,
            bl.image_url,
            COUNT(DISTINCT bl.user_id) as bookmark_count,
            AVG(CASE WHEN bl.rating > 0 THEN bl.rating END) as avg_rating,
            MAX(bl.update_date) as last_update
        FROM b_book_list bl
        INNER JOIN b_user u ON bl.user_id = u.user_id
        WHERE u.diary_policy = 1
        AND u.status = 1
        AND bl.update_date >= ?
        AND bl.name IS NOT NULL 
        AND bl.name != ''
        AND bl.image_url IS NOT NULL
        AND bl.image_url != ''
        AND bl.image_url NOT LIKE '%noimage%'
        GROUP BY bl.name, bl.author, bl.image_url
        HAVING COUNT(DISTINCT bl.user_id) > 0
        ORDER BY bookmark_count DESC, last_update DESC
        LIMIT ? OFFSET ?";
        $params = array($time_limit, $per_page, $offset);
    } else {
        // 全期間（トップページと同じロジック）
        $books_sql = "SELECT 
            MIN(bl.book_id) as book_id,
            bl.name as title,
            bl.author,
            bl.image_url,
            COUNT(DISTINCT bl.user_id) as bookmark_count,
            AVG(CASE WHEN bl.rating > 0 THEN bl.rating END) as avg_rating,
            MAX(bl.update_date) as last_update
        FROM b_book_list bl
        INNER JOIN b_user u ON bl.user_id = u.user_id
        WHERE u.diary_policy = 1
        AND u.status = 1
        AND bl.name IS NOT NULL 
        AND bl.name != ''
        AND bl.image_url IS NOT NULL
        AND bl.image_url != ''
        AND bl.image_url NOT LIKE '%noimage%'
        GROUP BY bl.name, bl.author, bl.image_url
        HAVING COUNT(DISTINCT bl.user_id) > 0
        ORDER BY bookmark_count DESC, last_update DESC
        LIMIT ? OFFSET ?";
        $params = array($per_page, $offset);
    }

    $books = $g_db->getAll($books_sql, $params, DB_FETCHMODE_ASSOC);
    if (DB::isError($books)) {
        error_log('Database error in popular_book.php books query: ' . $books->getMessage());
        
        // エラー時はフォールバッククエリを試す（シンプル版）
        $fallback_sql = "SELECT 
            MIN(bl.book_id) as book_id,
            bl.name as title,
            bl.author,
            bl.image_url,
            COUNT(DISTINCT bl.user_id) as bookmark_count,
            1 as avg_rating
        FROM b_book_list bl
        WHERE bl.name IS NOT NULL 
        AND bl.name != ''
        AND bl.image_url IS NOT NULL
        AND bl.image_url != ''
        AND bl.image_url NOT LIKE '%noimage%'
        AND bl.image_url NOT LIKE '%no-image%'
        GROUP BY bl.name, bl.author, bl.image_url
        HAVING COUNT(DISTINCT bl.user_id) > 0
        ORDER BY bookmark_count DESC
        LIMIT ? OFFSET ?";
        
        $books = $g_db->getAll($fallback_sql, array($per_page, $offset), DB_FETCHMODE_ASSOC);
        if (DB::isError($books)) {
            error_log('Fallback query also failed: ' . $books->getMessage());
            $books = array();
        }
    } else {
        // キャッシュに保存
        $cache->set($booksCacheKey, $books, $booksCacheTime);
    }
}

// amazon_idを追加（テンプレートで必要な場合）
foreach ($books as &$book) {
    if (!isset($book['amazon_id'])) {
        // book_idから amazon_id を取得
        $amazon_id = $g_db->getOne("SELECT amazon_id FROM b_book_list WHERE book_id = ?", array($book['book_id']));
        if (!DB::isError($amazon_id)) {
            $book['amazon_id'] = $amazon_id;
        }
    }
}

// ページタイトル
$d_site_title = '人気の本 - ReadNest';

// SEO設定
$d_meta_description = 'ReadNestで人気の本をチェック。多くのユーザーが読んでいる本、高評価の本を発見しよう。';
$d_meta_keywords = '人気の本,ベストセラー,おすすめ本,読書,ReadNest';

// 統計情報
$stats = [
    'total_books' => $total_count,
    'showing_from' => $offset + 1,
    'showing_to' => min($offset + $per_page, $total_count)
];

// テンプレートを使用
include(getTemplatePath('t_popular_book.php'));
?>