<?php
/**
 * Dynamic XML Sitemap Generator for ReadNest
 * 
 * This script generates a sitemap.xml file dynamically
 * to help search engines discover and index content.
 */

declare(strict_types=1);

// セキュリティ: 直接アクセスのみ許可
if (php_sapi_name() !== 'cli' && !isset($_GET['generate'])) {
    header('Content-Type: text/xml; charset=UTF-8');
}

require_once('config.php');
require_once('library/database.php');

// データベース接続
$g_db = DB_Connect();

// Base URL
$base_url = 'https://readnest.jp';

// XMLヘッダー
header('Content-Type: text/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 1. 静的ページ
$static_pages = [
    ['loc' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
    ['loc' => '/ranking', 'changefreq' => 'daily', 'priority' => '0.9'],
    ['loc' => '/popular_book.php', 'changefreq' => 'daily', 'priority' => '0.8'],
    ['loc' => '/popular_review.php', 'changefreq' => 'daily', 'priority' => '0.8'],
    ['loc' => '/activities.php', 'changefreq' => 'hourly', 'priority' => '0.8'],
    ['loc' => '/reviews.php', 'changefreq' => 'daily', 'priority' => '0.8'],
    ['loc' => '/search_review.php', 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['loc' => '/help', 'changefreq' => 'monthly', 'priority' => '0.5'],
    ['loc' => '/terms.php', 'changefreq' => 'monthly', 'priority' => '0.3'],
    ['loc' => '/register.php', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['loc' => '/announcements', 'changefreq' => 'weekly', 'priority' => '0.6'],
];

foreach ($static_pages as $page) {
    echo '<url>' . "\n";
    echo '  <loc>' . htmlspecialchars($base_url . $page['loc']) . '</loc>' . "\n";
    echo '  <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
    echo '  <priority>' . $page['priority'] . '</priority>' . "\n";
    echo '  <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '</url>' . "\n";
}

// 2. 本の詳細ページ（人気の本、最近更新された本）
$book_sql = "
    SELECT DISTINCT bl.book_id, 
           MAX(bl.update_date) as last_update
    FROM b_book_list bl
    INNER JOIN b_user u ON bl.user_id = u.user_id
    WHERE u.diary_policy = 1 
      AND u.status = 1
      AND bl.image_url IS NOT NULL 
      AND bl.image_url != ''
      AND bl.image_url NOT LIKE '%noimage%'
    GROUP BY bl.book_id
    ORDER BY last_update DESC
    LIMIT 1000
";

$books = $g_db->getAll($book_sql);
if (!DB::isError($books) && $books) {
    foreach ($books as $book) {
        echo '<url>' . "\n";
        echo '  <loc>' . htmlspecialchars($base_url . '/book/' . (string)$book['book_id']) . '</loc>' . "\n";
        echo '  <changefreq>weekly</changefreq>' . "\n";
        echo '  <priority>0.7</priority>' . "\n";
        if (!empty($book['last_update'])) {
            $lastmod = date('Y-m-d', strtotime($book['last_update']));
            echo '  <lastmod>' . $lastmod . '</lastmod>' . "\n";
        }
        echo '</url>' . "\n";
    }
}

// 3. ユーザープロフィールページ（公開設定のアクティブユーザー）
$user_sql = "
    SELECT user_id, 
           COALESCE(regist_date, create_date) as last_update
    FROM b_user
    WHERE diary_policy = 1 
      AND status = 1
      AND nickname IS NOT NULL
      AND nickname != ''
    ORDER BY last_update DESC
    LIMIT 500
";

$users = $g_db->getAll($user_sql);
if (!DB::isError($users) && $users) {
    foreach ($users as $user) {
        echo '<url>' . "\n";
        echo '  <loc>' . htmlspecialchars($base_url . '/user/' . (string)$user['user_id']) . '</loc>' . "\n";
        echo '  <changefreq>weekly</changefreq>' . "\n";
        echo '  <priority>0.6</priority>' . "\n";
        if (!empty($user['last_update'])) {
            $lastmod = date('Y-m-d', strtotime($user['last_update']));
            echo '  <lastmod>' . $lastmod . '</lastmod>' . "\n";
        }
        echo '</url>' . "\n";
    }
}

// 4. タグページ（人気のタグ）
$tag_sql = "
    SELECT tag_name, COUNT(*) as tag_count
    FROM b_book_tags
    GROUP BY tag_name
    HAVING tag_count > 5
    ORDER BY tag_count DESC
    LIMIT 100
";

$tags = $g_db->getAll($tag_sql);
if (!DB::isError($tags) && $tags) {
    foreach ($tags as $tag) {
        echo '<url>' . "\n";
        echo '  <loc>' . htmlspecialchars($base_url . '/tag/' . urlencode($tag['tag_name'])) . '</loc>' . "\n";
        echo '  <changefreq>weekly</changefreq>' . "\n";
        echo '  <priority>0.5</priority>' . "\n";
        echo '</url>' . "\n";
    }
}

// 5. 著者ページ（本が多い著者）
$author_sql = "
    SELECT author, COUNT(DISTINCT book_id) as book_count
    FROM b_book_list
    WHERE author IS NOT NULL 
      AND author != ''
    GROUP BY author
    HAVING book_count > 3
    ORDER BY book_count DESC
    LIMIT 200
";

$authors = $g_db->getAll($author_sql);
if (!DB::isError($authors) && $authors) {
    foreach ($authors as $author) {
        echo '<url>' . "\n";
        echo '  <loc>' . htmlspecialchars($base_url . '/search_book_by_author.php?author=' . urlencode($author['author'])) . '</loc>' . "\n";
        echo '  <changefreq>monthly</changefreq>' . "\n";
        echo '  <priority>0.5</priority>' . "\n";
        echo '</url>' . "\n";
    }
}

echo '</urlset>' . "\n";

// データベース接続を閉じる
// DB_PDOクラスにはdisconnect()メソッドがないため、コメントアウト
// if (isset($g_db) && is_object($g_db)) {
//     $g_db->disconnect();
// }