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
require_once(__DIR__ . '/library/recommendation_pool.php');

// 人気順プールから取るASINの件数
if (!defined('RECOMMENDATION_POOL_SIZE')) {
    define('RECOMMENDATION_POOL_SIZE', 600);
}
// 類似度計算にかける候補の総数上限（embeddingは1件30KB前後あるため上限を設ける）
if (!defined('RECOMMENDATION_CANDIDATE_MAX')) {
    define('RECOMMENDATION_CANDIDATE_MAX', 600);
}

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

            // 読了モーダルから受け取った余韻メモ
            // memo_action=save の場合のみPOST値を採用、それ以外（skip/未指定）は既存memoを保持
            $memo_action = $_POST['memo_action'] ?? 'skip';
            $finish_memo = ($memo_action === 'save')
                ? sanitizeInput($_POST['memo'] ?? '')
                : ($current_book['memo'] ?? '');

            // ステータスを読了に更新
            updateBook($mine_user_id, (int)$_POST['book_id'], READING_FINISH,
                      $current_book['rating'] ?? 0,
                      $finish_memo,
                      $finished_date);

            // 成功メッセージをセッションに保存
            $_SESSION['progress_updated'] = true;
            $_SESSION['progress_page'] = $current_book['total_page'] ?? 0;
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // 再読開始処理（読了済みの本を新しい読書エントリとして別途追加）
    if (isset($_POST['book_id']) && isset($_POST['action']) && $_POST['action'] === 'start_reread') {
        requireCSRFToken();

        $src_book_id = (int)$_POST['book_id'];

        // 対象の本を取得し、所有権を確認
        $sql = "SELECT * FROM b_book_list WHERE user_id = ? AND book_id = ?";
        $src_book = $g_db->getRow($sql, [$mine_user_id, $src_book_id]);

        if ($src_book && !DB::isError($src_book)) {
            // 書誌情報を引き継いで新しい読書エントリを作成
            // memo・ratingは引き継がず、新しい読書として「読んでいるところ」から開始
            $new_book_id = createBook(
                $mine_user_id,
                $src_book['name'],
                $src_book['amazon_id'],
                $src_book['isbn'],
                $src_book['author'],
                '',                              // memo（新しい読書のため空）
                (int)$src_book['total_page'],
                READING_NOW,                     // ステータス: 読んでいるところ
                $src_book['detail_url'],
                $src_book['image_url'],
                null,                            // finished_date
                null                             // categories
            );

            if ($new_book_id) {
                // 新しいエントリの詳細ページへ遷移
                header('Location: https://readnest.jp/book/' . $new_book_id);
                exit;
            }
        }

        // 失敗時は元のページへ戻す
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
                // 更新成功のログ
                error_log("Author updated successfully for book_id: $book_id, user_id: $mine_user_id, new author: '$author'");
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
// この本（同じamazon_id）をユーザーが通算何回読了したか
$finished_count = 0;

if ($login_flag) {
    // 表示している本の所有者かどうかをチェック
    $is_book_owner = (!empty($book['user_id']) && $book['user_id'] == $mine_user_id);

    // 通算読了回数を取得（別エントリの再読分も合算）
    if (!empty($amazon_id)) {
        $finished_count = getFinishedNumber($mine_user_id, $amazon_id);
    }
    
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
//
// 索引の都合上、必ず b_book_repository 側から引くこと。
// b_book_list.author には単独索引が無く（複合索引の2列目にしか現れない）、
// bl.author を条件にすると b_book_list のフルスキャンになる。
// b_book_repository.author には索引があるので、そちらで著者の本を引いてから
// b_book_list.amazon_id（索引あり）へ join して読者数を数える。
//
// 著者未設定の本は $book['author'] が '不明な著者' になるため対象外
if (!empty($book['author']) && $book['author'] !== '不明な著者') {
    $current_asin = (string)($book['amazon_id'] ?? '');

    // キャッシュは著者単位。現在の本の除外は取得後に行うので、
    // 同じ著者の別の本を開いてもキャッシュを共有できる。
    $author_cache_key = 'author_' . md5($book['author']);
    $author_books = BookCache::getSimilarBooks($author_cache_key);

    if (!is_array($author_books)) {
        $author_books = [];

        $author_books_sql = "
            SELECT
                br.asin,
                MIN(br.title) AS title,
                MIN(br.author) AS author,
                MIN(br.image_url) AS image_url,
                COUNT(DISTINCT bl.user_id) AS reader_count,
                AVG(CASE WHEN bl.rating > 0 THEN bl.rating END) AS avg_rating
            FROM b_book_repository br
            LEFT JOIN b_book_list bl ON bl.amazon_id = br.asin
            WHERE br.author = ?
            GROUP BY br.asin
            ORDER BY reader_count DESC, avg_rating DESC
            LIMIT 12
        ";

        $author_rows = $g_db->getAll($author_books_sql, [$book['author']], DB_FETCHMODE_ASSOC);

        if (!DB::isError($author_rows) && !empty($author_rows)) {
            foreach ($author_rows as $author_row) {
                $author_books[] = [
                    'asin' => $author_row['asin'],
                    'book_id' => 0,
                    'title' => $author_row['title'],
                    'author' => $author_row['author'],
                    'image_url' => !empty($author_row['image_url']) ? $author_row['image_url'] : '/img/no-image-book.png',
                    'reader_count' => (int)$author_row['reader_count'],
                    'avg_rating' => round((float)($author_row['avg_rating'] ?? 0), 1)
                ];
            }
        } else {
            // フォールバック：b_book_repository に無い著者（独自登録本など）。
            // searchBooksByAuthor() は LIKE '%...%' でフルスキャンになるため、
            // ここに来た場合も必ずキャッシュに載せて毎回は走らせない。
            $similar_books_data = searchBooksByAuthor($book['author'], 12);
            if ($similar_books_data && !DB::isError($similar_books_data)) {
                foreach ($similar_books_data as $similar) {
                    $author_books[] = [
                        'asin' => (string)($similar['amazon_id'] ?? ''),
                        'book_id' => $similar['book_id'],
                        'title' => $similar['name'],
                        'author' => $similar['author'],
                        'image_url' => $similar['image_url'] ?? '/img/no-image-book.png',
                        'reader_count' => 0,
                        'avg_rating' => 0
                    ];
                }
            }
        }

        BookCache::setSimilarBooks($author_cache_key, $author_books);
    }

    // 表示している本自身を除いて6件まで
    foreach ($author_books as $author_book) {
        if ($current_asin !== '' && $author_book['asin'] === $current_asin) {
            continue;
        }
        if (!empty($author_book['book_id']) && (int)$author_book['book_id'] === (int)$book_id) {
            continue;
        }
        $similar_books[] = $author_book;
        if (count($similar_books) >= 6) {
            break;
        }
    }
}

// ========== AI推薦機能 ==========
$ai_recommendations = [];
$embedding_generated = false;

// 推薦結果はASIN単位でファイルキャッシュする（BookCacheは1時間TTL）。
// ユーザーごとの所持本除外は表示直前に行い、キャッシュはユーザー非依存に保つ。
$rec_cache_key = '';
if (!empty($book['amazon_id'])) {
    $rec_cache_key = 'ai_' . preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$book['amazon_id']);
}

$cached_recommendations = ($rec_cache_key !== '') ? BookCache::getSimilarBooks($rec_cache_key) : null;

if (is_array($cached_recommendations)) {
    $ai_recommendations = $cached_recommendations;
} elseif (!empty($book['amazon_id'])) {
    // b_book_repositoryから情報を取得
    $repo_sql = "SELECT combined_embedding, description, google_categories, author
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

        // 基準ベクトルは一度だけデコードして候補全件で使い回す
        $book_vector = !empty($book_embedding) ? json_decode($book_embedding, true) : null;

        if (!is_array($book_vector) || empty($book_vector)) {
            if (!empty($book_embedding)) {
                error_log('AI recommendation: failed to decode embedding for ASIN ' . $book['amazon_id']);
            }
        } else {
            // カテゴリベースのフィルタリングで精度向上
            $book_categories_raw = $repo_info['google_categories'] ?? '';
            $book_categories = [];
            $main_category = '';
            $candidates = [];

            // タイトルからジャンルを推測する関数
            $detectGenreFromTitle = function($title) {
                $title_lower = mb_strtolower($title);

                // 技術書キーワード（誤判定を防ぐため厳選）
                $tech_keywords = [
                    'プログラミング', 'コーディング', '開発入門', 'エンジニア',
                    'python', 'javascript', 'java入門', 'ruby', 'php', 'go言語', 'rust', 'swift',
                    'html', 'css', 'sql', 'xml', 'json', 'データベース', 'api',
                    'linux', 'unix',
                    '機械学習', 'ディープラーニング', '人工知能', 'chatgpt',
                    'aws', 'azure', 'gcp', 'クラウド', 'docker', 'kubernetes',
                    'git', 'github', 'アルゴリズム', 'データ構造',
                    'devops', 'agile', 'スクラム',
                    'バイブコーディング', 'vibe coding',
                    'c言語', 'c#', 'c++', 'typescript', 'kotlin', 'scala', 'perl',
                    'react', 'vue', 'angular', 'node.js', 'rails', 'django', 'laravel',
                    'terraform', 'ansible', 'jenkins', 'テスト駆動'
                ];

                foreach ($tech_keywords as $keyword) {
                    if (mb_strpos($title_lower, $keyword) !== false) {
                        return 'tech';
                    }
                }

                // 小説・ラノベ・漫画キーワード
                $fiction_keywords = [
                    '小説', 'ノベル', 'ライトノベル', '文庫', '物語', '新書',
                    '殺人', '事件', '探偵', 'ミステリ', 'ミステリー',
                    '恋愛', 'ラブ', '青春', '学園', 'スクール',
                    'ファンタジー', '異世界', '転生', '魔法', '冒険', '勇者',
                    // 出版社・レーベル
                    '講談社box', '新潮', '角川', 'ハヤカワ', '早川', '集英社',
                    '電撃', 'メディアワークス', 'ga文庫', 'mf文庫',
                    // シリーズ表記（ラノベ・漫画の特徴）
                    'vol.', '〈', '《', '（上）', '（下）', '（前編）', '（後編）',
                    // その他
                    'コミック', 'マンガ', '漫画', 'アニメ',
                    'スラム', 'カレイドスコープ', 'ローレライ', 'ビジョン'
                ];

                foreach ($fiction_keywords as $keyword) {
                    if (mb_strpos($title_lower, $keyword) !== false) {
                        return 'fiction';
                    }
                }

                return 'unknown';
            };

            // この本のジャンルを推測
            $book_genre = $detectGenreFromTitle($book['title']);

            // カテゴリをパース（JSON配列形式）
            if (!empty($book_categories_raw)) {
                $book_categories = json_decode($book_categories_raw, true) ?: [];
                if (!empty($book_categories) && is_array($book_categories)) {
                    // 最初のカテゴリをメインカテゴリとして使用
                    $first_category = $book_categories[0] ?? '';
                    // "Computers / Programming" のような形式から主要部分を抽出
                    $category_parts = explode('/', $first_category);
                    $main_category = trim($category_parts[0] ?? '');
                }
            }

            // 候補プールを構築する
            //
            // 方針: まず候補ASINを「関連の強い順」に集め、最後に1回だけ
            // embeddingを取りに行く。embeddingは1件30KB前後あるため、
            // 総数を上限で抑えないとメモリと転送量が跳ねる。
            //
            // 人気順だけを候補にしていた時期は、16.8万件のembeddingに対して
            // 候補が585件（0.35%）しかなく、しかも多数派ジャンル（ビジネス書・
            // 小説）で占められていた。技術書など少数派の本は、減点や閾値の
            // 前にそもそも似た本が候補に存在せず、0件になっていた。
            $candidate_asins = [];
            $addCandidateAsins = function($rows, $column = 'amazon_id') use (&$candidate_asins, $book) {
                if (DB::isError($rows) || empty($rows)) {
                    return;
                }
                foreach ($rows as $row) {
                    if (count($candidate_asins) >= RECOMMENDATION_CANDIDATE_MAX) {
                        return;
                    }
                    $asin = (string)($row[$column] ?? '');
                    if ($asin === '' || $asin === $book['amazon_id']) {
                        continue;
                    }
                    $candidate_asins[$asin] = true;
                }
            };

            // 1) この本を読んでいる人が読んでいる他の本（協調フィルタリング）
            //
            // 少数派ジャンルの本にとっては、これが唯一まともに効く候補源。
            // dbtの本を読んでいる人の本棚には技術書が並ぶ、という発想。
            // 読者は公開設定のユーザーに限る（非公開ユーザーの本棚が
            // 推薦経由で露出しないようにするため）。
            // 読者数の多い本で爆発しないよう、参照する読者を上限で切る。
            $co_read_sql = "
                SELECT bl2.amazon_id, COUNT(DISTINCT bl2.user_id) AS co_count
                FROM (
                    SELECT DISTINCT bl1.user_id
                    FROM b_book_list bl1
                    INNER JOIN b_user u ON u.user_id = bl1.user_id
                    WHERE bl1.amazon_id = ?
                      AND u.diary_policy = 1
                      AND u.status = 1
                    LIMIT 50
                ) r
                INNER JOIN b_book_list bl2 ON bl2.user_id = r.user_id
                WHERE bl2.amazon_id IS NOT NULL
                  AND bl2.amazon_id != ''
                  AND bl2.amazon_id != ?
                GROUP BY bl2.amazon_id
                ORDER BY co_count DESC
                LIMIT 300
            ";
            $addCandidateAsins($g_db->getAll(
                $co_read_sql,
                [$book['amazon_id'], $book['amazon_id']],
                DB_FETCHMODE_ASSOC
            ));

            // 2) 同じ著者の本（br.author には索引がある）
            $candidate_author = !empty($repo_info['author']) ? $repo_info['author'] : ($book['author'] ?? '');
            if (!empty($candidate_author) && $candidate_author !== '不明な著者') {
                $addCandidateAsins($g_db->getAll("
                    SELECT br.asin
                    FROM b_book_repository br
                    WHERE br.author = ?
                      AND br.combined_embedding IS NOT NULL
                    LIMIT 20
                ", [$candidate_author], DB_FETCHMODE_ASSOC), 'asin');
            }

            // 「同じカテゴリの本を候補に引く」クエリはここにあったが削除した。
            // b_book_repository で google_categories を持つ本は 235,985件中34件しかなく、
            // かつ google_categories に索引が無いため、発動すると23.5万行の
            // フルスキャンになる割に、得られる候補は他の候補源とほぼ重複していた。
            // 下の類似度計算にあるカテゴリ一致の加点・減点はそのまま残してあるので、
            // google_categories が埋まれば加点側は自動的に効き始める。

            // 3) ReadNestで読まれている本（人気順）で残り枠を埋める
            //    ASINリストは library/recommendation_pool.php が6時間キャッシュする
            $addCandidateAsins(array_map(function($asin) {
                return ['amazon_id' => $asin];
            }, getRecommendationPoolAsins(RECOMMENDATION_POOL_SIZE)));

            // 集めたASINのembeddingをまとめて取得する
            if (!empty($candidate_asins)) {
                $asin_list = array_keys($candidate_asins);
                $asin_placeholders = implode(',', array_fill(0, count($asin_list), '?'));
                $candidates_result = $g_db->getAll("
                    SELECT
                        br.asin,
                        br.title,
                        br.author,
                        br.image_url,
                        br.description,
                        br.combined_embedding,
                        br.google_categories
                    FROM b_book_repository br
                    WHERE br.asin IN ({$asin_placeholders})
                      AND br.combined_embedding IS NOT NULL
                ", $asin_list, DB_FETCHMODE_ASSOC);

                if (!DB::isError($candidates_result)) {
                    $candidates = $candidates_result;
                }
            }

            if (!empty($candidates)) {
                // カテゴリ一致判定用の関数（JSON配列対応）
                $getMainCategory = function($categories_raw) {
                    if (empty($categories_raw)) return '';
                    $categories = is_array($categories_raw) ? $categories_raw : (json_decode($categories_raw, true) ?: []);
                    if (empty($categories)) return '';
                    $first_category = $categories[0] ?? '';
                    $parts = explode('/', $first_category);
                    return trim($parts[0] ?? '');
                };

                // 類似度計算
                foreach ($candidates as $candidate) {
                    $base_similarity = VectorSimilarity::cosineSimilarityWithVector(
                        $book_vector,
                        $candidate['combined_embedding']
                    );

                    if ($base_similarity <= 0) {
                        continue;
                    }

                    // カテゴリ一致ボーナス/ペナルティ
                    $candidate_categories_raw = $candidate['google_categories'] ?? '';
                    $candidate_main_category = $getMainCategory($candidate_categories_raw);
                    $category_match = !empty($main_category) && !empty($candidate_main_category)
                                      && $main_category === $candidate_main_category;

                    // タイトルベースのジャンル推測（カテゴリ情報がない場合のフォールバック）
                    $candidate_genre = $detectGenreFromTitle($candidate['title']);
                    $genre_match = ($book_genre !== 'unknown' && $candidate_genre !== 'unknown')
                                   && $book_genre === $candidate_genre;
                    $genre_mismatch = ($book_genre !== 'unknown' && $candidate_genre !== 'unknown')
                                      && $book_genre !== $candidate_genre;

                    // カテゴリまたはジャンルが一致する場合はボーナス、不一致の場合はペナルティ
                    if ($category_match || $genre_match) {
                        $similarity = min($base_similarity * 1.08, 1.0); // 8%ボーナス（上限100%）
                    } elseif (!empty($main_category) && !empty($candidate_main_category)) {
                        $similarity = $base_similarity * 0.80; // 20%ペナルティ（異なるカテゴリ）
                    } elseif ($genre_mismatch) {
                        $similarity = $base_similarity * 0.75; // 25%ペナルティ（異なるジャンル：技術書 vs 小説）
                    } else {
                        $similarity = $base_similarity;
                    }

                    if ($similarity > 0.5) { // 50%以上の類似度（正規化削除後の適正値）
                        $ai_recommendations[] = [
                            'asin' => $candidate['asin'],
                            'title' => $candidate['title'],
                            'author' => $candidate['author'],
                            'image_url' => $candidate['image_url'] ?? '/img/no-image-book.png',
                            'description' => $candidate['description'] ?? '',
                            'similarity' => round($similarity * 100, 1),
                            'reader_count' => 0,
                            'avg_rating' => 0,
                            'category_match' => $category_match,
                            'genre_match' => $genre_match
                        ];
                    }
                }

                // embeddingは巨大なので候補プールは早めに解放する
                unset($candidates, $candidate_asins);

                // 技術書の場合、小説/ラノベを除外
                if ($book_genre === 'tech') {
                    $ai_recommendations = array_filter($ai_recommendations, function($rec) use ($detectGenreFromTitle) {
                        $candidate_genre = $detectGenreFromTitle($rec['title']);
                        return $candidate_genre !== 'fiction'; // fictionは除外
                    });
                    $ai_recommendations = array_values($ai_recommendations); // インデックスをリセット
                }

                // 類似度でソート
                usort($ai_recommendations, function($a, $b) {
                    return $b['similarity'] <=> $a['similarity'];
                });

                // 上位10件に限定
                $ai_recommendations = array_slice($ai_recommendations, 0, 10);

                // 読者数・平均評価は上位10件だけまとめて取得する
                if (!empty($ai_recommendations)) {
                    $rec_asins = array_column($ai_recommendations, 'asin');
                    $placeholders = implode(',', array_fill(0, count($rec_asins), '?'));
                    $stats_sql = "SELECT amazon_id,
                                         COUNT(DISTINCT user_id) AS reader_count,
                                         AVG(CASE WHEN rating > 0 THEN rating END) AS avg_rating
                                  FROM b_book_list
                                  WHERE amazon_id IN ({$placeholders})
                                  GROUP BY amazon_id";
                    $rec_stats_rows = $g_db->getAll($stats_sql, $rec_asins, DB_FETCHMODE_ASSOC);
                    $rec_stats = [];
                    if (!DB::isError($rec_stats_rows) && $rec_stats_rows) {
                        foreach ($rec_stats_rows as $stats_row) {
                            $rec_stats[$stats_row['amazon_id']] = $stats_row;
                        }
                    }

                    // 各推薦本にReadNest内のレビュー情報を追加
                    foreach ($ai_recommendations as &$rec) {
                        $rec['reader_count'] = (int)($rec_stats[$rec['asin']]['reader_count'] ?? 0);
                        $rec['avg_rating'] = round((float)($rec_stats[$rec['asin']]['avg_rating'] ?? 0), 1);

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

    // 結果が空でもキャッシュする（毎リクエストで全候補を再計算しないため）
    if ($rec_cache_key !== '') {
        BookCache::setSimilarBooks($rec_cache_key, $ai_recommendations);
    }
}

// 所持済みの本には印を付ける（キャッシュをユーザー非依存に保つため表示直前に行う）
//
// かつては所持済みを除外していたが、読者が自分ひとりだけの本では
// 協調フィルタの候補が全て自分の本棚から来るため、除外すると必ず0件になった。
// 読者の少ない本ほどそうなるので、除外せず「本棚にあり」として見せる。
if ($login_flag && !empty($ai_recommendations)) {
    $owned_sql = "SELECT book_id, amazon_id FROM b_book_list WHERE user_id = ?";
    $owned_result = $g_db->getAll($owned_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
    if (!DB::isError($owned_result) && $owned_result) {
        $owned_books = [];
        foreach ($owned_result as $owned_row) {
            $owned_asin = $owned_row['amazon_id'] ?? '';
            if ($owned_asin === '' || isset($owned_books[$owned_asin])) {
                continue;
            }
            $owned_books[$owned_asin] = (int)$owned_row['book_id'];
        }

        foreach ($ai_recommendations as &$rec) {
            if (isset($owned_books[$rec['asin']])) {
                $rec['is_owned'] = true;
                $rec['owned_book_id'] = $owned_books[$rec['asin']];
            }
        }
        unset($rec);
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
        $progress_sql = "SELECT event_id, event_date, page, memo, event FROM b_book_event WHERE book_id = ? AND user_id = ? ORDER BY event_date DESC";
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

// SEOタグの生成（旧テンプレート用）
$g_seo_tags = generateSEOTags($seo_data);

// canonical + JSON-LD（モダンテンプレート用）
// /book/{id} と /book_detail/{id} は同一スクリプトなので canonical は /book/{id} に統一される
$g_structured_tags = generateStructuredTags($seo_data);

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// プロファイル写真取得用のヘルパー関数

// 同じ著者の本を検索する関数
function searchBooksByAuthor($author, $limit = 10) {
    global $g_db;
    
    // 注意: bl.author に単独索引が無いため、このクエリは b_book_list の
    // フルスキャンになる。呼び出し側で必ずキャッシュすること。
    $sql = "SELECT bl.book_id, bl.name, bl.author, bl.image_url, bl.amazon_id
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