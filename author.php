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
    AND br.title IS NOT NULL
    AND br.title != ''
    AND br.asin IS NOT NULL
    AND br.asin != ''
    GROUP BY br.asin, br.title, br.image_url
    ORDER BY reader_count DESC
    LIMIT 5
";

$popular_books = $g_db->getAll($popular_books_sql, [$author_name], DB_FETCHMODE_ASSOC);
if (DB::isError($popular_books)) {
    $popular_books = [];
}

// ReadNest自身が持つ事実だけで作る「読まれ方」データ。
// Wikipediaに記事が無い作家（全体の約4割）ではここがページの中身になる。
// 以前はそういう作家にLLMで経歴を書かせていたが、根拠を与えていないため
// 実在人物の生年や代表作を捏造していた。詳細は library/author_info_fetcher.php 参照。
require_once(dirname(__FILE__) . '/library/cache.php');
$author_cache = getCache();
$author_stats_key = 'author_readstats_' . md5($author_name);
$read_stats = $author_cache->get($author_stats_key);

if ($read_stats === false) {
    $read_stats = [
        'avg_rating' => 0.0,
        'rating_count' => 0,
        'review_count' => 0,
        'tags' => [],
        'related_authors' => [],
    ];

    // 評価とレビューの集計
    $rating_sql = "
        SELECT
            AVG(CASE WHEN bl.rating > 0 THEN bl.rating END) AS avg_rating,
            COUNT(CASE WHEN bl.rating > 0 THEN 1 END) AS rating_count,
            COUNT(CASE WHEN bl.memo IS NOT NULL AND bl.memo != '' THEN 1 END) AS review_count
        FROM b_book_repository br
        INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
        INNER JOIN b_user bu ON bl.user_id = bu.user_id
        WHERE br.author = ? AND bu.diary_policy = 1 AND bu.status = 1
    ";
    $row = $g_db->getRow($rating_sql, [$author_name], DB_FETCHMODE_ASSOC);
    if (!DB::isError($row) && $row) {
        $read_stats['avg_rating']   = round((float)($row['avg_rating'] ?? 0), 1);
        $read_stats['rating_count'] = (int)($row['rating_count'] ?? 0);
        $read_stats['review_count'] = (int)($row['review_count'] ?? 0);
    }

    // 読者が付けたタグ（ジャンル傾向）
    $tag_sql = "
        SELECT bt.tag_name, COUNT(DISTINCT bt.user_id) AS user_count
        FROM b_book_tags bt
        INNER JOIN b_book_list bl ON bt.book_id = bl.book_id
        INNER JOIN b_book_repository br ON br.asin = bl.amazon_id
        INNER JOIN b_user bu ON bl.user_id = bu.user_id
        WHERE br.author = ? AND bu.diary_policy = 1 AND bu.status = 1
          AND bt.tag_name IS NOT NULL AND bt.tag_name != ''
        GROUP BY bt.tag_name
        ORDER BY user_count DESC
        LIMIT 8
    ";
    $tags = $g_db->getAll($tag_sql, [$author_name], DB_FETCHMODE_ASSOC);
    if (!DB::isError($tags) && $tags) {
        $read_stats['tags'] = $tags;
    }

    // この作家を読む人が他に読んでいる作家。
    //
    // 読者を絞ってから蔵書を展開する。読者数×1人あたりの蔵書数だけ中間行が
    // 膨らむため、緩くすると人気作家でページが目に見えて遅くなる。
    // 本番実測: 読者200人で村上春樹 TTFB 2.4秒、40人でも最大3.2秒の外れ値が出た
    // （集計を入れる前の作家ページは 0.07秒）。蔵書数の多い読者が1人サンプルに
    // 入るだけで跳ねるため、読者数だけでなく展開する行自体を絞る。
    //
    // status = 3（読了）に限定する。「読みたい」に積んだだけの本を除くことで
    // 多読ユーザーの行数を抑えられ、併読の指標としても実際に読んだ本の方が妥当。
    //
    // br2 の結合を外して bl2.author を直接使えばさらに速くなるが、
    // b_book_list.author は「東野 圭吾」、b_book_repository.author は「東野圭吾」と
    // 表記が異なり、リンク先の author.php が空になるため結合したままにする。
    $related_sql = "
        SELECT br2.author, COUNT(DISTINCT bl2.user_id) AS reader_count
        FROM (
            SELECT DISTINCT bl.user_id
            FROM b_book_repository br
            INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
            INNER JOIN b_user bu ON bl.user_id = bu.user_id
            WHERE br.author = ? AND bu.diary_policy = 1 AND bu.status = 1
            LIMIT 25
        ) r
        INNER JOIN b_book_list bl2 ON bl2.user_id = r.user_id AND bl2.status = 3
        INNER JOIN b_book_repository br2 ON br2.asin = bl2.amazon_id
        WHERE br2.author IS NOT NULL AND br2.author != '' AND br2.author != '-'
          AND br2.author != ?
        GROUP BY br2.author
        HAVING reader_count >= 2
        ORDER BY reader_count DESC
        LIMIT 6
    ";
    $related = $g_db->getAll($related_sql, [$author_name, $author_name], DB_FETCHMODE_ASSOC);
    if (!DB::isError($related) && $related) {
        $read_stats['related_authors'] = $related;
    }

    // 読書記録は日々増えるが、この集計は傾向を見せるものなので日単位の鮮度は不要。
    // 22,000件超の作家ページをクローラーが巡回するため、再計算の頻度を下げる。
    $author_cache->set($author_stats_key, $read_stats, 86400 * 7);
}

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

// canonical。作家名のURLエンコード差（%20 と + など）で同一ページが
// 複数URLに分裂するのを防ぐ。内部リンクは全て urlencode() なので合わせる。
require_once('library/seo_helpers.php');
$g_structured_tags = generateStructuredTags([
    'canonical_url' => getBaseUrl() . '/author.php?name=' . urlencode($author_name),
]);

// テンプレートを読み込み
include(getTemplatePath('t_author.php'));
?>