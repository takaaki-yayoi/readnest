<?php
/**
 * お気に入り本一覧ページ
 */

require_once('modern_config.php');
require_once(dirname(__FILE__) . '/library/favorite_functions.php');
require_once(__DIR__ . '/library/vector_similarity.php');
require_once(__DIR__ . '/library/dynamic_embedding_generator.php');
require_once(__DIR__ . '/library/form_helpers.php');

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;

// 表示対象ユーザーの決定
$target_user_id = null;
$is_own_favorites = false;

// user_id パラメータの処理
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $target_user_id = (int)$_GET['user_id'];
    $is_own_favorites = ($login_flag && $target_user_id == $mine_user_id);
} elseif ($login_flag) {
    // user_idパラメータがない場合は自分のお気に入りを表示
    $target_user_id = $mine_user_id;
    $is_own_favorites = true;
} else {
    // ログインしていない場合はトップページへ
    header('Location: /');
    exit;
}

// ターゲットユーザーの情報を取得
$user_sql = sprintf("SELECT user_id, nickname, diary_policy FROM b_user WHERE user_id = %d", (int)$target_user_id);
$user_info = $g_db->getRow($user_sql, null, DB_FETCHMODE_ASSOC);

if (DB::isError($user_info) || !$user_info) {
    // ユーザーが存在しない場合
    header('Location: /');
    exit;
}

$target_nickname = $user_info['nickname'];
$is_public_user = ($user_info['diary_policy'] == 1);

// 自分のお気に入りページでない場合、公開設定をチェック
if (!$is_own_favorites && !$is_public_user) {
    // 非公開ユーザーのお気に入りは見れない
    header('Location: /profile.php?user_id=' . $target_user_id);
    exit;
}

// 公開設定の更新処理（自分のページの場合のみ）
if ($is_own_favorites && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRFトークン検証
    requireCSRFToken();
    
    if ($_POST['action'] === 'update_privacy' && isset($_POST['book_id']) && isset($_POST['is_public'])) {
        $book_id = (int)$_POST['book_id'];
        $is_public = (int)$_POST['is_public'];
        updateFavoritePrivacy($mine_user_id, $book_id, $is_public);
        
        // Ajax リクエストの場合はJSONレスポンスを返す
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    } elseif ($_POST['action'] === 'update_all_privacy' && isset($_POST['is_public'])) {
        $is_public = (int)$_POST['is_public'];
        updateAllFavoritesPrivacy($mine_user_id, $is_public);
        
        // リダイレクトして更新を反映
        header('Location: /favorites.php');
        exit;
    }
}

// お気に入り本を取得（他人の場合は公開のみ）
$public_only = !$is_own_favorites;
$favorites = getUserFavoriteBooks($target_user_id, 100, 0, $public_only);

// 本の詳細情報を整形
$books = [];
foreach ($favorites as $fav) {
    $books[] = [
        'book_id' => $fav['book_id'],
        'title' => html($fav['name']),
        'author' => html($fav['author'] ?: '著者不明'),
        'image_url' => !empty($fav['image_url']) && $fav['image_url'] !== 'NULL' ? $fav['image_url'] : '/img/no-image-book.png',
        'status' => $fav['status'],
        'rating' => $fav['rating'],
        'current_page' => $fav['current_page'],
        'total_page' => $fav['total_page'],
        'favorite_date' => date('Y/m/d', strtotime($fav['favorite_date'])),
        'update_date' => !empty($fav['update_date']) ? date('Y/m/d', strtotime($fav['update_date'])) : date('Y/m/d', strtotime($fav['favorite_date'])),
        'is_public' => isset($fav['is_public']) ? $fav['is_public'] : 0,
        'is_favorite' => true,
        'amazon_id' => $fav['amazon_id'] ?? null
    ];
}

// AI推薦を生成（自分のお気に入りページの場合のみ）
$ai_recommendations = [];
if ($is_own_favorites && !empty($favorites)) {
    $generator = new DynamicEmbeddingGenerator();
    $all_recommendations = [];
    
    // 最大5冊のお気に入り本を使用
    $favorites_for_embedding = array_slice($favorites, 0, 5);
    
    // まず全お気に入り本のembeddingを取得
    $favorite_embeddings = [];
    foreach ($favorites_for_embedding as $fav) {
        if (!empty($fav['amazon_id'])) {
            $embedding_sql = "
                SELECT combined_embedding, description, google_categories
                FROM b_book_repository 
                WHERE asin = ?
            ";
            $embedding_result = $g_db->getRow($embedding_sql, [$fav['amazon_id']], DB_FETCHMODE_ASSOC);
            
            $book_embedding = null;
            
            if (!DB::isError($embedding_result)) {
                if (!empty($embedding_result['combined_embedding'])) {
                    $book_embedding = $embedding_result['combined_embedding'];
                } else {
                    // embeddingがない場合は動的生成
                    $book_data = [
                        'asin' => $fav['amazon_id'],
                        'title' => $fav['name'],
                        'author' => $fav['author'] ?? '',
                        'description' => $embedding_result['description'] ?? '',
                        'google_categories' => $embedding_result['google_categories'] ?? ''
                    ];
                    
                    $book_embedding = $generator->generateBookEmbedding($book_data);
                }
            }
            
            if ($book_embedding) {
                $favorite_embeddings[] = [
                    'embedding' => $book_embedding,
                    'book_info' => $fav
                ];
            }
        }
    }
    
    // 候補本を一度だけ取得
    if (!empty($favorite_embeddings)) {
        // 既に持っている本のASINリストを作成
        $owned_asins = [];
        foreach ($favorites as $f) {
            if (!empty($f['amazon_id'])) {
                $owned_asins[] = $f['amazon_id'];
            }
        }
        
        $exclude_list = !empty($owned_asins) ? "AND br.asin NOT IN ('" . implode("','", $owned_asins) . "')" : "";
        
        $candidates_sql = "
            SELECT 
                br.asin,
                br.title,
                br.author,
                br.image_url,
                br.description,
                br.combined_embedding,
                (SELECT COUNT(*) FROM b_book_list WHERE amazon_id = br.asin) as reader_count,
                (SELECT AVG(rating) FROM b_book_list WHERE amazon_id = br.asin AND rating > 0) as avg_rating
            FROM b_book_repository br
            WHERE br.combined_embedding IS NOT NULL
            $exclude_list
            LIMIT 200
        ";
        
        $candidates = $g_db->getAll($candidates_sql, [], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($candidates) && $candidates) {
            // 各候補本に対して、全お気に入り本との類似度を計算
            foreach ($candidates as $candidate) {
                $best_similarity = 0;
                $best_base_book = null;
                
                // 各お気に入り本との類似度を計算
                foreach ($favorite_embeddings as $fav_emb) {
                    $similarity = VectorSimilarity::cosineSimilarity(
                        $fav_emb['embedding'],
                        $candidate['combined_embedding']
                    );
                    
                    // 最も類似度の高いお気に入り本を記録
                    if ($similarity > $best_similarity) {
                        $best_similarity = $similarity;
                        $best_base_book = $fav_emb['book_info'];
                    }
                }
                
                // 70%以上の類似度があれば推薦リストに追加
                if ($best_similarity > 0.7 && $best_base_book) {
                    $rec = [
                        'asin' => $candidate['asin'],
                        'title' => $candidate['title'],
                        'author' => $candidate['author'],
                        'image_url' => $candidate['image_url'] ?? '/img/no-image-book.png',
                        'description' => $candidate['description'] ?? '',
                        'similarity' => round($best_similarity * 100, 1),
                        'reader_count' => $candidate['reader_count'] ?? 0,
                        'avg_rating' => round((float)($candidate['avg_rating'] ?? 0), 1),
                        'base_book_title' => $best_base_book['name'],
                        'base_book_author' => $best_base_book['author'] ?? '',
                        'base_book_id' => $best_base_book['book_id']
                    ];
                    
                    $all_recommendations[] = $rec;
                }
            }
        }
    }
    
    // 類似度でソート
    usort($all_recommendations, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });
    
    // 上位10件に限定
    $ai_recommendations = array_slice($all_recommendations, 0, 10);
}

/**
 * 複数のEmbeddingの平均を計算
 */
function calculateAverageEmbedding($embeddings) {
    if (empty($embeddings)) {
        return null;
    }
    
    $dimension = count($embeddings[0]);
    $avg = array_fill(0, $dimension, 0);
    
    foreach ($embeddings as $embedding) {
        for ($i = 0; $i < $dimension; $i++) {
            $avg[$i] += $embedding[$i];
        }
    }
    
    $count = count($embeddings);
    for ($i = 0; $i < $dimension; $i++) {
        $avg[$i] /= $count;
    }
    
    return $avg;
}

// ページメタ情報
if ($is_own_favorites) {
    $d_site_title = 'お気に入りの本 - ReadNest';
    $g_meta_description = 'お気に入りに登録した本の一覧';
} else {
    $d_site_title = html($target_nickname) . 'さんのお気に入りの本 - ReadNest';
    $g_meta_description = html($target_nickname) . 'さんがお気に入りに登録した本の一覧';
}
$g_meta_keyword = 'お気に入り,本,読書,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_favorites.php'));
?>