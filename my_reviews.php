<?php
/**
 * マイレビュー一覧ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);


// モダン設定を読み込み
require_once('modern_config.php');
// 管理者認証関数を読み込み
require_once(__DIR__ . '/admin/admin_auth.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$logged_in_user_id = (int)$_SESSION['AUTH_USER'];
$user_info = getUserInformation($logged_in_user_id);

// 管理者権限チェック
$is_admin = isAdmin();

// 表示対象のユーザーID（管理者はGETパラメータで指定可能）
if ($is_admin && isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $viewing_other_user = true;
    
    // 対象ユーザー情報を取得
    $target_user_info = getUserInformation($user_id);
    if (!$target_user_info) {
        // ユーザーが存在しない場合は自分のレビューを表示
        $user_id = $logged_in_user_id;
        $viewing_other_user = false;
    }
} else {
    $user_id = $logged_in_user_id;
    $viewing_other_user = false;
}


// いいね機能のヘルパーを読み込み
require_once(dirname(__FILE__) . '/library/like_helpers.php');

// ソート条件の取得
$sort = $_GET['sort'] ?? 'update_date';
$order = $_GET['order'] ?? 'desc';

// 有効なソート条件のホワイトリスト
$valid_sorts = ['update_date', 'finished_date', 'rating', 'name'];
$valid_orders = ['asc', 'desc'];

if (!in_array($sort, $valid_sorts)) {
    $sort = 'update_date';
}
if (!in_array($order, $valid_orders)) {
    $order = 'desc';
}

// ページング設定
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// レビューのある本を取得
$reviews = [];
$total_count = 0;

try {
    // レビュー数を取得
    $count_sql = "SELECT COUNT(*) FROM b_book_list 
                  WHERE user_id = ? 
                  AND (rating > 0 OR (memo IS NOT NULL AND memo != ''))";
    $total_count = (int)$g_db->getOne($count_sql, [$user_id]);
    
    
    // レビューを取得
    $sql = "SELECT 
                bl.book_id,
                bl.name,
                bl.author,
                bl.image_url,
                bl.rating,
                bl.memo,
                bl.status,
                bl.finished_date,
                bl.update_date,
                bl.total_page,
                bl.current_page,
                bl.amazon_id
            FROM b_book_list bl
            WHERE bl.user_id = ? 
            AND (bl.rating > 0 OR (bl.memo IS NOT NULL AND bl.memo != ''))
            ORDER BY bl." . $sort . " " . $order . "
            LIMIT " . intval($per_page) . " OFFSET " . intval($offset);
    
    // DB_PDOのgetAllメソッドを使用（LIMITとOFFSETは直接埋め込み済み）
    $result = $g_db->getAll($sql, [$user_id]);
    
    if (!DB::isError($result)) {
        $reviews = $result;
    } else {
        error_log("DB Error in getAll: " . $result->getMessage());
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r([$user_id, $per_page, $offset], true));
        $reviews = [];
    }
} catch (Exception $e) {
    error_log("Error fetching reviews: " . $e->getMessage());
    $reviews = []; // エラー時は空配列を設定
}

// いいね情報を追加
if (!empty($reviews)) {
    // レビューのtarget_idを生成
    $review_target_ids = [];
    foreach ($reviews as $review) {
        $review_target_ids[] = generateReviewTargetId($review['book_id'], $user_id);
    }

    // いいね数を一括取得
    $like_counts = getLikeCounts('review', $review_target_ids);

    // ログインユーザーのいいね状態を取得
    if ($logged_in_user_id) {
        $user_like_states = getUserLikeStates($logged_in_user_id, 'review', $review_target_ids);
    } else {
        $user_like_states = [];
    }

    // 各レビューにいいね情報を追加
    foreach ($reviews as &$review) {
        $target_id = generateReviewTargetId($review['book_id'], $user_id);
        $review['like_count'] = $like_counts[$target_id] ?? 0;
        $review['is_liked'] = $user_like_states[$target_id] ?? false;
    }
    unset($review);
}

// ページネーション計算
$total_pages = ceil($total_count / $per_page);

// レビュー統計を計算
$stats = [
    'total_reviews' => $total_count,
    'avg_rating' => 0,
    'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
];

// 統計用に評価のある本の数を別途カウント
$rating_count_sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND rating > 0";
$rating_count = (int)$g_db->getOne($rating_count_sql, [$user_id]);

if ($rating_count > 0) {
    $stats_sql = "SELECT 
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5
                  FROM b_book_list 
                  WHERE user_id = ? AND rating > 0";
    
    $stats_result = $g_db->getRow($stats_sql, [$user_id]);
    if ($stats_result && !DB::isError($stats_result)) {
        $stats['avg_rating'] = round((float)$stats_result['avg_rating'], 1);
        $stats['rating_distribution'] = [
            1 => (int)$stats_result['rating_1'],
            2 => (int)$stats_result['rating_2'],
            3 => (int)$stats_result['rating_3'],
            4 => (int)$stats_result['rating_4'],
            5 => (int)$stats_result['rating_5']
        ];
    }
}


// ページタイトル
if ($viewing_other_user && isset($target_user_info)) {
    $d_site_title = 'マイレビュー (ユーザーID: ' . $user_id . ' - ' . html($target_user_info['nickname']) . ') - ReadNest';
} else {
    $d_site_title = 'マイレビュー - ReadNest';
}

// URLパラメータを保持するためのベースクエリ文字列
$base_query = '';
if ($viewing_other_user) {
    $base_query = 'user_id=' . $user_id . '&';
}

// メタ情報
$g_meta_description = 'あなたが書いたレビューの一覧です。評価やメモを振り返ることができます。';
$g_meta_keyword = 'レビュー,評価,読書記録,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_my_reviews.php'));
?>