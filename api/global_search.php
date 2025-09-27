<?php
/**
 * グローバル検索API
 * 本、著者、レビューを横断検索（高速版：自分の本棚を優先）
 */

require_once(dirname(__DIR__) . '/modern_config.php');

// JSON出力の設定
header('Content-Type: application/json; charset=utf-8');

// ログインチェック
if (!isset($_SESSION['AUTH_USER'])) {
    echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// 検索クエリを取得
$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all'; // all, books, authors, reviews
$limit = min(50, intval($_GET['limit'] ?? 20));

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'error' => '検索文字は2文字以上入力してください']);
    exit;
}

$results = [
    'success' => true,
    'query' => $query,
    'books' => [],
    'authors' => [],
    'reviews' => [],
    'total' => 0
];

try {
    // 1. 本の検索（自分の本棚から）
    if ($type === 'all' || $type === 'books') {
        $book_sql = "
            SELECT DISTINCT
                bl.book_id,
                bl.name as title,
                COALESCE(br.author, bl.author, '') as author,
                bl.image_url,
                bl.status,
                bl.rating,
                bl.total_page,
                bl.current_page,
                bl.update_date,
                bl.amazon_id as asin,
                bl.isbn,
                CASE 
                    WHEN bl.name LIKE ? THEN 1
                    WHEN bl.name LIKE ? THEN 2
                    ELSE 3
                END as relevance
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND (
                bl.name LIKE ?
                OR COALESCE(br.author, bl.author, '') LIKE ?
            )
            ORDER BY relevance, bl.update_date DESC
            LIMIT ?
        ";
        
        $params = [
            $query . '%',  // 前方一致で高スコア
            '%' . $query . '%',  // 部分一致
            $user_id,
            '%' . $query . '%',
            '%' . $query . '%',
            $limit
        ];
        
        $book_results = $g_db->getAll($book_sql, $params, DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($book_results)) {
            foreach ($book_results as $book) {
                // ASINまたはISBNがある場合はbook_entityへ、なければbook_detailへ
                $url = !empty($book['asin']) ? '/book_entity/' . urlencode($book['asin']) : 
                       (!empty($book['isbn']) ? '/book_entity/' . urlencode($book['isbn']) : 
                       '/book/' . $book['book_id']);
                
                $results['books'][] = [
                    'book_id' => $book['book_id'],
                    'asin' => $book['asin'],
                    'isbn' => $book['isbn'],
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'image_url' => !empty($book['image_url']) ? $book['image_url'] : '/img/no-image-book.png',
                    'status' => $book['status'],
                    'rating' => $book['rating'],
                    'progress' => $book['total_page'] > 0 ? round(($book['current_page'] / $book['total_page']) * 100) : 0,
                    'url' => $url
                ];
            }
        }
    }
    
    // 2. 著者の検索（自分の本棚から）
    if ($type === 'all' || $type === 'authors') {
        $author_sql = "
            SELECT DISTINCT
                COALESCE(br.author, bl.author, '') as author,
                COUNT(DISTINCT bl.book_id) as book_count,
                AVG(CASE WHEN bl.rating > 0 THEN bl.rating ELSE NULL END) as avg_rating
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND COALESCE(br.author, bl.author, '') LIKE ?
            AND COALESCE(br.author, bl.author, '') != ''
            GROUP BY COALESCE(br.author, bl.author, '')
            ORDER BY book_count DESC
            LIMIT 10
        ";
        
        $author_results = $g_db->getAll($author_sql, [$user_id, '%' . $query . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($author_results)) {
            foreach ($author_results as $author) {
                $results['authors'][] = [
                    'name' => $author['author'],
                    'book_count' => intval($author['book_count']),
                    'avg_rating' => $author['avg_rating'] ? round(floatval($author['avg_rating']), 1) : null,
                    'url' => '/search_book_by_author.php?author=' . urlencode($author['author'])
                ];
            }
        }
    }
    
    // 3. レビューの検索（自分のレビュー）
    if ($type === 'all' || $type === 'reviews') {
        $review_sql = "
            SELECT 
                bl.book_id,
                bl.name as title,
                bl.author,
                bl.image_url,
                bl.memo as review,
                bl.rating,
                bl.amazon_id as asin,
                bl.isbn
            FROM b_book_list bl
            WHERE bl.user_id = ?
            AND bl.memo IS NOT NULL
            AND bl.memo != ''
            AND bl.memo LIKE ?
            ORDER BY bl.update_date DESC
            LIMIT 10
        ";
        
        $review_results = $g_db->getAll($review_sql, [$user_id, '%' . $query . '%'], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($review_results)) {
            foreach ($review_results as $review) {
                $url = !empty($review['asin']) ? '/book_entity/' . urlencode($review['asin']) : 
                       (!empty($review['isbn']) ? '/book_entity/' . urlencode($review['isbn']) : 
                       '/book/' . $review['book_id']);
                
                $results['reviews'][] = [
                    'book_id' => $review['book_id'],
                    'title' => $review['title'],
                    'author' => $review['author'],
                    'image_url' => !empty($review['image_url']) ? $review['image_url'] : '/img/no-image-book.png',
                    'review' => mb_substr($review['review'], 0, 100) . '...',
                    'rating' => $review['rating'],
                    'url' => $url
                ];
            }
        }
    }
    
    // 総件数を計算
    $results['total'] = count($results['books']) + count($results['authors']) + count($results['reviews']);
    
} catch (Exception $e) {
    error_log('Global search error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'データベースエラーが発生しました']);
    exit;
}

echo json_encode($results);
?>