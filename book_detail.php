<?php
/**
 * モダン版本詳細ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// デバッグモード設定（本番環境では false に設定）
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

// キャッシュヘッダーを設定
require_once(dirname(__FILE__) . '/library/cache_headers.php');
setCacheControlHeaders();

// モダン設定を読み込み
require_once('modern_config.php');

// 本のキャッシュ機能を読み込み
require_once(dirname(__FILE__) . '/library/book_cache.php');

// レベル表示関連
$achievement_system_path = dirname(__FILE__) . '/library/achievement_system.php';
if (file_exists($achievement_system_path)) {
    require_once($achievement_system_path);
} else {
    error_log("Error: achievement_system.php not found at: " . $achievement_system_path);
}

$level_display_helper_path = dirname(__FILE__) . '/library/level_display_helper.php';
if (file_exists($level_display_helper_path)) {
    require_once($level_display_helper_path);
} else {
    error_log("Error: level_display_helper.php not found at: " . $level_display_helper_path);
}

// CSRF対策を読み込み
require_once(__DIR__ . '/library/csrf.php');
require_once(__DIR__ . '/library/form_helpers.php');

// お気に入り機能
require_once(dirname(__FILE__) . '/library/favorite_functions.php');

// AI推薦機能
require_once(__DIR__ . '/library/vector_similarity.php');
require_once(__DIR__ . '/library/dynamic_embedding_generator.php');

// レビューembedding生成
require_once(__DIR__ . '/library/review_embedding_generator.php');

// ジャンル判定ライブラリを読み込み（一時無効化）
// require_once(__DIR__ . '/library/genre_detector.php');

$login_flag = false;
$book = [];
$reviews = [];
$readers = [];
$similar_books = [];
$is_in_bookshelf = false;
$average_rating = 0;
$total_users = 0;
$total_reviews = 0;

// ログインチェック
if (checkLogin()) {
    $mine_user_id = $_SESSION['AUTH_USER'];
    $d_nickname = getNickname($mine_user_id);
    $login_flag = true;


    // 削除処理
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'delete') {
        // CSRF検証
        requireCSRFToken();
        
        deleteBook($mine_user_id, (int)$_POST['book_id']);
        // キャッシュバスターを追加してリダイレクト
        header('Location: https://readnest.jp/bookshelf.php?t=' . time());
        exit;
    }

    // 進捗更新処理
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'progress') {
        // CSRF検証
        requireCSRFToken();
        $number_of_pages = (int)$_POST['page_list'];
        $memo = sanitizeInput($_POST['memo'] ?? '');
        
        if ($number_of_pages > 0) {
            createEvent((int)$mine_user_id, (int)$_POST['book_id'], $memo, (int)$number_of_pages);
            // 成功メッセージをセッションに保存
            $_SESSION['progress_updated'] = true;
            $_SESSION['progress_page'] = $number_of_pages;
        }
        
        // リダイレクトして再読み込みを防ぐ
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    // 進捗更新処理（モダンテンプレート用）
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'update_progress') {
        $current_page = (int)($_POST['current_page'] ?? 0);
        $memo = sanitizeInput($_POST['memo'] ?? '');
        
        if ($current_page > 0) {
            createEvent((int)$mine_user_id, (int)$_POST['book_id'], $memo, (int)$current_page);
            // 成功メッセージをセッションに保存
            $_SESSION['progress_updated'] = true;
            $_SESSION['progress_page'] = $current_page;
        }
        
        // リダイレクトして再読み込みを防ぐ
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    // 読了マーク処理
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'mark_as_finished') {
        requireCSRFToken();
        
        // 現在の本の情報を取得
        $sql = "SELECT * FROM b_book_list WHERE user_id = ? AND book_id = ?";
        $current_book = $g_db->getRow($sql, [$mine_user_id, (int)$_POST['book_id']]);
        
        if ($current_book && !DB::isError($current_book)) {
            // 読了日を今日に設定
            $finished_date = date('Y-m-d');
            
            // ステータスを読了に更新
            updateBook($mine_user_id, (int)$_POST['book_id'], READING_FINISH, 
                      $current_book['rating'] ?? 0, 
                      $current_book['memo'] ?? '', 
                      $finished_date);
            
            // 成功メッセージをセッションに保存
            $_SESSION['progress_updated'] = true;
            $_SESSION['progress_page'] = $current_book['total_page'] ?? 0;
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // コメント投稿処理（無効化）
    // if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'comment') {
    //     $comment = sanitizeInput($_POST['comment'] ?? '');
    //     if (!empty($comment)) {
    //         createComment((int)$_POST['book_id'], $mine_user_id, $comment);
    //     }
    //     
    //     header('Location: ' . $_SERVER['REQUEST_URI']);
    //     exit;
    // }
    // 
    // // コメント削除処理（無効化）
    // if (isset($_POST['action']) && $_POST['action'] === 'delete_comment' && isset($_POST['comment_id'])) {
    //     $comment_id = (int)$_POST['comment_id'];
    //     deleteComment($comment_id, $mine_user_id);
    //     
    //     header('Location: ' . $_SERVER['REQUEST_URI']);
    //     exit;
    // }
    
    // ページ数更新処理
    if (isset($_POST['action']) && $_POST['action'] === 'update_pages' && isset($_POST['book_id']) && isset($_POST['total_pages'])) {
        // CSRF検証
        requireCSRFToken();

        $book_id = (int)$_POST['book_id'];
        $total_pages = (int)$_POST['total_pages'];

        // ユーザーがこの本を所有しているか確認
        $sql = "SELECT user_id FROM b_book_list WHERE book_id = ? AND user_id = ?";
        $owner_check = $g_db->getOne($sql, array($book_id, $mine_user_id));

        if ($owner_check) {
            // ページ数を更新
            $update_sql = "UPDATE b_book_list SET total_page = ? WHERE book_id = ? AND user_id = ?";
            $result = $g_db->query($update_sql, array($total_pages, $book_id, $mine_user_id));

            if (DB::isError($result)) {
                error_log("Error updating total pages: " . $result->getMessage());
            }
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // 著者更新処理
    if (isset($_POST['action']) && $_POST['action'] === 'update_author' && isset($_POST['book_id']) && isset($_POST['author'])) {
        // CSRF検証
        requireCSRFToken();

        $book_id = (int)$_POST['book_id'];
        $author = trim($_POST['author']);

        // ユーザーがこの本を所有しているか確認
        $sql = "SELECT user_id FROM b_book_list WHERE book_id = ? AND user_id = ?";
        $owner_check = $g_db->getOne($sql, array($book_id, $mine_user_id));

        if ($owner_check) {
            // 著者を更新（update_dateは更新しない - 書誌情報の変更のため）
            $update_sql = "UPDATE b_book_list SET author = ? WHERE book_id = ? AND user_id = ?";
            $result = $g_db->query($update_sql, array($author, $book_id, $mine_user_id));

            if (DB::isError($result)) {
                error_log("Error updating author: " . $result->getMessage());
            } else {
                // 著者更新成功時、本棚のキャッシュをクリア
                require_once(dirname(__FILE__) . '/library/cache.php');
                $cache = getCache();

                // 本棚統計キャッシュをクリア
                $cache->delete('bookshelf_stats_' . md5((string)$mine_user_id));

                // 本棚の本一覧キャッシュをクリア（全ステータス・全ソート順）
                $statuses = ['', '0', '1', '2', '3', '4']; // 全ステータス
                $sorts = ['update_date_desc', 'update_date_asc', 'title_asc', 'title_desc', 'author_asc', 'author_desc', 'rating_desc', 'rating_asc'];
                foreach ($statuses as $status) {
                    foreach ($sorts as $sort) {
                        $booksCacheKey = 'bookshelf_books_' . md5((string)$mine_user_id . '_' . $status . '_' . $sort . '_____');
                        $cache->delete($booksCacheKey);
                    }
                }

                // タグクラウドキャッシュもクリア（念のため）
                $cache->delete('user_tags_' . md5((string)$mine_user_id));
            }
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // 購入済み処理
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'bought') {
        boughtBook($mine_user_id, (int)$_POST['book_id']);
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    // ページ更新処理（createEvent()をコール）
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'update_page') {
        $current_page = (int)($_POST['current_page'] ?? 0);
        $memo = sanitizeInput($_POST['memo'] ?? '');
        
        if ($current_page > 0) {
            createEvent((int)$mine_user_id, (int)$_POST['book_id'], $memo, (int)$current_page);
            // 成功メッセージをセッションに保存
            $_SESSION['progress_updated'] = true;
            $_SESSION['progress_page'] = $current_page;
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    // レビュー更新処理（updateBook()をコール - ステータスは変更しない）
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'update_review') {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = sanitizeInput($_POST['comment'] ?? '');
        
        // 現在のステータスを維持
        $sql = "SELECT * FROM b_book_list WHERE user_id = ? AND book_id = ?";
        $current_book = $g_db->getRow($sql, [$mine_user_id, (int)$_POST['book_id']]);
        
        if ($current_book && !DB::isError($current_book)) {
            $current_status = $current_book['status'];
            // POSTから読了日を取得（インライン編集の場合）
            $finished_date = isset($_POST['finished_date']) && !empty($_POST['finished_date']) 
                ? $_POST['finished_date'] 
                : $current_book['finished_date'];
        } else {
            $current_status = NOT_STARTED;
            $finished_date = null;
        }
        
        try {
            // 読了日が設定され、かつ読書進捗がない場合の処理
            if ($finished_date && ($current_status == READING_FINISH || $current_status == READ_BEFORE)) {
                // 読書進捗があるかチェック
                $progress_check_sql = "SELECT COUNT(*) FROM b_book_event WHERE user_id = ? AND book_id = ?";
                $progress_count = $g_db->getOne($progress_check_sql, [$mine_user_id, (int)$_POST['book_id']]);
                
                if ($progress_count == 0 && $current_book['total_page'] > 0) {
                    // updateBookのイベント作成を抑制
                    $_SESSION['suppress_book_event'] = true;
                }
            }
            
            updateBook($mine_user_id, (int)$_POST['book_id'], $current_status, $rating, $comment, $finished_date);
            
            // レビューembeddingを生成
            if (!empty($comment)) {
                try {
                    $embeddingGenerator = new ReviewEmbeddingGenerator();
                    $embeddingGenerator->updateReviewEmbedding((int)$_POST['book_id'], $mine_user_id);
                } catch (Exception $e) {
                    error_log("Failed to generate review embedding: " . $e->getMessage());
                }
            }
            
            // 読了日が設定され、かつ読書進捗がない場合、読了日に読了イベントを作成
            if ($finished_date && ($current_status == READING_FINISH || $current_status == READ_BEFORE)) {
                // 読書進捗があるか再チェック
                $progress_check_sql = "SELECT COUNT(*) FROM b_book_event WHERE user_id = ? AND book_id = ?";
                $progress_count = $g_db->getOne($progress_check_sql, [$mine_user_id, (int)$_POST['book_id']]);
                
                if ($progress_count == 0 && $current_book['total_page'] > 0) {
                    // 読書進捗がない場合、読了日に読了イベントを作成（X投稿なし）
                    // createEvent関数を使用して、読了日を指定し、X投稿を抑制
                    createEvent(
                        $mine_user_id, 
                        (int)$_POST['book_id'], 
                        '読了', 
                        $current_book['total_page'],
                        $finished_date . ' 00:00:00',  // 読了日を指定
                        true  // X投稿を抑制
                    );
                }
            }
        } catch (Exception $e) {
            error_log("Exception updating book review: " . $e->getMessage());
            error_log("User ID: " . $mine_user_id . ", Book ID: " . $_POST['book_id']);
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    // タグ更新処理
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'update_tags') {
        $post_book_id = (int)$_POST['book_id'];
        
        // 本の所有者確認
        $check_sql = "SELECT * FROM b_book_list WHERE book_id = ? AND user_id = ?";
        $check_result = $g_db->getRow($check_sql, [$post_book_id, $mine_user_id], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($check_result) && $check_result) {
            $tags_string = sanitizeInput($_POST['tags'] ?? '');
            $tags_array = array_filter(array_map('trim', explode(',', $tags_string)));
            
            updateTag($mine_user_id, $post_book_id, $tags_array);
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    // 読書状況更新処理（統合版 - 既存の互換性のために残す）
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $new_status = (int)($_POST['new_status'] ?? 0);
        $current_page = (int)($_POST['current_page'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = sanitizeInput($_POST['comment'] ?? '');
        
        try {
            // まず、更新対象のレコードが存在するかチェック
            $check_sql = "SELECT book_id FROM b_book_list WHERE user_id = ? AND book_id = ?";
            $existing = $g_db->getOne($check_sql, [$mine_user_id, (int)$_POST['book_id']]);
            
            if (DB::isError($existing) || !$existing) {
                error_log("Book not found in user's bookshelf: user_id=" . $mine_user_id . ", book_id=" . $_POST['book_id']);
                // 本が本棚にない場合は、まず追加する必要がある
                // この場合はエラーとして処理
            } else {
                // ページ更新がある場合はcreateEvent()をコール
                if ($current_page > 0) {
                    createEvent((int)$mine_user_id, (int)$_POST['book_id'], $comment, (int)$current_page);
                }
                
                // 読了日を取得（POSTから送信されている場合）
                $finished_date = isset($_POST['finished_date']) && !empty($_POST['finished_date']) 
                    ? $_POST['finished_date'] 
                    : null;
                
                // レビュー更新がある場合はupdateBook()をコール
                if ($new_status > 0 || $rating > 0 || !empty($comment) || $finished_date !== null) {
                    // 読了日が設定され、かつ読書進捗がない場合の処理
                    if ($finished_date && ($new_status == READING_FINISH || $new_status == READ_BEFORE)) {
                        // 読書進捗があるかチェック
                        $progress_check_sql = "SELECT COUNT(*) FROM b_book_event WHERE user_id = ? AND book_id = ?";
                        $progress_count = $g_db->getOne($progress_check_sql, [$mine_user_id, (int)$_POST['book_id']]);
                        
                        if ($progress_count == 0) {
                            // 現在の本の情報を取得
                            $book_info_sql = "SELECT total_page FROM b_book_list WHERE user_id = ? AND book_id = ?";
                            $book_info = $g_db->getRow($book_info_sql, [$mine_user_id, (int)$_POST['book_id']]);
                            
                            if ($book_info && $book_info['total_page'] > 0) {
                                // updateBookのイベント作成を抑制
                                $_SESSION['suppress_book_event'] = true;
                            }
                        }
                    }
                    
                    updateBook($mine_user_id, (int)$_POST['book_id'], $new_status, $rating, $comment, $finished_date);
                    
                    // 読了日が設定され、かつ読書進捗がない場合、読了日に読了イベントを作成
                    if ($finished_date && ($new_status == READING_FINISH || $new_status == READ_BEFORE)) {
                        // 読書進捗があるか再チェック
                        $progress_check_sql = "SELECT COUNT(*) FROM b_book_event WHERE user_id = ? AND book_id = ?";
                        $progress_count = $g_db->getOne($progress_check_sql, [$mine_user_id, (int)$_POST['book_id']]);
                        
                        if ($progress_count == 0 && isset($book_info) && $book_info['total_page'] > 0) {
                            // 読書進捗がない場合、読了日に読了イベントを作成（X投稿なし）
                            createEvent(
                                $mine_user_id, 
                                (int)$_POST['book_id'], 
                                '読了', 
                                $book_info['total_page'],
                                $finished_date . ' 00:00:00',  // 読了日を指定
                                true  // X投稿を抑制
                            );
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log("Exception updating book status: " . $e->getMessage());
            error_log("User ID: " . $mine_user_id . ", Book ID: " . $_POST['book_id']);
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    
} else {
    $mine_user_id = '';
    $d_nickname = 'ゲスト';
}

// 本IDの取得
$book_id = 0;
if (isset($_GET['book_id'])) {
    $book_id = (int)$_GET['book_id'];
} elseif (isset($_POST['book_id'])) {
    $book_id = (int)$_POST['book_id'];
}

if (empty($book_id)) {
    header('Location: https://readnest.jp/');
    exit;
}

// 本の情報を取得
$book_array = getBookInformation($book_id);

if (!$book_array) {
    header('Location: https://readnest.jp/');
    exit;
}

// 本の基本情報を整理
$book = [
    'book_id' => $book_array['book_id'],
    'title' => $book_array['name'],
    'author' => $book_array['author'] ?? '不明な著者',
    'description' => $book_array['memo'] ?? '',
    'image_url' => $book_array['image_url'] ?? '/img/no-image-book.png',
    'amazon_url' => $book_array['detail_url'] ?? '',
    'amazon_id' => $book_array['amazon_id'] ?? '',
    'isbn' => $book_array['isbn'] ?? '',
    'pages' => $book_array['total_page'] ?? '',
    'publisher' => $book_array['publisher'] ?? '',
    'published_date' => $book_array['published_date'] ?? '',
    'status' => $book_array['status'] ?? 0,
    'current_page' => $book_array['current_page'] ?? 0,
    'rating' => $book_array['rating'] ?? 0,
    'user_id' => $book_array['user_id'],
    'create_date' => $book_array['create_date'],
    'update_date' => $book_array['update_date'],
    'reference_count' => $book_array['number_of_refer'] ?? 0
];

// ジャンル情報を取得（一時的に無効化）
// $book['genres'] = getBookGenres($book_id);
// $book['primary_genre'] = getBookPrimaryGenre($book_id);
$book['genres'] = [];
$book['primary_genre'] = null;

// 本の所有者情報を取得
$book_owner_info = null;
if (!empty($book['user_id'])) {
    $owner_info = getUserInformation($book['user_id']);
    if ($owner_info && !DB::isError($owner_info)) {
        $book_owner_info = [
            'user_id' => $book['user_id'],
            'nickname' => getNickname($book['user_id']),
            'user_photo' => getProfilePhotoURL($book['user_id']),
            'diary_policy' => $owner_info['diary_policy'] ?? 0
        ];
    }
}

// アクセス数を増加（本人以外の場合）
if (!$login_flag || $mine_user_id !== $book['user_id']) {
    incrementReferNum($book_id);
}

// 同じ本を読んでいる他の読者を取得（キャッシュ対応）
$amazon_id = $book_array['amazon_id'] ?? '';
if (!empty($amazon_id)) {
    // キャッシュから読者統計を取得
    $reader_stats_cache = BookCache::getReaderStats($book_id);
    
    if ($reader_stats_cache === null) {
        // キャッシュがない場合はDBから取得
        $readers_book = getBooksWithAsin($amazon_id);
        if ($readers_book) {
            // ユーザーIDのリストを作成
            $reader_ids = array_map(function($r) { return $r['user_id']; }, $readers_book);
            
            // 一括でユーザー情報を取得（最適化）
            $users_info = [];
            if (!empty($reader_ids)) {
                $placeholders = implode(',', array_fill(0, count($reader_ids), '?'));
                $users_sql = "SELECT user_id, nickname, photo, diary_policy 
                             FROM b_user 
                             WHERE user_id IN ($placeholders) 
                             AND diary_policy = 1";
                $users_result = $g_db->getAll($users_sql, $reader_ids, DB_FETCHMODE_ASSOC);
                if (!DB::isError($users_result)) {
                    foreach ($users_result as $u) {
                        $users_info[$u['user_id']] = $u;
                    }
                }
            }
            
            foreach ($readers_book as $reader_book) {
                $reader_id = $reader_book['user_id'];

                // プライベート設定でない場合のみ表示
                if (isset($users_info[$reader_id])) {
                    $user_info = $users_info[$reader_id];
                    $readers[] = [
                        'user_id' => $reader_id,
                        'nickname' => $user_info['nickname'],
                        'user_photo' => getProfilePhotoURL($reader_id),
                        'status' => $reader_book['status'] ?? 0,
                    'book_id' => $reader_book['book_id'],
                    'has_review' => !empty($reader_book['memo']) && $reader_book['memo'] !== ''
                ];

                    // レビューがある場合は$reviewsに追加
                    if (!empty($reader_book['memo']) || ($reader_book['rating'] ?? 0) > 0) {
                        $reviews[] = [
                            'user_id' => $reader_id,
                            'nickname' => $user_info['nickname'],
                            'user_photo' => getProfilePhotoURL($reader_id),
                            'rating' => $reader_book['rating'] ?? 0,
                            'comment' => $reader_book['memo'] ?? '',
                            'book_id' => $reader_book['book_id'],
                            'update_date' => $reader_book['update_date'] ?? date('Y-m-d H:i:s')
                        ];
                    }
                }
            }
            
            // キャッシュに保存
            BookCache::setReaderStats($book_id, $readers);
        }
    } else {
        // キャッシュから読み込み
        $readers = $reader_stats_cache;
    }
}

// この本に対するコメント・レビューを取得（無効化）
// $comments = getComment($book_id);
// if ($comments) {
//     foreach ($comments as $comment) {
//         $comment_user_id = $comment['from_user'];
//         $user_info = getUserInformation($comment_user_id);
//         
//         if ($user_info && $user_info['diary_policy'] == 1) {
//             $reviews[] = [
//                 'comment_id' => $comment['id'],
//                 'user_id' => $comment_user_id,
//                 'nickname' => getNickname($comment_user_id),
//                 'user_photo' => getProfilePhotoURL($comment_user_id),
//                 'comment' => $comment['comment'],
//                 'rating' => 0, // Comments don't have ratings in this system
//                 'created_at' => date('Y-m-d H:i:s', $comment['created'])
//             ];
//         }
//     }
// }

// この本のタグを取得
$book_id_for_tags = (int)$book['book_id'];
$book_tags = getTag($book_id_for_tags);

// ログインユーザーがこの本に付けたタグを取得
$user_tags = [];
if ($login_flag) {
    $user_tags = getUserTags($book_id_for_tags, $mine_user_id);
}

// いいね機能のヘルパーを読み込み
require_once(dirname(__FILE__) . '/library/like_helpers.php');

// レビューにいいね情報を追加
if (!empty($reviews)) {
    // レビューのtarget_idを生成
    $review_target_ids = [];
    foreach ($reviews as $review) {
        $review_target_ids[] = generateReviewTargetId($review['book_id'], $review['user_id']);
    }

    // いいね数を一括取得
    $like_counts = getLikeCounts('review', $review_target_ids);

    // ログインユーザーのいいね状態を取得
    if ($login_flag) {
        $user_like_states = getUserLikeStates($mine_user_id, 'review', $review_target_ids);
    } else {
        $user_like_states = [];
    }

    // 各レビューにいいね情報を追加
    foreach ($reviews as &$review) {
        $target_id = generateReviewTargetId($review['book_id'], $review['user_id']);
        $review['like_count'] = $like_counts[$target_id] ?? 0;
        $review['is_liked'] = $user_like_states[$target_id] ?? false;
    }
    unset($review);
}

// レビューとコメントのユーザーレベル情報を一括取得
$all_user_ids = [];
foreach ($reviews as $review) {
    $all_user_ids[] = $review['user_id'];
}
foreach ($readers as $reader) {
    $all_user_ids[] = $reader['user_id'];
}
$all_user_ids = array_unique($all_user_ids);

if (!empty($all_user_ids)) {
    // getUsersLevels関数が存在するか確認
    if (function_exists('getUsersLevels')) {
        $user_levels = getUsersLevels($all_user_ids);
    } else {
        // 関数が存在しない場合は代替処理
        error_log("Warning: getUsersLevels function not found in book_detail.php");
        $user_levels = [];
        foreach ($all_user_ids as $uid) {
            // getReadingLevel関数も存在確認
            if (function_exists('getReadingLevel')) {
                $user_levels[$uid] = getReadingLevel(0);
            } else {
                // 関数が存在しない場合はデフォルト値
                $user_levels[$uid] = [
                    'level' => 1,
                    'progress' => 0,
                    'next_level_pages' => 100,
                    'total_pages' => 0,
                    'name' => '読書初心者',
                    'badge' => '📚'
                ];
            }
        }
    }
    
    // レビューにレベル情報を追加
    foreach ($reviews as &$review) {
        if (isset($user_levels[$review['user_id']])) {
            $review['user_level'] = $user_levels[$review['user_id']];
        } else {
            // デフォルトレベル情報
            if (function_exists('getReadingLevel')) {
                $review['user_level'] = getReadingLevel(0);
            } else {
                $review['user_level'] = [
                    'level' => 1,
                    'progress' => 0,
                    'next_level_pages' => 100,
                    'total_pages' => 0,
                    'name' => '読書初心者',
                    'badge' => '📚'
                ];
            }
        }
    }
    unset($review);
    
    // 読者リストにレベル情報を追加
    foreach ($readers as &$reader) {
        if (isset($user_levels[$reader['user_id']])) {
            $reader['user_level'] = $user_levels[$reader['user_id']];
        } else {
            // デフォルトレベル情報
            if (function_exists('getReadingLevel')) {
                $reader['user_level'] = getReadingLevel(0);
            } else {
                $reader['user_level'] = [
                    'level' => 1,
                    'progress' => 0,
                    'next_level_pages' => 100,
                    'total_pages' => 0,
                    'name' => '読書初心者',
                    'badge' => '📚'
                ];
            }
        }
    }
    unset($reader);
}

// 統計情報
$total_users = count($readers);
$total_reviews = count($reviews);

// 平均評価を計算
if (!empty($reviews)) {
    $rating_sum = 0;
    $rating_count = 0;
    foreach ($reviews as $review) {
        if ($review['rating'] > 0) {
            $rating_sum += $review['rating'];
            $rating_count++;
        }
    }
    if ($rating_count > 0) {
        $average_rating = $rating_sum / $rating_count;
    }
}

// ログインユーザーが本棚に持っているかチェック
$user_book_info = null;
$is_in_bookshelf = false;
$is_book_owner = false; // 表示している本の所有者かどうか
$is_favorite = false;

if ($login_flag) {
    // 表示している本の所有者かどうかをチェック
    $is_book_owner = (!empty($book['user_id']) && $book['user_id'] == $mine_user_id);
    
    // ユーザーの本棚における本の詳細情報を取得
    try {
        $sql = "SELECT * FROM b_book_list WHERE user_id = ? AND book_id = ?";
        $user_book_info = $g_db->getRow($sql, [$mine_user_id, $book_id]);
        
        if (DEBUG_MODE) error_log("Checking bookshelf for user $mine_user_id, book $book_id");
        if (DEBUG_MODE) error_log("Is book owner: " . ($is_book_owner ? 'true' : 'false'));
        
        if ($user_book_info && !DB::isError($user_book_info)) {
            $is_in_bookshelf = true;
            // ユーザーが設定したページ数があればそれを使用
            if (!empty($user_book_info['total_page']) && $user_book_info['total_page'] > 0) {
                $book['pages'] = $user_book_info['total_page'];
            }
            // お気に入り状態をチェック
            $is_favorite = isFavoriteBook($mine_user_id, $book_id);
        } else {
            // book_idで見つからない場合、amazon_idでも確認
            if (!empty($amazon_id)) {
                $is_in_bookshelf = is_bookmarked($mine_user_id, $amazon_id);
                if (DEBUG_MODE) error_log("Amazon ID check result: " . ($is_in_bookshelf ? 'true' : 'false'));
            }
        }
        
        if (DEBUG_MODE) error_log("Final is_in_bookshelf: " . ($is_in_bookshelf ? 'true' : 'false'));
    } catch (Exception $e) {
        error_log("Error getting user book info: " . $e->getMessage());
    }
    
    // 未読コメントを既読にする（無効化）
    // if ($mine_user_id === $book['user_id']) {
    //     setCommentRead($book_id);
    // }
}

// 関連書籍（同じ著者の他の本）
if (!empty($book['author'])) {
    $similar_books_data = searchBooksByAuthor($book['author'], 6);
    if ($similar_books_data) {
        foreach ($similar_books_data as $similar) {
            if ($similar['book_id'] != $book_id) {
                $similar_books[] = [
                    'book_id' => $similar['book_id'],
                    'title' => $similar['name'],
                    'author' => $similar['author'],
                    'image_url' => $similar['image_url'] ?? '/img/no-image-book.png'
                ];
            }
        }
    }
}

// ========== AI推薦機能 ==========
$ai_recommendations = [];
$embedding_generated = false;

// b_book_repositoryから情報を取得
if (!empty($book['amazon_id'])) {
    $repo_sql = "SELECT combined_embedding, description, google_categories 
                 FROM b_book_repository 
                 WHERE asin = ?";
    $repo_info = $g_db->getRow($repo_sql, [$book['amazon_id']], DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($repo_info) && $repo_info) {
        $book_embedding = $repo_info['combined_embedding'];
        
        // embeddingがない場合は動的生成
        if (empty($book_embedding)) {
            $generator = new DynamicEmbeddingGenerator();
            $book_data = [
                'asin' => $book['amazon_id'],
                'title' => $book['title'],
                'author' => $book['author'],
                'description' => $repo_info['description'] ?? '',
                'google_categories' => $repo_info['google_categories'] ?? ''
            ];
            
            $book_embedding = $generator->generateBookEmbedding($book_data);
            $embedding_generated = true;
        }
        
        // embeddingがある場合、類似本を検索
        if (!empty($book_embedding)) {
            // 既に所有している本を除外するリスト
            $exclude_asins = [$book['amazon_id']];
            if ($login_flag) {
                $owned_sql = "SELECT amazon_id FROM b_book_list WHERE user_id = ?";
                $owned_result = $g_db->getAll($owned_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
                if (!DB::isError($owned_result)) {
                    $exclude_asins = array_merge($exclude_asins, array_column($owned_result, 'amazon_id'));
                }
            }
            
            // 類似本を検索
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
                AND br.asin NOT IN ('" . implode("','", $exclude_asins) . "')
                LIMIT 200
            ";
            
            $candidates = $g_db->getAll($candidates_sql, [], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($candidates) && $candidates) {
                // 類似度計算
                foreach ($candidates as $candidate) {
                    $similarity = VectorSimilarity::cosineSimilarity(
                        $book_embedding,
                        $candidate['combined_embedding']
                    );
                    
                    if ($similarity > 0.7) { // 70%以上の類似度
                        $ai_recommendations[] = [
                            'asin' => $candidate['asin'],
                            'title' => $candidate['title'],
                            'author' => $candidate['author'],
                            'image_url' => $candidate['image_url'] ?? '/img/no-image-book.png',
                            'description' => $candidate['description'] ?? '',
                            'similarity' => round($similarity * 100, 1),
                            'reader_count' => $candidate['reader_count'] ?? 0,
                            'avg_rating' => round((float)($candidate['avg_rating'] ?? 0), 1)
                        ];
                    }
                }
                
                // 類似度でソート
                usort($ai_recommendations, function($a, $b) {
                    return $b['similarity'] <=> $a['similarity'];
                });
                
                // 上位10件に限定
                $ai_recommendations = array_slice($ai_recommendations, 0, 10);
                
                // 各推薦本にReadNest内のレビュー情報を追加
                foreach ($ai_recommendations as &$rec) {
                    // この本がReadNest内で読まれているか確認
                    $check_sql = "SELECT bl.book_id, bl.user_id, bl.rating, bl.memo,
                                        u.nickname, u.diary_policy
                                 FROM b_book_list bl
                                 JOIN b_user u ON bl.user_id = u.user_id
                                 WHERE bl.amazon_id = ?
                                 AND u.diary_policy = 1
                                 AND (bl.rating > 0 OR (bl.memo IS NOT NULL AND bl.memo != ''))
                                 ORDER BY 
                                    CASE WHEN bl.memo IS NOT NULL AND bl.memo != '' THEN 1 ELSE 0 END DESC,
                                    bl.rating DESC,
                                    bl.update_date DESC
                                 LIMIT 1";
                    
                    $best_review = $g_db->getRow($check_sql, [$rec['asin']], DB_FETCHMODE_ASSOC);
                    
                    if (!DB::isError($best_review) && $best_review) {
                        $rec['has_review'] = true;
                        $rec['review_book_id'] = $best_review['book_id'];
                        $rec['review_user_id'] = $best_review['user_id'];
                        $rec['review_nickname'] = $best_review['nickname'];
                        $rec['review_rating'] = $best_review['rating'];
                        $rec['review_has_memo'] = !empty($best_review['memo']);
                    } else {
                        $rec['has_review'] = false;
                    }
                }
                unset($rec);
            }
        }
    }
}


// 読書進捗履歴を取得
$reading_progress = [];
$latest_progress_memo = '';

// 本の所有者の読書履歴を取得（自分の本 または 公開設定の他人の本）
$should_show_progress = false;
$progress_user_id = null;


// $book['user_id']は本の所有者のID
if (!empty($book['user_id'])) {
    if ($login_flag && $book['user_id'] == $mine_user_id) {
        // 自分の本の場合
        $should_show_progress = true;
        $progress_user_id = $mine_user_id;
    } else {
        // 他人の本の場合、公開設定を確認
        $privacy_sql = "SELECT diary_policy FROM b_user WHERE user_id = ?";
        $diary_policy = $g_db->getOne($privacy_sql, [$book['user_id']]);
        
        
        if (!DB::isError($diary_policy) && $diary_policy == 1) { // 1 = 公開
            $should_show_progress = true;
            $progress_user_id = $book['user_id'];
        }
    }
}


if ($should_show_progress && $progress_user_id) {
    try {
        $progress_sql = "SELECT event_id, event_date, page, memo, event FROM b_book_event WHERE book_id = ? AND user_id = ? ORDER BY event_date DESC LIMIT 20";
        $progress_result = $g_db->getAll($progress_sql, [$book_id, $progress_user_id]);
        
        
        if ($progress_result && !DB::isError($progress_result)) {
            foreach ($progress_result as $event) {
                $reading_progress[] = [
                    'event_id' => $event['event_id'],
                    'date' => $event['event_date'],
                    'page' => $event['page'],
                    'memo' => $event['memo'],
                    'event_type' => $event['event']
                ];
            }
            
            // 最新の読書メモを取得（空でない最初のメモ）
            foreach ($progress_result as $event) {
                if (!empty($event['memo'])) {
                    $latest_progress_memo = $event['memo'];
                    break;
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching reading progress: " . $e->getMessage());
    }
    
    // ユーザーの本情報に最新の読書メモを追加（自分の本の場合のみ）
    if ($is_in_bookshelf && $user_book_info && is_array($user_book_info)) {
        $user_book_info['current_memo'] = $latest_progress_memo;
    }
}

// 公開設定ユーザーのレビューを取得
$public_user_review = null;
if (!$is_in_bookshelf && !empty($book['user_id'])) {
    // 他人の本の場合、公開設定を確認してレビューを取得
    if (!empty($book_owner_info) && $book_owner_info['diary_policy'] == 1) {
        $review_sql = "SELECT rating, memo FROM b_book_list WHERE book_id = ? AND user_id = ?";
        $review_result = $g_db->getRow($review_sql, [$book_id, $book['user_id']], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($review_result) && $review_result) {
            if (!empty($review_result['rating']) || !empty($review_result['memo'])) {
                $public_user_review = [
                    'user_id' => $book['user_id'],
                    'rating' => $review_result['rating'],
                    'memo' => $review_result['memo'],
                    'nickname' => $book_owner_info['nickname']
                ];
            }
        }
    }
}

// 進捗更新の成功メッセージをチェック
$show_progress_success = false;
$progress_page = 0;
if (isset($_SESSION['progress_updated']) && $_SESSION['progress_updated'] === true) {
    $show_progress_success = true;
    $progress_page = $_SESSION['progress_page'] ?? 0;
    // メッセージを表示したらセッションから削除
    unset($_SESSION['progress_updated']);
    unset($_SESSION['progress_page']);
}

// SEOヘルパーを読み込み
require_once('library/seo_helpers.php');

// ページタイトル設定
$d_site_title = $book['title'] . ' - ' . $book['author'] . ' - ReadNest';

// メタ情報
$g_meta_description = cleanMetaDescription($book['title'] . ' by ' . $book['author'] . '。' . $book['description']);
$g_meta_keyword = $book['title'] . ',' . $book['author'] . ',本,書評,レビュー,ReadNest';

// SEOデータの準備
$canonical_url = getBaseUrl() . '/book/' . $book['book_id'];
$og_image = (!empty($book['image_url']) && strpos($book['image_url'], 'noimage') === false) 
    ? $book['image_url'] 
    : getBaseUrl() . '/img/og-image.jpg';

$seo_data = [
    'title' => $d_site_title,
    'description' => $g_meta_description,
    'canonical_url' => $canonical_url,
    'og' => [
        'title' => $book['title'] . ' - ' . $book['author'],
        'description' => $g_meta_description,
        'url' => $canonical_url,
        'image' => $og_image,
        'type' => 'book'
    ],
    'twitter' => [
        'title' => $book['title'] . ' - ' . $book['author'],
        'description' => $g_meta_description,
        'image' => $og_image
    ]
];

// 構造化データの生成
$book_schema = generateBookSchema([
    'title' => $book['title'],
    'author' => $book['author'],
    'isbn' => $book['isbn'] ?? '',
    'description' => $book['description'],
    'image_url' => $book['image_url'],
    'publisher' => $book['publisher'] ?? '',
    'published_date' => $book['published_date'] ?? '',
    'pages' => $book['pages'] ?? 0,
    'rating_average' => $average_rating,
    'rating_count' => count($reviews)
]);

// パンくずリストの構造化データ
$breadcrumb_schema = generateBreadcrumbSchema([
    ['name' => 'ホーム', 'url' => getBaseUrl()],
    ['name' => '本を探す', 'url' => getBaseUrl() . '/search_results.php'],
    ['name' => $book['title'], 'url' => $canonical_url]
]);

$seo_data['schema'] = [$book_schema, $breadcrumb_schema];

// SEOタグの生成
$g_seo_tags = generateSEOTags($seo_data);

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// プロファイル写真取得用のヘルパー関数

// 同じ著者の本を検索する関数
function searchBooksByAuthor($author, $limit = 10) {
    global $g_db;
    
    $sql = "SELECT bl.book_id, bl.name, bl.author, bl.image_url 
            FROM b_book_list bl 
            WHERE bl.author LIKE ? 
            AND bl.status IN (2, 3)
            GROUP BY bl.amazon_id 
            ORDER BY bl.update_date DESC 
            LIMIT ?";
    
    try {
        $result = $g_db->getAll($sql, ["%{$author}%", $limit]);
        return $result ?: [];
    } catch (Exception $e) {
        error_log("Error searching books by author: " . $e->getMessage());
        return [];
    }
}

// CSRFトークンを生成
$csrf_token = generateCSRFToken();

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_book_detail.php'));