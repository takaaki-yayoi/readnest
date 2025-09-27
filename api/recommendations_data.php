<?php
/**
 * 推薦データAPI - 非同期読み込み用
 * recommendations.phpの重い処理部分をAPIとして提供
 */

// エラー出力を制御
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 出力バッファリング開始
ob_start();

// JSON出力の設定
header('Content-Type: application/json; charset=utf-8');

try {
    require_once(dirname(__DIR__) . '/modern_config.php');
    require_once(dirname(__DIR__) . '/library/vector_similarity.php');
    
    // 動的エンベディング生成クラスを条件付きで読み込み
    $dynamic_embedding_path = dirname(__DIR__) . '/library/dynamic_embedding_generator.php';
    if (file_exists($dynamic_embedding_path)) {
        require_once($dynamic_embedding_path);
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'システムエラー: ' . $e->getMessage()]);
    exit;
}

// ログインチェック
if (!isset($_SESSION['AUTH_USER'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// パラメータ取得
$rec_type = $_GET['type'] ?? 'recommended';
$base_book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : null;
$force_refresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';

// キャッシュキーの生成
$cache_key = 'recommendations_' . $user_id . '_' . $rec_type;
if ($base_book_id) {
    $cache_key .= '_' . $base_book_id;
}

// セッションベースのキャッシュチェック（recommendedタイプのみ）
if ($rec_type === 'recommended' && !$force_refresh && !$base_book_id) {
    if (isset($_SESSION[$cache_key]) && isset($_SESSION[$cache_key . '_time'])) {
        // キャッシュが24時間以内なら使用
        if (time() - $_SESSION[$cache_key . '_time'] < 86400) {
            ob_end_clean();
            echo json_encode($_SESSION[$cache_key]);
            exit;
        }
    }
}

// 既に持っている本のASINリスト（推薦から除外するため）
$owned_books_sql = "SELECT amazon_id FROM b_book_list WHERE user_id = ?";
$owned_books_result = $g_db->getAll($owned_books_sql, [$user_id], DB_FETCHMODE_ASSOC);
$owned_books = [];
if (!DB::isError($owned_books_result)) {
    $owned_books = array_column($owned_books_result, 'amazon_id');
}

// 推薦データの配列
$recommendations = [];
$base_book_info = null;
$highly_rated_books = [];

// タイプ別の推薦処理（recommendations.phpからコピー）
switch ($rec_type) {
    case 'similar':
        // 特定の本に基づく推薦
        if ($base_book_id) {
            $base_book_sql = "
                SELECT 
                    bl.book_id,
                    bl.amazon_id,
                    bl.name as title,
                    bl.author,
                    br.combined_embedding,
                    br.image_url,
                    br.description,
                    br.google_categories,
                    bl.rating
                FROM b_book_list bl
                LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                WHERE bl.book_id = ? AND bl.user_id = ?
            ";
            $base_book_info = $g_db->getRow($base_book_sql, [$base_book_id, $user_id], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($base_book_info) && $base_book_info) {
                // embeddingがない場合は動的生成を試みる
                if (empty($base_book_info['combined_embedding']) && class_exists('DynamicEmbeddingGenerator')) {
                    try {
                        $generator = new DynamicEmbeddingGenerator();
                        $book_data = [
                            'asin' => $base_book_info['amazon_id'],
                            'title' => $base_book_info['title'],
                            'author' => $base_book_info['author'],
                            'description' => $base_book_info['description'] ?? '',
                            'google_categories' => $base_book_info['google_categories'] ?? ''
                        ];
                        
                        $generated_embedding = $generator->generateBookEmbedding($book_data);
                        if ($generated_embedding) {
                            $base_book_info['combined_embedding'] = $generated_embedding;
                            
                            // 生成したembeddingをDBに保存
                            $update_sql = "UPDATE b_book_repository SET combined_embedding = ? WHERE asin = ?";
                            $g_db->query($update_sql, [$generated_embedding, $base_book_info['amazon_id']]);
                        }
                    } catch (Exception $e) {
                        error_log("Embedding generation failed: " . $e->getMessage());
                    }
                }
                
                // 類似本を検索
                if (!empty($base_book_info['combined_embedding'])) {
                    $recommendations = getEmbeddingSimilarBooks(
                        $base_book_info['combined_embedding'],
                        $owned_books,
                        20
                    );
                    
                    // 基準本の情報を各推薦に追加
                    foreach ($recommendations as &$rec) {
                        $rec['base_book_title'] = $base_book_info['title'];
                        $rec['base_book_author'] = $base_book_info['author'];
                    }
                }
            }
        }
        break;
        
    case 'author':
        // お気に入り作家の新作
        $favorite_authors_sql = "
            SELECT 
                br.author,
                COUNT(DISTINCT bl.amazon_id) as book_count,
                AVG(CASE WHEN bl.rating > 0 THEN bl.rating ELSE NULL END) as avg_rating
            FROM b_book_list bl
            INNER JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.status IN (2, 3, 4)
            AND br.author IS NOT NULL
            AND br.author != ''
            GROUP BY br.author
            HAVING book_count >= 2
            ORDER BY book_count DESC, avg_rating DESC
            LIMIT 10
        ";
        $favorite_authors = $g_db->getAll($favorite_authors_sql, [$user_id], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($favorite_authors) && !empty($favorite_authors)) {
            $author_names = array_column($favorite_authors, 'author');
            
            // 安全なクエリを構築
            $author_placeholders = array_fill(0, count($author_names), '?');
            $params = $author_names;
            
            // 除外リストの処理
            $exclude_clause = "";
            if (!empty($owned_books)) {
                $exclude_clause = "AND br.asin NOT IN ('" . implode("','", array_map('addslashes', $owned_books)) . "')";
            }
            
            $author_books_sql = "
                SELECT 
                    br.asin as amazon_id,
                    br.title,
                    br.author,
                    br.image_url,
                    br.description,
                    (SELECT AVG(rating) FROM b_book_list WHERE amazon_id = br.asin AND rating > 0) as avg_rating,
                    (SELECT COUNT(*) FROM b_book_list WHERE amazon_id = br.asin) as reader_count
                FROM b_book_repository br
                WHERE br.author IN (" . implode(',', $author_placeholders) . ")
                $exclude_clause
                ORDER BY reader_count DESC, avg_rating DESC
                LIMIT 20
            ";
            
            $author_recommendations = $g_db->getAll($author_books_sql, $params, DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($author_recommendations)) {
                $recommendations = $author_recommendations;
            }
        }
        break;
        
    case 'popular':
        // 人気の本
        $exclude_list = !empty($owned_books) ? "AND br.asin NOT IN ('" . implode("','", array_map('addslashes', $owned_books)) . "')" : "";
        
        $popular_sql = "
            SELECT 
                br.asin as amazon_id,
                br.title,
                br.author,
                br.image_url,
                br.description,
                AVG(bl.rating) as avg_rating,
                COUNT(DISTINCT bl.user_id) as reader_count,
                MAX(bl.update_date) as last_read
            FROM b_book_repository br
            INNER JOIN b_book_list bl ON br.asin = bl.amazon_id
            WHERE bl.status IN (3, 4)
            AND bl.rating >= 4
            $exclude_list
            GROUP BY br.asin
            HAVING reader_count >= 1
            ORDER BY avg_rating DESC, reader_count DESC, last_read DESC
            LIMIT 20
        ";
        
        $popular_books = $g_db->getAll($popular_sql, [], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($popular_books)) {
            $recommendations = $popular_books;
        }
        break;
        
    case 'recommended':
    default:
        // パーソナライズされたおすすめ（高評価本ベース）
        
        // すべての高評価本を取得
        $highly_rated_sql = "
            SELECT 
                bl.book_id,
                bl.amazon_id,
                bl.name as title,
                bl.author,
                bl.rating,
                br.image_url,
                br.combined_embedding,
                br.description,
                br.google_categories
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND bl.rating >= 4
            ORDER BY bl.rating DESC, bl.update_date DESC
        ";
        $all_highly_rated = $g_db->getAll($highly_rated_sql, [$user_id], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($all_highly_rated) && !empty($all_highly_rated)) {
            // ランダムに10冊選択（またはすべて if < 10）
            $num_to_select = min(10, count($all_highly_rated));
            
            // キャッシュがない場合、または強制リフレッシュの場合はランダムに選択
            if ($force_refresh || !isset($_SESSION[$cache_key])) {
                // シャッフルして最初の10冊を選択
                shuffle($all_highly_rated);
                $highly_rated_books_full = array_slice($all_highly_rated, 0, $num_to_select);
            } else {
                // キャッシュがある場合は通常の順序で選択
                $highly_rated_books_full = array_slice($all_highly_rated, 0, $num_to_select);
            }
        } else {
            $highly_rated_books_full = [];
        }
        
        if (!DB::isError($highly_rated_books_full) && !empty($highly_rated_books_full)) {
            // 表示用の高評価本リスト（embeddingを除く）
            foreach ($highly_rated_books_full as $book) {
                $highly_rated_books[] = [
                    'book_id' => $book['book_id'],
                    'amazon_id' => $book['amazon_id'],
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'rating' => $book['rating'],
                    'image_url' => $book['image_url']
                ];
            }
            
            // 高評価本からembeddingがあるものを優先
            $books_with_embedding = [];
            $books_without_embedding = [];
            
            foreach ($highly_rated_books_full as $book) {
                if (!empty($book['combined_embedding'])) {
                    $books_with_embedding[] = $book;
                } else {
                    $books_without_embedding[] = $book;
                }
            }
            
            // 動的にembeddingを生成（最大3冊まで）
            if (!empty($books_without_embedding) && class_exists('DynamicEmbeddingGenerator')) {
                $generator = new DynamicEmbeddingGenerator();
                $generated_count = 0;
                
                foreach ($books_without_embedding as $book) {
                    if ($generated_count >= 3) break;
                    
                    try {
                        $book_data = [
                            'asin' => $book['amazon_id'],
                            'title' => $book['title'],
                            'author' => $book['author'],
                            'description' => $book['description'] ?? '',
                            'google_categories' => $book['google_categories'] ?? ''
                        ];
                        
                        $generated_embedding = $generator->generateBookEmbedding($book_data);
                        if ($generated_embedding) {
                            $book['combined_embedding'] = $generated_embedding;
                            $books_with_embedding[] = $book;
                            $generated_count++;
                            
                            // DBに保存
                            $update_sql = "UPDATE b_book_repository SET combined_embedding = ? WHERE asin = ?";
                            $g_db->query($update_sql, [$generated_embedding, $book['amazon_id']]);
                        }
                    } catch (Exception $e) {
                        error_log("Embedding generation failed: " . $e->getMessage());
                    }
                }
            }
            
            // 各本に基づいて類似本を検索
            $all_similar_books = [];
            
            // embeddingがある本から最大3冊をランダムに選択して処理
            $max_books_to_process = min(3, count($books_with_embedding));
            
            // ランダムに選択するためシャッフル
            if ($max_books_to_process > 0) {
                shuffle($books_with_embedding);
            }
            
            for ($i = 0; $i < $max_books_to_process; $i++) {
                $book = $books_with_embedding[$i];
                $similar_books = getEmbeddingSimilarBooks(
                    $book['combined_embedding'],
                    $owned_books,
                    10
                );
                
                foreach ($similar_books as $similar) {
                    $similar['base_book_title'] = $book['title'];
                    $similar['base_book_author'] = $book['author'];
                    $similar['base_book_rating'] = $book['rating'];
                    
                    // 既に追加されている本は除外
                    $already_exists = false;
                    foreach ($all_similar_books as $existing) {
                        if ($existing['amazon_id'] === $similar['amazon_id']) {
                            $already_exists = true;
                            break;
                        }
                    }
                    
                    if (!$already_exists) {
                        $all_similar_books[] = $similar;
                    }
                }
            }
            
            // 類似度でソート
            usort($all_similar_books, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });
            
            // 上位20冊を推薦
            $recommendations = array_slice($all_similar_books, 0, 20);
        }
        break;
}

// レスポンス構造
$response = [
    'success' => true,
    'type' => $rec_type,
    'recommendations' => $recommendations,
    'highly_rated_books' => $highly_rated_books,
    'base_book_info' => $base_book_info
];

// キャッシュに保存（recommendedタイプの場合のみ）
if ($rec_type === 'recommended' && !$base_book_id) {
    $_SESSION[$cache_key] = $response;
    $_SESSION[$cache_key . '_time'] = time();
}

// バッファの内容を取得してクリア
$output = ob_get_contents();
ob_end_clean();

// 予期しない出力があった場合はログに記録
if (!empty($output)) {
    error_log('Recommendations API Unexpected output: ' . $output);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>