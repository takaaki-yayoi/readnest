<?php
/**
 * 読書マップデータAPI
 * ユーザーの読書履歴をジャンル別に集計してJSON形式で返す
 */

require_once '../config.php';
require_once '../library/database.php';

// セッション確認
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$g_login_id = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : null;

if (!$g_login_id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ユーザーID取得
$user_id = isset($_GET['user']) ? $_GET['user'] : $g_login_id;

// 他人のデータの場合は公開設定を確認
if ($user_id != $g_login_id) {
    $target_user = getUserInformation($user_id);
    if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
}

// データベース接続
global $g_db;

// まず、シンプルに本の数を取得
$count_sql = "SELECT COUNT(*) FROM b_book_list WHERE user_id = ?";
$book_count = $g_db->getOne($count_sql, [$user_id]);

if (DB::isError($book_count) || $book_count == 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'data' => ['name' => '読書マップ', 'children' => []],
        'stats' => [
            'total_books' => 0,
            'finished_books' => 0,
            'reading_books' => 0,
            'genres_explored' => 0,
            'most_read_genre' => '',
            'least_explored_genres' => []
        ],
        'user_id' => $user_id
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// タグデータを取得（シンプルなクエリ）
$tag_sql = "SELECT 
    bt.tag_name,
    COUNT(*) as book_count
FROM b_book_tags bt
WHERE bt.user_id = ?
GROUP BY bt.tag_name
ORDER BY book_count DESC
LIMIT 20";

$tag_data = $g_db->getAll($tag_sql, [$user_id], DB_FETCHMODE_ASSOC);

// 著者データを取得（シンプルなクエリ）
$author_sql = "SELECT 
    author,
    COUNT(*) as book_count,
    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as finished_count,
    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as reading_count
FROM b_book_list
WHERE user_id = ? AND author IS NOT NULL AND author != ''
GROUP BY author
ORDER BY book_count DESC
LIMIT 10";

$author_data = $g_db->getAll($author_sql, [READING_FINISH, READING_NOW, $user_id], DB_FETCHMODE_ASSOC);

// データを統合
$genre_data = [];

// タグデータを追加
if (!DB::isError($tag_data)) {
    foreach ($tag_data as $tag) {
        $genre_data[] = [
            'genre' => $tag['tag_name'],
            'genre2' => '',
            'book_count' => $tag['book_count'],
            'finished_count' => 0,
            'reading_count' => 0,
            'unread_count' => 0
        ];
    }
}

// 著者データを追加
if (!DB::isError($author_data)) {
    foreach ($author_data as $author) {
        $genre_data[] = [
            'genre' => '著者: ' . $author['author'],
            'genre2' => '',
            'book_count' => $author['book_count'],
            'finished_count' => $author['finished_count'],
            'reading_count' => $author['reading_count'],
            'unread_count' => $author['book_count'] - $author['finished_count'] - $author['reading_count']
        ];
    }
}

// ジャンルデータが空の場合
if (empty($genre_data)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'data' => ['name' => '読書マップ', 'children' => []],
        'stats' => [
            'total_books' => $book_count,
            'finished_books' => 0,
            'reading_books' => 0,
            'genres_explored' => 0,
            'most_read_genre' => '',
            'least_explored_genres' => []
        ],
        'user_id' => $user_id
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// カラーパレット（タグや著者用）
$color_palette = [
    '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
    '#DDA0DD', '#FFA07A', '#98D8C8', '#FFD93D', '#6BCB77',
    '#F8B595', '#F67280', '#C06C84', '#6C5CE7', '#A8E6CF'
];

// ジャンルキーワードマッピング（タグを推測ジャンルに分類）
$genre_keywords = [
    '文学・小説' => ['小説', '文学', '物語', 'ノベル', 'フィクション'],
    'ミステリー' => ['ミステリー', 'ミステリ', '推理', '探偵', 'サスペンス'],
    'SF・ファンタジー' => ['SF', 'ファンタジー', 'ファンタジ', '魔法', '異世界'],
    'ビジネス' => ['ビジネス', '経営', '経済', 'マーケティング', '起業'],
    '自己啓発' => ['自己啓発', '成功', '心理学', 'コーチング', 'マインド'],
    'ノンフィクション' => ['歴史', '科学', '社会', 'ドキュメンタリー', '伝記'],
    'コミック' => ['漫画', 'マンガ', 'コミック', 'まんが'],
    '技術書' => ['プログラミング', '技術', 'エンジニア', 'IT', 'コンピュータ']
];

// データを整形
$formatted_data = [
    'name' => '読書マップ',
    'children' => []
];

// ジャンルデータを階層構造に変換
$genre_totals = [];
$color_index = 0;

foreach ($genre_data as $row) {
    $tag_or_author = $row['genre'] ?: 'その他';
    
    // タグをジャンルカテゴリに分類
    $category = 'その他';
    $tag_lower = mb_strtolower($tag_or_author);
    
    foreach ($genre_keywords as $genre_name => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_strpos($tag_lower, mb_strtolower($keyword)) !== false) {
                $category = $genre_name;
                break 2;
            }
        }
    }
    
    // 著者名の場合は「著者別」カテゴリに
    if (strpos($tag_or_author, '著者: ') === 0) {
        $category = '著者別';
    }
    
    if (!isset($genre_totals[$category])) {
        $genre_totals[$category] = [
            'name' => $category,
            'color' => $color_palette[$color_index % count($color_palette)],
            'total' => 0,
            'finished' => 0,
            'reading' => 0,
            'unread' => 0,
            'children' => []
        ];
        $color_index++;
    }
    
    $genre_totals[$category]['total'] += $row['book_count'];
    $genre_totals[$category]['finished'] += $row['finished_count'];
    $genre_totals[$category]['reading'] += $row['reading_count'];
    $genre_totals[$category]['unread'] += $row['unread_count'];
    
    // 個別のタグ/著者を子要素として追加
    $genre_totals[$category]['children'][] = [
        'name' => $tag_or_author,
        'value' => $row['book_count'],
        'finished' => $row['finished_count'],
        'reading' => $row['reading_count'],
        'unread' => $row['unread_count']
    ];
}

// 配列に変換
foreach ($genre_totals as $genre) {
    $formatted_data['children'][] = [
        'name' => $genre['name'],
        'color' => $genre['color'],
        'value' => $genre['total'],
        'finished' => $genre['finished'],
        'reading' => $genre['reading'],
        'unread' => $genre['unread'],
        'children' => $genre['children']
    ];
}

// 統計情報を別途取得（効率化）
$stats_sql = "SELECT 
    COUNT(*) as total_books,
    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as finished_books,
    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as reading_books
FROM b_book_list 
WHERE user_id = ?";

$stats_result = $g_db->getRow($stats_sql, [READING_FINISH, READING_NOW, $user_id], DB_FETCHMODE_ASSOC);

$stats = [
    'total_books' => $stats_result['total_books'] ?? 0,
    'finished_books' => $stats_result['finished_books'] ?? 0,
    'reading_books' => $stats_result['reading_books'] ?? 0,
    'genres_explored' => count($genre_totals),
    'most_read_genre' => '',
    'least_explored_genres' => []
];

// 最も読んでいるカテゴリを特定
$max_books = 0;
foreach ($genre_totals as $genre_name => $genre) {
    if ($genre['total'] > $max_books) {
        $max_books = $genre['total'];
        $stats['most_read_genre'] = $genre_name;
    }
}

// 探索が少ないカテゴリを特定
$all_categories = array_keys($genre_keywords);
foreach ($all_categories as $category) {
    if (!isset($genre_totals[$category]) || $genre_totals[$category]['total'] < 3) {
        $stats['least_explored_genres'][] = $category;
    }
}

// レスポンスを返す
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'data' => $formatted_data,
    'stats' => $stats,
    'user_id' => $user_id
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);