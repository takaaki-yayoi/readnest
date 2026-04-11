<?php
/**
 * 作家紹介ページ（非ログインユーザーも閲覧可能）
 */

require_once('modern_config.php');
require_once('library/author_info_fetcher.php');

// 作家名を取得
$author_name = $_GET['name'] ?? '';

if (empty($author_name)) {
    header('Location: /');
    exit;
}

// ログインチェック（ログインは不要だが、状態を取得）
$login_flag = checkLogin();
$user_id = $login_flag ? (int)$_SESSION['AUTH_USER'] : 0;

// 作家情報を取得
$author_fetcher = new AuthorInfoFetcher();
$author_info = $author_fetcher->getAuthorInfo($author_name);

// 作家の本の統計を取得
$stats_sql = "
    SELECT 
        COUNT(DISTINCT br.asin) as total_books,
        COUNT(DISTINCT bl.user_id) as total_readers
    FROM b_book_repository br
    LEFT JOIN b_book_list bl ON br.asin = bl.amazon_id
    LEFT JOIN b_user bu ON bl.user_id = bu.user_id
    WHERE br.author = ?
    AND (bu.diary_policy = 1 OR bu.diary_policy IS NULL)
    AND (bu.status = 1 OR bu.status IS NULL)
";

$stats = $g_db->getRow($stats_sql, [$author_name], DB_FETCHMODE_ASSOC);

// 人気の本を取得（最大5冊）
$popular_books_sql = "
    SELECT 
        br.asin,
        br.title,
        br.image_url,
        COUNT(DISTINCT bl.user_id) as reader_count
    FROM b_book_repository br
    INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
    INNER JOIN b_user bu ON bl.user_id = bu.user_id
    WHERE br.author = ?
    AND bu.diary_policy = 1
    AND bu.status = 1
    GROUP BY br.asin, br.title, br.image_url
    ORDER BY reader_count DESC
    LIMIT 5
";

$popular_books = $g_db->getAll($popular_books_sql, [$author_name], DB_FETCHMODE_ASSOC);

// ログインユーザー向け：この作家のまだ持っていない著作を取得
$undiscovered_books = [];
if ($login_flag) {
    try {
        // ユーザーの本棚にあるこの作家の本タイトルを取得
        $my_books_sql = "
            SELECT DISTINCT bl.name
            FROM b_book_list bl
            WHERE bl.user_id = ?
            AND (bl.author = ? OR bl.author LIKE ?)
            AND bl.name IS NOT NULL AND bl.name != ''
        ";
        $my_book_rows = $g_db->getAll($my_books_sql, [$user_id, $author_name, '%' . $author_name . '%'], DB_FETCHMODE_ASSOC);
        $my_book_titles = [];
        if (!DB::isError($my_book_rows) && !empty($my_book_rows)) {
            $my_book_titles = array_map(function($b) { return mb_strtolower($b['name']); }, $my_book_rows);
        }

        // Google Books APIで著作一覧を取得
        require_once(dirname(__FILE__) . '/library/google_books_api.php');
        $google_api = new GoogleBooksAPI();
        $author_works = $google_api->searchByAuthor($author_name, 30);

        // 本棚にない本をフィルタリング
        foreach ($author_works as $work) {
            if (empty($work['title'])) continue;
            $work_title_lower = mb_strtolower($work['title']);

            // 本棚に同じタイトルがあるかチェック（部分一致）
            $found = false;
            foreach ($my_book_titles as $my_title) {
                if (mb_strpos($my_title, $work_title_lower) !== false || mb_strpos($work_title_lower, $my_title) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $undiscovered_books[] = $work;
            }
        }
    } catch (Exception $e) {
        error_log('Failed to get undiscovered books: ' . $e->getMessage());
    }
}

// ページメタ情報
$d_site_title = htmlspecialchars($author_name) . ' - 作家紹介 - ReadNest';
$g_meta_description = htmlspecialchars($author_name) . 'の作品一覧と読者数。ReadNestで人気の本を探そう。';
$g_meta_keyword = htmlspecialchars($author_name) . ',作家,著者,本,読書,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_author.php'));
?>