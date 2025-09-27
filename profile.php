<?php
/**
 * モダン版プロフィールページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');
require_once('library/achievement_system.php');
require_once('library/level_display_helper.php');

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = null;
$d_nickname = '';

if ($login_flag) {
    $mine_user_id = $_SESSION['AUTH_USER'];
    $d_nickname = getNickname($mine_user_id);
}

// プロフィール表示対象のユーザーID取得
$target_user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? $mine_user_id ?? '';

if (empty($target_user_id)) {
    header('Location: https://readnest.jp');
    exit;
}

// ユーザー情報取得
$user_info = getUserInformation($target_user_id);

if (!$user_info) {
    header('Location: https://readnest.jp');
    exit;
}

// プライベート設定チェック
if ($user_info['diary_policy'] != 1 && $target_user_id !== $mine_user_id) {
    $d_site_title = "プロフィール - ReadNest";
    $g_meta_description = "このユーザーのプロフィールは非公開に設定されています。";
    $g_meta_keyword = "プロフィール,ReadNest";
    
    $profile_accessible = false;
    $target_nickname = 'ユーザー';
} else {
    $profile_accessible = true;
    $target_nickname = getNickname($target_user_id);
    
    // ページタイトル設定
    $d_site_title = "{$target_nickname}さんのプロフィール - ReadNest";
    
    // メタ情報
    $g_meta_description = "{$target_nickname}さんのReadNestプロフィール。読書記録や本棚をご覧いただけます。";
    $g_meta_keyword = "{$target_nickname},プロフィール,読書記録,本棚,ReadNest";
}

// SEOヘルパーを読み込み
require_once('library/seo_helpers.php');

// SEOデータの準備（プロフィールがアクセス可能な場合のみ）
if ($profile_accessible) {
    $canonical_url = getBaseUrl() . '/user/' . $target_user_id;
    $og_image = (!empty($user_info['profile_photo_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user_info['profile_photo_path']))
        ? getBaseUrl() . $user_info['profile_photo_path']
        : getBaseUrl() . '/img/og-image.jpg';
    
    $seo_data = [
        'title' => $d_site_title,
        'description' => $g_meta_description,
        'canonical_url' => $canonical_url,
        'og' => [
            'title' => "{$target_nickname}さんのプロフィール",
            'description' => $g_meta_description,
            'url' => $canonical_url,
            'image' => $og_image,
            'type' => 'profile'
        ],
        'twitter' => [
            'title' => "{$target_nickname}さんのプロフィール",
            'description' => $g_meta_description,
            'image' => $og_image
        ]
    ];
    
    // 構造化データの生成
    $person_schema = generatePersonSchema([
        'nickname' => $target_nickname,
        'user_id' => $target_user_id,
        'profile_text' => $user_info['profile_text'] ?? ''
    ]);
    
    // パンくずリストの構造化データ
    $breadcrumb_schema = generateBreadcrumbSchema([
        ['name' => 'ホーム', 'url' => getBaseUrl()],
        ['name' => $target_nickname, 'url' => $canonical_url]
    ]);
    
    $seo_data['schema'] = [$person_schema, $breadcrumb_schema];
    
    // SEOタグの生成
    $g_seo_tags = generateSEOTags($seo_data);
}

// プロフィール写真URL
$profile_photo_url = '/img/noimage.jpg';
if ($profile_accessible && 
    $user_info['photo'] && 
    $user_info['photo_state'] == PHOTO_REGISTER_STATE) {
    $profile_photo_url = "/display_profile_photo.php?user_id={$target_user_id}&mode=thumbnail";
}

// 読書統計取得
$reading_stats = null;
$recent_books = [];
$reading_progress = [];
$user_level_info = null;

if ($profile_accessible) {
    // 読書統計
    try {
        global $g_db;
        
        // 総読書数
        $total_books = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status IN (?, ?)", 
                                   [$target_user_id, READING_FINISH, READ_BEFORE]);
        
        // 今年読んだ本（finished_dateベース、なければupdate_dateを使用）
        $this_year_books_sql = "SELECT COUNT(*) FROM b_book_list 
                               WHERE user_id = ? AND status IN (?, ?)
                               AND (
                                   (finished_date IS NOT NULL AND YEAR(finished_date) = YEAR(NOW()))
                                   OR (finished_date IS NULL 
                                       AND update_date IS NOT NULL 
                                       AND update_date != '0000-00-00 00:00:00'
                                       AND update_date != '0000-00-00'
                                       AND update_date > '1970-01-01 00:00:00'
                                       AND YEAR(update_date) = YEAR(NOW()))
                               )";
        $this_year_books = $g_db->getOne($this_year_books_sql, 
                                       [$target_user_id, READING_FINISH, READ_BEFORE]);
        
        // 現在読書中の本
        $reading_now = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND status = ?", 
                                   [$target_user_id, READING_NOW]);
        
        // 総読書ページ数
        $total_pages = $g_db->getOne("SELECT SUM(total_page) FROM b_book_list WHERE user_id = ? AND status IN (?, ?) AND total_page > 0", 
                                   [$target_user_id, READING_FINISH, READ_BEFORE]);
        
        // レビュー総数
        $total_reviews = $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE user_id = ? AND (rating > 0 OR (memo IS NOT NULL AND memo != ''))", 
                                      [$target_user_id]);
        
        $reading_stats = [
            'total_books' => intval($total_books ?? 0),
            'this_year_books' => intval($this_year_books ?? 0),
            'reading_now' => intval($reading_now ?? 0),
            'total_pages' => intval($total_pages ?? 0),
            'total_reviews' => intval($total_reviews ?? 0)
        ];
        
        // ユーザーレベル情報を取得
        $user_level_info = getReadingLevel($reading_stats['total_pages']);
        
        // 最近読んだ本（b_book_repositoryから著者情報も取得）
        $recent_books_sql = "SELECT bl.book_id, bl.name, 
                           COALESCE(br.author, bl.author, '') as author,
                           bl.image_url, bl.update_date, bl.status
                           FROM b_book_list bl
                           LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                           WHERE bl.user_id = ? AND bl.status IN (?, ?)
                           AND bl.update_date IS NOT NULL 
                           AND bl.update_date != '0000-00-00 00:00:00'
                           AND bl.update_date != '0000-00-00'
                           AND bl.update_date > '1970-01-01 00:00:00'
                           ORDER BY bl.update_date DESC
                           LIMIT 12";
        $recent_books = $g_db->getAll($recent_books_sql, [$target_user_id, READING_FINISH, READING_NOW]);
        
        // 月別読書進捗（過去12ヶ月）
        // finished_dateがある場合はそちらを優先、なければupdate_dateを使用
        $progress_sql = "SELECT month, SUM(count) as count FROM (
                            -- finished_dateベースの集計
                            SELECT DATE_FORMAT(finished_date, '%Y-%m') as month, COUNT(*) as count 
                            FROM b_book_list 
                            WHERE user_id = ? AND status IN (?, ?)
                            AND finished_date IS NOT NULL 
                            AND finished_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                            GROUP BY DATE_FORMAT(finished_date, '%Y-%m')
                            
                            UNION ALL
                            
                            -- update_dateベースの集計（finished_dateがないもの）
                            SELECT DATE_FORMAT(update_date, '%Y-%m') as month, COUNT(*) as count 
                            FROM b_book_list 
                            WHERE user_id = ? AND status IN (?, ?)
                            AND finished_date IS NULL
                            AND update_date IS NOT NULL 
                            AND update_date != '0000-00-00 00:00:00'
                            AND update_date != '0000-00-00'
                            AND update_date > '1970-01-01 00:00:00'
                            AND update_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                            GROUP BY DATE_FORMAT(update_date, '%Y-%m')
                        ) as combined
                        GROUP BY month
                        ORDER BY month";
        $reading_progress = $g_db->getAll($progress_sql, [
            $target_user_id, READING_FINISH, READ_BEFORE,
            $target_user_id, READING_FINISH, READ_BEFORE
        ]);
        
    } catch (Exception $e) {
        error_log("Error getting profile data: " . $e->getMessage());
        $reading_stats = [
            'total_books' => 0,
            'this_year_books' => 0,
            'reading_now' => 0,
            'total_pages' => 0
        ];
    }
    
}

// 自分のプロフィールかどうか
$is_own_profile = ($login_flag && $mine_user_id === $target_user_id);

// お気に入り本を取得（公開されているもののみ）
$favorite_books = [];
if ($profile_accessible) {
    require_once(dirname(__FILE__) . '/library/favorite_functions.php');
    // 自分のプロフィールの場合はすべて、他人の場合は公開のみ
    $public_only = !$is_own_profile;
    $favorite_books = getUserFavoriteBooks($target_user_id, 12, 0, $public_only);
}

// 読書傾向分析を取得（公開されているものまたは自分のもの）
if ($profile_accessible) {
    $reading_analysis = null;
    if ($is_own_profile) {
        // 自分のプロフィールの場合は最新のものを取得
        $reading_analysis = getLatestReadingAnalysis($target_user_id, 'trend');
    } else {
        // 他人のプロフィールの場合は公開されているもののみ
        $analysis_sql = "SELECT * FROM b_reading_analysis 
                        WHERE user_id = ? AND analysis_type = 'trend' AND is_public = 1 
                        ORDER BY created_at DESC 
                        LIMIT 1";
        $reading_analysis = $g_db->getRow($analysis_sql, array($target_user_id), DB_FETCHMODE_ASSOC);
    }
}

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_profile.php'));
?>