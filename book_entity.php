<?php
/**
 * 本のエンティティページ（b_book_repository単位）
 * ASINベースで本の情報を表示し、各ユーザーのbook_detailへのハブとなる
 */

declare(strict_types=1);

require_once('modern_config.php');
require_once('library/database.php');

// MySQL接続の再接続を試みる関数
function ensureDbConnection() {
    global $g_db;
    
    // 接続テスト
    $test = @$g_db->getOne("SELECT 1");
    if (DB::isError($test) || $test === false) {
        error_log("book_entity.php - DB connection lost, attempting to reconnect...");
        
        // 再接続を試みる
        try {
            // config.phpを再読み込みして接続を再確立
            include(dirname(__FILE__) . '/config.php');
            
            // 再度テスト
            $test = @$g_db->getOne("SELECT 1");
            if (!DB::isError($test) && $test !== false) {
                error_log("book_entity.php - DB reconnection successful");
                return true;
            }
        } catch (Exception $e) {
            error_log("book_entity.php - DB reconnection failed: " . $e->getMessage());
        }
        return false;
    }
    return true;
}

// 接続確認
ensureDbConnection();

// パラメータ取得
$asin = isset($_GET['asin']) ? trim($_GET['asin']) : '';
$isbn = isset($_GET['isbn']) ? trim($_GET['isbn']) : '';

if (empty($asin) && empty($isbn)) {
    header('Location: /');
    exit;
}

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = $login_flag ? (int)$_SESSION['AUTH_USER'] : 0;

// デバッグ情報（開発環境のみ）
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("book_entity.php - ASIN: " . $asin . ", ISBN: " . $isbn);
}

// 本の基本情報を取得
$book_info = null;

// まずb_book_repositoryテーブルの存在を確認
ensureDbConnection(); // 接続確認
$table_check = $g_db->getOne("SHOW TABLES LIKE 'b_book_repository'");
$has_repository = !DB::isError($table_check) && $table_check;

if ($has_repository && !empty($asin)) {
    // b_book_repositoryから取得を試みる
    ensureDbConnection(); // 接続確認
    $sql = "SELECT * FROM b_book_repository WHERE asin = ?";
    $book_info = $g_db->getRow($sql, [$asin], DB_FETCHMODE_ASSOC);
    if (defined('DEBUG_MODE') && DEBUG_MODE) error_log("book_entity.php - Query with ASIN from repository: " . $sql);
} else if ($has_repository && !empty($isbn)) {
    // ISBNでも検索可能にする
    ensureDbConnection(); // 接続確認
    $sql = "SELECT * FROM b_book_repository WHERE isbn = ?";
    $book_info = $g_db->getRow($sql, [$isbn], DB_FETCHMODE_ASSOC);
    if (defined('DEBUG_MODE') && DEBUG_MODE) error_log("book_entity.php - Query with ISBN from repository: " . $sql);
}

if (DB::isError($book_info)) {
    error_log("book_entity.php - DB Error: " . $book_info->getMessage()); // エラーは常に記録
}

// b_book_repositoryで見つからない、またはテーブルがない場合は、b_book_listから取得
if (!$book_info || DB::isError($book_info) || !$has_repository) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) error_log("book_entity.php - Falling back to b_book_list. Repository exists: " . ($has_repository ? 'yes' : 'no'));
    
    // b_book_listから直接検索を試みる
    if (!empty($asin)) {
        ensureDbConnection(); // 接続確認
        $fallback_sql = "SELECT 
            bl.book_id,
            bl.name as title,
            bl.author,
            bl.image_url,
            bl.amazon_id as asin,
            bl.isbn,
            '' as description,
            '' as publisher,
            '' as published_date,
            0 as page_count
        FROM b_book_list bl
        WHERE bl.amazon_id = ?
        LIMIT 1";
        
        $book_info = $g_db->getRow($fallback_sql, [$asin], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($book_info)) {
            error_log("book_entity.php - b_book_list DB Error: " . $book_info->getMessage());
        }
    } else if (!empty($isbn)) {
        ensureDbConnection(); // 接続確認
        $fallback_sql = "SELECT 
            bl.book_id,
            bl.name as title,
            bl.author,
            bl.image_url,
            bl.amazon_id as asin,
            bl.isbn,
            '' as description,
            '' as publisher,
            '' as published_date,
            0 as page_count
        FROM b_book_list bl
        WHERE bl.isbn = ?
        LIMIT 1";
        
        $book_info = $g_db->getRow($fallback_sql, [$isbn], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($book_info)) {
            error_log("book_entity.php - b_book_list ISBN DB Error: " . $book_info->getMessage());
        }
    }
    
    // それでも見つからない場合は検索ページへ
    if (!$book_info || DB::isError($book_info)) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) error_log("book_entity.php - No book found at all. Redirecting to search.");
        header('Location: /search_results.php');
        exit;
    }
}

// ASINを正しく取得
$book_asin = $book_info['asin'] ?? $asin;
if (defined('DEBUG_MODE') && DEBUG_MODE) error_log("book_entity.php - Using ASIN for readers query: " . $book_asin);

// この本を読んでいるユーザーの情報を取得
// book_detailと同じロジックを使用
$readers = [];

// ISBNが指定されていて、book_infoにISBNがあり、空文字列でない場合は、ISBNを優先
if (!empty($isbn) && !empty($book_info['isbn']) && $book_info['isbn'] !== '') {
    // ISBNで直接検索
    $isbn_sql = "
        SELECT 
            bl.*,
            u.nickname,
            u.diary_policy
        FROM b_book_list bl
        INNER JOIN b_user u ON bl.user_id = u.user_id
        WHERE bl.isbn = ?
        AND bl.status > 0  -- 有効な本のみ（いつか買うを除外）
        AND u.status = 1
        AND (u.diary_policy = 1 OR bl.user_id = ?)
        ORDER BY 
            CASE WHEN bl.user_id = ? THEN 0 ELSE 1 END,
            bl.rating DESC,
            bl.update_date DESC
        LIMIT 50
    ";
    
    $isbn_readers = $g_db->getAll($isbn_sql, [$book_info['isbn'], $mine_user_id, $mine_user_id], DB_FETCHMODE_ASSOC);
    if (!DB::isError($isbn_readers) && count($isbn_readers) > 0) {
        foreach ($isbn_readers as &$reader) {
            $reader['photo_url'] = getProfilePhotoURL($reader['user_id']);
        }
        $readers = $isbn_readers;
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("book_entity.php - Found " . count($readers) . " readers by ISBN priority match");
        }
    }
}

// ISBNで見つからない場合、まずASINで検索
if (empty($readers) && !empty($book_asin)) {
    $readers_book = getBooksWithAsin($book_asin);
    
    if ($readers_book && count($readers_book) > 0) {
        // ユーザー情報を一括取得
        $reader_ids = array_map(function($r) { return $r['user_id']; }, $readers_book);
        $users_info = [];
        
        if (!empty($reader_ids)) {
            $placeholders = implode(',', array_fill(0, count($reader_ids), '?'));
            $users_sql = "SELECT user_id, nickname, photo as photo_url, diary_policy 
                         FROM b_user 
                         WHERE user_id IN ($placeholders) 
                         AND status = 1
                         AND (diary_policy = 1 OR user_id = ?)";
            $params = array_merge($reader_ids, [$mine_user_id]);
            $users_result = $g_db->getAll($users_sql, $params, DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($users_result)) {
                foreach ($users_result as $u) {
                    $users_info[$u['user_id']] = $u;
                }
            }
        }
        
        // 読者情報を構築（公開ユーザーのみ）
        foreach ($readers_book as $reader_book) {
            $reader_id = $reader_book['user_id'];
            
            if (isset($users_info[$reader_id])) {
                $user_info = $users_info[$reader_id];
                $readers[] = array_merge($reader_book, [
                    'nickname' => $user_info['nickname'],
                    'photo_url' => getProfilePhotoURL($reader_id),
                    'diary_policy' => $user_info['diary_policy']
                ]);
            }
        }
    }
}

// ASINでも見つからない場合、タイトル+著者で検索
if (empty($readers) && !empty($book_info['title'])) {
    $title_author_sql = "
        SELECT 
            bl.*,
            u.nickname,
            u.diary_policy
        FROM b_book_list bl
        INNER JOIN b_user u ON bl.user_id = u.user_id
        WHERE bl.name = ?
        " . (!empty($book_info['author']) ? "AND bl.author = ?" : "") . "
        AND bl.status > 0  -- 有効な本のみ（いつか買うを除外）
        AND u.status = 1
        AND (u.diary_policy = 1 OR bl.user_id = ?)
        ORDER BY 
            CASE WHEN bl.user_id = ? THEN 0 ELSE 1 END,
            bl.rating DESC,
            bl.update_date DESC
        LIMIT 50
    ";
    
    $params = [$book_info['title']];
    if (!empty($book_info['author'])) {
        $params[] = $book_info['author'];
    }
    $params[] = $mine_user_id;
    $params[] = $mine_user_id;
    
    $title_readers = $g_db->getAll($title_author_sql, $params, DB_FETCHMODE_ASSOC);
    if (!DB::isError($title_readers) && count($title_readers) > 0) {
        foreach ($title_readers as &$reader) {
            $reader['photo_url'] = getProfilePhotoURL($reader['user_id']);
        }
        $readers = $title_readers;
        if (isset($_GET['debug'])) {
            error_log("book_entity.php - Found " . count($readers) . " readers by title+author match");
        }
    }
}

if (isset($_GET['debug'])) {
    error_log("book_entity.php - Total readers found: " . count($readers));
}

// 統計情報を計算
$stats = [
    'total_readers' => count($readers),
    'reading_now' => 0,
    'finished' => 0,
    'want_to_read' => 0,
    'avg_rating' => 0,
    'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
    'reviews_count' => 0
];

$total_rating = 0;
$rating_count = 0;

foreach ($readers as $reader) {
    // ステータス別カウント
    switch ($reader['status']) {
        case READING_NOW:
            $stats['reading_now']++;
            break;
        case READING_FINISH:
        case READ_BEFORE:
            $stats['finished']++;
            break;
        case NOT_STARTED:
            $stats['want_to_read']++;
            break;
        case BUY_SOMEDAY:
            // status = 0 は「いつか買う」なので、まだ本を持っていない状態
            // グローバル検索では status > 0 でフィルタしているので除外
            break;
    }
    
    // 評価の集計
    if ($reader['rating'] > 0) {
        $total_rating += $reader['rating'];
        $rating_count++;
        $stats['rating_distribution'][$reader['rating']]++;
    }
    
    // レビューのカウント
    if (!empty($reader['memo'])) {
        $stats['reviews_count']++;
    }
}

if ($rating_count > 0) {
    $stats['avg_rating'] = round($total_rating / $rating_count, 1);
}

// タグ情報を取得（この本に付けられたタグを集計）
$popular_tags = [];

if (!empty($readers)) {
    // 読者のbook_idからタグを集計
    $book_ids = array_unique(array_column($readers, 'book_id'));
    if (!empty($book_ids)) {
        $placeholders = implode(',', array_fill(0, count($book_ids), '?'));
        $tags_sql = "
            SELECT 
                bt.tag_name,
                COUNT(DISTINCT bt.user_id) as user_count
            FROM b_book_tags bt
            WHERE bt.book_id IN ($placeholders)
            GROUP BY bt.tag_name
            ORDER BY user_count DESC
            LIMIT 20
        ";
        $popular_tags = $g_db->getAll($tags_sql, $book_ids, DB_FETCHMODE_ASSOC);
    }
}
if (DB::isError($popular_tags)) {
    error_log("book_entity.php - DB Error in tags query: " . $popular_tags->getMessage());
    $popular_tags = [];
} else {
    if (defined('DEBUG_MODE') && DEBUG_MODE) error_log("book_entity.php - Found " . count($popular_tags) . " tags");
}

// 自分がこの本を登録しているかチェック
$my_book = null;
if ($login_flag && !empty($readers)) {
    // 読者リストから自分の本を探す
    foreach ($readers as $reader) {
        if ($reader['user_id'] == $mine_user_id) {
            $my_book = [
                'book_id' => $reader['book_id'],
                'status' => $reader['status'],
                'rating' => $reader['rating'] ?? 0,
                'memo' => $reader['memo'] ?? ''
            ];
            break;
        }
    }
}

// レベル情報を取得するヘルパー
require_once('library/achievement_system.php');
$user_ids = array_column($readers, 'user_id');
$user_levels = getUsersLevels($user_ids);

// 関連書籍（同じ著者の他の本）
$similar_books = [];
if (!empty($book_info['author'])) {
    $similar_books_sql = "
        SELECT DISTINCT
            br.asin,
            br.title,
            br.author,
            br.image_url,
            COUNT(DISTINCT bl.user_id) as reader_count,
            AVG(bl.rating) as avg_rating
        FROM b_book_repository br
        LEFT JOIN b_book_list bl ON br.asin = bl.amazon_id
        WHERE br.author = ?
        AND br.asin != ?
        GROUP BY br.asin
        ORDER BY reader_count DESC, avg_rating DESC
        LIMIT 6
    ";
    
    $similar_books_result = $g_db->getAll($similar_books_sql, [$book_info['author'], $book_asin], DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($similar_books_result) && !empty($similar_books_result)) {
        $similar_books = $similar_books_result;
    } else {
        // b_book_repositoryで見つからない場合はb_book_listから取得
        $fallback_sql = "
            SELECT DISTINCT
                bl.amazon_id as asin,
                bl.name as title,
                bl.author,
                bl.image_url,
                COUNT(DISTINCT bl.user_id) as reader_count,
                AVG(bl.rating) as avg_rating
            FROM b_book_list bl
            WHERE bl.author = ?
            AND bl.amazon_id != ?
            AND bl.status > 0
            GROUP BY bl.amazon_id
            ORDER BY reader_count DESC, avg_rating DESC
            LIMIT 6
        ";
        
        $fallback_result = $g_db->getAll($fallback_sql, [$book_info['author'], $book_asin], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($fallback_result)) {
            $similar_books = $fallback_result;
        }
    }
}

// ページタイトル
$d_site_title = htmlspecialchars($book_info['title'] ?? '') . ' - ReadNest';

// メタ情報
$author_text = !empty($book_info['author']) ? ' by ' . htmlspecialchars($book_info['author']) : '';
$g_meta_description = htmlspecialchars($book_info['title'] ?? '') . $author_text . ' - ' . 
                      $stats['total_readers'] . '人が読書中。平均評価' . $stats['avg_rating'] . '。ReadNestで読書記録を共有しよう。';
$g_meta_keyword = htmlspecialchars($book_info['title'] ?? '') . ',' . htmlspecialchars($book_info['author'] ?? '') . ',読書,レビュー,評価';

// Open Graph画像
$og_image = !empty($book_info['image_url']) ? $book_info['image_url'] : '/img/og-image.jpg';

// テンプレートを読み込み
include(getTemplatePath('t_book_entity.php'));
?>