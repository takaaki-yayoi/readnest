<?php
/**
 * ブックリンクAPI
 *
 * 本の詳細ページの「この本で紹介された本」から使う。
 *
 * POST /api/book_links.php
 * Body (JSON):
 *   { action: "add", source_book_id, asin, isbn, title, author, image_url, detail_url, pages, categories,
 *     page, memo, add_to_shelf }
 *   { action: "update", id, page, memo }
 *   { action: "delete", id }
 */

header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

try {
    require_once(dirname(__DIR__) . '/modern_config.php');
    require_once(dirname(__DIR__) . '/library/csrf.php');
    require_once(dirname(__DIR__) . '/library/book_links.php');
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'システムエラー']);
    exit;
}

function bookLinksRespond($status_code, array $body) {
    ob_end_clean();
    http_response_code($status_code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bookLinksRespond(405, ['success' => false, 'error' => 'POSTのみ受け付けます']);
}

// ログインチェック
if (!checkLogin()) {
    bookLinksRespond(401, ['success' => false, 'error' => 'ログインが必要です']);
}

// CSRF検証
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verifyCSRFToken($csrf_token)) {
    bookLinksRespond(403, ['success' => false, 'error' => '不正なリクエストです']);
}

$user_id = (int)$_SESSION['AUTH_USER'];
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    bookLinksRespond(400, ['success' => false, 'error' => 'リクエストが不正です']);
}

$action = $input['action'] ?? '';

global $g_db;

if ($action === 'add') {
    $source_book_id = (int)($input['source_book_id'] ?? 0);
    $asin = trim((string)($input['asin'] ?? ''));
    $title = trim((string)($input['title'] ?? ''));

    if ($source_book_id <= 0 || $asin === '' || $title === '') {
        bookLinksRespond(400, ['success' => false, 'error' => '必須パラメータが不足しています']);
    }

    // AI推薦のダミーASINは拒否（add_book_from_search.php と同じ）
    if (strpos($asin, 'ai_') === 0 || strpos($asin, 'SAMPLE') === 0) {
        bookLinksRespond(400, ['success' => false, 'error' => 'この本はリンクできません']);
    }

    $source = getOwnBookForLink($user_id, $source_book_id);
    if (!$source) {
        bookLinksRespond(404, ['success' => false, 'error' => '紹介元の本が見つかりません']);
    }
    if (!isBookLinkSourceStatus($source['status'])) {
        bookLinksRespond(400, ['success' => false, 'error' => '読書中・読了の本にだけ追加できます']);
    }
    if ($source['amazon_id'] === $asin) {
        bookLinksRespond(400, ['success' => false, 'error' => '同じ本はリンクできません']);
    }

    $existing_link_id = findBookLinkId($user_id, $source_book_id, $asin);
    if ($existing_link_id) {
        bookLinksRespond(409, ['success' => false, 'error' => 'この本はすでに追加されています', 'id' => $existing_link_id]);
    }

    $isbn = trim((string)($input['isbn'] ?? ''));
    $author = trim((string)($input['author'] ?? ''));
    $image_url = trim((string)($input['image_url'] ?? ''));
    $detail_url = trim((string)($input['detail_url'] ?? ''));
    $pages = max(0, (int)($input['pages'] ?? 0));
    $categories = $input['categories'] ?? null;

    // 既に本棚にあればその本に紐づける。なければ希望に応じて「いつか買う」で追加
    $target_book_id = is_bookmarked($user_id, $asin);
    $added_to_shelf = false;
    if (!$target_book_id && !empty($input['add_to_shelf'])) {
        $target_book_id = createBook(
            $user_id,
            $title,
            $asin,
            $isbn,
            $author,
            '',          // memo
            $pages,
            BUY_SOMEDAY,
            $detail_url,
            $image_url,
            null,        // finished_date
            $categories
        );
        if (!$target_book_id) {
            bookLinksRespond(500, ['success' => false, 'error' => '本棚への追加に失敗しました']);
        }
        $added_to_shelf = true;
    }

    // 本棚に入れない場合も、表示用に書誌情報を b_book_repository に残す
    if (!$target_book_id) {
        $exists = $g_db->getOne('SELECT asin FROM b_book_repository WHERE asin = ?', [$asin]);
        if (!DB::isError($exists) && !$exists) {
            addBookToRepository($asin, $title, $image_url, $author);
        }
    }

    $link_id = createBookLinkRecord(
        $user_id,
        $source_book_id,
        $asin,
        $target_book_id ?: null,
        $input['page'] ?? null,
        $input['memo'] ?? null
    );
    if (!$link_id) {
        bookLinksRespond(500, ['success' => false, 'error' => 'リンクの追加に失敗しました']);
    }

    $rows = getBookLinksFromBook($source_book_id, $link_id);
    bookLinksRespond(200, [
        'success' => true,
        'added_to_shelf' => $added_to_shelf,
        'link' => $rows ? formatBookLinkForDisplay($rows[0]) : null,
    ]);
}

if ($action === 'update' || $action === 'delete') {
    $link_id = (int)($input['id'] ?? 0);
    $link = $link_id > 0 ? getBookLinkById($user_id, $link_id) : null;
    if (!$link) {
        bookLinksRespond(404, ['success' => false, 'error' => 'リンクが見つかりません']);
    }

    if ($action === 'delete') {
        if (!deleteBookLinkRecord($user_id, $link_id)) {
            bookLinksRespond(500, ['success' => false, 'error' => 'リンクの削除に失敗しました']);
        }
        bookLinksRespond(200, ['success' => true]);
    }

    if (!updateBookLinkRecord($user_id, $link_id, $input['page'] ?? null, $input['memo'] ?? null)) {
        bookLinksRespond(500, ['success' => false, 'error' => 'リンクの更新に失敗しました']);
    }
    $rows = getBookLinksFromBook((int)$link['source_book_id'], $link_id);
    bookLinksRespond(200, [
        'success' => true,
        'link' => $rows ? formatBookLinkForDisplay($rows[0]) : null,
    ]);
}

bookLinksRespond(400, ['success' => false, 'error' => '不明な操作です']);
