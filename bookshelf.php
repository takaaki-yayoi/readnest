<?php
/**
 * モダン版本棚ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// キャッシュを無効化（動的コンテンツのため）
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// モダン設定を読み込み
require_once('modern_config.php');

// レベル表示関連
require_once(dirname(__FILE__) . '/library/achievement_system.php');
require_once(dirname(__FILE__) . '/library/level_display_helper.php');
// お気に入り機能
require_once(dirname(__FILE__) . '/library/favorite_functions.php');
// ユーザー作家クラウド
require_once(dirname(__FILE__) . '/library/user_author_cloud.php');

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = $login_flag ? $_SESSION['AUTH_USER'] : '';

// ユーザーID取得
$user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? $mine_user_id;

// ログインしていない場合はログインページへ
if (!$login_flag && empty($user_id)) {
    header('Location: https://readnest.jp');
    exit;
}

// ユーザー情報取得
if (!empty($user_id)) {
    $user_info_array = getUserInformation($user_id);

    if ($user_info_array === null) {
        header('Location: https://readnest.jp');
        exit;
    }

    $d_target_nickname = getNickname($user_id);
} else {
    $user_id = $mine_user_id;
    $user_info_array = getUserInformation($user_id);
    $d_target_nickname = getNickname($user_id);
}

// ページタイトル設定
$is_own_bookshelf = ($user_id === $mine_user_id);

// 非公開ユーザーのチェック（自分の本棚でない場合）
if (!$is_own_bookshelf) {
    // diary_policyを取得（1=公開、0=非公開）
    $privacy_sql = "SELECT diary_policy FROM b_user WHERE user_id = ?";
    $privacy_result = $g_db->getOne($privacy_sql, array($user_id));

    if (DB::isError($privacy_result) || $privacy_result != 1) {
        // 非公開ユーザーの本棚は見れない
        header('Location: /profile.php?user_id=' . $user_id);
        exit;
    }
}
$d_site_title = $is_own_bookshelf ? "あなたの本棚 - ReadNest" : "{$d_target_nickname}さんの本棚 - ReadNest";

// メタ情報
$g_meta_description = $is_own_bookshelf 
    ? "あなたの本棚を管理し、読書進捗を記録しましょう。" 
    : "{$d_target_nickname}さんの読書記録をご覧ください。";
$g_meta_keyword = "本棚,読書記録,読書進捗,ReadNest,{$d_target_nickname}";

// キャッシュライブラリを読み込み（統計情報のキャッシュ用）
require_once(dirname(__FILE__) . '/library/cache.php');
$cache = getCache();

// 本棚データ取得
$status_filter = $_GET['status'] ?? '';
$sort_order = $_GET['sort'] ?? 'update_date_desc';

// レガシー互換性: 古いソート値を新しい形式に変換
$legacy_sort_map = [
    'update_date' => 'update_date_desc',
    'rating' => 'rating_desc',
    'name' => 'title_asc',
    'author' => 'author_asc'
];
if (isset($legacy_sort_map[$sort_order])) {
    $sort_order = $legacy_sort_map[$sort_order];
}

$search_type = $_GET['search_type'] ?? '';
$search_word = $_GET['search_word'] ?? '';
$filter_year = $_GET['filter_year'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$tag_filter = $_GET['tag_filter'] ?? ''; // 'no_tags' for untagged books
$cover_filter = $_GET['cover_filter'] ?? ''; // 'no_cover' for books without cover

// 本棚統計取得（キャッシュ対応）
$statsCacheKey = 'bookshelf_stats_' . md5((string)$user_id);
$statsCacheTime = 600; // 10分キャッシュ

$cachedStats = $cache->get($statsCacheKey);
if ($cachedStats !== false) {
    $bookshelf_stats = $cachedStats['bookshelf_stats'];
    $read_stats = $cachedStats['read_stats'];
} else {
    $bookshelf_stats = getBookshelfNum($user_id);
    $read_stats = getBookshelfStat($user_id);
    
    $cache->set($statsCacheKey, [
        'bookshelf_stats' => $bookshelf_stats,
        'read_stats' => $read_stats
    ], $statsCacheTime);
}

// ユーザーレベル情報を取得
$user_level_info = null;
if (!empty($user_id)) {
    // 総読書ページ数を取得
    global $g_db;
    $total_pages_sql = "SELECT SUM(total_page) FROM b_book_list WHERE user_id = ? AND status IN (?, ?) AND total_page > 0";
    $total_pages = $g_db->getOne($total_pages_sql, array($user_id, READING_FINISH, READ_BEFORE));
    
    if (!DB::isError($total_pages) && $total_pages > 0) {
        $user_level_info = getReadingLevel(intval($total_pages));
    } else {
        $user_level_info = getReadingLevel(0);
    }
}

// ユーザーのタグクラウドデータ取得（キャッシュ対応）
$tagsCacheKey = 'user_tags_' . md5((string)$user_id);
$tagsCacheTime = 1800; // 30分キャッシュ（パフォーマンス改善）

// キャッシュクリアパラメータのチェック
if (isset($_GET['clear_tag_cache']) && $is_own_bookshelf) {
    $cache->delete($tagsCacheKey);
}

// タグクラウドの表示フラグ（遅延読み込み対応）
$show_tag_cloud = $is_own_bookshelf && !isset($_GET['hide_tags']);
// タグクラウドデータを取得（タブ表示用）
global $tag_cloud_data_popular, $tag_cloud_data_recent, $tag_cloud_stats;
$tag_cloud_data_popular = [];
$tag_cloud_data_recent = [];
$tag_cloud_stats = [];

if ($is_own_bookshelf) {
    // タグクラウドデータのキャッシュチェック
    $cachedTags = $cache->get($tagsCacheKey);
    if ($cachedTags !== false) {
        $tag_cloud_data_popular = $cachedTags['popular'] ?? [];
        $tag_cloud_data_recent = $cachedTags['recent'] ?? [];
        $tag_cloud_stats = $cachedTags['stats'] ?? [];
    } else {
        // 人気タグデータの取得
        $sql = "SELECT tag_name as tag, COUNT(*) as count 
                FROM b_book_tags 
                WHERE user_id = ? 
                GROUP BY tag_name 
                ORDER BY count DESC, tag_name ASC 
                LIMIT 50";
        $result = $g_db->getAll($sql, array($user_id), DB_FETCHMODE_ASSOC);
    if (!DB::isError($result) && !empty($result)) {
        $max_count = $result[0]['count'];
        $min_count = end($result)['count'];
        foreach ($result as $tag) {
            $ratio = ($max_count > $min_count) ? 
                    (($tag['count'] - $min_count) / ($max_count - $min_count)) : 
                    0.5;
            $fontSize = 12 + round($ratio * 12); // 12px〜24px
            
            // 色の設定
            if ($tag['count'] >= 10) {
                $colorClass = 'bg-blue-500 text-white';
            } elseif ($tag['count'] >= 5) {
                $colorClass = 'bg-green-500 text-white';
            } else {
                $colorClass = 'bg-gray-300 text-gray-700';
            }
            
            $tag_cloud_data_popular[] = [
                'tag' => $tag['tag'],
                'count' => $tag['count'],
                'font_size' => $fontSize,
                'color_class' => $colorClass
            ];
        }
        
        // 統計情報
        $tag_cloud_stats = [
            'total_tags' => count($result),
            'total_books' => array_sum(array_column($result, 'count'))
        ];
    }
    
    // 最近のタグデータの取得
    // created_atカラムを使って最近作成されたタグを取得
    $sql = "SELECT tag_name as tag, COUNT(*) as count, MAX(created_at) as last_created
            FROM b_book_tags
            WHERE user_id = ? 
            GROUP BY tag_name 
            ORDER BY last_created DESC, count DESC
            LIMIT 50";
    
    $result = $g_db->getAll($sql, array($user_id), DB_FETCHMODE_ASSOC);
    if (!DB::isError($result) && !empty($result)) {
        $max_count = !empty($result) ? max(array_column($result, 'count')) : 1;
        $min_count = !empty($result) ? min(array_column($result, 'count')) : 1;
        foreach ($result as $tag) {
            $ratio = ($max_count > $min_count) ? 
                    (($tag['count'] - $min_count) / ($max_count - $min_count)) : 
                    0.5;
            $fontSize = 12 + round($ratio * 12); // 12px〜24px
            
            // 色の設定（最近使用したものは青系統）
            $colorClass = 'bg-blue-500 text-white';
            
            $tag_cloud_data_recent[] = [
                'tag' => $tag['tag'],
                'count' => $tag['count'],
                'font_size' => $fontSize,
                'color_class' => $colorClass
            ];
        }
    }
    
        // キャッシュに保存
        $cache->set($tagsCacheKey, [
            'popular' => $tag_cloud_data_popular,
            'recent' => $tag_cloud_data_recent,
            'stats' => $tag_cloud_stats
        ], $tagsCacheTime);
    }
    
    // デフォルトは人気タグを表示（互換性のため保持）
    $tag_cloud_data = $tag_cloud_data_popular;
}

// ステータス別の本を取得
function getBooksByStatus($user_id, $status = '', $sort = 'update_date_desc', $search_type = '', $search_word = '', $filter_year = '', $filter_month = '', $tag_filter = '', $cover_filter = '') {
    global $g_star_array, $cache, $g_db, $mine_user_id;
    
    
    // 本棚データは常に最新のものを取得（キャッシュは使用しない）
    // Keep it simple: リアルタイム性が重要なため、キャッシュを無効化
    if (!empty($search_word) || !empty($filter_year) || !empty($filter_month) || !empty($tag_filter) || !empty($cover_filter)) {
        $books = getBookshelfWithSearch($user_id, $status, $sort, $search_type, $search_word, $filter_year, $filter_month, $tag_filter, $cover_filter);
    } else {
        $books = getBookshelf($user_id, $status, $sort);
    }
    $formatted_books = [];
    
    // パフォーマンス最適化：タグ表示を有効にするかどうか
    $enable_tag_display = !isset($_GET['disable_tags']) && count($books ?? []) <= 100;
    $all_tags = [];
    
    if ($enable_tag_display) {
        // すべての本のタグを一度に取得（最大100冊分）
        $book_ids = array_column($books, 'book_id');
        if (!empty($book_ids)) {
            // メモリ節約のため、最大100冊分のタグのみ取得
            $limited_book_ids = array_slice($book_ids, 0, 100);
            $placeholders = implode(',', array_fill(0, count($limited_book_ids ?? []), '?'));
            $tag_sql = "SELECT book_id, tag_name FROM b_book_tags WHERE user_id = ? AND book_id IN ($placeholders) ORDER BY book_id, tag_name LIMIT 500";
            $tag_params = array_merge([$user_id], $limited_book_ids);
            
            try {
                $tag_results = $g_db->getAll($tag_sql, $tag_params, DB_FETCHMODE_ASSOC);
                
                if (!DB::isError($tag_results) && is_array($tag_results)) {
                    foreach ($tag_results as $tag) {
                        if (!isset($all_tags[$tag['book_id']])) {
                            $all_tags[$tag['book_id']] = [];
                        }
                        $all_tags[$tag['book_id']][] = $tag;
                    }
                }
            } catch (Exception $e) {
                // タグ取得エラーの場合は空配列のままにする
                error_log('Tag fetch error: ' . $e->getMessage());
            }
        }
    }
    
    // お気に入り状態を一括取得（自分の本棚の場合のみ）
    global $mine_user_id;
    $is_own_bookshelf = ($user_id === $mine_user_id);
    $book_ids = array_column($books, 'book_id');
    $favorite_status = $is_own_bookshelf ? getBulkFavoriteStatus($mine_user_id, $book_ids) : [];
    
    foreach ($books as $book) {
        $book_id = $book['book_id'];
        $title = html($book['name']);
        $author = html($book['author'] ?: '-');
        $status_id = $book['status'];
        $rating = $book['rating'];
        $current_page = $book['current_page'];
        $total_page = $book['total_page'];
        $image_url = (!empty($book['image_url']) && $book['image_url'] !== 'NULL') ? $book['image_url'] : '/img/no-image-book.png';
        $memo = $book['memo'];
        $is_favorite = isset($favorite_status[$book_id]) ? $favorite_status[$book_id] : false;
        // update_dateがDATETIME文字列の場合とUnix timestampの場合に対応
        if (is_numeric($book['update_date']) && $book['update_date'] > 0) {
            $update_date = date('Y/m/d', $book['update_date']);
        } else {
            $update_date = date('Y/m/d', strtotime($book['update_date']));
        }
        
        // create_dateがDATETIME文字列の場合とUnix timestampの場合に対応
        if (isset($book['create_date'])) {
            if (is_numeric($book['create_date']) && $book['create_date'] > 0) {
                $create_date = date('Y/m/d', $book['create_date']);
            } else {
                $create_date = date('Y/m/d', strtotime($book['create_date']));
            }
        } else {
            $create_date = null;
        }
        
        // 進捗計算
        $progress = $total_page > 0 ? round(($current_page / $total_page) * 100) : 0;
        
        // 評価
        $star_display = $rating > 0 ? $g_star_array[$rating] : '未評価';
        
        // ステータス表示
        $status_labels = [
            BUY_SOMEDAY => ['いつか買う', 'bg-gray-100 text-gray-800'],
            NOT_STARTED => ['未読', 'bg-blue-100 text-blue-800'],
            READING_NOW => ['読書中', 'bg-yellow-100 text-yellow-800'],
            READING_FINISH => ['読了', 'bg-green-100 text-green-800'],
            READ_BEFORE => ['読了済み', 'bg-purple-100 text-purple-800']
        ];
        
        // タグを取得（事前に取得したデータから）
        $tags = isset($all_tags[$book_id]) ? $all_tags[$book_id] : [];
        
        // レビューの有無を判定（memoフィールドに内容があるかどうか）
        $has_review = !empty($memo) && trim($memo ?? '') !== '';
        
        $formatted_books[] = [
            'book_id' => $book_id,
            'title' => $title,
            'author' => $author,
            'status_id' => $status_id,
            'status_label' => $status_labels[$status_id][0] ?? '不明',
            'status_class' => $status_labels[$status_id][1] ?? 'bg-gray-100 text-gray-800',
            'rating' => $rating,
            'star_display' => $star_display,
            'current_page' => $current_page,
            'total_page' => $total_page,
            'progress' => $progress,
            'image_url' => $image_url,
            'memo' => $memo,
            'has_review' => $has_review,
            'tags' => $tags,
            'update_date' => $update_date,
            'create_date' => $create_date,
            'is_favorite' => $is_favorite
        ];
    }

    // キャッシュは使用しない（常に最新データを提供）
    return $formatted_books;
}

// 画像URLが無効かチェック
function isInvalidCoverImage($url) {
    if (empty($url) || $url === '/img/no-image-book.png') {
        return true;
    }
    
    // 既知の「画像なし」パターン
    $invalidPatterns = [
        'noimage',
        'no-image', 
        'no_image',
        'not-available',
        'not_available',
        'image_not_available'
    ];
    
    $lowerUrl = strtolower($url);
    foreach ($invalidPatterns as $pattern) {
        if (strpos($lowerUrl, $pattern) !== false) {
            return true;
        }
    }
    
    // Google Booksのパターンチェック
    if (strpos($url, 'books.google.com') !== false) {
        // Google Booksのzoom=2以上は「image not available」の可能性が高い
        if (preg_match('/zoom=(\d+)/', $url, $zoomMatch)) {
            $zoomLevel = (int)$zoomMatch[1];
            if ($zoomLevel >= 2) {
                return true;
            }
        }
        // zoom=1以下は通常有効な画像
    }
    
    return false;
}

// 検索付き本棚取得関数
function getBookshelfWithSearch($user_id, $status = '', $sort = 'update_date_desc', $search_type = '', $search_word = '', $filter_year = '', $filter_month = '', $tag_filter = '', $cover_filter = '') {
    global $g_db;
    
    
    // b_book_repositoryテーブルから著者情報も取得
    // ユーザーが編集した著者情報（bl.author）を優先
    // 注意: bl.*とCOALESCEを併用すると上書きされないため、authorを除外してから追加
    $sql = "SELECT bl.book_id, bl.user_id, bl.amazon_id, bl.isbn, bl.name,
            bl.image_url, bl.detail_url, bl.status, bl.rating, bl.memo,
            bl.total_page, bl.current_page, bl.create_date, bl.update_date,
            bl.finished_date, bl.number_of_refer, bl.memo_updated,
            COALESCE(bl.author, br.author, '') as author
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?";
    $params = [$user_id];
    
    // ステータスフィルタ
    if ($status !== '') {
        // 読了の場合は「昔読んだ」も含める
        if ($status == READING_FINISH) {
            $sql .= " AND status IN (?, ?)";
            $params[] = READING_FINISH;
            $params[] = READ_BEFORE;
        } else {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
    }
    
    // 年フィルタ（読了日ベース、なければ更新日）
    if (!empty($filter_year) && is_numeric($filter_year)) {
        $sql .= " AND (
            (status IN (?, ?) AND finished_date IS NOT NULL AND YEAR(finished_date) = ?)
            OR (status IN (?, ?) AND finished_date IS NULL AND YEAR(update_date) = ?)
            OR (status NOT IN (?, ?) AND YEAR(update_date) = ?)
        )";
        $params[] = READING_FINISH;
        $params[] = READ_BEFORE;
        $params[] = $filter_year;
        $params[] = READING_FINISH;
        $params[] = READ_BEFORE;
        $params[] = $filter_year;
        $params[] = READING_FINISH;
        $params[] = READ_BEFORE;
        $params[] = $filter_year;
    }
    
    // 月フィルタ（読了日ベース、なければ更新日）
    if (!empty($filter_month) && preg_match('/^\d{4}-\d{2}$/', $filter_month)) {
        $sql .= " AND (
            (status IN (?, ?) AND finished_date IS NOT NULL AND DATE_FORMAT(finished_date, '%Y-%m') = ?)
            OR (status IN (?, ?) AND finished_date IS NULL AND DATE_FORMAT(update_date, '%Y-%m') = ?)
            OR (status NOT IN (?, ?) AND DATE_FORMAT(update_date, '%Y-%m') = ?)
        )";
        $params[] = READING_FINISH;
        $params[] = READ_BEFORE;
        $params[] = $filter_month;
        $params[] = READING_FINISH;
        $params[] = READ_BEFORE;
        $params[] = $filter_month;
        $params[] = READING_FINISH;
        $params[] = READ_BEFORE;
        $params[] = $filter_month;
    }
    
    // 検索条件
    if (!empty($search_word)) {
        if ($search_type === 'author') {
            // 著者検索: bl.author（ユーザー編集）とbr.author（レポジトリ）の両方を検索（曖昧検索対応）
            $sql .= " AND (
                bl.author LIKE ?
                OR bl.author LIKE ?
                OR REPLACE(bl.author, ' ', '') LIKE ?
                OR amazon_id IN (
                    SELECT asin FROM b_book_repository
                    WHERE author LIKE ?
                    OR author LIKE ?
                    OR REPLACE(author, ' ', '') LIKE ?
                )
            )";
            $params[] = '%' . $search_word . '%';
            $params[] = '%' . str_replace(' ', '%', $search_word) . '%'; // スペースを%に置換
            $params[] = '%' . str_replace(' ', '', $search_word) . '%'; // スペースを削除
            $params[] = '%' . $search_word . '%';
            $params[] = '%' . str_replace(' ', '%', $search_word) . '%'; // スペースを%に置換
            $params[] = '%' . str_replace(' ', '', $search_word) . '%'; // スペースを削除
        } elseif ($search_type === 'title') {
            $sql .= " AND name LIKE ?";
            $params[] = '%' . $search_word . '%';
        } elseif ($search_type === 'tag') {
            // タグ検索の場合はサブクエリを使用
            $sql .= " AND book_id IN (SELECT book_id FROM b_book_tags WHERE user_id = ? AND tag_name = ?)";
            $params[] = $user_id;
            $params[] = $search_word;
        } else {
            // デフォルトはタイトルと著者両方で検索（bl.authorとbr.authorの両方）
            $sql .= " AND (
                name LIKE ?
                OR bl.author LIKE ?
                OR amazon_id IN (
                    SELECT asin FROM b_book_repository
                    WHERE author LIKE ?
                )
            )";
            $params[] = '%' . $search_word . '%';
            $params[] = '%' . $search_word . '%';
            $params[] = '%' . $search_word . '%';
        }
    }
    
    // タグフィルタ
    if ($tag_filter === 'no_tags') {
        // タグがついていない本のみ
        $sql .= " AND book_id NOT IN (SELECT DISTINCT book_id FROM b_book_tags WHERE user_id = ?)";
        $params[] = $user_id;
    }
    
    // 表紙フィルタ - 本当に表紙がない本のみ
    if ($cover_filter === 'no_cover') {
        // 確実に表紙がない本のみを取得
        $sql .= " AND (image_url IS NULL 
                    OR image_url = '' 
                    OR image_url = '/img/no-image-book.png'
                    OR image_url LIKE '%noimage%'
                    OR image_url LIKE '%no-image%'
                    OR image_url LIKE '%no_image%')";
    }
    
    // ソート順（昇順・降順対応）
    switch ($sort) {
        // タイトル
        case 'title_asc':
            $sql .= " ORDER BY name ASC";
            break;
        case 'title_desc':
            $sql .= " ORDER BY name DESC";
            break;
        // 著者名
        case 'author_asc':
            $sql .= " ORDER BY author ASC";
            break;
        case 'author_desc':
            $sql .= " ORDER BY author DESC";
            break;
        // 評価
        case 'rating_asc':
            $sql .= " ORDER BY rating ASC, update_date DESC";
            break;
        case 'rating_desc':
            $sql .= " ORDER BY rating DESC, update_date DESC";
            break;
        // 読了日
        case 'finished_date_asc':
            $sql .= " ORDER BY finished_date ASC";
            break;
        case 'finished_date_desc':
            $sql .= " ORDER BY finished_date DESC";
            break;
        // ページ数
        case 'pages_asc':
            $sql .= " ORDER BY total_page ASC";
            break;
        case 'pages_desc':
            $sql .= " ORDER BY total_page DESC";
            break;
        // 登録日
        case 'created_date_asc':
            $sql .= " ORDER BY create_date ASC";
            break;
        case 'created_date_desc':
            $sql .= " ORDER BY create_date DESC";
            break;
        // 更新日（デフォルト）
        case 'update_date_asc':
            $sql .= " ORDER BY update_date ASC";
            break;
        case 'update_date_desc':
        default:
            $sql .= " ORDER BY update_date DESC";
    }
    
    $result = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
    
    if (DB::isError($result)) {
        return [];
    }
    
    // 表紙フィルタが有効な場合、既にSQLで適切にフィルタリングされている
    
    return $result;
}


$books = getBooksByStatus($user_id, $status_filter, $sort_order, $search_type, $search_word, $filter_year, $filter_month, $tag_filter, $cover_filter);

// ユーザー作家クラウドデータを取得
$user_author_cloud = new UserAuthorCloud($user_id);
$author_cloud_data = $user_author_cloud->getUserAuthorCloud($user_id, 30);
$author_stats = $user_author_cloud->getUserAuthorStats($user_id);

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// パンくずリストを生成
require_once(dirname(__FILE__) . '/library/navigation_helper.php');
$breadcrumbs = generateBreadcrumbs('bookshelf');

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_bookshelf.php'));