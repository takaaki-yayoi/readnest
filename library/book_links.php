<?php
/**
 * ブックリンク機能
 *
 * 読んでいる本(紹介元)の中で紹介されていた本(紹介先)を紐づける。
 * 紹介元は本人の本棚の本(b_book_list.book_id)、紹介先は ASIN で識別する。
 * テーブル定義: sql/create_book_links.sql
 *
 * 旧ブックリンク(b_book_relation / addBookLink() 等)とは別テーブル。
 */

if (!defined('CONFIG')) {
    die('Direct access not allowed');
}

// メモの最大文字数（テーブル定義の VARCHAR(500) に合わせる）
if (!defined('BOOK_LINK_MEMO_MAX')) {
    define('BOOK_LINK_MEMO_MAX', 500);
}

/**
 * 紹介元にできる本か（読書中・読了・既読）
 */
function isBookLinkSourceStatus($status) {
    return in_array((int)$status, [READING_NOW, READING_FINISH, READ_BEFORE], true);
}

/**
 * 本人の本棚の本を取得する（所有者チェックを兼ねる）
 */
function getOwnBookForLink($user_id, $book_id) {
    global $g_db;

    $sql = "SELECT book_id, user_id, amazon_id, name, status FROM b_book_list WHERE book_id = ? AND user_id = ?";
    $row = $g_db->getRow($sql, [(int)$book_id, (int)$user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($row) || !$row) {
        return null;
    }
    return $row;
}

/**
 * ブックリンクを1件取得する（本人のもののみ）
 */
function getBookLinkById($user_id, $link_id) {
    global $g_db;

    $sql = "SELECT * FROM book_links WHERE id = ? AND user_id = ?";
    $row = $g_db->getRow($sql, [(int)$link_id, (int)$user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($row) || !$row) {
        return null;
    }
    return $row;
}

/**
 * 同じ組み合わせのリンクが既にあれば id を返す
 */
function findBookLinkId($user_id, $source_book_id, $target_asin) {
    global $g_db;

    $sql = "SELECT id FROM book_links WHERE user_id = ? AND source_book_id = ? AND target_asin = ?";
    $id = $g_db->getOne($sql, [(int)$user_id, (int)$source_book_id, $target_asin]);
    if (DB::isError($id) || !$id) {
        return null;
    }
    return (int)$id;
}

/**
 * ページ番号を正規化（未入力・0以下は NULL）
 */
function normalizeBookLinkPage($page) {
    if ($page === null || $page === '' || !is_scalar($page)) {
        return null;
    }
    $page = (int)$page;
    return $page > 0 ? $page : null;
}

/**
 * メモを正規化（空は NULL、長すぎるものは切り詰め）
 */
function normalizeBookLinkMemo($memo) {
    if (!is_scalar($memo)) {
        return null;
    }
    $memo = trim((string)$memo);
    if ($memo === '') {
        return null;
    }
    return mb_substr($memo, 0, BOOK_LINK_MEMO_MAX);
}

/**
 * ブックリンクを追加する
 *
 * @return int|null 追加したリンクの id
 */
function createBookLinkRecord($user_id, $source_book_id, $target_asin, $target_book_id, $page, $memo) {
    global $g_db;

    $sql = "INSERT INTO book_links
            (user_id, source_book_id, target_asin, target_book_id, page, memo, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $result = $g_db->query($sql, [
        (int)$user_id,
        (int)$source_book_id,
        $target_asin,
        $target_book_id ? (int)$target_book_id : null,
        normalizeBookLinkPage($page),
        normalizeBookLinkMemo($memo),
    ]);
    if (DB::isError($result)) {
        error_log('createBookLinkRecord failed: ' . $result->getMessage());
        return null;
    }

    $id = $g_db->lastInsertId();
    return $id ? (int)$id : null;
}

/**
 * ブックリンクのページ番号・メモを更新する
 */
function updateBookLinkRecord($user_id, $link_id, $page, $memo) {
    global $g_db;

    $sql = "UPDATE book_links SET page = ?, memo = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
    $result = $g_db->query($sql, [
        normalizeBookLinkPage($page),
        normalizeBookLinkMemo($memo),
        (int)$link_id,
        (int)$user_id,
    ]);
    if (DB::isError($result)) {
        error_log('updateBookLinkRecord failed: ' . $result->getMessage());
        return false;
    }
    return true;
}

/**
 * ブックリンクを削除する（本棚の本は削除しない）
 */
function deleteBookLinkRecord($user_id, $link_id) {
    global $g_db;

    $sql = "DELETE FROM book_links WHERE id = ? AND user_id = ?";
    $result = $g_db->query($sql, [(int)$link_id, (int)$user_id]);
    if (DB::isError($result)) {
        error_log('deleteBookLinkRecord failed: ' . $result->getMessage());
        return false;
    }
    return true;
}

/**
 * b_book_repository から ASIN ごとの書誌情報を取る
 *
 * ASIN が重複しうるため、最初に見つかった1件を使う。
 *
 * @return array asin => ['title', 'author', 'image_url']
 */
function getRepositoryInfoByAsins(array $asins) {
    global $g_db;

    $asins = array_values(array_unique(array_filter(array_map('strval', $asins), 'strlen')));
    $info = [];
    foreach (array_chunk($asins, 500) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $rows = $g_db->getAll(
            "SELECT asin, title, author, image_url FROM b_book_repository WHERE asin IN ({$placeholders})",
            $chunk,
            DB_FETCHMODE_ASSOC
        );
        if (DB::isError($rows)) {
            error_log('getRepositoryInfoByAsins failed: ' . $rows->getMessage());
            continue;
        }
        foreach ($rows ?: [] as $row) {
            if (!isset($info[$row['asin']])) {
                $info[$row['asin']] = $row;
            }
        }
    }
    return $info;
}

/**
 * リンクの行に紹介先の本の情報（target_book_id / target_status / target_title / target_author / target_image_url）を付ける
 *
 * 本人の本棚にあればその本、なければ b_book_repository から取る。
 * 本棚の本は保存済みの target_book_id を優先し、削除されていれば同じ ASIN の本を探す
 * （リンク後に本棚へ追加した場合もここで拾える）。
 *
 * 以前は SQL の JOIN 条件に相関サブクエリ（COALESCE）を入れていたが、主キーが使えず
 * リンク数 × 本棚の冊数の走査になり、全件を引く一覧ページで MySQL が接続を落とした（2006）。
 * そのため本棚・リポジトリは IN (...) でまとめて引いて PHP で突き合わせる。
 *
 * @param array $rows user_id / target_asin / target_book_id を含むリンクの行
 */
function attachBookLinkTargets(array $rows) {
    global $g_db;

    if (empty($rows)) {
        return [];
    }

    // ユーザーごとに本棚の本を引く（一覧・本詳細ではすべて同じユーザー）
    $by_user = [];
    foreach ($rows as $row) {
        $by_user[(int)$row['user_id']][] = $row;
    }

    $books_by_id = [];   // book_id => 本棚の本
    $book_by_asin = [];  // user_id => [asin => 本棚の本（book_id が最大のもの）]
    foreach ($by_user as $user_id => $user_rows) {
        $book_ids = array_values(array_unique(array_filter(array_map('intval', array_column($user_rows, 'target_book_id')))));
        foreach (array_chunk($book_ids, 500) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $books = $g_db->getAll(
                "SELECT book_id, amazon_id, name, author, image_url, status FROM b_book_list
                 WHERE user_id = ? AND book_id IN ({$placeholders})",
                array_merge([$user_id], $chunk),
                DB_FETCHMODE_ASSOC
            );
            if (DB::isError($books)) {
                error_log('attachBookLinkTargets failed: ' . $books->getMessage());
                continue;
            }
            foreach ($books ?: [] as $book) {
                $books_by_id[(int)$book['book_id']] = $book;
            }
        }

        // 保存済みの book_id で見つからなかったものは ASIN で探す
        $asins = [];
        foreach ($user_rows as $row) {
            if (empty($row['target_book_id']) || !isset($books_by_id[(int)$row['target_book_id']])) {
                $asins[] = (string)$row['target_asin'];
            }
        }
        $asins = array_values(array_unique($asins));
        foreach (array_chunk($asins, 500) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $books = $g_db->getAll(
                "SELECT book_id, amazon_id, name, author, image_url, status FROM b_book_list
                 WHERE user_id = ? AND amazon_id IN ({$placeholders})",
                array_merge([$user_id], $chunk),
                DB_FETCHMODE_ASSOC
            );
            if (DB::isError($books)) {
                error_log('attachBookLinkTargets failed: ' . $books->getMessage());
                continue;
            }
            foreach ($books ?: [] as $book) {
                $asin = (string)$book['amazon_id'];
                if (!isset($book_by_asin[$user_id][$asin]) || (int)$book['book_id'] > (int)$book_by_asin[$user_id][$asin]['book_id']) {
                    $book_by_asin[$user_id][$asin] = $book;
                }
            }
        }
    }

    $repository = getRepositoryInfoByAsins(array_column($rows, 'target_asin'));

    foreach ($rows as &$row) {
        $user_id = (int)$row['user_id'];
        $asin = (string)$row['target_asin'];
        $book = null;
        if (!empty($row['target_book_id']) && isset($books_by_id[(int)$row['target_book_id']])) {
            $book = $books_by_id[(int)$row['target_book_id']];
        } elseif (isset($book_by_asin[$user_id][$asin])) {
            $book = $book_by_asin[$user_id][$asin];
        }
        $repo = $repository[$asin] ?? null;

        $row['target_book_id'] = $book ? (int)$book['book_id'] : null;
        $row['target_status'] = $book ? (int)$book['status'] : null;
        $row['target_title'] = (string)($book['name'] ?? $repo['title'] ?? '');
        $row['target_author'] = (string)(($book && $book['author'] !== '' && $book['author'] !== null) ? $book['author'] : ($repo['author'] ?? ''));
        $row['target_image_url'] = (string)(($book && !empty($book['image_url'])) ? $book['image_url'] : ($repo['image_url'] ?? ''));
    }
    unset($row);

    return $rows;
}

/**
 * 紹介元の本から張られたリンク（この本で紹介された本）
 *
 * 紹介先の本の情報は attachBookLinkTargets() で付ける。
 * 呼び出し側で閲覧権限（本人 or 公開ユーザー）を確認すること。
 *
 * @param int|null $link_id 指定すればその1件だけ返す
 */
function getBookLinksFromBook($source_book_id, $link_id = null) {
    global $g_db;

    $sql = "SELECT l.id, l.user_id, l.source_book_id, l.target_asin, l.target_book_id, l.page, l.memo,
                   l.created_at, l.updated_at
            FROM book_links l
            WHERE l.source_book_id = ?";
    $params = [(int)$source_book_id];
    if ($link_id !== null) {
        $sql .= " AND l.id = ?";
        $params[] = (int)$link_id;
    }
    $sql .= " ORDER BY (l.page IS NULL), l.page, l.created_at";

    $rows = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
    if (DB::isError($rows)) {
        error_log('getBookLinksFromBook failed: ' . $rows->getMessage());
        return [];
    }
    return attachBookLinkTargets($rows ?: []);
}

/**
 * ASIN の本を紹介していた本（逆リンク）
 *
 * 閲覧者本人のリンクと、読書記録を公開しているユーザー(diary_policy = 1)のリンクだけを返す。
 * 紹介元の本が本棚から削除されているリンクは出さない。
 *
 * @param bool $own_only true なら閲覧者本人のリンクだけ返す（MCP 用）
 */
function getBookLinksToAsin($target_asin, $viewer_user_id = null, $limit = 30, $own_only = false) {
    global $g_db;

    if ($target_asin === '' || $target_asin === null) {
        return [];
    }

    $viewer_user_id = $viewer_user_id ? (int)$viewer_user_id : 0;
    $limit = max(1, (int)$limit);
    $visibility = $own_only ? 'l.user_id = ?' : '(l.user_id = ? OR u.diary_policy = 1)';

    $sql = "SELECT l.id, l.user_id, l.page, l.memo, l.created_at,
                   sb.book_id AS source_book_id, sb.name AS source_title,
                   COALESCE(NULLIF(sb.author, ''),
                            (SELECT r.author FROM b_book_repository r WHERE r.asin = sb.amazon_id LIMIT 1),
                            '') AS source_author,
                   COALESCE(NULLIF(sb.image_url, ''),
                            (SELECT r.image_url FROM b_book_repository r WHERE r.asin = sb.amazon_id LIMIT 1),
                            '') AS source_image_url,
                   u.nickname
            FROM book_links l
            INNER JOIN b_book_list sb ON sb.book_id = l.source_book_id AND sb.user_id = l.user_id
            INNER JOIN b_user u ON u.user_id = l.user_id
            WHERE l.target_asin = ?
              AND {$visibility}
            ORDER BY (l.user_id = ?) DESC, l.created_at DESC
            LIMIT {$limit}";

    $rows = $g_db->getAll($sql, [$target_asin, $viewer_user_id, $viewer_user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($rows)) {
        error_log('getBookLinksToAsin failed: ' . $rows->getMessage());
        return [];
    }
    return $rows ?: [];
}

/**
 * ユーザーのブックリンクをすべて取得する（ブックリンク一覧ページ用）
 *
 * 紹介先の本の情報は attachBookLinkTargets() で、紹介元の本の著者・表紙の補完は
 * b_book_repository からまとめて引いて付ける（どちらも相関サブクエリを使わない）。
 * 紹介元の本が本棚から削除されているリンクは出さない。
 */
function getUserBookLinks($user_id, $limit = 2000) {
    global $g_db;

    $limit = max(1, (int)$limit);

    $sql = "SELECT l.id, l.user_id, l.source_book_id, l.target_asin, l.target_book_id, l.page, l.memo,
                   l.created_at, l.updated_at,
                   sb.name AS source_title, sb.status AS source_status, sb.amazon_id AS source_asin,
                   sb.author AS source_author, sb.image_url AS source_image_url
            FROM book_links l
            INNER JOIN b_book_list sb ON sb.book_id = l.source_book_id
            WHERE l.user_id = ? AND sb.user_id = l.user_id
            ORDER BY l.created_at DESC
            LIMIT {$limit}";

    $rows = $g_db->getAll($sql, [(int)$user_id], DB_FETCHMODE_ASSOC);
    if (DB::isError($rows)) {
        error_log('getUserBookLinks failed: ' . $rows->getMessage());
        return [];
    }
    if (empty($rows)) {
        return [];
    }

    // 紹介元の本の著者・表紙が空ならリポジトリで補う
    $repository = getRepositoryInfoByAsins(array_column($rows, 'source_asin'));
    foreach ($rows as &$row) {
        $repo = $repository[(string)$row['source_asin']] ?? null;
        if ((string)$row['source_author'] === '') {
            $row['source_author'] = (string)($repo['author'] ?? '');
        }
        if ((string)$row['source_image_url'] === '') {
            $row['source_image_url'] = (string)($repo['image_url'] ?? '');
        }
    }
    unset($row);

    return attachBookLinkTargets($rows);
}

/**
 * 画面・API 用にリンク1件を整形する（紹介先側）
 */
function formatBookLinkForDisplay(array $link) {
    $target_book_id = !empty($link['target_book_id']) ? (int)$link['target_book_id'] : null;
    $title = $link['target_title'] !== '' ? $link['target_title'] : $link['target_asin'];

    return [
        'id' => (int)$link['id'],
        'target_asin' => $link['target_asin'],
        'target_book_id' => $target_book_id,
        'title' => $title,
        'author' => $link['target_author'] ?? '',
        'image_url' => !empty($link['target_image_url']) ? $link['target_image_url'] : '/img/no-image-book.png',
        'url' => $target_book_id ? '/book/' . $target_book_id : '/book_entity/' . rawurlencode($link['target_asin']),
        'page' => $link['page'] !== null ? (int)$link['page'] : null,
        'memo' => $link['memo'] ?? '',
    ];
}
