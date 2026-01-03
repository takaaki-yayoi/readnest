<?php
/**
 * モダンテンプレートを使用するトップページ
 * PHP 8.2.28対応版
 * 既存のindex.phpをベースに、新しいテンプレートシステムを適用
 */

declare(strict_types=1);

// デバッグモード設定
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

// ブラウザキャッシュ制御を先に設定
require_once(dirname(__FILE__) . '/library/cache_headers.php');
setCacheControlHeaders();

// モダン設定を読み込み
require_once('modern_config.php');

// セッション管理は自動的に開始される（session.phpで処理済み）

// データベース接続は既にconfig.phpで$g_dbとして設定済み

// 月間目標ライブラリを読み込み
require_once(dirname(__FILE__) . '/library/monthly_goals.php');

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = '';
$my_recent_books = [];

// ログアウトユーザー向け高速化は一時的に無効化（ログイン問題のため）
// if (!$login_flag && !isset($_GET['full'])) {
//     // 静的コンテンツを表示（パフォーマンス向上）
//     if (file_exists('index_light.php')) {
//         include_once('index_light.php');
//         exit;
//     }
// }

if ($login_flag) {
    $mine_user_id = $_SESSION['AUTH_USER'];
    
    
    // 自分の最近更新した本を取得
    try {
        $my_books_sql = "
            SELECT bl.book_id, bl.name as title, bl.author, bl.image_url, bl.status, bl.current_page, bl.total_page, bl.update_date
            FROM b_book_list bl
            WHERE bl.user_id = ?
            ORDER BY bl.update_date DESC
            LIMIT 5
        ";
        $my_recent_books = $g_db->getAll($my_books_sql, [$mine_user_id]);
        
        if(DB::isError($my_recent_books)) {
            $my_recent_books = [];
        }
    } catch (Exception $e) {
        $my_recent_books = [];
    }
}

// SEOヘルパーを読み込み
require_once('library/seo_helpers.php');

// 画像ヘルパーを読み込み
require_once('library/image_helpers.php');

// ページタイトル設定
$d_site_title = "ReadNest - あなたの読書の巣";

// メタ情報
$g_meta_description = "ReadNest - 読書の進捉を記録し、レビューを書き、本を整理するための居心地のよい空間です。読書仲間とのつながりを楽しみましょう。";
$g_meta_keyword = "読書,本,書評,レビュー,本棚,読書記録,ReadNest";

// SEOデータの準備
$canonical_url = getBaseUrl();
$og_image = getBaseUrl() . '/img/og-image.jpg';

$seo_data = [
    'title' => $d_site_title,
    'description' => $g_meta_description,
    'canonical_url' => $canonical_url,
    'og' => [
        'title' => 'ReadNest - あなたの読書の巣',
        'description' => $g_meta_description,
        'url' => $canonical_url,
        'image' => $og_image,
        'type' => 'website'
    ],
    'twitter' => [
        'title' => 'ReadNest - あなたの読書の巣',
        'description' => $g_meta_description,
        'image' => $og_image
    ]
];

// 構造化データの生成
$organization_schema = generateOrganizationSchema();
$seo_data['schema'] = [$organization_schema];

// SEOタグの生成
$g_seo_tags = generateSEOTags($seo_data);

// キャッシュライブラリを読み込み
require_once(dirname(__FILE__) . '/library/cache.php');
$cache = getCache();

// サイト設定を読み込み
require_once(dirname(__FILE__) . '/library/site_settings.php');

// ニックネームヘルパー関数を読み込み
require_once(dirname(__FILE__) . '/library/nickname_helpers.php');
// レベル表示関連
require_once(dirname(__FILE__) . '/library/achievement_system.php');
require_once(dirname(__FILE__) . '/library/level_display_helper.php');

// 最適化されたデータベース関数を読み込み
require_once(dirname(__FILE__) . '/library/database_optimized.php');
if (file_exists(dirname(__FILE__) . '/library/database_optimized_v2.php')) {
    require_once(dirname(__FILE__) . '/library/database_optimized_v2.php');
}

// 統計情報を取得（キャッシュ機能付き）
try {
    global $g_db;
    
    $statsCacheKey = 'site_statistics_v1';
    $statsCacheTime = 86400; // 24時間キャッシュ（統計はあまり変わらない）
    
    $stats = $cache->get($statsCacheKey);
    
    if ($stats === false) {
        // 統計情報を1つのクエリで取得（パフォーマンス向上）
        $stats_sql = "
            SELECT 
                (SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL) as total_users,
                (SELECT COUNT(DISTINCT book_id) FROM b_book_list) as total_books,
                (SELECT COUNT(*) FROM b_book_list WHERE memo != '' AND memo IS NOT NULL) as total_reviews,
                (SELECT SUM(CASE WHEN status = ? THEN total_page ELSE current_page END) 
                 FROM b_book_list WHERE current_page > 0) as total_pages_read
        ";
        
        $stats_result = $g_db->getRow($stats_sql, array(READING_FINISH), DB_FETCHMODE_ASSOC);
        
        if(DB::isError($stats_result)) {
            // エラー時はデフォルト値
            $stats = [
                'total_users' => 1234,
                'total_books' => 45678,
                'total_reviews' => 8901,
                'total_pages_read' => 234567
            ];
        } else {
            $stats = [
                'total_users' => intval($stats_result['total_users'] ?? 0),
                'total_books' => intval($stats_result['total_books'] ?? 0),
                'total_reviews' => intval($stats_result['total_reviews'] ?? 0),
                'total_pages_read' => intval($stats_result['total_pages_read'] ?? 0)
            ];
        }
        
        // キャッシュに保存
        $cache->set($statsCacheKey, $stats, $statsCacheTime);
    }
    
    // 統計情報を変数に展開
    $total_users = $stats['total_users'];
    $total_books = $stats['total_books'];
    $total_reviews = $stats['total_reviews'];
    $total_pages_read = $stats['total_pages_read'];
    
} catch (Exception $e) {
    // エラー時はデフォルト値を使用
    $total_users = 1234;
    $total_books = 45678;
    $total_reviews = 8901;
    $total_pages_read = 234567;
}

// 最新のお知らせを取得
$latest_announcement = null;
try {
    $announcementCacheKey = 'latest_announcement_v1';
    $announcementCacheTime = 300; // 5分キャッシュ
    $announcementBackupCacheTime = 1800; // 30分バックアップキャッシュ
    
    $latest_announcement = $cache->get($announcementCacheKey);
    
    if ($latest_announcement === false) {
        // カラム存在チェックもキャッシュ化（24時間有効）
        $columnCheckCacheKey = 'announcement_type_column_exists';
        $has_type_column = $cache->get($columnCheckCacheKey);
        
        if ($has_type_column === false) {
            // キャッシュがない場合のみカラムチェック実行
            $check_type_column = $g_db->getOne("SHOW COLUMNS FROM b_announcement LIKE 'type'");
            $has_type_column = !empty($check_type_column);
            // カラム存在情報を24時間キャッシュ
            $cache->set($columnCheckCacheKey, $has_type_column, 86400);
        }
        
        if ($has_type_column) {
            $announcement_sql = "
                SELECT id as announcement_id, title, content, type, created
                FROM b_announcement
                ORDER BY created DESC
                LIMIT 1
            ";
        } else {
            $announcement_sql = "
                SELECT id as announcement_id, title, content, created
                FROM b_announcement
                ORDER BY created DESC
                LIMIT 1
            ";
        }
        
        $announcement_result = $g_db->getRow($announcement_sql, array(), DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($announcement_result) && $announcement_result) {
            $latest_announcement = $announcement_result;
            $cache->set($announcementCacheKey, $latest_announcement, $announcementCacheTime);
            $cache->set($announcementCacheKey . '_backup', $latest_announcement, $announcementBackupCacheTime);
        } else {
            // データがない場合もバックアップから復元を試みる
            $backup_announcement = $cache->get($announcementCacheKey . '_backup');
            if ($backup_announcement !== false) {
                $latest_announcement = $backup_announcement;
                // バックアップを使用した場合、メインキャッシュにも短時間保存
                $cache->set($announcementCacheKey, $latest_announcement, 60); // 1分間
            } else {
                // 空データを短時間キャッシュして頻繁なクエリを防ぐ
                $cache->set($announcementCacheKey, null, 60); // 1分間
            }
        }
    }
} catch (Exception $e) {
    error_log('Failed to fetch latest announcement: ' . $e->getMessage());
    // エラー時もバックアップから復元を試みる
    $backup_announcement = $cache->get('latest_announcement_v1_backup');
    if ($backup_announcement !== false) {
        $latest_announcement = $backup_announcement;
    } else {
        $latest_announcement = null;
    }
}

// ログインユーザーの作家クラウドを取得
$user_author_cloud_html = '';
if ($login_flag) {
    require_once('library/user_author_cloud.php');
    $user_cloud = new UserAuthorCloud($mine_user_id);
    $user_author_cloud_html = $user_cloud->generateCloudHtml($mine_user_id, 15, true); // コンパクト表示
}

// 新着レビューを取得（元の実装を使用しつつキャッシュを追加）
if (isNewReviewsEnabled()) {
    try {
    $reviewsCacheKey = 'new_reviews_v7'; // v7: update_dateでソートするように修正
    $reviewsCacheTime = 600; // 10分キャッシュ
    
    // デバッグモードまたはキャッシュミスの場合
    $debug_reviews = isset($_GET['debug_reviews']) && $_GET['debug_reviews'] == '1';
    $new_reviews = $debug_reviews ? false : $cache->get($reviewsCacheKey);
    
    if ($new_reviews === false) {
        // 最適化された関数を使用してレビューを取得（10件取得して6件以上あれば「もっと見る」を表示）
        $new_review_data = getNewReviewOptimized('', 10);
        $new_reviews = array();
        
        if ($new_review_data && !DB::isError($new_review_data)) {
            foreach ($new_review_data as $review) {
                // データが正しく取得できているか確認
                if (!empty($review['book_id']) && !empty($review['name'])) {
                    $new_reviews[] = array(
                        'book_id' => $review['book_id'],
                        'book_title' => $review['name'],
                        'comment' => isset($review['memo']) ? $review['memo'] : '',
                        'rating' => isset($review['rating']) ? intval($review['rating']) : 0,
                        'user_id' => $review['user_id'],
                        'nickname' => isset($review['nickname']) ? $review['nickname'] : '名無しさん',
                        'user_photo' => getProfilePhotoURL($review['user_id']),
                        'created_at' => isset($review['update_date']) && !empty($review['update_date']) ? strtotime($review['update_date']) : time(),
                        'image_url' => normalizeBookImageUrl(isset($review['image_url']) ? $review['image_url'] : '')
                    );
                }
            }
        }
        
        // データが取得できた場合のみキャッシュに保存
        if (!empty($new_reviews)) {
            $cache->set($reviewsCacheKey, $new_reviews, $reviewsCacheTime);
        }
    }
    } catch (Exception $e) {
        error_log("Error fetching new reviews: " . $e->getMessage());
        $new_reviews = array();
    }
} else {
    $new_reviews = array();
}

// 読書中の本を取得（キャッシュ機能付き・エラーハンドリング強化）
if (isPopularBooksEnabled()) {
    try {
    $booksCacheKey = 'popular_reading_books_v1';
    $booksCacheTime = 3600; // 1時間キャッシュ（人気の本もゆっくり変化）
    
    // デバッグモード（URLに?debug_popular=1を追加で有効）
    $debug_popular = isset($_GET['debug_popular']) && $_GET['debug_popular'] == '1';
    
    // キャッシュクリアモード（URLに?clear_popular_cache=1を追加で有効）
    if (isset($_GET['clear_popular_cache']) && $_GET['clear_popular_cache'] == '1') {
        $cache->delete($booksCacheKey);
    }
    
    $reading_books = $debug_popular ? false : $cache->get($booksCacheKey);
    
    if ($reading_books === false) {
        
        // 人気の本を取得（最適化版を使用）
        if (function_exists('getPopularBooksFromCache')) {
            // 集計テーブルから高速取得
            $reading_books = getPopularBooksFromCache(9);
        } elseif (function_exists('getPopularBooksOptimized')) {
            // 最適化されたクエリを使用
            $reading_books = getPopularBooksOptimized(9);
        } else {
            // 従来のクエリを使用
            $reading_books_sql = "
                SELECT 
                    MIN(bl.book_id) as book_id,
                    bl.name as title,
                    bl.image_url,
                    MIN(bl.amazon_id) as amazon_id,
                    COUNT(DISTINCT bl.user_id) as bookmark_count
                FROM b_book_list bl
                INNER JOIN b_user u ON bl.user_id = u.user_id
                WHERE u.diary_policy = 1 
                    AND u.status = 1
                    AND bl.name IS NOT NULL 
                    AND bl.name != ''
                    AND bl.image_url IS NOT NULL
                    AND bl.image_url != ''
                    AND bl.image_url NOT LIKE '%noimage%'
                GROUP BY bl.name, bl.image_url
                HAVING COUNT(DISTINCT bl.user_id) > 0
                ORDER BY bookmark_count DESC, MAX(bl.update_date) DESC
                LIMIT 9
            ";
            
            $reading_books = $g_db->getAll($reading_books_sql, array(), DB_FETCHMODE_ASSOC);
            
            if(DB::isError($reading_books)) {
                error_log("ERROR: Popular books query failed: " . $reading_books->getMessage());
                $reading_books = array();
            } else {
            }
        }
        
        // データが空の場合、より緩い条件で再試行
        if (empty($reading_books)) {
            $fallback_sql = "
                SELECT 
                    MIN(bl.book_id) as book_id,
                    bl.name as title,
                    bl.image_url,
                    MIN(bl.amazon_id) as amazon_id,
                    COUNT(DISTINCT bl.user_id) as bookmark_count
                FROM b_book_list bl
                LEFT JOIN b_user u ON bl.user_id = u.user_id
                WHERE bl.name IS NOT NULL 
                    AND bl.name != ''
                    AND bl.image_url IS NOT NULL
                    AND bl.image_url != ''
                    AND bl.image_url NOT LIKE '%noimage%'
                    AND bl.image_url NOT LIKE '%no-image%'
                GROUP BY bl.name, bl.image_url
                HAVING COUNT(DISTINCT bl.user_id) > 0
                ORDER BY bookmark_count DESC
                LIMIT 9
            ";
            
            $reading_books = $g_db->getAll($fallback_sql, array(), DB_FETCHMODE_ASSOC);
            
            if(DB::isError($reading_books)) {
                error_log("ERROR: Fallback query also failed: " . $reading_books->getMessage());
                $reading_books = array();
            } else {
            }
        }
        
        // 最終的にもデータが空の場合、デバッグ用にテーブル状況を確認
        if (empty($reading_books)) {
            $total_books = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE name IS NOT NULL AND name != ''");
            $books_with_images = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE image_url IS NOT NULL AND image_url != ''");
            $public_users = $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE diary_policy = 1");
            
            // 最低限のサンプルデータを作成（運用継続のため）
            $sample_books_sql = "
                SELECT 
                    book_id,
                    name as title,
                    image_url,
                    amazon_id,
                    1 as bookmark_count
                FROM b_book_list
                WHERE name IS NOT NULL 
                    AND name != ''
                    AND image_url IS NOT NULL
                    AND image_url != ''
                ORDER BY book_id DESC
                LIMIT 3
            ";
            $sample_books = $g_db->getAll($sample_books_sql, array(), DB_FETCHMODE_ASSOC);
            if (!DB::isError($sample_books) && !empty($sample_books)) {
                $reading_books = $sample_books;
            }
        }
        
        // キャッシュに保存（データがある場合のみ）
        if (!empty($reading_books)) {
            $cache->set($booksCacheKey, $reading_books, $booksCacheTime);
            
            // 追加でより短期間のキャッシュも作成（1時間失効対策）
            $cache->set($booksCacheKey . '_backup', $reading_books, $booksCacheTime * 3); // 3時間キャッシュ
            
        } else {
            // 空のデータの場合は短時間キャッシュして頻繁なクエリを防ぐ
            // ただし、バックアップキャッシュがあれば使用
            $backup_books = $cache->get($booksCacheKey . '_backup');
            if ($backup_books !== false && !empty($backup_books)) {
                $reading_books = $backup_books;
                // バックアップを使用した場合、メインキャッシュにも短時間保存
                $cache->set($booksCacheKey, $reading_books, 180); // 3分間
            } else {
                // バックアップもない場合は空データをキャッシュしない
                // $cache->set($booksCacheKey, array(), 60); // キャッシュしない
            }
        }
        
        // デバッグ情報を追加
        if ($debug_popular) {
            if (empty($reading_books)) {
            } else {
            }
        }
    } else {
    }
    
    // amazon_idが無い本について一括取得（パフォーマンス改善）
    if (!empty($reading_books)) {
        $book_ids_without_asin = [];
        foreach ($reading_books as $book) {
            if (empty($book['amazon_id']) && !empty($book['book_id'])) {
                $book_ids_without_asin[] = $book['book_id'];
            }
        }
        
        if (!empty($book_ids_without_asin)) {
            // 一括でamazon_idを取得
            $placeholders = str_repeat('?,', count($book_ids_without_asin) - 1) . '?';
            $asin_sql = "SELECT book_id, amazon_id FROM b_book_list WHERE book_id IN ($placeholders) AND amazon_id IS NOT NULL";
            $asin_results = $g_db->getAll($asin_sql, $book_ids_without_asin, DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($asin_results)) {
                // 結果をマップに変換
                $asin_map = [];
                foreach ($asin_results as $row) {
                    $asin_map[$row['book_id']] = $row['amazon_id'];
                }
                
                // 本のデータを更新
                foreach ($reading_books as &$book) {
                    if (empty($book['amazon_id']) && isset($asin_map[$book['book_id']])) {
                        $book['amazon_id'] = $asin_map[$book['book_id']];
                    }
                }
            }
        }
    }
    
    } catch (Exception $e) {
        error_log("ERROR in popular books: " . $e->getMessage() . " at line " . $e->getLine());
        $reading_books = array();
        // エラー情報もキャッシュして頻繁なエラーを防ぐ
        $cache->set($booksCacheKey, array(), 300); // 5分間
    }
} else {
    $reading_books = array();
}

// 最新の活動を取得（キャッシュ機能付き）
if (isLatestActivitiesEnabled()) {
    try {
    $cacheKey = 'recent_activities_formatted_v8'; // v8: プロフィール画像とbook_name処理を修正
    $cacheTime = 300; // 5分間キャッシュ
    
    // デバッグモード（URLに?debug_activities=1を追加で有効）
    $debug_mode = isset($_GET['debug_activities']) && $_GET['debug_activities'] == '1';
    
    // キャッシュクリアモード（URLに?clear_activities_cache=1を追加で有効）
    if (isset($_GET['clear_activities_cache']) && $_GET['clear_activities_cache'] == '1') {
        $cache->delete($cacheKey);
        $cache->delete($cacheKey . '_backup');
    }
    
    // キャッシュから取得を試みる
    $formatted_activities = $cache->get($cacheKey);
    
    // キャッシュされたデータのニックネームを検証
    if ($formatted_activities !== false && is_array($formatted_activities)) {
        $formatted_activities = validateCachedNicknames($formatted_activities);
        
        // キャッシュから読み込んだ場合もレベル情報を再取得
        // これにより、レベルバッジが表示されない問題を解決
        $user_ids = array_unique(array_column($formatted_activities, 'user_id'));
        if (!empty($user_ids)) {
            $user_levels = getUsersLevels($user_ids);
            foreach ($formatted_activities as &$activity) {
                $activity['user_level'] = $user_levels[$activity['user_id']] ?? getReadingLevel(0);
            }
            unset($activity);
        }
    }
    
    if ($formatted_activities === false || $debug_mode) {
        // キャッシュがない場合は最適化されたクエリで取得（N+1問題の解決）
        // LEFT JOINを使用してb_book_listにエントリがない場合でも活動を取得
        // インデックスヒントを追加してパフォーマンス向上
        // ユーザーごとの最新活動を取得（重複を防ぐ）
        $activities_sql = "
            SELECT 
                be.book_id, be.event_date, be.event, be.memo, be.page, be.user_id,
                bl.name as book_name, bl.image_url as book_image_url,
                u.nickname, u.photo, u.photo_state
            FROM (
                SELECT 
                    be2.book_id, be2.event_date, be2.event, be2.memo, be2.page, be2.user_id,
                    ROW_NUMBER() OVER (PARTITION BY be2.user_id ORDER BY be2.event_date DESC) as rn
                FROM b_book_event be2
                INNER JOIN b_user u2 ON be2.user_id = u2.user_id
                WHERE u2.diary_policy = 1 
                    AND u2.status = 1
                    AND be2.event IN (?, ?)
            ) be
            INNER JOIN b_user u ON be.user_id = u.user_id
            LEFT JOIN b_book_list bl ON be.book_id = bl.book_id AND be.user_id = bl.user_id
            WHERE be.rn <= 2  -- 各ユーザー最大2件まで
            ORDER BY be.event_date DESC
            LIMIT 10
        ";
        
        $recent_activities = $g_db->getAll($activities_sql, array(READING_NOW, READING_FINISH), DB_FETCHMODE_ASSOC);
        
        // 活動データを整形
        $formatted_activities = array();
        
        if(DB::isError($recent_activities) || empty($recent_activities)) {
            // エラーまたは結果が空の場合は元の関数を使用
            error_log("Using fallback getDisclosedDiary due to error or empty result");
            $recent_activities = getDisclosedDiary();
            if(!DB::isError($recent_activities) && $recent_activities) {
                foreach (array_slice($recent_activities, 0, 10) as $activity) {
                    $book_info = getBookInformation($activity['book_id']);
                    $user_info = getUserInformation($activity['user_id']);
                    
                    if ($book_info && $user_info) {
                        $activity_type = '';
                        $activity_color = 'gray';
                        switch ($activity['event']) {
                            case NOT_STARTED:
                                $activity_type = '未読';
                                $activity_color = 'blue';
                                break;
                            case READING_NOW:
                                $activity_type = '読書中';
                                $activity_color = 'yellow';
                                break;
                            case READING_FINISH:
                                $activity_type = '読了';
                                $activity_color = 'green';
                                break;
                            case READ_BEFORE:
                                $activity_type = '既読';
                                $activity_color = 'green';
                                break;
                            default:
                                $activity_type = '更新';
                                $activity_color = 'gray';
                                break;
                        }
                        
                        // ニックネーム表示の改善（フォールバック版でも適用）
                        $display_nickname = getSafeNickname($user_info, $activity['user_id']);
                        
                        // プロフィール画像URLを生成（activities.phpと同じ方法）
                        $user_photo_url = '/img/no-image-user.png';
                        if (!empty($user_info['photo']) && $user_info['photo_state'] == PHOTO_REGISTER_STATE) {
                            $user_photo_url = '/display_profile_photo.php?user_id=' . $activity['user_id'] . '&mode=thumbnail';
                        }
                        
                        $formatted_activities[] = array(
                            'type' => $activity_type,
                            'type_color' => $activity_color,
                            'user_id' => $activity['user_id'],
                            'user_name' => $display_nickname,
                            'user_photo' => $user_photo_url,
                            'book_id' => $activity['book_id'],
                            'book_title' => $book_info['name'],
                            'book_image' => $book_info['image_url'] ?: '/img/no-image-book.png',
                            'activity_date' => formatDate($activity['event_date'], 'Y年n月j日 H:i'),
                            'memo' => $activity['memo'],
                            'page' => $activity['page']
                        );
                    }
                }
            }
        } else {
            // 最適化されたクエリの結果を使用
            foreach ($recent_activities as $activity) {
                $activity_type = '';
                $activity_color = 'gray';
                switch ($activity['event']) {
                    case NOT_STARTED:
                        $activity_type = '未読';
                        $activity_color = 'blue';
                        break;
                    case READING_NOW:
                        $activity_type = '読書中';
                        $activity_color = 'yellow';
                        break;
                    case READING_FINISH:
                        $activity_type = '読了';
                        $activity_color = 'green';
                        break;
                    case READ_BEFORE:
                        $activity_type = '既読';
                        $activity_color = 'green';
                        break;
                    default:
                        $activity_type = '更新';
                        $activity_color = 'gray';
                        break;
                }
                
                
                // b_book_listにエントリがない場合の処理
                if (empty($activity['book_name'])) {
                    // 本の情報がない場合でもイベントは表示（タイトル不明として）
                    $activity['book_name'] = 'Book #' . $activity['book_id'];
                }
                
                // ニックネーム処理（改善版）
                $nickname = $activity['nickname'];
                
                // ニックネームの検証と正規化
                if (!isValidNickname($nickname)) {
                    // キャッシュのニックネームが無効な場合、データベースから再取得
                    $fresh_user_info = getUserInformation($activity['user_id']);
                    $nickname = getSafeNickname($fresh_user_info, $activity['user_id']);
                    
                    // デバッグ情報
                    if ($debug_mode) {
                        debugNickname($activity['user_id'], $activity['nickname'], 'cache_invalid');
                        debugNickname($activity['user_id'], $nickname, 'refreshed');
                    }
                }
                
                // プロフィール画像URLをactivities.phpと同じ方法で生成
                $user_photo_url = '/img/no-image-user.png';
                if (!empty($activity['photo']) && $activity['photo_state'] == PHOTO_REGISTER_STATE) {
                    $user_photo_url = '/display_profile_photo.php?user_id=' . $activity['user_id'] . '&mode=thumbnail';
                }
                
                $formatted_activities[] = array(
                    'type' => $activity_type,
                    'type_color' => $activity_color,
                    'user_id' => $activity['user_id'],
                    'user_name' => $nickname,
                    'user_photo' => $user_photo_url,
                    'book_id' => $activity['book_id'],
                    'book_title' => $activity['book_name'],
                    'book_image' => $activity['book_image_url'] ?: '/img/no-image-book.png',
                    'activity_date' => formatDate($activity['event_date'], 'Y年n月j日 H:i'),
                    'memo' => $activity['memo'],
                    'page' => $activity['page']
                );
            }
        }
        
        // キャッシュに保存（データがある場合のみ）
        if (!empty($formatted_activities)) {
            // キャッシュ前に事前検証と最終検証
            $formatted_activities = preValidateNicknames($formatted_activities);
            $formatted_activities = validateCachedNicknames($formatted_activities);
            
            // ユーザーレベル情報を一括取得
            $user_ids = array_unique(array_column($formatted_activities, 'user_id'));
            $user_levels = getUsersLevels($user_ids);
            
            // 各活動にレベル情報を追加
            foreach ($formatted_activities as &$activity) {
                $activity['user_level'] = $user_levels[$activity['user_id']] ?? getReadingLevel(0);
            }
            unset($activity);
            
            // 適応型キャッシュに保存
            if (function_exists('getAdaptiveCache')) {
                require_once(dirname(__FILE__) . '/library/adaptive_cache.php');
                $adaptiveCache = getAdaptiveCache();
                $adaptiveCache->set($cacheKey, $formatted_activities, 'recent_activities');
            } else {
                $cache->set($cacheKey, $formatted_activities, $cacheTime);
            }
            
            // バックアップキャッシュも作成（アクティビティの空表示対策）
            $cache->set($cacheKey . '_backup', $formatted_activities, $cacheTime * 6); // 30分バックアップ
        } else {
            // 空の場合はバックアップから復元を試みる
            $backup_activities = $cache->get($cacheKey . '_backup');
            if ($backup_activities !== false && !empty($backup_activities)) {
                $formatted_activities = $backup_activities;
            }
        }
    }
    
    } catch (Exception $e) {
        $formatted_activities = array();
    }
} else {
    $formatted_activities = array();
}

// 読書統計取得関数
function getUserReadingStats($user_id) {
    global $g_db;
    
    try {
        $stats = array();
        
        
        // 総読書数
        $total_books = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status IN (?, ?)", 
                                   [$user_id, READING_FINISH, READ_BEFORE]);
        if (DB::isError($total_books)) {
            $total_books = 0;
        }
        $stats['total_books'] = intval($total_books ?? 0);
        
        
        // 今年読んだ本（update_dateがNULLや不正な値の場合も考慮）
        // まずUnix timestampの可能性もチェック
        $this_year_books_sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status IN (?, ?)
                               AND (
                                   (finished_date IS NOT NULL AND YEAR(finished_date) = YEAR(NOW()))
                                   OR
                                   (finished_date IS NULL AND update_date IS NOT NULL 
                                    AND update_date != '0000-00-00 00:00:00'
                                    AND update_date > '1970-01-01 00:00:00'
                                    AND YEAR(update_date) = YEAR(NOW()))
                                   OR
                                   (finished_date IS NULL AND update_date REGEXP '^[0-9]+$' 
                                    AND CAST(update_date AS UNSIGNED) > 1000000000 
                                    AND YEAR(FROM_UNIXTIME(CAST(update_date AS UNSIGNED))) = YEAR(NOW()))
                               )";
        $this_year_books = $g_db->getOne($this_year_books_sql, [$user_id, READING_FINISH, READ_BEFORE]);
        if (DB::isError($this_year_books)) {
            // フォールバック：読了ステータスの本の総数を使用
            $fallback_sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status = ?";
            $this_year_books = $g_db->getOne($fallback_sql, [$user_id, READING_FINISH]);
            if (DB::isError($this_year_books)) {
                $this_year_books = 0;
            }
        }
        $stats['this_year_books'] = intval($this_year_books ?? 0);
        
        // 今月読んだ本（updateUserReadingStatと同じロジックで統一）
        $month_start = date('Y-m-01 00:00:00');
        $month_end = date('Y-m-t 23:59:59');

        // 1. b_book_eventからREADING_FINISHイベントをカウント
        $events_sql = "SELECT COUNT(DISTINCT book_id) FROM b_book_event
                       WHERE user_id = ? AND event_date BETWEEN ? AND ? AND event = ?";
        $this_month_events = $g_db->getOne($events_sql, [$user_id, $month_start, $month_end, READING_FINISH]);
        if (DB::isError($this_month_events)) {
            $this_month_events = 0;
        }

        // 2. b_book_list.finished_dateから今月読了の本をカウント（イベントがないもの）
        $finished_sql = "SELECT COUNT(DISTINCT bl.book_id) FROM b_book_list bl
                         WHERE bl.user_id = ?
                         AND bl.finished_date >= DATE(?)
                         AND bl.finished_date <= DATE(?)
                         AND bl.status IN (?, ?)
                         AND NOT EXISTS (
                           SELECT 1 FROM b_book_event be
                           WHERE be.user_id = bl.user_id
                           AND be.book_id = bl.book_id
                           AND be.event = ?
                           AND be.event_date BETWEEN ? AND ?
                         )";
        $this_month_finished = $g_db->getOne($finished_sql, [
            $user_id,
            $month_start, $month_end,
            READING_FINISH, READ_BEFORE,
            READING_FINISH,
            $month_start, $month_end
        ]);
        if (DB::isError($this_month_finished)) {
            $this_month_finished = 0;
        }

        // 両方の合計
        $stats['this_month_books'] = intval($this_month_events) + intval($this_month_finished);
        
        // 現在読書中の本
        $reading_now = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status = ?", 
                                   [$user_id, READING_NOW]);
        if (DB::isError($reading_now)) {
            $reading_now = 0;
        }
        $stats['reading_now'] = intval($reading_now ?? 0);
        
        // 総読書ページ数
        $total_pages = $g_db->getOne("SELECT SUM(total_page) FROM b_book_list WHERE user_id = ? AND status IN (?, ?) AND total_page > 0", 
                                   [$user_id, READING_FINISH, READ_BEFORE]);
        if (DB::isError($total_pages)) {
            $total_pages = 0;
        }
        $stats['total_pages'] = intval($total_pages ?? 0);
        
        // レビュー総数
        $total_reviews = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND (rating > 0 OR (memo IS NOT NULL AND memo != ''))", 
                                      [$user_id]);
        if (DB::isError($total_reviews)) {
            $total_reviews = 0;
        }
        $stats['total_reviews'] = intval($total_reviews ?? 0);
        
        return $stats;
    } catch (Exception $e) {
        return array(
            'total_books' => 0,
            'this_year_books' => 0,
            'this_month_books' => 0,
            'reading_now' => 0,
            'total_pages' => 0,
            'total_reviews' => 0
        );
    }
}

function getMonthlyReadingStats($user_id) {
    global $g_db, $cache;
    
    // キャッシュチェック
    $cacheKey = 'monthly_reading_stats_' . $user_id;
    $cached = $cache->get($cacheKey);
    if ($cached !== false) {
        return $cached;
    }
    
    try {
        // finished_dateとイベントの両方から月別読書数を取得
        $sql = "SELECT month, SUM(count) as count FROM (
                    -- finished_dateベースの集計
                    SELECT DATE_FORMAT(finished_date, '%Y-%m') as month, COUNT(*) as count 
                    FROM b_book_list 
                    WHERE user_id = ? AND status IN (?, ?)
                    AND finished_date IS NOT NULL
                    AND finished_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(finished_date, '%Y-%m')
                    
                    UNION ALL
                    
                    -- イベントベースの集計（finished_dateがない本のみ）
                    SELECT DATE_FORMAT(be.event_date, '%Y-%m') as month, COUNT(DISTINCT be.book_id) as count 
                    FROM b_book_event be
                    JOIN b_book_list bl ON be.user_id = bl.user_id AND be.book_id = bl.book_id
                    WHERE be.user_id = ? AND be.event = ? 
                    AND bl.finished_date IS NULL
                    AND be.event_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(be.event_date, '%Y-%m')
                    
                    UNION ALL
                    
                    -- update_dateベースの集計（finished_dateもイベントもない本のみ）
                    SELECT 
                        CASE 
                            WHEN bl.update_date REGEXP '^[0-9]+$' AND CAST(bl.update_date AS UNSIGNED) > 1000000000
                            THEN DATE_FORMAT(FROM_UNIXTIME(CAST(bl.update_date AS UNSIGNED)), '%Y-%m')
                            ELSE DATE_FORMAT(bl.update_date, '%Y-%m')
                        END as month, 
                        COUNT(*) as count 
                    FROM b_book_list bl
                    WHERE bl.user_id = ? AND bl.status IN (?, ?)
                    AND bl.finished_date IS NULL
                    AND NOT EXISTS (
                        SELECT 1 FROM b_book_event be2 
                        WHERE be2.user_id = bl.user_id 
                        AND be2.book_id = bl.book_id 
                        AND be2.event = ?
                    )
                    AND (
                        (bl.update_date REGEXP '^[0-9]+$' AND CAST(bl.update_date AS UNSIGNED) >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 12 MONTH)))
                        OR
                        (bl.update_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH))
                    )
                    GROUP BY month
                ) as combined
                GROUP BY month
                ORDER BY month";
        
        $results = $g_db->getAll($sql, [
            $user_id, READING_FINISH, READ_BEFORE,
            $user_id, READING_FINISH,
            $user_id, READING_FINISH, READ_BEFORE, READING_FINISH
        ]);
        
        if (DB::isError($results)) {
            return [];
        }
        
        $results = $results ?: [];
        
        // キャッシュに保存（1時間）
        $cache->set($cacheKey, $results, 3600);
        
        return $results;
    } catch (Exception $e) {
        return [];
    }
}

function getYearlyReadingProgress($user_id) {
    global $g_db, $cache;
    
    // キャッシュチェック
    $cacheKey = 'yearly_reading_progress_' . $user_id;
    $cached = $cache->get($cacheKey);
    if ($cached !== false) {
        return $cached;
    }
    
    try {
        // finished_dateとイベントの両方から年別読書数を取得
        $sql = "SELECT year, SUM(count) as count FROM (
                    -- finished_dateベースの集計
                    SELECT YEAR(finished_date) as year, COUNT(*) as count 
                    FROM b_book_list 
                    WHERE user_id = ? AND status IN (?, ?)
                    AND finished_date IS NOT NULL
                    AND finished_date >= DATE_SUB(NOW(), INTERVAL 5 YEAR)
                    GROUP BY YEAR(finished_date)
                    
                    UNION ALL
                    
                    -- イベントベースの集計（finished_dateがない本のみ）
                    SELECT YEAR(be.event_date) as year, COUNT(DISTINCT be.book_id) as count 
                    FROM b_book_event be
                    JOIN b_book_list bl ON be.user_id = bl.user_id AND be.book_id = bl.book_id
                    WHERE be.user_id = ? AND be.event = ? 
                    AND bl.finished_date IS NULL
                    AND be.event_date >= DATE_SUB(NOW(), INTERVAL 5 YEAR)
                    GROUP BY YEAR(be.event_date)
                    
                    UNION ALL
                    
                    -- update_dateベースの集計（finished_dateもイベントもない本のみ）
                    SELECT YEAR(bl.update_date) as year, COUNT(*) as count 
                    FROM b_book_list bl
                    WHERE bl.user_id = ? AND bl.status IN (?, ?)
                    AND bl.finished_date IS NULL
                    AND NOT EXISTS (
                        SELECT 1 FROM b_book_event be2 
                        WHERE be2.user_id = bl.user_id 
                        AND be2.book_id = bl.book_id 
                        AND be2.event = ?
                    )
                    AND bl.update_date >= DATE_SUB(NOW(), INTERVAL 5 YEAR)
                    GROUP BY YEAR(bl.update_date)
                ) as combined
                GROUP BY year
                ORDER BY year";
        
        $results = $g_db->getAll($sql, [
            $user_id, READING_FINISH, READ_BEFORE,
            $user_id, READING_FINISH,
            $user_id, READING_FINISH, READ_BEFORE, READING_FINISH
        ]);
        
        if (DB::isError($results)) {
            return [];
        }
        
        $results = $results ?: [];
        
        // キャッシュに保存（1時間）
        $cache->set($cacheKey, $results, 3600);
        
        return $results;
    } catch (Exception $e) {
        return [];
    }
}

// 日別のページ読書進捗を取得（page_history_amdata.phpを参考に）
function getDailyPageProgress($user_id, $days = 30) {
    global $g_db;
    
    try {
        // 表示期間より前に読了した本の総ページ数を取得（ベース値）
        $start_date = date('Y-m-d', time() - ($days * 24 * 60 * 60));
        $base_pages_sql = "
            SELECT SUM(total_page) 
            FROM b_book_list 
            WHERE user_id = ? 
            AND status IN (?, ?) 
            AND total_page > 0
            AND (
                (finished_date IS NOT NULL AND finished_date < ?) OR
                (finished_date IS NULL AND update_date < ?)
            )";
        $base_finished_pages = $g_db->getOne($base_pages_sql, [$user_id, READING_FINISH, READ_BEFORE, $start_date, $start_date]);
        if (DB::isError($base_finished_pages)) {
            $base_finished_pages = 0;
        }
        
        $base_pages = intval($base_finished_pages ?? 0);
        
        // 日別進捗データを作成
        $daily_progress = [];
        $current_time = time();
        $start_time = $current_time - ($days * 24 * 60 * 60);
        
        // イベントデータを取得（読書進捗のみ）
        // event_dateがDATETIME型の場合
        $start_datetime = date('Y-m-d H:i:s', $start_time);
        $events_sql = "
            SELECT book_id, event_date, page, event 
            FROM b_book_event 
            WHERE user_id = ? 
            AND event_date >= ?
            AND page > 0
            ORDER BY event_date ASC";
        
        $events = $g_db->getAll($events_sql, [$user_id, $start_datetime]);
        if (DB::isError($events)) {
            $events = [];
        }
        
        // finished_dateベースの読了本も取得
        $start_date = date('Y-m-d', $start_time);
        $finished_books_sql = "
            SELECT book_id, finished_date, total_page 
            FROM b_book_list 
            WHERE user_id = ? 
            AND finished_date >= ?
            AND finished_date <= CURDATE()
            AND status IN (?, ?)
            AND total_page > 0
            ORDER BY finished_date ASC";
        
        $finished_books = $g_db->getAll($finished_books_sql, [$user_id, $start_date, READING_FINISH, READ_BEFORE]);
        if (DB::isError($finished_books)) {
            $finished_books = [];
        }
        
        // 日付をキーとしたデータ構造を作成
        $date_pages = [];
        $book_progress = []; // 各本の現在ページを追跡
        
        // finished_dateベースの本を先に処理
        foreach ($finished_books as $book) {
            $date_key = $book['finished_date'];
            if (!isset($date_pages[$date_key])) {
                $date_pages[$date_key] = 0;
            }
            // 読了本の総ページ数を加算
            $date_pages[$date_key] += intval($book['total_page']);
        }
        
        foreach ($events as $event) {
            // event_dateがDATETIME形式の場合とUnix timestampの場合に対応
            if (is_numeric($event['event_date'])) {
                $date_key = date('Y-m-d', $event['event_date']);
            } else {
                $date_key = date('Y-m-d', strtotime($event['event_date']));
            }
            
            if (!isset($date_pages[$date_key])) {
                $date_pages[$date_key] = 0;
            }
            
            // その日の進捗を計算
            $book_id = $event['book_id'];
            $current_page = $event['page'];
            $previous_page = $book_progress[$book_id] ?? 0;
            
            if ($current_page > $previous_page) {
                $date_pages[$date_key] += ($current_page - $previous_page);
            }
            
            $book_progress[$book_id] = $current_page;
        }
        
        // データが少ない場合のフォールバック処理
        // 本棚に本がない場合はサンプルデータを生成しない
        $has_books_sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ?";
        $book_count = $g_db->getOne($has_books_sql, [$user_id]);
        if (DB::isError($book_count)) {
            $book_count = 0;
        }
        
        // 本が登録されていない場合は空のデータを返す
        if ($book_count == 0) {
            return [];
        }
        
        // 日付範囲で配列を作成（累積計算付き）
        $result = [];
        $cumulative = $base_pages; // ベース値から開始
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', $current_time - ($i * 24 * 60 * 60));
            $daily_pages = $date_pages[$date] ?? 0;
            $cumulative += $daily_pages;
            
            $result[] = [
                'date' => $date,
                'pages' => $daily_pages,
                'cumulative_pages' => $cumulative
            ];
        }
        
        return $result;
        
    } catch (Exception $e) {
        return [];
    }
}

// プロファイル写真取得用のヘルパー関数


// 人気のタグを取得（キャッシュ機能付き）
if (isPopularTagsEnabled()) {
    $popular_tags = [];
    try {
        $tagsCacheKey = 'popular_tags_v1';
        $tagsCacheTime = 1800; // 30分キャッシュ
        $tagsBackupCacheTime = 5400; // 90分バックアップキャッシュ
        
        // キャッシュから取得を試みる
        $popular_tags = $cache->get($tagsCacheKey);
        
        if ($popular_tags === false) {
            // キャッシュがない場合はデータベースから取得
            // キャッシュテーブルから取得するため、多めに取得してもパフォーマンスへの影響は最小限
            $popular_tags = getPopularTags(30);
            
            if (!empty($popular_tags) && !DB::isError($popular_tags)) {
                // メインキャッシュとバックアップキャッシュに保存
                $cache->set($tagsCacheKey, $popular_tags, $tagsCacheTime);
                $cache->set($tagsCacheKey . '_backup', $popular_tags, $tagsBackupCacheTime);
            } else {
                // 空の場合はバックアップから復元を試みる
                $backup_tags = $cache->get($tagsCacheKey . '_backup');
                if ($backup_tags !== false && !empty($backup_tags)) {
                    $popular_tags = $backup_tags;
                    // バックアップを使用した場合、メインキャッシュにも短時間保存
                    $cache->set($tagsCacheKey, $popular_tags, 180); // 3分間
                } else {
                    $popular_tags = [];
                    // 空データはキャッシュしない（再試行を可能にする）
                    // $cache->set($tagsCacheKey, array(), 60); // キャッシュしない
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching popular tags: " . $e->getMessage());
        // エラー時もバックアップから復元を試みる
        $backup_tags = $cache->get('popular_tags_v1_backup');
        if ($backup_tags !== false && !empty($backup_tags)) {
            $popular_tags = $backup_tags;
        } else {
            $popular_tags = [];
        }
    }
} else {
    $popular_tags = [];
}

// ログイン処理は checkLogin() 関数を使用して処理される
$login_flag = checkLogin();

// ログインしている場合の情報取得
if ($login_flag) {
    $user_id = $_SESSION['AUTH_USER'];
    $d_nickname = getNickname($user_id);
    
    // モダンテンプレートを有効化
    $_SESSION['use_modern_template'] = true;
    setcookie('use_modern_template', '1', time() + (86400 * 30), '/'); // 30日間有効
    
    // ユーザーの読書統計を取得
    $user_stats = getUserReadingStats($user_id);
    $monthly_stats = getMonthlyReadingStats($user_id);
    $yearly_progress = getYearlyReadingProgress($user_id);
    $daily_progress = getDailyPageProgress($user_id, 30); // 過去30日分
    
    // 今月のランキング情報を取得
    $my_ranking_info = null;
    try {
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-t');
        
        // 自分の今月の読了冊数を取得
        $my_books_sql = "
            SELECT COUNT(DISTINCT book_id) as book_count
            FROM b_book_event
            WHERE user_id = ?
            AND event = ?
            AND event_date BETWEEN ? AND ?
        ";
        $my_book_count = $g_db->getOne($my_books_sql, [$user_id, READING_FINISH, $month_start, $month_end]);
        
        if (!DB::isError($my_book_count)) {
            $my_book_count = intval($my_book_count);
            
            if ($my_book_count > 0) {
                // 自分より上位のユーザー数を取得（ranking.phpと同じロジック）
                // 同点の場合はuser_idの昇順で順位を決定
                $rank_sql = "
                    SELECT COUNT(*) as rank FROM (
                        SELECT u.user_id, COUNT(DISTINCT be.book_id) as score
                        FROM b_user u
                        INNER JOIN b_book_event be ON u.user_id = be.user_id
                        WHERE u.diary_policy = 1
                        AND u.status = 1
                        AND be.event = ?
                        AND be.event_date BETWEEN ? AND ?
                        GROUP BY u.user_id
                        HAVING score > ? OR (score = ? AND u.user_id < ?)
                    ) as t
                ";
                $my_rank = $g_db->getOne($rank_sql, [READING_FINISH, $month_start, $month_end, $my_book_count, $my_book_count, $user_id]);
                
                if (DB::isError($my_rank) || $my_rank === null) {
                    $my_rank = 1; // エラーの場合は1位
                } else {
                    $my_rank = intval($my_rank) + 1; // 自分より上位の人数 + 1
                }
            } else {
                // 0冊の場合はランキング圏外
                $my_rank = '圏外';
            }
            
            $my_ranking_info = [
                'rank' => $my_rank,
                'book_count' => $my_book_count,
                'month' => date('n')
            ];
        }
    } catch (Exception $e) {
        error_log('Failed to get ranking info: ' . $e->getMessage());
    }
    
    // 今月の読書カレンダーデータを取得
    $current_year = date('Y');
    $current_month = date('n');
    $start_date = sprintf('%04d-%02d-01 00:00:00', $current_year, $current_month);
    $end_date = date('Y-m-d 23:59:59', strtotime('last day of ' . $current_year . '-' . $current_month));
    
    $reading_days_sql = "
        SELECT 
            DATE(be.event_date) as reading_date,
            COUNT(DISTINCT be.book_id) as book_count,
            COUNT(*) as event_count
        FROM b_book_event be
        WHERE be.user_id = ? 
        AND be.event_date >= ? 
        AND be.event_date <= ?
        AND be.event IN (?, ?, ?)
        GROUP BY DATE(be.event_date)
    ";
    
    $reading_days = $g_db->getAll($reading_days_sql, 
        [$user_id, $start_date, $end_date, READING_NOW, READING_FINISH, 4], // 4 = 進捗更新
        DB_FETCHMODE_ASSOC
    );
    
    // 読書日のマップを作成
    $reading_map = [];
    foreach ($reading_days as $day) {
        $reading_map[$day['reading_date']] = [
            'event_count' => $day['event_count'],
            'book_count' => $day['book_count']
        ];
    }
    
    // ヒートマップ用に過去3ヶ月のデータも取得
    $heatmap_start = date('Y-m-d 00:00:00', strtotime('-3 months'));
    $heatmap_end = date('Y-m-d 23:59:59');
    
    $heatmap_sql = "
        SELECT DATE(event_date) as reading_date, COUNT(DISTINCT book_id) as book_count
        FROM b_book_event
        WHERE user_id = ?
        AND event_date >= ?
        AND event_date <= ?
        AND event IN (?, ?, ?)
        GROUP BY DATE(event_date)
    ";
    
    $heatmap_data = $g_db->getAll($heatmap_sql, 
        [$user_id, $heatmap_start, $heatmap_end, READING_NOW, READING_FINISH, 4], 
        DB_FETCHMODE_ASSOC
    );
    
    // 既存のreading_mapに追加
    foreach ($heatmap_data as $day) {
        if (!isset($reading_map[$day['reading_date']])) {
            $reading_map[$day['reading_date']] = [
                'event_count' => 0,
                'book_count' => $day['book_count']
            ];
        }
    }
    
    // 今月の読書連続記録を計算
    $current_streak = 0;
    
    // データベースから直接計算（最適化版）
    try {
        $today = date('Y-m-d');
        $check_date = $today;
        $has_today_record = false;
        
        // まず今日の記録があるか確認（進捗更新も含む）
        $sql = "SELECT COUNT(*) FROM b_book_event 
                WHERE user_id = ? AND DATE(event_date) = ? 
                AND event IN (?, ?, ?)";
        $today_count = $g_db->getOne($sql, [$user_id, $today, READING_NOW, READING_FINISH, 4]); // 4 = 進捗更新
        
        if ($today_count > 0) {
            $has_today_record = true;
        } else {
            // 今日の記録がなければ昨日から開始
            $check_date = date('Y-m-d', strtotime('-1 day'));
        }
        
        // 連続記録を計算（上限を撤廃し、実際の記録がある限りカウント）
        // ただし、パフォーマンス考慮で最大3年（約1095日）まで
        for ($i = 0; $i < 1095; $i++) {
            $sql = "SELECT COUNT(*) FROM b_book_event
                    WHERE user_id = ? AND DATE(event_date) = ?
                    AND event IN (?, ?, ?)";
            $count = $g_db->getOne($sql, [$user_id, $check_date, READING_NOW, READING_FINISH, 4]); // 4 = 進捗更新

            if ($count > 0) {
                $current_streak++;
            } else {
                // 記録がない日が見つかったら終了
                break;
            }

            // 次の日（過去に遡る）
            $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
        }
    } catch (Exception $e) {
        error_log('Error calculating streak: ' . $e->getMessage());
        $current_streak = 0;
    }
    
    // 達成度システムを読み込み
    require_once('library/achievement_system.php');
    
    // モチベーション要素を計算
    $streak_milestone = getStreakMilestone($current_streak);
    $reading_level = getReadingLevel($user_stats['total_pages']);
    $monthly_pace = getMonthlyPaceRating($user_stats['this_month_books'], (int)date('j'));
    $motivational_message = getMotivationalMessage($current_streak, $reading_level['level'], $monthly_pace);
    
    // ユーザー情報を取得して年間目標読書数を取得
    $user_info = getUserInformation($user_id);
    $yearly_goal = isset($user_info['books_per_year']) && $user_info['books_per_year'] > 0 
        ? (int)$user_info['books_per_year'] 
        : 50; // デフォルト: 50冊
    $current_year = date('Y');
    $days_in_year = 365 + (date('L') ? 1 : 0); // うるう年対応
    $days_passed = date('z') + 1; // 今年の経過日数（1月1日が1日目）
    $days_remaining = $days_in_year - $days_passed;
    
    // 進捗率と予測
    $goal_progress_rate = $yearly_goal > 0 ? ($user_stats['this_year_books'] / $yearly_goal) * 100 : 0;
    $expected_books_by_now = $days_in_year > 0 ? round(($days_passed / $days_in_year) * $yearly_goal, 1) : 0;
    $books_behind_or_ahead = $user_stats['this_year_books'] - $expected_books_by_now;
    
    // 現在のペースでの年間予測冊数
    $current_pace_yearly = $days_passed > 0 ? round(($user_stats['this_year_books'] / $days_passed) * $days_in_year) : 0;
    
    // 目標達成に必要な月間ペース
    $books_remaining = max(0, $yearly_goal - $user_stats['this_year_books']);
    $months_remaining = 12 - date('n') + (date('j') / date('t')); // 残り月数（小数含む）
    $required_monthly_pace = $months_remaining > 0 ? round($books_remaining / $months_remaining, 1) : 0;
    
    // 月間目標情報を取得
    $current_month = (int)date('n');
    $current_year = (int)date('Y');
    $monthly_goal_info = getMonthlyGoal($user_id, $current_year, $current_month);
    $monthly_achievement = getMonthlyAchievement($user_id, $current_year, $current_month);
    $monthly_progress = calculateMonthlyProgress($monthly_achievement, $monthly_goal_info['goal']);
    
    // 月間目標達成履歴を取得（今年分）
    $yearly_monthly_achievements = getYearlyMonthlyAchievements($user_id, $current_year);
    
    // 読書ペース分析データを取得
    require_once('library/reading_pace_analysis.php');
    $hourly_pattern = getHourlyReadingPattern($user_id);
    $completion_rate = getCompletionRateAnalysis($user_id);
    
    // シンプルな平均読書速度を計算
    $sql = "
        SELECT 
            COUNT(*) as book_count,
            SUM(total_page) as total_pages,
            SUM(DATEDIFF(finished_date, start_date)) as total_days
        FROM b_book_list
        WHERE user_id = ?
        AND status = ?
        AND finished_date IS NOT NULL
        AND start_date IS NOT NULL
        AND total_page > 0
        AND DATEDIFF(finished_date, start_date) > 0
        AND finished_date >= DATE_SUB(NOW(), INTERVAL 180 DAY)
    ";
    $speed_data = $g_db->getRow($sql, [$user_id, READING_FINISH], DB_FETCHMODE_ASSOC);
    
    $avg_reading_speed = 0;
    if (!DB::isError($speed_data) && $speed_data && $speed_data['total_days'] > 0) {
        $avg_reading_speed = round($speed_data['total_pages'] / $speed_data['total_days']);
    }
    
    // start_dateがない場合のフォールバック
    if ($avg_reading_speed == 0) {
        $sql = "
            SELECT 
                SUM(total_page) as total_pages,
                DATEDIFF(MAX(finished_date), MIN(finished_date)) + 1 as days_span
            FROM b_book_list
            WHERE user_id = ?
            AND status = ?
            AND total_page > 0
            AND finished_date >= DATE_SUB(NOW(), INTERVAL 180 DAY)
        ";
        $fallback_data = $g_db->getRow($sql, [$user_id, READING_FINISH], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($fallback_data) && $fallback_data && $fallback_data['days_span'] > 0) {
            $avg_reading_speed = round($fallback_data['total_pages'] / $fallback_data['days_span']);
        }
    }
    
    // 最も活発な時間帯を特定
    $most_active_hour = 0;
    $max_activity = 0;
    foreach ($hourly_pattern as $hour => $days_data) {
        $hour_total = 0;
        if (is_array($days_data)) {
            foreach ($days_data as $day => $count) {
                $hour_total += $count;
            }
        }
        if ($hour_total > $max_activity) {
            $max_activity = $hour_total;
            $most_active_hour = $hour;
        }
    }

    // いいね通知を取得（最終確認時刻以降）
    require_once('library/like_helpers.php');

    // 最終確認時刻を取得
    $last_check_sql = "SELECT last_like_check FROM b_user WHERE user_id = ?";
    $last_check_result = $g_db->getRow($last_check_sql, [$user_id]);

    $last_check_timestamp = null;
    if ($last_check_result && !DB::isError($last_check_result) && !empty($last_check_result['last_like_check'])) {
        $last_check_timestamp = strtotime($last_check_result['last_like_check']);
    } else {
        // 初回の場合は過去24時間
        $last_check_timestamp = time() - 86400;
    }

    $recent_likes = getReceivedLikes($user_id, 100, $last_check_timestamp);

} else {
    $user_id = null;
    $d_nickname = '';
    $user_stats = null;
    $monthly_stats = [];
    $yearly_progress = [];
    $daily_progress = [];
    $recent_likes = [];
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// 初回ログインフラグをグローバル変数として設定（テンプレートで使用）
$GLOBALS['is_first_login'] = isset($is_first_login) ? $is_first_login : false;

// モダンテンプレートを使用してページを表示
if (isset($_SESSION['AUTH_USER'])) {
    // ログイン済みの場合はダッシュボード表示
    include(getTemplatePath('t_index.php'));
} else {
    // 未ログインの場合は情緒的なデザインのトップページ
    include(getTemplatePath('t_index_emotional.php'));
}