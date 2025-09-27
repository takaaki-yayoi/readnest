<?php
/**
 * モダンレビュー検索ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ページタイトル設定
$d_site_title = "レビュー検索 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestのレビュー検索。キーワードで本のレビューを検索し、他の読者の感想や評価を参考にできます。";
$g_meta_keyword = "レビュー検索,書評,感想,ReadNest,本,検索";

// キャッシュライブラリを読み込み
require_once(dirname(__FILE__) . '/library/cache.php');
$cache = getCache();

// ログイン状態を確認
$login_flag = checkLogin();
$user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;
$d_nickname = $login_flag ? getNickname($user_id) : '';

// 検索パラメータの取得
$keyword = trim($_GET['keyword'] ?? $_POST['keyword'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

// 検索結果の初期化
$search_results = [];
$total_results = 0;
$total_pages = 1;
$popular_reviews = [];
$tag_cloud = [];

try {
    if (!empty($keyword)) {
        // キャッシュキーを生成（検索キーワードとページ番号を含む）
        $searchCacheKey = 'search_review_' . md5($keyword) . '_page_' . $page;
        $searchCacheTime = 900; // 15分キャッシュ
        
        // キャッシュから取得を試みる
        $cachedData = $cache->get($searchCacheKey);
        
        if ($cachedData !== false) {
            // キャッシュがある場合
            $search_results = $cachedData['results'];
            $total_results = $cachedData['total'];
            $total_pages = $cachedData['pages'];
        } else {
            // キャッシュがない場合は検索を実行
            $keyword_search = "%{$keyword}%";
            
            // JOINを使用してユーザー情報も一度に取得（N+1問題の解決）
            $search_sql = "
                SELECT bl.*, u.nickname as user_nickname
                FROM b_book_list bl
                INNER JOIN b_user u ON bl.user_id = u.user_id
                WHERE bl.memo LIKE ? 
                    AND bl.memo != ''
                    AND u.diary_policy = 1
                ORDER BY bl.memo_updated DESC
            ";
            
            $all_results = $g_db->getAll($search_sql, [$keyword_search], DB_FETCHMODE_ASSOC);
            
            if ($all_results && !DB::isError($all_results)) {
                $total_results = count($all_results);
                $total_pages = max(1, (int)ceil($total_results / $per_page));
                $page = min($page, $total_pages);
                
                $start_index = ($page - 1) * $per_page;
                $search_results = array_slice($all_results, $start_index, $per_page);
                
                // 検索結果を整形
                foreach ($search_results as &$result) {
                    // update_dateがDATETIME文字列の場合とUnix timestampの場合に対応
                    if (is_numeric($result['update_date']) && $result['update_date'] > 0) {
                        $result['formatted_date'] = date('Y年n月j日', $result['update_date']);
                    } else {
                        $result['formatted_date'] = date('Y年n月j日', strtotime($result['update_date']));
                    }
                    $result['short_memo'] = mb_strlen($result['memo'] ?? '') > 100 ? 
                        mb_substr($result['memo'] ?? '', 0, 100) . '...' : ($result['memo'] ?? '');
                    $result['detail_url'] = "/book/{$result['book_id']}";
                }
                
                // キャッシュに保存
                $cacheData = [
                    'results' => $search_results,
                    'total' => $total_results,
                    'pages' => $total_pages
                ];
                $cache->set($searchCacheKey, $cacheData, $searchCacheTime);
            }
        }
    } else {
        // キーワードが空の場合は人気のレビューと関連情報を表示
        $popularCacheKey = 'popular_reviews_and_tags_v1';
        $popularCacheTime = 1800; // 30分キャッシュ
        
        $cachedPopularData = $cache->get($popularCacheKey);
        
        if ($cachedPopularData !== false) {
            // キャッシュがある場合
            $popular_reviews = $cachedPopularData['reviews'];
            $tag_cloud = $cachedPopularData['tags'];
        } else {
            // キャッシュがない場合
            // JOINを使用してユーザー情報も一度に取得
            $popular_sql = "
                SELECT bl.book_id, bl.name, bl.memo, bl.image_url, bl.user_id, 
                       bl.number_of_refer, u.nickname as user_nickname
                FROM b_book_list bl
                INNER JOIN b_user u ON bl.user_id = u.user_id
                WHERE bl.memo != '' 
                    AND bl.memo IS NOT NULL
                    AND u.diary_policy = 1
                ORDER BY bl.number_of_refer DESC, bl.memo_updated DESC
                LIMIT 8
            ";
            
            $popular_reviews_data = $g_db->getAll($popular_sql, [], DB_FETCHMODE_ASSOC);
            
            if ($popular_reviews_data && !DB::isError($popular_reviews_data)) {
                foreach ($popular_reviews_data as $review) {
                    $popular_reviews[] = [
                        'book_id' => $review['book_id'],
                        'name' => $review['name'],
                        'memo' => $review['memo'],
                        'short_memo' => mb_strlen($review['memo'] ?? '') > 80 ? 
                            mb_substr($review['memo'] ?? '', 0, 80) . '...' : ($review['memo'] ?? ''),
                        'image_url' => $review['image_url'] ?: '/img/no-image-book.png',
                        'user_id' => $review['user_id'],
                        'user_nickname' => $review['user_nickname'],
                        'number_of_refer' => $review['number_of_refer'],
                        'detail_url' => "/book/{$review['book_id']}"
                    ];
                }
            }
            
            // タグクラウドを取得（新しい関数を使用）
            $tag_cloud_data = getPopularTags(20);
            if ($tag_cloud_data && !DB::isError($tag_cloud_data)) {
                $tag_cloud = $tag_cloud_data;
            }
            
            // キャッシュに保存
            $cache->set($popularCacheKey, [
                'reviews' => $popular_reviews,
                'tags' => $tag_cloud
            ], $popularCacheTime);
        }
    }
} catch (Exception $e) {
    error_log("Error in search_review.php: " . $e->getMessage());
}

// 検索統計
$start_num = ($page - 1) * $per_page + 1;
$end_num = min($page * $per_page, $total_results);

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_search_review.php'));
?>