-- 旧ブックリンク(b_book_relation)を book_links へ移行する
-- create_book_links.sql の実行後に流す。b_book_relation は削除しない。
--
-- 事前確認:
--   SHOW TABLES LIKE 'b_book_relation';
--   SHOW CREATE TABLE b_book_relation;   -- create_date が INT(UNIX時刻)か DATETIME かを確認
--   SELECT COUNT(*) FROM b_book_relation;
--
-- 移行対象外になるもの:
--   - 紹介元・紹介先のどちらかが本棚から削除済み
--   - 紹介元が本人の本でない（旧実装は所有者チェックをしていなかった）
--   - 紹介先に ASIN が無い（手入力で登録した本など）
-- 同じ組み合わせが複数ある場合は INSERT IGNORE で最初の1件だけ残る。

INSERT IGNORE INTO book_links
    (user_id, source_book_id, target_asin, target_book_id, page, memo, created_at, updated_at)
SELECT
    r.user_id,
    r.from_book,
    tb.amazon_id,
    tb.book_id,
    NULL,
    NULLIF(r.memo, ''),
    IF(CAST(r.create_date AS CHAR) REGEXP '^[0-9]+$', FROM_UNIXTIME(r.create_date), r.create_date),
    IF(CAST(r.create_date AS CHAR) REGEXP '^[0-9]+$', FROM_UNIXTIME(r.create_date), r.create_date)
FROM b_book_relation r
INNER JOIN b_book_list sb ON sb.book_id = r.from_book AND sb.user_id = r.user_id
INNER JOIN b_book_list tb ON tb.book_id = r.to_book AND tb.user_id = r.user_id
WHERE tb.amazon_id IS NOT NULL
  AND tb.amazon_id <> ''
  AND tb.amazon_id <> sb.amazon_id
ORDER BY r.relation_id;

-- 移行結果の確認
-- SELECT COUNT(*) FROM book_links;
