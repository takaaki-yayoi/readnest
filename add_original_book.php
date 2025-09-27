<?php
/**
 * モダン版手動本追加ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// フォームヘルパーを読み込み
require_once(dirname(__FILE__) . '/library/form_helpers.php');

// ログインチェック
if (!checkLogin()) {
    header('Location: https://readnest.jp');
    exit;
}

$user_id = $_SESSION['AUTH_USER'];
$d_nickname = getNickname($user_id);
$d_message = '';
$errors = [];

// ページタイトル設定
$d_site_title = "手動で本を追加 - ReadNest";

// メタ情報
$g_meta_description = "検索で見つからない本を手動で追加できます。タイトル、著者、ページ数を入力して本棚に追加しましょう。";
$g_meta_keyword = "本追加,手動追加,ReadNest,本棚,読書記録";

// バリデーション関数
function validateBookTitle(string $title): array {
    $errors = [];
    $title = trim($title);
    
    if (empty($title)) {
        $errors[] = "タイトルを入力してください。";
    } elseif (mb_strlen($title) > 200) {
        $errors[] = "タイトルは200文字以内で入力してください。";
    }
    
    return $errors;
}

function validateBookAuthor(string $author): array {
    $errors = [];
    $author = trim($author);
    
    if (empty($author)) {
        $errors[] = "著者名を入力してください。";
    } elseif (mb_strlen($author) > 100) {
        $errors[] = "著者名は100文字以内で入力してください。";
    }
    
    return $errors;
}

function validateBookPages(string $pages): array {
    $errors = [];
    $pages = trim($pages);
    
    if (empty($pages)) {
        $errors[] = "ページ数を入力してください。";
    } elseif (!is_numeric($pages)) {
        $errors[] = "ページ数は数字で入力してください。";
    } elseif ((int)$pages < 1 || (int)$pages > 9999) {
        $errors[] = "ページ数は1〜9999の範囲で入力してください。";
    }
    
    return $errors;
}

// フォームデータの初期化
$form_data = [
    'title' => trim($_GET['title'] ?? ''),
    'author' => trim($_GET['author'] ?? ''),
    'number_of_pages' => '',
    'status_list' => NOT_STARTED
];

$step = 'input'; // input, confirm, complete

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームデータの取得
    $form_data['title'] = trim($_POST['title'] ?? '');
    $form_data['author'] = trim($_POST['author'] ?? '');
    $form_data['number_of_pages'] = trim($_POST['number_of_pages'] ?? '');
    $form_data['status_list'] = (int)($_POST['status_list'] ?? NOT_STARTED);
    $confirm = $_POST['confirm'] ?? '';
    
    // バリデーション
    $errors = array_merge(
        validateBookTitle($form_data['title']),
        validateBookAuthor($form_data['author']),
        validateBookPages($form_data['number_of_pages'])
    );
    
    if (empty($errors)) {
        if ($confirm === 'yes') {
            // 最終確認後の本棚への追加処理
            try {
                $book_asin = ''; // 手動追加の場合は空
                $book_isbn = ''; // 手動追加の場合は空
                $memo = '';
                $detail_url = '';
                $image_url = '';
                
                $book_id = createBook(
                    $user_id,
                    $form_data['title'],
                    $book_asin,
                    $book_isbn,
                    $form_data['author'],
                    $memo,
                    (int)($form_data['number_of_pages'] ?: 0),
                    $form_data['status_list'],
                    $detail_url,
                    $image_url
                );
                
                // 読了の場合はイベントも作成
                if ($form_data['status_list'] == READING_FINISH) {
                    createEvent($user_id, $book_id, $memo, (int)($form_data['number_of_pages'] ?: 0));
                }
                
                $book_title_escaped = html($form_data['title']);
                $d_message = "「<a href=\"/book/{$book_id}\" class=\"text-readnest-primary hover:underline\">{$book_title_escaped}</a>」を本棚に追加しました。";
                $step = 'complete';
                
            } catch (Exception $e) {
                error_log("Error adding manual book: " . $e->getMessage());
                $errors[] = "本の追加中にエラーが発生しました。もう一度お試しください。";
            }
        } else {
            // 確認画面表示
            $step = 'confirm';
        }
    }
}

// ステータス選択肢
$status_options = [
    BUY_SOMEDAY => 'いつか買う',
    NOT_STARTED => '買ったけどまだ読んでない',
    READING_NOW => '読んでいるところ',
    READING_FINISH => '読み終わった！',
    READ_BEFORE => '昔読んだ'
];

// Analytics設定
$g_analytics = '<!-- Google Analytics code would go here -->';

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_add_original_book.php'));
?>