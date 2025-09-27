<?php
/**
 * 読書活動一覧ページ
 * 全ユーザーの読書活動を表示
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// グローバル変数
global $g_db;

// キャッシュライブラリを読み込み
require_once(dirname(__FILE__) . '/library/cache.php');
require_once(dirname(__FILE__) . '/library/adaptive_cache.php');
$cache = getAdaptiveCache();

// レベル表示関連
require_once(dirname(__FILE__) . '/library/achievement_system.php');
require_once(dirname(__FILE__) . '/library/level_display_helper.php');
require_once(dirname(__FILE__) . '/library/optimized_pagination.php');

// ページネーション設定
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 30;

// 最適化されたページネーションを使用
$pagination = new OptimizedPagination($g_db, $per_page);
$pagination->setCurrentPage($page);

// 大量データの場合は推定カウントを使用（100万件以上の場合）
$quick_count_check = $g_db->getOne("SELECT COUNT(*) FROM b_book_event LIMIT 1");
if (!DB::isError($quick_count_check) && $quick_count_check > 1000000) {
    $pagination->useEstimateCount(true);
}

$offset = $pagination->getOffset();

// フィルター条件
$activity_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, started, progress, finished

// 活動総数を取得
$count_sql = "SELECT COUNT(*) as total 
              FROM b_book_event be 
              INNER JOIN b_user u ON be.user_id = u.user_id 
              WHERE u.diary_policy = 1";

$params = array();
if ($activity_type !== 'all') {
    switch ($activity_type) {
        case 'started':
            // 本を買った、または読み始めたイベント
            $count_sql .= " AND be.event IN (" . NOT_STARTED . ", " . READING_NOW . ")";
            break;
        case 'progress':
            // 読書中のイベントのみ
            $count_sql .= " AND be.event = " . READING_NOW;
            break;
        case 'finished':
            // 読了イベント
            $count_sql .= " AND be.event IN (" . READING_FINISH . ", " . READ_BEFORE . ")";
            break;
    }
}

// 最適化されたページネーションで総件数を取得
$total_count = $pagination->getTotalCount($count_sql, $params);
$total_pages = $pagination->getTotalPages();

// 活動を取得（適応型キャッシュ対応）
// キャッシュバージョンを追加（コード変更時にインクリメント）
$cacheVersion = 'v2_20250802'; // 日付を含めることで管理しやすくする
$activitiesCacheKey = 'activities_' . $cacheVersion . '_' . md5($activity_type . '_' . $page);
$activities = array(); // 初期化

$cachedData = $cache->get($activitiesCacheKey, 'activities');
if ($cachedData !== false) {
    $activities = $cachedData;
} else {
    // プロフィール画像URLもJOINで取得（N+1問題の解決）
    // book_idを直接使用して本の情報を取得
    $activities_sql = "SELECT 
        be.event_id,
        be.book_id,
        be.user_id,
        be.event_date,
        be.event,
        be.page,
        be.memo as comment,
        COALESCE(bl.name, CONCAT('Book ID: ', be.book_id)) as book_title,
        COALESCE(br.author, bl.author, '') as author,
        COALESCE(bl.image_url, '') as image_url,
        bl.amazon_id,
        u.nickname,
        u.photo as user_photo
    FROM b_book_event be
    LEFT JOIN b_book_list bl ON be.book_id = bl.book_id AND be.user_id = bl.user_id
    LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
    INNER JOIN b_user u ON be.user_id = u.user_id
    WHERE u.diary_policy = 1";

    if ($activity_type !== 'all') {
        switch ($activity_type) {
            case 'started':
                // 本を買った、または読み始めたイベント
                $activities_sql .= " AND be.event IN (" . NOT_STARTED . ", " . READING_NOW . ")";
                break;
            case 'progress':
                // 読書中のイベントのみ
                $activities_sql .= " AND be.event = " . READING_NOW;
                break;
            case 'finished':
                // 読了イベント
                $activities_sql .= " AND be.event IN (" . READING_FINISH . ", " . READ_BEFORE . ")";
                break;
        }
    }

    $activities_sql .= " ORDER BY be.event_date DESC" . $pagination->getLimitClause();

    $activities = $g_db->getAll($activities_sql, array(), DB_FETCHMODE_ASSOC);
    if (DB::isError($activities)) {
        error_log('Database error in activities.php activities query: ' . $activities->getMessage());
        $activities = array();
    } else {
        // 適応型キャッシュに保存（10分間のみ有効）
        // 短めの有効期限で自動更新を促進
        $cache->set($activitiesCacheKey, $activities, 'activities', 600);
    }
}

// 活動データをフォーマット
$formatted_activities = array();
if ($activities && !DB::isError($activities)) {
    foreach ($activities as $activity) {
        $activity_type_text = '';
        $activity_color = '';
        
        switch ($activity['event']) {
            case NOT_STARTED:
                $activity_type_text = '未読';
                $activity_color = 'blue';
                break;
            case READING_NOW:
                $activity_type_text = '読書中';
                $activity_color = 'yellow';
                break;
            case READING_FINISH:
                $activity_type_text = '読了';
                $activity_color = 'green';
                break;
            case READ_BEFORE:
                $activity_type_text = '既読';
                $activity_color = 'green';
                break;
            default:
                $activity_type_text = '更新';
                $activity_color = 'gray';
        }
        
        // プロフィール画像URLを決定（N+1問題を回避）
        $user_photo_url = '';
        if (!empty($activity['user_photo'])) {
            // データベースから画像を表示（thumbnailモードで適切なサイズ）
            $user_photo_url = '/display_profile_photo.php?user_id=' . $activity['user_id'] . '&mode=thumbnail';
        } else {
            $user_photo_url = '/img/no-image-user.png';
        }
        
        $formatted_activities[] = array(
            'event_id' => $activity['event_id'],
            'book_id' => $activity['book_id'],
            'user_id' => $activity['user_id'],
            'user_name' => $activity['nickname'],
            'user_photo' => $user_photo_url,
            'book_title' => $activity['book_title'] ?: 'タイトル不明',
            'author' => $activity['author'] ?: '著者不明',
            'book_image' => $activity['image_url'] ?: '/img/no-image-book.png',
            'type' => $activity_type_text,
            'type_color' => $activity_color,
            'page' => $activity['page'],
            'comment' => $activity['comment'],
            'activity_date' => formatRelativeTime($activity['event_date']),
            'timestamp' => $activity['event_date']
        );
    }
}

// ユーザーレベル情報を一括取得
if (!empty($formatted_activities)) {
    $user_ids = array_unique(array_column($formatted_activities, 'user_id'));
    $user_levels = getUsersLevels($user_ids);
    
    // 各活動にレベル情報を追加
    foreach ($formatted_activities as &$activity) {
        $activity['user_level'] = $user_levels[$activity['user_id']] ?? getReadingLevel(0);
    }
    unset($activity);
}

// 統計情報を取得
$stats = array(
    'today' => 0,
    'week' => 0,
    'month' => 0
);

// DATETIME形式で日付を作成
$today_start = date('Y-m-d 00:00:00');
$week_start = date('Y-m-d 00:00:00', strtotime('-7 days'));
$month_start = date('Y-m-d 00:00:00', strtotime('-30 days'));

$stats_sql = "SELECT 
    COUNT(CASE WHEN event_date >= ? THEN 1 END) as today,
    COUNT(CASE WHEN event_date >= ? THEN 1 END) as week,
    COUNT(CASE WHEN event_date >= ? THEN 1 END) as month
FROM b_book_event be
INNER JOIN b_user u ON be.user_id = u.user_id
WHERE u.diary_policy = 1";

$stats_result = $g_db->getAll($stats_sql, array($today_start, $week_start, $month_start), DB_FETCHMODE_ASSOC);
if (DB::isError($stats_result)) {
    error_log('Database error in activities.php stats query: ' . $stats_result->getMessage());
    $stats_result = array();
}
if ($stats_result && count($stats_result) > 0) {
    $stats = $stats_result[0];
}


// 日付フォーマット関数は library/date_helpers.php の formatRelativeTime() を使用

// ページタイトル
$d_site_title = 'みんなの読書活動 - ReadNest';

// SEO設定
$d_meta_description = 'ReadNestユーザーの読書活動をリアルタイムで確認。誰がどんな本を読んでいるか、読書の進捗状況をチェックしよう。';
$d_meta_keywords = '読書活動,読書記録,読書進捗,読書履歴,ReadNest';

// ページネーションリンクを生成
$pagination_links = $pagination->generateLinks('/activities.php', ['type' => $activity_type]);

// テンプレートを使用
include(getTemplatePath('t_activities.php'));
?>