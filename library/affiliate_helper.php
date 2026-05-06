<?php
if (!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

/**
 * Amazonアソシエイトリンク生成ヘルパー
 *
 * 優先度:
 *   1. ASIN（amazon_id / asin キー）が有効なら商品ページ直リンク /dp/{ASIN}
 *   2. ISBN（isbn / isbn13 / isbn10 / industryIdentifiers）があれば検索URL /s?k={ISBN}
 *   3. フォールバックでタイトル+著者の検索URL
 *
 * MANUAL_ プレフィックス付きの amazon_id は手動追加書籍なので ASIN として扱わない。
 * Google Books API レスポンス形式（industryIdentifiers, authors配列）にも対応。
 */
function getAmazonProductUrl(array $book): string
{
    $tag = defined('AMAZON_ASSOCIATE_TAG') ? AMAZON_ASSOCIATE_TAG : '';

    // ASIN: amazon_id または asin キー
    $asin = $book['amazon_id'] ?? ($book['asin'] ?? '');
    if ($asin !== '' && strpos($asin, 'MANUAL_') !== 0 && preg_match('/^[A-Z0-9]{10}$/', $asin)) {
        return 'https://www.amazon.co.jp/dp/' . rawurlencode($asin)
            . ($tag !== '' ? '?tag=' . rawurlencode($tag) : '');
    }

    // ISBN: 直接キー、または Google Books の industryIdentifiers から抽出
    $isbn = $book['isbn'] ?? ($book['isbn13'] ?? ($book['isbn10'] ?? ''));
    if ($isbn === '' && !empty($book['industryIdentifiers']) && is_array($book['industryIdentifiers'])) {
        $isbn13 = '';
        $isbn10 = '';
        foreach ($book['industryIdentifiers'] as $id) {
            if (!is_array($id)) continue;
            if (($id['type'] ?? '') === 'ISBN_13') {
                $isbn13 = $id['identifier'] ?? '';
            } elseif (($id['type'] ?? '') === 'ISBN_10') {
                $isbn10 = $id['identifier'] ?? '';
            }
        }
        $isbn = $isbn13 !== '' ? $isbn13 : $isbn10;
    }
    $isbn = preg_replace('/[^0-9X]/', '', (string)$isbn);
    if ($isbn !== '') {
        return 'https://www.amazon.co.jp/s?k=' . rawurlencode($isbn)
            . ($tag !== '' ? '&tag=' . rawurlencode($tag) : '');
    }

    // フォールバック: タイトル + 著者
    $author = $book['author'] ?? '';
    if ($author === '' && !empty($book['authors']) && is_array($book['authors'])) {
        $author = implode(' ', $book['authors']);
    }
    $query = trim(($book['title'] ?? '') . ' ' . $author);
    return 'https://www.amazon.co.jp/s?k=' . rawurlencode($query)
        . ($tag !== '' ? '&tag=' . rawurlencode($tag) : '');
}
