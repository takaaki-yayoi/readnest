-- ブックリンク: 読んでいる本(source)の中で紹介されていた本(target)を紐づける
-- source は利用者の本棚の本(b_book_list.book_id)、target は ASIN で識別する。
-- target を本棚に入れた場合は target_book_id にその book_id を保持する。
CREATE TABLE IF NOT EXISTS book_links (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT             NOT NULL,
    source_book_id INT             NOT NULL,
    target_asin    VARCHAR(64)     NOT NULL,
    target_book_id INT             DEFAULT NULL,
    page           INT             DEFAULT NULL,
    memo           VARCHAR(500)    DEFAULT NULL,
    created_at     DATETIME        NOT NULL,
    updated_at     DATETIME        NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_user_source_target (user_id, source_book_id, target_asin),
    KEY idx_source_book (source_book_id),
    KEY idx_target_asin (target_asin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
