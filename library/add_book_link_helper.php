<?php
if (!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

/**
 * 本の追加ページ（add_book.php）へのリンク生成ヘルパー
 *
 * タイトルだけのキーワード検索は、Google Books APIで関連書籍や別の版が上位に来て
 * 目的の本がヒットしないことがあるため、本を特定できる情報があればそれを使う。
 *
 * 優先度:
 *   1. ASIN（b_book_repository 登録済みの本）→ ?asin=（リポジトリの行をそのまま表示できる）
 *   2. ISBN → ?isbn=（見つからない場合に備えて title も渡す）
 *   3. フォールバックでタイトルのキーワード検索
 */
function getAddBookUrl(array $book): string
{
    $asin = trim((string)($book['amazon_id'] ?? ($book['asin'] ?? '')));
    if ($asin !== '') {
        return '/add_book.php?asin=' . urlencode($asin);
    }

    $title = trim((string)($book['title'] ?? ''));
    $isbn = str_replace(['-', ' '], '', trim((string)($book['isbn'] ?? '')));
    // Google Books API形式（industryIdentifiers）にも対応。ISBN-13を優先
    if ($isbn === '' && !empty($book['industryIdentifiers']) && is_array($book['industryIdentifiers'])) {
        foreach ($book['industryIdentifiers'] as $id) {
            if (!is_array($id)) continue;
            if (($id['type'] ?? '') === 'ISBN_13') {
                $isbn = $id['identifier'] ?? '';
                break;
            }
            if (($id['type'] ?? '') === 'ISBN_10' && $isbn === '') {
                $isbn = $id['identifier'] ?? '';
            }
        }
    }
    if (preg_match('/^([0-9]{9}[0-9Xx]|97[89][0-9]{10})$/', $isbn)) {
        return '/add_book.php?isbn=' . urlencode($isbn)
            . ($title !== '' ? '&title=' . urlencode($title) : '');
    }

    return '/add_book.php?keyword=' . urlencode($title);
}
