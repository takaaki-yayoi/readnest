<?php
/**
 * 新刊チェックcronジョブ
 *
 * お気に入り作家の新刊をGoogle Books APIで検出し、ユーザーに通知する。
 * 1回の実行で最大20作家をチェック（API負荷軽減のため）。
 *
 * crontab:
 * 0 6 * * * cd /path/to/readnest && php cron/check_new_releases.php >> /var/log/readnest/new_releases.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/notification_helpers.php');
require_once(dirname(__DIR__) . '/library/google_books_api.php');
require_once(dirname(__DIR__) . '/library/push_helper.php');

$g_db = DB_Connect();
if (!$g_db || DB::isError($g_db)) {
    error_log('[NewReleases] Database connection failed');
    exit(1);
}

$start_time = microtime(true);
$authors_checked = 0;
$new_books_found = 0;
$notifications_sent = 0;

error_log('[NewReleases] Starting at ' . date('Y-m-d H:i:s'));

// テーブルが存在しなければ作成
$g_db->query("
    CREATE TABLE IF NOT EXISTS b_author_books_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        author_name VARCHAR(255) NOT NULL,
        book_title VARCHAR(500) NOT NULL,
        published_date VARCHAR(20) DEFAULT NULL,
        image_url TEXT DEFAULT NULL,
        google_books_id VARCHAR(100) DEFAULT NULL,
        first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_author (author_name),
        UNIQUE KEY uk_author_title (author_name, book_title(200))
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
");

// notification_typeにnew_releaseを追加（既に存在してもエラーにならないようtry）
$g_db->query("
    ALTER TABLE b_notifications
    MODIFY COLUMN notification_type ENUM('like', 'monthly_report', 'new_release') NOT NULL
");

// 全ユーザーのお気に入り作家を集約（2冊以上読了 or 平均4.0以上）
$fav_authors_sql = "
    SELECT
        br.author,
        COUNT(DISTINCT bl.user_id) as fan_count
    FROM b_book_list bl
    INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
    INNER JOIN b_user u ON bl.user_id = u.user_id
    WHERE u.status = 1
    AND bl.status IN (" . READING_FINISH . ", " . READ_BEFORE . ")
    AND br.author IS NOT NULL AND br.author != ''
    GROUP BY br.author
    HAVING COUNT(DISTINCT bl.amazon_id) >= 2
    ORDER BY fan_count DESC
    LIMIT 50
";
$all_fav_authors = $g_db->getAll($fav_authors_sql, [], DB_FETCHMODE_ASSOC);

if (DB::isError($all_fav_authors) || empty($all_fav_authors)) {
    error_log('[NewReleases] No favorite authors found');
    exit(0);
}

// 最近チェックした作家を除外（24時間以内）
$recently_checked_sql = "
    SELECT DISTINCT author_name
    FROM b_author_books_cache
    WHERE first_seen_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
";
$recently_checked = $g_db->getAll($recently_checked_sql, [], DB_FETCHMODE_ASSOC);
$skip_authors = [];
if (!DB::isError($recently_checked)) {
    $skip_authors = array_column($recently_checked, 'author_name');
}

$google_api = new GoogleBooksAPI();

foreach ($all_fav_authors as $author_row) {
    if ($authors_checked >= 20) break; // 1回の実行で最大20作家
    $author_name = $author_row['author'];

    // 最近チェック済みならスキップ
    if (in_array($author_name, $skip_authors)) continue;

    $authors_checked++;
    error_log("[NewReleases] Checking: {$author_name}");

    try {
        $works = $google_api->searchByAuthor($author_name, 10);
    } catch (Exception $e) {
        error_log("[NewReleases] API error for {$author_name}: " . $e->getMessage());
        continue;
    }

    if (empty($works)) continue;

    foreach ($works as $work) {
        if (empty($work['title'])) continue;

        // キャッシュに挿入を試みる（重複はUNIQUEキーで無視）
        $insert_sql = "
            INSERT IGNORE INTO b_author_books_cache
            (author_name, book_title, published_date, image_url, google_books_id)
            VALUES (?, ?, ?, ?, ?)
        ";
        $google_id = '';
        if (!empty($work['industryIdentifiers'])) {
            foreach ($work['industryIdentifiers'] as $id) {
                if ($id['type'] === 'ISBN_13' || $id['type'] === 'ISBN_10') {
                    $google_id = $id['identifier'];
                    break;
                }
            }
        }
        $image_url = $work['imageLinks']['thumbnail'] ?? '';

        $result = $g_db->query($insert_sql, [
            $author_name,
            $work['title'],
            $work['publishedDate'] ?? null,
            $image_url,
            $google_id
        ]);

        if (!DB::isError($result)) {
            $affected = $g_db->getOne("SELECT ROW_COUNT()");
            if (!DB::isError($affected) && intval($affected) > 0) {
                // 新しい本が見つかった
                $new_books_found++;

                // 出版日が1年以内の場合のみ「新刊」として通知
                $is_recent = false;
                if (!empty($work['publishedDate'])) {
                    $pub_year = intval(substr($work['publishedDate'], 0, 4));
                    if ($pub_year >= intval(date('Y')) - 1) {
                        $is_recent = true;
                    }
                }

                if (!$is_recent) continue;

                // この作家のファンユーザーに通知
                $fans_sql = "
                    SELECT DISTINCT bl.user_id
                    FROM b_book_list bl
                    INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
                    INNER JOIN b_user u ON bl.user_id = u.user_id
                    WHERE br.author = ?
                    AND u.status = 1
                    AND bl.status IN (" . READING_FINISH . ", " . READ_BEFORE . ")
                    GROUP BY bl.user_id
                    HAVING COUNT(DISTINCT bl.amazon_id) >= 2
                ";
                $fans = $g_db->getAll($fans_sql, [$author_name], DB_FETCHMODE_ASSOC);

                if (!DB::isError($fans) && !empty($fans)) {
                    foreach ($fans as $fan) {
                        $notif_id = createNotification(
                            $fan['user_id'],
                            'new_release',
                            "{$author_name}の新刊『{$work['title']}』",
                            [
                                'message' => "お気に入り作家の新しい作品が見つかりました",
                                'link_url' => '/add_book.php?keyword=' . urlencode($work['title']),
                                'data' => [
                                    'author' => $author_name,
                                    'title' => $work['title'],
                                    'image_url' => $image_url,
                                    'published_date' => $work['publishedDate'] ?? null
                                ]
                            ]
                        );
                        if ($notif_id) {
                            $notifications_sent++;
                            sendPushIfOptedIn((int)$fan['user_id'], [
                                'title' => "📚 {$author_name}の新刊",
                                'body' => "『{$work['title']}』が見つかりました",
                                'url' => '/new_releases.php',
                                'tag' => 'new-release-' . $notif_id,
                            ]);
                        }
                    }
                }
            }
        }
    }

    // API負荷軽減のため少し待つ
    usleep(500000); // 0.5秒
}

$elapsed = round(microtime(true) - $start_time, 2);
$summary = "[NewReleases] Done in {$elapsed}s: {$authors_checked} authors checked, {$new_books_found} new books, {$notifications_sent} notifications";
error_log($summary);

// cronログに記録
$g_db->query(
    "INSERT INTO b_cron_log (cron_type, status, message, execution_time, created_at) VALUES (?, ?, ?, ?, ?)",
    ['check_new_releases', 'success', $summary, intval($elapsed * 1000), time()]
);
