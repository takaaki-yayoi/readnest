<?php
/**
 * 読書マップデータAPI（シンプル版）
 * 著者別のみで集計
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

// データベース接続
global $g_db;

// シンプルに著者別で集計（読了本のみ）
$sql = "SELECT 
    author,
    COUNT(*) as book_count
FROM b_book_list
WHERE user_id = ? 
    AND status = 3
    AND author IS NOT NULL 
    AND author != ''
GROUP BY author
ORDER BY book_count DESC
LIMIT 20";

try {
    $author_data = $g_db->getAll($sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($author_data)) {
        throw new Exception($author_data->getMessage());
    }
    
    // データを整形
    $formatted_data = [
        'name' => '読書マップ',
        'children' => []
    ];
    
    // カラーパレット
    $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57', '#DDA0DD', '#FFA07A', '#98D8C8'];
    $color_index = 0;
    
    // 著者データをグループ化
    $categories = [];
    foreach ($author_data as $author) {
        $category = '著者別';
        
        if (!isset($categories[$category])) {
            $categories[$category] = [
                'name' => $category,
                'color' => $colors[$color_index % count($colors)],
                'value' => 0,
                'finished' => 0,
                'reading' => 0,
                'unread' => 0,
                'children' => []
            ];
            $color_index++;
        }
        
        $categories[$category]['value'] += $author['book_count'];
        $categories[$category]['finished'] += $author['book_count']; // 読了本のみなので同じ
        $categories[$category]['reading'] += 0;
        $categories[$category]['unread'] += 0;
        
        $categories[$category]['children'][] = [
            'name' => $author['author'],
            'value' => $author['book_count'],
            'finished' => $author['book_count'], // 読了本のみなので同じ
            'reading' => 0,
            'unread' => 0
        ];
    }
    
    // 配列に変換
    foreach ($categories as $category) {
        $formatted_data['children'][] = $category;
    }
    
    // 統計情報
    $stats_sql = "SELECT 
        COUNT(*) as total_books,
        SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as finished_books,
        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as reading_books
    FROM b_book_list 
    WHERE user_id = ?";
    
    $stats_result = $g_db->getRow($stats_sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    $stats = [
        'total_books' => $stats_result['total_books'] ?? 0,
        'finished_books' => $stats_result['finished_books'] ?? 0,
        'reading_books' => $stats_result['reading_books'] ?? 0,
        'genres_explored' => count($author_data) > 0 ? 1 : 0, // カテゴリ数
        'most_read_genre' => '著者別',
        'most_read_author' => count($author_data) > 0 ? $author_data[0]['author'] : '',
        'least_explored_genres' => []
    ];
    
    // レスポンスを返す
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'data' => $formatted_data,
        'stats' => $stats,
        'user_id' => $user_id
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>