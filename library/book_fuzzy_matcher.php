<?php
/**
 * 書籍あいまい検索ライブラリ
 *
 * MCPのis_book_readエンドポイントとWeb APIの両方から使用する。
 * タイトルや著者名の表記ゆれを吸収してユーザーの本棚から既読本を検索する。
 */

/**
 * 書籍タイトルを正規化する
 *
 * 以下の表記ゆれを吸収:
 * - 全角/半角英数スペース
 * - 中黒(・)の有無
 * - 副題（括弧内）の除去
 * - 半角カナ→全角カナ
 * - 大文字→小文字
 *
 * @param string $title
 * @return string 正規化されたタイトル
 */
function normalizeBookTitle(string $title): string {
    // 全角英数スペースを半角に変換
    $title = mb_convert_kana($title, 'as', 'UTF-8');
    // 半角カタカナを全角カタカナに変換
    $title = mb_convert_kana($title, 'KV', 'UTF-8');

    // 中黒(ナカグロ)を除去
    $title = str_replace(['・', '·', '‧', '・'], '', $title);

    // 副題（各種括弧内）を除去
    // 例: 「漂流 (新潮文庫)」→「漂流」、「吾輩は猫である（文庫版）」→「吾輩は猫である」
    $title = preg_replace('/[\s]*[\(（\[【〈《「『].+?[\)）\]】〉》」』]/u', '', $title);

    // 小文字化
    $title = mb_strtolower($title, 'UTF-8');

    // 連続する空白を1つに正規化
    $title = preg_replace('/\s+/', ' ', $title);

    // 前後の空白を除去
    $title = trim($title);

    return $title;
}

/**
 * 著者名を正規化する
 *
 * @param string $author
 * @return string 正規化された著者名
 */
function normalizeAuthorName(string $author): string {
    // 全角英数スペースを半角に変換
    $author = mb_convert_kana($author, 'as', 'UTF-8');
    // 半角カタカナを全角カタカナに変換
    $author = mb_convert_kana($author, 'KV', 'UTF-8');

    // 中黒を除去
    $author = str_replace(['・', '·', '‧', '・'], '', $author);

    // 小文字化
    $author = mb_strtolower($author, 'UTF-8');

    // 空白を正規化
    $author = preg_replace('/\s+/', ' ', $author);
    $author = trim($author);

    return $author;
}

/**
 * 2つのタイトルの類似度を計算する (0.0-1.0)
 *
 * @param string $title1 正規化済みタイトル
 * @param string $title2 正規化済みタイトル
 * @return float 類似度 (0.0-1.0)
 */
function titleSimilarity(string $title1, string $title2): float {
    // 完全一致
    if ($title1 === $title2) {
        return 1.0;
    }

    // 空文字チェック
    if (empty($title1) || empty($title2)) {
        return 0.0;
    }

    // 一方が他方を含む場合（部分一致）
    if (mb_strpos($title1, $title2) !== false || mb_strpos($title2, $title1) !== false) {
        $shorter = min(mb_strlen($title1), mb_strlen($title2));
        $longer = max(mb_strlen($title1), mb_strlen($title2));
        return $shorter / $longer;
    }

    // similar_textで類似度計算
    similar_text($title1, $title2, $percent);

    return $percent / 100.0;
}

/**
 * ユーザーの本棚から指定タイトルにマッチする本を検索する
 *
 * @param string $title 検索タイトル
 * @param string|null $author 著者名（任意）
 * @param int $user_id ユーザーID
 * @return array|null マッチした本の情報、なければnull
 *   返値: ['book_id' => int, 'name' => string, 'author' => string, 'rating' => int|null, 'status' => int, 'similarity' => float]
 */
function findMatchingBook(string $title, ?string $author, int $user_id): ?array {
    global $g_db;

    $normalized_title = normalizeBookTitle($title);
    $normalized_author = $author ? normalizeAuthorName($author) : null;

    if (empty($normalized_title)) {
        return null;
    }

    // DBから候補を取得
    // 正規化前のタイトルと正規化後のタイトルの両方でLIKE検索
    // 短いタイトルの場合はそのまま、長い場合は先頭部分で絞り込む
    $search_terms = [];
    $params = [$user_id];

    // 元のタイトルでの検索
    $search_terms[] = 'bl.name LIKE ?';
    $params[] = '%' . $title . '%';

    // 正規化タイトルが元と異なる場合、正規化版でも検索
    if ($normalized_title !== mb_strtolower($title, 'UTF-8')) {
        $search_terms[] = 'bl.name LIKE ?';
        $params[] = '%' . $normalized_title . '%';
    }

    // タイトルの先頭部分で検索（副題除去後の短いタイトル用）
    $title_prefix = mb_substr($normalized_title, 0, min(mb_strlen($normalized_title), 10), 'UTF-8');
    if (mb_strlen($title_prefix) >= 2) {
        $search_terms[] = 'bl.name LIKE ?';
        $params[] = '%' . $title_prefix . '%';
    }

    // 著者名でも検索（指定がある場合）
    if ($normalized_author && mb_strlen($normalized_author) >= 2) {
        $search_terms[] = "COALESCE(bl.author, br.author, '') LIKE ?";
        $params[] = '%' . $author . '%';
    }

    $where_search = implode(' OR ', $search_terms);

    $sql = "SELECT bl.book_id, bl.name, bl.status, bl.rating,
            COALESCE(bl.author, br.author, '') as author
            FROM b_book_list bl
            LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
            WHERE bl.user_id = ?
            AND ({$where_search})
            LIMIT 100";

    $results = $g_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);

    if (DB::isError($results) || empty($results)) {
        return null;
    }

    // PHP側で類似度計算してベストマッチを探す
    $best_match = null;
    $best_score = 0.0;
    $threshold = 0.6; // マッチ閾値

    foreach ($results as $book) {
        $book_normalized = normalizeBookTitle($book['name']);
        $score = titleSimilarity($normalized_title, $book_normalized);

        // 著者名が一致する場合はスコアをブースト
        if ($normalized_author && !empty($book['author'])) {
            $book_author_normalized = normalizeAuthorName($book['author']);
            $author_sim = titleSimilarity($normalized_author, $book_author_normalized);
            if ($author_sim > 0.7) {
                // 著者一致でタイトルスコアをブースト
                $score = min(1.0, $score + 0.15);
            }
        }

        if ($score > $best_score) {
            $best_score = $score;
            $best_match = $book;
        }
    }

    if ($best_match && $best_score >= $threshold) {
        return [
            'book_id' => (int)$best_match['book_id'],
            'name' => $best_match['name'],
            'author' => $best_match['author'],
            'rating' => $best_match['rating'] ? (int)$best_match['rating'] : null,
            'status' => (int)$best_match['status'],
            'similarity' => round($best_score, 3)
        ];
    }

    return null;
}
