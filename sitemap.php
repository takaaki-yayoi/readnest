<?php
/**
 * Dynamic XML Sitemap Generator for ReadNest
 *
 * /sitemap.xml           サイトマップインデックス
 * /sitemap-{type}-{n}.xml  各サイトマップ本体（.htaccess で ?type=&p= に書き換え）
 *
 * 収録方針
 * - 作家は b_author_stats_cache から。このテーブルは author.php と同じ結合条件
 *   （公開ユーザーの蔵書に存在する b_book_repository.author）で cron が作るため、
 *   ここに載っている作家は author.php が必ず中身を返す。
 * - 書籍は /book_entity/{asin} を使う。/book/{book_id} は b_book_list の行 ID、
 *   つまり「あるユーザーの1冊」なので、同じ本でも読者の数だけ URL が生える
 *   （例: カッコウの卵は誰のもの = /book/262168, /book/302147, /book/333017）。
 *   薄い重複ページを Google に推薦しないため、書籍単位で一意な entity 側を出す。
 * - レビューも評価も無い本は載せない。中身が無いページを増やしてもクロール予算を
 *   食うだけで、インデックスされない。
 * - ユーザープロフィール /user/{id} は載せない。サイト内公開の同意と検索エンジンへの
 *   露出の同意は別物であり、かつ検索流入にも寄与していないため。
 */

declare(strict_types=1);

require_once('config.php');
require_once('library/database.php');
require_once('library/cache.php');

$g_db = DB_Connect();

$base_url = 'https://readnest.jp';

// 1ファイルあたりの URL 数。サイトマップの仕様上限は 50,000 だが、
// PHP 側のメモリと生成時間を抑えるため小さめにしている。
const SITEMAP_CHUNK_SIZE = 10000;

// 作家ページを載せる下限作品数。1作品だけの作家はページが薄くなりやすい。
const SITEMAP_AUTHOR_MIN_BOOKS = 2;

// タグページを載せる下限ユーザー数。
const SITEMAP_TAG_MIN_USERS = 3;

// 件数クエリのキャッシュ TTL（秒）。インデックス生成のたびに全件 COUNT すると重い。
const SITEMAP_COUNT_TTL = 21600; // 6時間

$type = isset($_GET['type']) ? (string)$_GET['type'] : '';
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;

header('Content-Type: application/xml; charset=UTF-8');

/**
 * 静的ページの一覧。
 * 検索経由で入ってきた人が次に辿れる公開ページのみ。ログイン必須のページは載せない。
 */
function sitemapStaticPages(): array {
    return [
        ['loc' => '/',                   'changefreq' => 'daily',   'priority' => '1.0'],
        ['loc' => '/ranking',            'changefreq' => 'daily',   'priority' => '0.9'],
        ['loc' => '/popular_book.php',   'changefreq' => 'daily',   'priority' => '0.8'],
        ['loc' => '/popular_review.php', 'changefreq' => 'daily',   'priority' => '0.8'],
        ['loc' => '/activities.php',     'changefreq' => 'hourly',  'priority' => '0.8'],
        ['loc' => '/reviews.php',        'changefreq' => 'daily',   'priority' => '0.8'],
        ['loc' => '/sakka_cloud.php',    'changefreq' => 'weekly',  'priority' => '0.8'],
        ['loc' => '/search_review.php',  'changefreq' => 'weekly',  'priority' => '0.7'],
        ['loc' => '/leveling_guide.php', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ['loc' => '/help',               'changefreq' => 'monthly', 'priority' => '0.5'],
        ['loc' => '/terms.php',          'changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => '/register.php',       'changefreq' => 'monthly', 'priority' => '0.6'],
        ['loc' => '/announcements',      'changefreq' => 'weekly',  'priority' => '0.6'],
    ];
}

/**
 * b_author_stats_cache が使えるか
 */
function sitemapHasAuthorCache($db): bool {
    $exists = $db->getOne("SHOW TABLES LIKE 'b_author_stats_cache'");
    return !DB::isError($exists) && !empty($exists);
}

/**
 * getOne() の戻り値を安全に int にする。
 * DB エラー時は DB_Error オブジェクトが返るため、そのまま (int) にすると 1 になってしまう。
 */
function sitemapToInt($result): int {
    if (DB::isError($result) || $result === null || !is_scalar($result)) {
        return 0;
    }
    $n = (int)$result;
    return $n > 0 ? $n : 0;
}

/**
 * 各タイプの件数。インデックス生成用なのでキャッシュする。
 */
function sitemapCount(string $type, $db): int {
    $cache = getCache();
    $key = 'sitemap_count_' . $type;
    $cached = $cache->get($key);
    if ($cached !== false && $cached !== null) {
        return (int)$cached;
    }

    $count = 0;
    switch ($type) {
        case 'static':
            $count = count(sitemapStaticPages());
            break;

        case 'authors':
            if (sitemapHasAuthorCache($db)) {
                $sql = "SELECT COUNT(*) FROM b_author_stats_cache WHERE book_count >= ?";
                $count = sitemapToInt($db->getOne($sql, [SITEMAP_AUTHOR_MIN_BOOKS]));
            } else {
                // キャッシュテーブルが未生成のときのフォールバック
                $sql = "
                    SELECT COUNT(*) FROM (
                        SELECT br.author
                        FROM b_book_repository br
                        INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
                        INNER JOIN b_user bu ON bl.user_id = bu.user_id
                        WHERE bu.diary_policy = 1 AND bu.status = 1
                          AND br.author IS NOT NULL AND br.author != '' AND br.author != '-'
                        GROUP BY br.author
                        HAVING COUNT(DISTINCT bl.book_id) >= ?
                    ) t
                ";
                $count = sitemapToInt($db->getOne($sql, [SITEMAP_AUTHOR_MIN_BOOKS]));
            }
            break;

        case 'books':
            $sql = "
                SELECT COUNT(DISTINCT bl.amazon_id)
                FROM b_book_list bl
                INNER JOIN b_user bu ON bl.user_id = bu.user_id
                WHERE bu.diary_policy = 1 AND bu.status = 1
                  AND bl.amazon_id IS NOT NULL AND bl.amazon_id != ''
                  AND ((bl.memo IS NOT NULL AND bl.memo != '') OR bl.rating > 0)
            ";
            $count = sitemapToInt($db->getOne($sql));
            break;

        case 'tags':
            $sql = "
                SELECT COUNT(*) FROM (
                    SELECT tag_name
                    FROM b_book_tags
                    WHERE tag_name IS NOT NULL AND tag_name != ''
                    GROUP BY tag_name
                    HAVING COUNT(DISTINCT user_id) >= ?
                ) t
            ";
            $count = sitemapToInt($db->getOne($sql, [SITEMAP_TAG_MIN_USERS]));
            break;
    }

    $cache->set($key, $count, SITEMAP_COUNT_TTL);
    return $count;
}

function sitemapChunkCount(int $total): int {
    return $total > 0 ? (int)ceil($total / SITEMAP_CHUNK_SIZE) : 0;
}

function sitemapUrlTag(string $loc, string $changefreq, string $priority, ?string $lastmod = null): void {
    echo "<url>\n";
    echo '  <loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_SUBSTITUTE, "UTF-8") . "</loc>\n";
    echo '  <changefreq>' . $changefreq . "</changefreq>\n";
    echo '  <priority>' . $priority . "</priority>\n";
    if ($lastmod) {
        $ts = strtotime($lastmod);
        if ($ts) {
            echo '  <lastmod>' . date('Y-m-d', $ts) . "</lastmod>\n";
        }
    }
    echo "</url>\n";
}

// ---------------------------------------------------------------------------
// サイトマップインデックス
// ---------------------------------------------------------------------------
if ($type === '') {
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach (['static', 'authors', 'books', 'tags'] as $t) {
        $chunks = sitemapChunkCount(sitemapCount($t, $g_db));
        for ($i = 1; $i <= $chunks; $i++) {
            echo "<sitemap>\n";
            echo '  <loc>' . $base_url . '/sitemap-' . $t . '-' . $i . ".xml</loc>\n";
            echo '  <lastmod>' . date('Y-m-d') . "</lastmod>\n";
            echo "</sitemap>\n";
        }
    }

    echo '</sitemapindex>' . "\n";
    exit;
}

// ---------------------------------------------------------------------------
// 各サイトマップ本体
// ---------------------------------------------------------------------------
$offset = ($page - 1) * SITEMAP_CHUNK_SIZE;
$limit  = SITEMAP_CHUNK_SIZE;

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

switch ($type) {
    case 'static':
        foreach (sitemapStaticPages() as $p) {
            sitemapUrlTag($base_url . $p['loc'], $p['changefreq'], $p['priority'], date('Y-m-d'));
        }
        break;

    case 'authors':
        if (sitemapHasAuthorCache($g_db)) {
            $sql = "
                SELECT author, last_read_date
                FROM b_author_stats_cache
                WHERE book_count >= ?
                ORDER BY reader_count DESC, author ASC
                LIMIT {$limit} OFFSET {$offset}
            ";
            $rows = $g_db->getAll($sql, [SITEMAP_AUTHOR_MIN_BOOKS], DB_FETCHMODE_ASSOC);
        } else {
            $sql = "
                SELECT br.author AS author, MAX(bl.update_date) AS last_read_date
                FROM b_book_repository br
                INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
                INNER JOIN b_user bu ON bl.user_id = bu.user_id
                WHERE bu.diary_policy = 1 AND bu.status = 1
                  AND br.author IS NOT NULL AND br.author != '' AND br.author != '-'
                GROUP BY br.author
                HAVING COUNT(DISTINCT bl.book_id) >= ?
                ORDER BY COUNT(DISTINCT bl.user_id) DESC, br.author ASC
                LIMIT {$limit} OFFSET {$offset}
            ";
            $rows = $g_db->getAll($sql, [SITEMAP_AUTHOR_MIN_BOOKS], DB_FETCHMODE_ASSOC);
        }

        if (!DB::isError($rows) && $rows) {
            foreach ($rows as $row) {
                // author.php の canonical と同じ urlencode を使う
                sitemapUrlTag(
                    $base_url . '/author.php?name=' . urlencode((string)$row['author']),
                    'weekly',
                    '0.8',
                    $row['last_read_date'] ?? null
                );
            }
        }
        break;

    case 'books':
        // /book_entity/{asin}。/book/{book_id} は読者ごとに URL が分かれるので使わない。
        $sql = "
            SELECT bl.amazon_id AS asin, MAX(bl.update_date) AS last_update
            FROM b_book_list bl
            INNER JOIN b_user bu ON bl.user_id = bu.user_id
            WHERE bu.diary_policy = 1 AND bu.status = 1
              AND bl.amazon_id IS NOT NULL AND bl.amazon_id != ''
              AND ((bl.memo IS NOT NULL AND bl.memo != '') OR bl.rating > 0)
            GROUP BY bl.amazon_id
            ORDER BY last_update DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $rows = $g_db->getAll($sql, null, DB_FETCHMODE_ASSOC);

        if (!DB::isError($rows) && $rows) {
            foreach ($rows as $row) {
                sitemapUrlTag(
                    $base_url . '/book_entity/' . rawurlencode((string)$row['asin']),
                    'weekly',
                    '0.7',
                    $row['last_update'] ?? null
                );
            }
        }
        break;

    case 'tags':
        $sql = "
            SELECT tag_name, COUNT(DISTINCT user_id) AS user_count
            FROM b_book_tags
            WHERE tag_name IS NOT NULL AND tag_name != ''
            GROUP BY tag_name
            HAVING user_count >= ?
            ORDER BY user_count DESC, tag_name ASC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $rows = $g_db->getAll($sql, [SITEMAP_TAG_MIN_USERS], DB_FETCHMODE_ASSOC);

        if (!DB::isError($rows) && $rows) {
            foreach ($rows as $row) {
                // パスセグメントなので rawurlencode。search_book_by_tag.php の canonical と揃える。
                sitemapUrlTag(
                    $base_url . '/tag/' . rawurlencode((string)$row['tag_name']),
                    'weekly',
                    '0.5'
                );
            }
        }
        break;
}

echo '</urlset>' . "\n";
