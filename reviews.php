<?php
/**
 * レビュー一覧ページ
 * 全ユーザーのレビューを表示
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// グローバル変数
global $g_db;

// レベル表示関連
require_once(dirname(__FILE__) . '/library/achievement_system.php');
require_once(dirname(__FILE__) . '/library/level_display_helper.php');

// ページネーション設定
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 検索条件
$search_keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'recent'; // recent, rating_high, rating_low

// レビュー総数を取得
$count_sql = "SELECT COUNT(DISTINCT bl.book_id, bl.user_id) as total 
              FROM b_book_list bl 
              INNER JOIN b_user u ON bl.user_id = u.user_id 
              WHERE bl.memo IS NOT NULL AND bl.memo != '' 
              AND u.diary_policy = 1";

$params = array();
if (!empty($search_keyword)) {
    $count_sql .= " AND (bl.name LIKE ? OR bl.author LIKE ? OR bl.memo LIKE ?)";
    $search_param = '%' . $search_keyword . '%';
    $params = array($search_param, $search_param, $search_param);
}

$total_count = $g_db->getOne($count_sql, $params);
if (DB::isError($total_count)) {
    error_log('Database error in reviews.php count query: ' . $total_count->getMessage());
    $total_count = 0;
}
$total_pages = ceil($total_count / $per_page);

// レビューを取得
$reviews_sql = "SELECT 
    bl.book_id,
    bl.user_id,
    bl.name as book_title,
    bl.author,
    bl.image_url,
    bl.memo as comment,
    bl.rating,
    bl.update_date,
    bl.amazon_id,
    u.nickname,
    u.photo as user_photo
FROM b_book_list bl
INNER JOIN b_user u ON bl.user_id = u.user_id
WHERE bl.memo IS NOT NULL AND bl.memo != ''
AND u.diary_policy = 1";

if (!empty($search_keyword)) {
    $reviews_sql .= " AND (bl.name LIKE ? OR bl.author LIKE ? OR bl.memo LIKE ?)";
}

// ソート条件
switch ($sort_by) {
    case 'rating_high':
        $reviews_sql .= " ORDER BY bl.rating DESC, bl.update_date DESC";
        break;
    case 'rating_low':
        $reviews_sql .= " ORDER BY bl.rating ASC, bl.update_date DESC";
        break;
    default: // recent
        $reviews_sql .= " ORDER BY bl.update_date DESC";
        break;
}

$reviews_sql .= " LIMIT $per_page OFFSET $offset";

$reviews = $g_db->getAll($reviews_sql, $params, DB_FETCHMODE_ASSOC);
if (DB::isError($reviews)) {
    error_log('Database error in reviews.php reviews query: ' . $reviews->getMessage());
    $reviews = array();
}

// レビューデータをフォーマット
$formatted_reviews = array();
if ($reviews && !DB::isError($reviews)) {
    foreach ($reviews as $review) {
        $formatted_reviews[] = array(
            'book_id' => $review['book_id'],
            'user_id' => $review['user_id'],
            'book_title' => $review['book_title'],
            'author' => $review['author'],
            'image_url' => $review['image_url'] ?: '/img/no-image-book.png',
            'comment' => $review['comment'],
            'rating' => $review['rating'],
            'update_date' => formatRelativeTime($review['update_date']),
            'nickname' => $review['nickname'],
            'user_photo' => getProfilePhotoURL($review['user_id']),
            'amazon_id' => $review['amazon_id']
        );
    }
}

// ユーザーレベル情報を一括取得
if (!empty($formatted_reviews)) {
    $user_ids = array_unique(array_column($formatted_reviews, 'user_id'));
    $user_levels = getUsersLevels($user_ids);
    
    // 各レビューにレベル情報を追加
    foreach ($formatted_reviews as &$review) {
        $review['user_level'] = $user_levels[$review['user_id']] ?? getReadingLevel(0);
    }
    unset($review);
}

// 日付フォーマット関数は library/date_helpers.php の formatRelativeTime() を使用


// ページタイトル
$d_site_title = 'みんなのレビュー - ReadNest';

// SEO設定
$d_meta_description = 'ReadNestユーザーによる本のレビュー一覧。様々な本の感想や評価をチェックして、次に読む本を見つけよう。';
$d_meta_keywords = '本,レビュー,書評,感想,評価,読書,ReadNest';

// テンプレートを使用
include(getTemplatePath('t_reviews.php'));
?>