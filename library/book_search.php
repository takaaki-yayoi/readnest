<?php
/**
 * 本検索ライブラリ
 * Google Books API使用
 */

if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
}

require_once(dirname(__FILE__) . '/memory_utils.php');
require_once(dirname(__FILE__) . '/book_image_helper.php');

/**
 * ISBNかどうかを判定
 */
function isISBN($keyword) {
    $keyword = trim($keyword);
    
    // ハイフンを除去
    $clean_keyword = str_replace(['-', ' '], '', $keyword);
    
    // ISBN-10: 10桁（最後の文字は X の場合もある）
    if (preg_match('/^[0-9]{9}[0-9Xx]$/', $clean_keyword)) {
        return true;
    }
    
    // ISBN-13: 13桁（978または979で始まる）
    if (preg_match('/^(978|979)[0-9]{10}$/', $clean_keyword)) {
        return true;
    }
    
    return false;
}

/**
 * 検索クエリを構築
 */
function buildSearchQuery($keyword) {
    // キーワードをトリム
    $keyword = trim($keyword);
    
    // 短すぎるキーワードはそのまま返す
    if (mb_strlen($keyword) <= 2) {
        return $keyword;
    }
    
    // ISBNの場合は専用の検索クエリを構築
    if (isISBN($keyword)) {
        $clean_isbn = str_replace(['-', ' '], '', $keyword);
        return "isbn:{$clean_isbn}";
    }
    
    // 基本的にはキーワードをそのまま使用（Google Books APIが自動的に関連性を判断）
    return $keyword;
}

/**
 * Google Books APIで本を検索
 */
function searchBooksWithGoogleAPI($keyword, $page = 1, $max_results = 20) {
    // メモリ使用量制限
    $memory_limit = ini_get('memory_limit');
    if ($memory_limit !== '-1') {
        $memory_bytes = convertToBytes($memory_limit);
        $current_usage = memory_get_usage(true);
        // 利用可能メモリの80%を超えている場合は検索を制限
        if ($current_usage > $memory_bytes * 0.8) {
            error_log("Memory usage high: " . round($current_usage / 1024 / 1024, 2) . "MB of " . $memory_limit);
            return ['books' => [], 'total' => 0];
        }
    }
    
    $original_keyword = $keyword;
    $start_index = ($page - 1) * $max_results;
    
    // キーワードの前処理
    $keyword = trim($keyword);
    
    // 複数の検索戦略を試す
    $search_strategies = [];
    
    // ISBNかどうかを判定
    if (isISBN($keyword)) {
        // ISBNの場合は専用検索のみ
        $clean_isbn = str_replace(['-', ' '], '', $keyword);
        $search_strategies[] = "isbn:{$clean_isbn}";
        
        // フォールバック：ハイフンなしの数字で検索
        $search_strategies[] = $clean_isbn;
    } else {
        // 通常のキーワード検索
        
        // キーワードを分析して著者名が含まれている可能性を判定
        $words = preg_split('/[\s　]+/u', $keyword);
        $has_multiple_words = count($words) > 1;
        
        // search_typeパラメータがauthorの場合は著者検索を優先
        $search_type = $_GET['search_type'] ?? null;
        if ($search_type === 'author') {
            // 著者名での検索を最優先
            $search_strategies[] = 'inauthor:' . $keyword;
            // フォールバック：通常検索
            $search_strategies[] = $keyword;
        } else {
            // 1. まず通常の全文検索（タイトル、著者、出版社など全てを対象）
            // これがGoogle Books APIで最も効果的
            $search_strategies[] = $keyword;
            
            // 2. タイトルに絞った検索（単一キーワードまたは短いフレーズの場合）
            if (!$has_multiple_words || mb_strlen($keyword) <= 20) {
                $search_strategies[] = 'intitle:' . $keyword;
            }
        }
    }
    
    $all_books = [];
    $total_items = 0;
    
    // 最大2つの戦略まで試す（通常は最初の戦略で十分）
    $max_strategies = min(2, count($search_strategies));
    
    for ($strategy_index = 0; $strategy_index < $max_strategies; $strategy_index++) {
        $search_query = $search_strategies[$strategy_index];
        $encoded_query = urlencode($search_query);
        
        // Google Books API URL - 日本語の本を優先
        $api_url = "https://www.googleapis.com/books/v1/volumes?q={$encoded_query}&startIndex={$start_index}&maxResults={$max_results}&langRestrict=ja&orderBy=relevance";

        // APIキーが設定されていれば追加（レート制限緩和のため）
        if (defined('GOOGLE_BOOKS_API_KEY') && !empty(GOOGLE_BOOKS_API_KEY)) {
            $api_url .= "&key=" . GOOGLE_BOOKS_API_KEY;
        }
        
        error_log("Google Books API search strategy {$strategy_index}: {$search_query}");
        
        // HTTPコンテキストオプションを設定
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'user_agent' => 'ReadNest/1.0',
                'ignore_errors' => true
            ]
        ]);
        
        // APIリクエスト
        $response = @file_get_contents($api_url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            error_log("Google Books API request failed for strategy {$strategy_index}: " . ($error['message'] ?? 'Unknown error'));
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (!$data) {
            error_log("Google Books API: Failed to decode JSON response for strategy {$strategy_index}");
            continue;
        }
        
        if (isset($data['items']) && !empty($data['items'])) {
            error_log("Google Books API: Found " . count($data['items']) . " items with strategy {$strategy_index}");
            $all_books = $data['items'];
            $total_items = $data['totalItems'] ?? count($data['items']);
            break; // 結果が見つかったら終了
        }
    }
    
    if (empty($all_books)) {
        error_log("Google Books API: No items found for any strategy");
        return ['books' => [], 'total' => 0];
    }
    
    $books = [];
    
    foreach ($all_books as $item) {
        $volume_info = $item['volumeInfo'] ?? [];
        
        // 基本情報
        $title = $volume_info['title'] ?? '不明なタイトル';
        $authors = $volume_info['authors'] ?? ['不明な著者'];
        $author = is_array($authors) ? implode(', ', $authors) : $authors;
        $description = $volume_info['description'] ?? '';
        
        // 関連性チェックは廃止（Google APIの結果をそのまま信頼）
        // ユーザーが検索したキーワードに対してAPIが返した結果はすべて表示
        
        // 識別子
        $isbn = '';
        $google_id = $item['id'] ?? '';
        
        if (isset($volume_info['industryIdentifiers'])) {
            foreach ($volume_info['industryIdentifiers'] as $identifier) {
                if ($identifier['type'] === 'ISBN_13' || $identifier['type'] === 'ISBN_10') {
                    $isbn = $identifier['identifier'];
                    break;
                }
            }
        }
        
        // 画像（より高解像度を優先）
        $image_url = '';
        if (isset($volume_info['imageLinks'])) {
            // 高解像度から低解像度の順で選択
            $image_url = $volume_info['imageLinks']['large'] ?? 
                        $volume_info['imageLinks']['medium'] ?? 
                        $volume_info['imageLinks']['small'] ?? 
                        $volume_info['imageLinks']['thumbnail'] ?? 
                        $volume_info['imageLinks']['smallThumbnail'] ?? '';
            // HTTPSに変換
            $image_url = str_replace('http://', 'https://', $image_url);
            // zoom=1パラメータを追加（zoom=2は時々失敗するため、より安定したzoom=1を使用）
            if (!empty($image_url)) {
                $image_url = str_replace('&edge=curl', '', $image_url); // 不要なパラメータを削除
                if (strpos($image_url, 'zoom=') === false) {
                    $image_url .= '&zoom=1'; // zoom=1で標準解像度
                } else {
                    // 既存のzoomパラメータを1に変更
                    $image_url = preg_replace('/zoom=\d/', 'zoom=1', $image_url);
                }
            }
        }
        
        // ISBNを使った代替画像URLの生成
        if (empty($image_url) && !empty($isbn)) {
            // OpenLibrary の画像APIをフォールバックとして使用
            $image_url = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg";
        }
        
        // タイトルと著者でGoogle画像検索用のURLを生成（最終手段）
        if (empty($image_url) && !empty($title)) {
            // 画像が全くない場合のデバッグログ
            error_log("No image found for book: {$title} (ISBN: {$isbn}, Google ID: {$google_id})");
        }
        
        // その他の情報
        $page_count = $volume_info['pageCount'] ?? 0;
        $published_date = $volume_info['publishedDate'] ?? '';
        $preview_link = $volume_info['previewLink'] ?? '';
        
        // カテゴリ情報を取得
        $categories = $volume_info['categories'] ?? [];
        
        // BookImageHelperを使用して信頼性の高い画像URLを取得
        $finalImageUrl = '';
        if (!empty($image_url) || !empty($isbn)) {
            $imageHelper = getBookImageHelper();
            $finalImageUrl = $imageHelper->getReliableImageUrl($image_url, $isbn);
        }
        
        // データの安全性を確保
        $books[] = [
            'Title' => !empty($title) ? $title : '不明なタイトル',
            'Author' => !empty($author) ? $author : '不明な著者',
            'ASIN' => !empty($google_id) ? $google_id : uniqid('gbook_'), // Google Books IDをASINの代わりに使用
            'ISBN' => $isbn,
            'LargeImage' => $finalImageUrl,
            'DetailPageURL' => !empty($preview_link) ? $preview_link : '',
            'NumberOfPages' => max(0, $page_count),
            'PublishedDate' => $published_date,
            'Description' => mb_substr($description, 0, 200) . (mb_strlen($description) > 200 ? '...' : ''),
            'Categories' => $categories // カテゴリ情報を追加
        ];
    }
    
    // Google Books APIから返された総数を使用
    $actual_total = count($books);
    
    // 実際の結果数が0の場合は0を返す
    if ($actual_total === 0) {
        return [
            'books' => $books,
            'total' => 0
        ];
    }
    
    // APIが返した総数を信頼（ただし現実的な範囲に制限）
    $final_total = min(1000, max($actual_total, $total_items));
    
    error_log("Search results: actual={$actual_total}, total_items={$total_items}, final={$final_total}");
    
    // 画像がない本の数を記録
    $books_without_images = 0;
    foreach ($books as $book) {
        if (empty($book['LargeImage']) || $book['LargeImage'] === '') {
            $books_without_images++;
            error_log("Book without image: " . $book['Title'] . " (ISBN: " . $book['ISBN'] . ")");
        }
    }
    if ($books_without_images > 0) {
        error_log("Books without images: {$books_without_images} out of {$actual_total}");
    }
    
    return [
        'books' => $books,
        'total' => $final_total
    ];
}

/**
 * 楽天ブックスAPIで本を検索（フォールバック）
 */
function searchBooksWithRakutenAPI($keyword, $page = 1) {
    $keyword = urlencode($keyword);
    $application_id = '1001'; // ダミーのアプリケーションID
    
    // 楽天ブックス API URL
    $api_url = "https://app.rakuten.co.jp/services/api/BooksBook/Search/20170404?format=json&keyword={$keyword}&page={$page}&applicationId={$application_id}";
    
    $response = @file_get_contents($api_url);
    
    if ($response === false) {
        return ['books' => [], 'total' => 0];
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['Items'])) {
        return ['books' => [], 'total' => 0];
    }
    
    $books = [];
    foreach ($data['Items'] as $item) {
        $book_data = $item['Item'] ?? [];
        
        $books[] = [
            'Title' => $book_data['title'] ?? '不明なタイトル',
            'Author' => $book_data['author'] ?? '不明な著者',
            'ASIN' => $book_data['isbn'] ?? '',
            'ISBN' => $book_data['isbn'] ?? '',
            'LargeImage' => $book_data['largeImageUrl'] ?? '',
            'DetailPageURL' => $book_data['itemUrl'] ?? '',
            'NumberOfPages' => 0,
            'PublishedDate' => $book_data['salesDate'] ?? '',
            'Description' => $book_data['itemCaption'] ?? ''
        ];
    }
    
    return [
        'books' => $books,
        'total' => $data['pageCount'] ?? 0
    ];
}

/**
 * 統合検索関数
 */
function searchBooks($keyword, $page = 1, $max_results = 20) {
    // Google Books APIで検索
    $result = searchBooksWithGoogleAPI($keyword, $page, $max_results);
    
    // 結果が得られない場合は空の結果を返す（サンプルデータは返さない）
    if (empty($result['books'])) {
        return ['books' => [], 'total' => 0];
    }
    
    return $result;
}

/**
 * キーワードに応じたサンプル本データ生成
 */
function generateSampleBooksForKeyword($keyword) {
    $sample_books = [
        [
            'Title' => "検索結果: {$keyword}に関する本",
            'Author' => '著者名',
            'ASIN' => 'SAMPLE001',
            'ISBN' => '9784000000001',
            'LargeImage' => '/img/noimage.jpg',
            'DetailPageURL' => 'https://books.google.co.jp',
            'NumberOfPages' => 200,
            'PublishedDate' => date('Y-m-d'),
            'Description' => "{$keyword}について詳しく解説した一冊です。"
        ],
        [
            'Title' => "実践 {$keyword}",
            'Author' => '専門家',
            'ASIN' => 'SAMPLE002',
            'ISBN' => '9784000000002',
            'LargeImage' => '/img/noimage.jpg',
            'DetailPageURL' => 'https://books.google.co.jp',
            'NumberOfPages' => 300,
            'PublishedDate' => date('Y-m-d'),
            'Description' => "{$keyword}の実践的な内容を扱った書籍です。"
        ]
    ];
    
    // キーワードでフィルタリング
    $filtered_books = array_filter($sample_books, function($book) use ($keyword) {
        return stripos($book['Title'], $keyword) !== false;
    });
    
    return [
        'books' => array_values($filtered_books),
        'total' => count($filtered_books)
    ];
}


/**
 * 本の関連性をチェック
 */
function isBookRelevant($keyword, $title, $author, $description) {
    // 基本的な品質チェックのみ実施
    
    // 明らかに無効な本をフィルタ（タイトルが空または意味のない場合）
    if (empty(trim($title)) || trim($title) === '不明なタイトル') {
        return false;
    }
    
    // 著者名が空の場合も除外
    if (empty(trim($author)) || trim($author) === '不明な著者') {
        return false;
    }
    
    // キーワードが非常に短い場合はすべて許可
    if (mb_strlen(trim($keyword)) <= 2) {
        return true;
    }
    
    // キーワードを正規化
    $keyword = mb_strtolower(trim($keyword));
    $title = mb_strtolower($title);
    $author = mb_strtolower($author);
    $description = mb_strtolower($description);
    
    // 検索対象テキスト
    $search_text = $title . ' ' . $author . ' ' . $description;
    
    // キーワードを分割
    $words = preg_split('/[\s　]+/u', $keyword);
    $words = array_filter($words, function($word) {
        return mb_strlen(trim($word)) >= 1; // 1文字以上の単語
    });
    
    if (empty($words)) {
        return true;
    }
    
    // 少なくとも1つの単語が含まれていれば許可
    $found_words = 0;
    foreach ($words as $word) {
        $word = trim($word);
        if (empty($word)) continue;
        
        if (mb_strpos($search_text, $word) !== false) {
            $found_words++;
        }
    }
    
    // 最低1つの単語が見つかるか、キーワードが英数字のみで5文字以下の場合は許可
    if ($found_words > 0) {
        return true;
    }
    
    // 英数字のみのキーワードで5文字以下の場合は厳しく判定
    if (preg_match('/^[a-zA-Z0-9]+$/', $keyword) && mb_strlen($keyword) <= 5) {
        return false;
    }
    
    return false;
}

/**
 * レガシー関数との互換性維持
 * Note: getBookSearchResult() function is already defined in amazon.php
 */