<?php
/**
 * モダン人気レビューページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ページタイトル設定
$d_site_title = "人気のレビュー - ReadNest";

// メタ情報
$g_meta_description = "ReadNestで最も読まれている人気のレビューをご紹介。読書仲間のおすすめ本や感想を参考にして、次に読む本を見つけましょう。";
$g_meta_keyword = "人気レビュー,おすすめ本,書評,ReadNest,読書記録,レビューランキング";

// ログイン状態を確認
$login_flag = checkLogin();
$user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;
$d_nickname = $login_flag ? getNickname($user_id) : '';

// ページングパラメータ
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

// ソートパラメータ
$sort = $_GET['sort'] ?? 'popular'; // popular, recent, rating
$sort_options = [
    'popular' => '人気順',
    'recent' => '新着順', 
    'rating' => '評価順'
];

// 期間フィルター
$period = $_GET['period'] ?? 'all'; // all, week, month, year
$period_options = [
    'all' => '全期間',
    'week' => '1週間',
    'month' => '1ヶ月',
    'year' => '1年'
];

// レビューデータを取得
$popular_reviews = [];
$total_reviews = 0;
$total_pages = 1;

try {
    // すべての人気レビューを取得
    $all_reviews = getPopularReview('', '');
    
    if ($all_reviews && !DB::isError($all_reviews)) {
        // ソートとフィルタリング
        $filtered_reviews = $all_reviews;
        
        // 期間フィルター適用
        if ($period !== 'all') {
            $cutoff_time = match($period) {
                'week' => time() - (7 * 24 * 60 * 60),
                'month' => time() - (30 * 24 * 60 * 60),
                'year' => time() - (365 * 24 * 60 * 60),
                default => 0
            };
            
            $filtered_reviews = array_filter($filtered_reviews, function($review) use ($cutoff_time) {
                return $review['update_date'] >= $cutoff_time;
            });
        }
        
        // ソート適用
        usort($filtered_reviews, function($a, $b) use ($sort) {
            return match($sort) {
                'recent' => $b['update_date'] <=> $a['update_date'],
                'rating' => ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0),
                default => ($b['number_of_refer'] ?? 0) <=> ($a['number_of_refer'] ?? 0) // popular
            };
        });
        
        $total_reviews = count($filtered_reviews);
        $total_pages = max(1, (int)ceil($total_reviews / $per_page));
        $page = min($page, $total_pages);
        
        $start_index = ($page - 1) * $per_page;
        $page_reviews = array_slice($filtered_reviews, $start_index, $per_page);
        
        // レビューデータを整形
        foreach ($page_reviews as $review) {
            $user_nickname = getNickname($review['user_id']);
            $user_info = getUserInformation($review['user_id']);
            
            $popular_reviews[] = [
                'book_id' => $review['book_id'],
                'name' => $review['name'],
                'memo' => $review['memo'],
                'short_memo' => mb_strlen($review['memo']) > 120 ? 
                    mb_substr($review['memo'], 0, 120) . '...' : $review['memo'],
                'image_url' => $review['image_url'] ?: '/img/no-image-book.png',
                'user_id' => $review['user_id'],
                'user_nickname' => $user_nickname,
                'user_photo' => ($user_info['photo'] && $user_info['photo_state'] == PHOTO_REGISTER_STATE) ? 
                    "https://readnest.jp/display_profile_photo.php?user_id={$review['user_id']}&mode=icon" : 
                    '/img/noimage.jpg',
                'number_of_refer' => $review['number_of_refer'],
                'rating' => $review['rating'] ?? 0,
                'update_date' => $review['update_date'],
                'formatted_date' => date('Y年n月j日', strtotime($review['update_date'])),
                'detail_url' => "/book/{$review['book_id']}"
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error in popular_review.php: " . $e->getMessage());
}

// 統計情報
$start_num = ($page - 1) * $per_page + 1;
$end_num = min($page * $per_page, $total_reviews);

// 評価表示用のヘルパー関数
function renderStars(int $rating): string {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star text-yellow-400"></i>';
        } else {
            $stars .= '<i class="far fa-star text-gray-300"></i>';
        }
    }
    return $stars;
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_popular_review.php'));
?>