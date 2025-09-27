<?php
require_once('modern_config.php');

$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

if ($book_id <= 0) {
    header('Location: /bookshelf.php');
    exit;
}

$g_db = DB_Connect();

// 本の情報を取得
$sql = "SELECT * FROM b_book_list WHERE book_id = ? AND user_id = ?";
$book = $g_db->getRow($sql, [$book_id, $mine_user_id], DB_FETCHMODE_ASSOC);

if (DB::isError($book) || empty($book)) {
    header('Location: /bookshelf.php');
    exit;
}

$message = '';
$message_type = '';

// CSRFトークン生成
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 画像更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'セキュリティトークンが無効です。';
        $message_type = 'error';
    } else {
        // URLによる更新
        if (isset($_POST['image_url'])) {
            $new_url = $_POST['image_url'];
            // デフォルト画像または有効なURLの場合
            if ($new_url === '/img/no-image-book.png' || filter_var($new_url, FILTER_VALIDATE_URL)) {
                $result = $g_db->query(
                    "UPDATE b_book_list SET image_url = ? WHERE book_id = ? AND user_id = ?",
                    [$new_url, $book_id, $mine_user_id]
                );
                if (!DB::isError($result)) {
                    header('Location: /book/' . $book_id);
                    exit;
                }
            }
        }
        // ファイルアップロードによる更新
        elseif (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_file = $_FILES['image_file'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            // MIMEタイプチェック
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $upload_file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $message = '許可されていないファイル形式です。JPEG、PNG、GIF、WebPのみアップロード可能です。';
                $message_type = 'error';
            } else {
                // ファイルサイズチェック（5MB）
                if ($upload_file['size'] > 5 * 1024 * 1024) {
                    $message = 'ファイルサイズが大きすぎます。5MB以下のファイルをアップロードしてください。';
                    $message_type = 'error';
                } else {
                    // アップロードディレクトリの設定
                    $upload_base_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/book_covers';
                    $year_month = date('Y/m');
                    $upload_dir = $upload_base_dir . '/' . $year_month;
                    
                    // ディレクトリが存在しない場合は作成
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // ユニークなファイル名を生成
                    $extension = pathinfo($upload_file['name'], PATHINFO_EXTENSION);
                    $filename = 'book_' . $book_id . '_' . uniqid() . '.' . $extension;
                    $upload_path = $upload_dir . '/' . $filename;
                    $web_path = '/uploads/book_covers/' . $year_month . '/' . $filename;
                    
                    // ファイルを移動
                    if (move_uploaded_file($upload_file['tmp_name'], $upload_path)) {
                        // 画像リサイズ（必要な場合）
                        require_once(__DIR__ . '/library/image_helpers.php');
                        resizeBookCoverImage($upload_path, 400, 600); // 最大幅400px、最大高さ600px
                        
                        // データベース更新
                        $result = $g_db->query(
                            "UPDATE b_book_list SET image_url = ? WHERE book_id = ? AND user_id = ?",
                            [$web_path, $book_id, $mine_user_id]
                        );
                        
                        if (!DB::isError($result)) {
                            // 古い画像がアップロードされたものだった場合は削除
                            if (!empty($book['image_url']) && strpos($book['image_url'], '/uploads/book_covers/') === 0) {
                                $old_file = $_SERVER['DOCUMENT_ROOT'] . $book['image_url'];
                                if (file_exists($old_file)) {
                                    unlink($old_file);
                                }
                            }
                            
                            header('Location: /book/' . $book_id);
                            exit;
                        } else {
                            // DB更新失敗時はアップロードしたファイルを削除
                            unlink($upload_path);
                            $message = 'データベースの更新に失敗しました。';
                            $message_type = 'error';
                        }
                    } else {
                        $message = 'ファイルのアップロードに失敗しました。';
                        $message_type = 'error';
                    }
                }
            }
        }
    }
}

// 画像候補を取得
$candidates = [];

// 書籍検索ライブラリを読み込み
require_once(__DIR__ . '/library/book_search.php');
require_once(__DIR__ . '/library/book_image_helper.php');

// タイトルと著者名で検索
$search_keyword = $book['name'];
if (!empty($book['author'])) {
    $search_keyword .= ' ' . $book['author'];
}

// Google Books APIで検索
$search_results = searchBooksWithGoogleAPI($search_keyword, 1, 10);
if (!empty($search_results['books'])) {
    foreach ($search_results['books'] as $result) {
        if (!empty($result['LargeImage']) && $result['LargeImage'] !== '/img/noimage.jpg') {
            $title = $result['Title'];
            if (!empty($result['Author'])) {
                $title .= ' - ' . $result['Author'];
            }
            // 重複を避けるためURLをキーにする
            $candidates[$result['LargeImage']] = [
                'url' => $result['LargeImage'],
                'source' => 'Google Books',
                'title' => $title
            ];
        }
    }
}

// ISBNがある場合は追加のAPIでも検索
if (!empty($book['isbn'])) {
    $imageHelper = getBookImageHelper();
    
    // OpenLibrary
    try {
        $url = $imageHelper->getOpenLibraryImageUrl($book['isbn'], 'L');
        if ($url && !isset($candidates[$url])) {
            $candidates[$url] = [
                'url' => $url,
                'source' => 'OpenLibrary',
                'title' => 'ISBN: ' . $book['isbn']
            ];
        }
    } catch (Exception $e) {}
    
    // 国立国会図書館
    try {
        $url = $imageHelper->getNationalDietLibraryImageUrl($book['isbn']);
        if ($url && !isset($candidates[$url])) {
            $candidates[$url] = [
                'url' => $url,
                'source' => '国立国会図書館',
                'title' => 'ISBN: ' . $book['isbn']
            ];
        }
    } catch (Exception $e) {}
}

// 最大10件に制限
$candidates = array_slice($candidates, 0, 10, true);

// ページメタ情報
$d_site_title = '表紙画像の変更 - ' . $book['name'] . ' - ReadNest';
$g_meta_description = '書籍の表紙画像を変更できます。';
$g_meta_keyword = '書籍,画像,変更,ReadNest';
$g_analytics = '';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_change_book_image.php'));
?>