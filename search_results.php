<?php
/**
 * グローバル検索結果ページ
 */

require_once('modern_config.php');
require_once(dirname(__FILE__) . '/library/navigation_helper.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// 検索クエリを取得
$search_query = $_GET['q'] ?? '';
$search_type = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// 検索結果を格納
$results = [
    'books' => [],
    'authors' => [],
    'reviews' => [],
    'total' => 0
];

// 検索実行
if (strlen($search_query) >= 2) {
    try {
        // 1. 本の検索
        if ($search_type === 'all' || $search_type === 'books') {
            $offset = ($page - 1) * $per_page;
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
                    bl.memo,
                    bl.finished_date,
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
                LIMIT ? OFFSET ?
            ";
            
            $params = [
                $search_query . '%',
                '%' . $search_query . '%',
                $user_id,
                '%' . $search_query . '%',
                '%' . $search_query . '%',
                $per_page,
                $offset
            ];
            
            $book_results = $g_db->getAll($book_sql, $params, DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($book_results)) {
                $results['books'] = $book_results;
            }
            
            // 総件数を取得
            $count_sql = "
                SELECT COUNT(DISTINCT bl.book_id)
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND (
                    bl.name LIKE ?
                    OR COALESCE(br.author, bl.author, '') LIKE ?
                )
            ";
            $total_books = $g_db->getOne($count_sql, [$user_id, '%' . $search_query . '%', '%' . $search_query . '%']);
            if (!DB::isError($total_books)) {
                $results['total'] = intval($total_books);
            }
        }
        
        // 2. 著者の検索
        if ($search_type === 'authors') {
            $offset = ($page - 1) * $per_page;
            $author_sql = "
                SELECT DISTINCT
                    COALESCE(br.author, bl.author, '') as author,
                    COUNT(DISTINCT bl.book_id) as book_count,
                    AVG(CASE WHEN bl.rating > 0 THEN bl.rating ELSE NULL END) as avg_rating,
                    GROUP_CONCAT(DISTINCT bl.name ORDER BY bl.update_date DESC SEPARATOR '|||') as book_titles
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND COALESCE(br.author, bl.author, '') LIKE ?
                AND COALESCE(br.author, bl.author, '') != ''
                GROUP BY COALESCE(br.author, bl.author, '')
                ORDER BY book_count DESC
                LIMIT ? OFFSET ?
            ";
            
            $author_results = $g_db->getAll($author_sql, [$user_id, '%' . $search_query . '%', $per_page, $offset], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($author_results)) {
                foreach ($author_results as &$author) {
                    $author['book_titles'] = explode('|||', $author['book_titles']);
                    $author['book_titles'] = array_slice($author['book_titles'], 0, 3);
                }
                $results['authors'] = $author_results;
            }
            
            // 総件数を取得
            $count_sql = "
                SELECT COUNT(DISTINCT COALESCE(br.author, bl.author, ''))
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND COALESCE(br.author, bl.author, '') LIKE ?
                AND COALESCE(br.author, bl.author, '') != ''
            ";
            $total_authors = $g_db->getOne($count_sql, [$user_id, '%' . $search_query . '%']);
            if (!DB::isError($total_authors)) {
                $results['total'] = intval($total_authors);
            }
        }
        
        // 3. レビューの検索
        if ($search_type === 'reviews') {
            $offset = ($page - 1) * $per_page;
            $review_sql = "
                SELECT 
                    bl.book_id,
                    bl.name as title,
                    COALESCE(br.author, bl.author, '') as author,
                    bl.image_url,
                    bl.rating,
                    bl.memo,
                    bl.update_date,
                    bl.finished_date
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.user_id = ?
                AND bl.memo LIKE ?
                AND bl.memo != ''
                ORDER BY bl.update_date DESC
                LIMIT ? OFFSET ?
            ";
            
            $review_results = $g_db->getAll($review_sql, [$user_id, '%' . $search_query . '%', $per_page, $offset], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($review_results)) {
                $results['reviews'] = $review_results;
            }
            
            // 総件数を取得
            $count_sql = "
                SELECT COUNT(*)
                FROM b_book_list bl
                WHERE bl.user_id = ?
                AND bl.memo LIKE ?
                AND bl.memo != ''
            ";
            $total_reviews = $g_db->getOne($count_sql, [$user_id, '%' . $search_query . '%']);
            if (!DB::isError($total_reviews)) {
                $results['total'] = intval($total_reviews);
            }
        }
        
    } catch (Exception $e) {
        error_log('Search error: ' . $e->getMessage());
    }
}

// ページネーション計算
$total_pages = ceil($results['total'] / $per_page);

// メタ情報
$d_site_title = "「{$search_query}」の検索結果 - ReadNest";
$g_meta_description = "「{$search_query}」の検索結果。本、著者、レビューから検索しています。";
$g_meta_keyword = "検索,{$search_query},ReadNest";

// パンくずリスト
$breadcrumbs = [
    ['label' => 'ホーム', 'url' => '/'],
    ['label' => '検索結果', 'url' => null]
];

// テンプレートを読み込み
include(getTemplatePath('t_search_results.php'));