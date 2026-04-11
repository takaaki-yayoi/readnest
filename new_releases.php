<?php
/**
 * 新刊情報ページ
 * お気に入り作家の新刊通知を一覧表示
 */

declare(strict_types=1);

require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /login.php');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];
$d_nickname = getNickname($mine_user_id);

global $g_db;

// ページネーション
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 30;
$offset = ($page - 1) * $per_page;

// フィルター: all（全て）, unread（未読通知のみ）, recent（直近30日の新刊）
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// ユーザーのお気に入り作家を取得（2冊以上読了 or 平均4.0以上）
$fav_authors_sql = "
    SELECT
        br.author,
        COUNT(DISTINCT bl.amazon_id) as book_count,
        AVG(CASE WHEN bl.rating > 0 THEN bl.rating ELSE NULL END) as avg_rating
    FROM b_book_list bl
    INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
    WHERE bl.user_id = ?
    AND bl.status IN (" . READING_NOW . ", " . READING_FINISH . ", " . READ_BEFORE . ")
    AND br.author IS NOT NULL AND br.author != ''
    GROUP BY br.author
    HAVING book_count >= 2 OR avg_rating >= 4.0
    ORDER BY avg_rating DESC, book_count DESC
";
$fav_authors_rows = $g_db->getAll($fav_authors_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
$fav_authors = [];
if (!DB::isError($fav_authors_rows) && !empty($fav_authors_rows)) {
    foreach ($fav_authors_rows as $row) {
        $fav_authors[$row['author']] = $row;
    }
}

// 新刊通知を取得
$notifications = [];
$total_count = 0;

if (!empty($fav_authors)) {
    // 新刊通知の取得
    if ($filter === 'unread') {
        $where_extra = "AND n.is_read = 0";
    } else {
        $where_extra = "";
    }

    $count_sql = "
        SELECT COUNT(*)
        FROM b_notifications n
        WHERE n.user_id = ?
        AND n.notification_type = 'new_release'
        {$where_extra}
    ";
    $total_count = $g_db->getOne($count_sql, [$mine_user_id]);
    if (DB::isError($total_count)) {
        $total_count = 0;
    }

    $notif_sql = "
        SELECT n.notification_id, n.title, n.message, n.data, n.link_url,
               n.is_read, n.created_at
        FROM b_notifications n
        WHERE n.user_id = ?
        AND n.notification_type = 'new_release'
        {$where_extra}
        ORDER BY n.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $notif_rows = $g_db->getAll($notif_sql, [$mine_user_id, $per_page, $offset], DB_FETCHMODE_ASSOC);
    if (!DB::isError($notif_rows) && !empty($notif_rows)) {
        foreach ($notif_rows as $row) {
            $data = json_decode($row['data'], true);
            $row['parsed_data'] = $data ?: [];
            $notifications[] = $row;
        }
    }
}

$total_pages = $total_count > 0 ? (int)ceil($total_count / $per_page) : 0;

// お気に入り作家のキャッシュされた著作情報も取得（通知がなくても表示用）
$cached_books = [];
if (!empty($fav_authors)) {
    $author_names = array_keys($fav_authors);
    $placeholders = implode(',', array_fill(0, count($author_names), '?'));

    // 直近1年以内に出版された本
    $cache_sql = "
        SELECT author_name, book_title, published_date, image_url, google_books_id, first_seen_at
        FROM b_author_books_cache
        WHERE author_name IN ({$placeholders})
        AND published_date IS NOT NULL
        AND CAST(SUBSTRING(published_date, 1, 4) AS UNSIGNED) >= ?
        ORDER BY published_date DESC, first_seen_at DESC
        LIMIT 50
    ";
    $params = array_merge($author_names, [intval(date('Y')) - 1]);
    $cache_rows = $g_db->getAll($cache_sql, $params, DB_FETCHMODE_ASSOC);

    if (!DB::isError($cache_rows) && !empty($cache_rows)) {
        // ユーザーが既に持っている本のタイトルを取得
        $owned_titles_sql = "
            SELECT DISTINCT bl.name
            FROM b_book_list bl
            WHERE bl.user_id = ?
            AND bl.name IS NOT NULL AND bl.name != ''
        ";
        $owned_rows = $g_db->getAll($owned_titles_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
        $owned_titles = [];
        if (!DB::isError($owned_rows)) {
            $owned_titles = array_column($owned_rows, 'name');
        }

        foreach ($cache_rows as $row) {
            // 既に本棚にある本は除外
            $is_owned = false;
            foreach ($owned_titles as $owned) {
                if (mb_strpos($row['book_title'], $owned) !== false || mb_strpos($owned, $row['book_title']) !== false) {
                    $is_owned = true;
                    break;
                }
            }
            if (!$is_owned) {
                $cached_books[] = $row;
            }
        }
    }
}

// 未読通知数
$unread_count = 0;
$unread_sql = "SELECT COUNT(*) FROM b_notifications WHERE user_id = ? AND notification_type = 'new_release' AND is_read = 0";
$unread_result = $g_db->getOne($unread_sql, [$mine_user_id]);
if (!DB::isError($unread_result)) {
    $unread_count = intval($unread_result);
}

// ページタイトル
$d_site_title = '新刊情報 - ReadNest';
$g_meta_description = 'お気に入り作家の新刊情報をチェック。新しい作品の発売を見逃さない。';
$g_meta_keyword = '新刊,新刊情報,お気に入り作家,ReadNest';

// テンプレートを使用
include(getTemplatePath('t_new_releases.php'));
?>
