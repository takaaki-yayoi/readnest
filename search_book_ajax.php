<?php
/**
 * モダン版本検索AJAX API
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// JSON レスポンスを返すため、HTMLを出力しない
header('Content-Type: application/json; charset=utf-8');

// モダン設定を読み込み
require_once('modern_config.php');
require_once('library/book_search.php');

// CORS対応（必要に応じて）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // ログインチェック
    if (!checkLogin()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'ログインが必要です',
            'books' => []
        ]);
        exit;
    }
    
    $user_id = $_SESSION['AUTH_USER'];
    
    // パラメータ取得（GET/POST両方に対応）
    $keyword = trim($_GET['q'] ?? $_POST['q'] ?? $_GET['keyword'] ?? $_POST['keyword'] ?? '');
    $page = (int)($_GET['page'] ?? $_POST['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 10);
    
    // バリデーション
    if (empty($keyword)) {
        echo json_encode([
            'success' => false,
            'error' => '検索キーワードを入力してください',
            'books' => []
        ]);
        exit;
    }
    
    if (mb_strlen($keyword) < 2) {
        echo json_encode([
            'success' => false,
            'error' => '検索キーワードは2文字以上で入力してください',
            'books' => []
        ]);
        exit;
    }
    
    // 検索実行
    $search_result = searchBooks($keyword, $page, $limit);
    $books = $search_result['books'] ?? [];
    $total = $search_result['total'] ?? 0;
    
    // 結果を整形
    $formatted_books = [];
    foreach ($books as $book) {
        $asin = $book['ASIN'] ?? '';
        $is_bookmarked = !empty($asin) ? is_bookmarked($user_id, $asin) : false;
        
        $formatted_books[] = [
            'asin' => $asin,
            'isbn' => $book['ISBN'] ?? '',
            'title' => $book['Title'] ?? '',
            'author' => $book['Author'] ?? '',
            'image_url' => $book['LargeImage'] ?? '/img/noimage.jpg',
            'detail_url' => $book['DetailPageURL'] ?? '',
            'pages' => $book['NumberOfPages'] ?? 0,
            'description' => $book['Description'] ?? '',
            'is_bookmarked' => $is_bookmarked,
            'book_id' => $is_bookmarked ?: null
        ];
    }
    
    // レスポンス
    echo json_encode([
        'success' => true,
        'keyword' => $keyword,
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => (int)ceil($total / $limit),
        'books' => $formatted_books
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Search API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '検索中にエラーが発生しました',
        'books' => []
    ]);
}
?>