<?php
/**
 * 作家クラウド（モダンテンプレート版）
 */

require_once('modern_config.php');
require_once('library/sakka_cloud_generator.php');

// SEO用メタ情報
$d_site_title = '作家クラウド - ReadNest';
$g_meta_description = '人気の作家をクラウド形式で表示。読書傾向や人気作家を一目で確認できます。';
$g_meta_keyword = '作家,著者,作家クラウド,人気作家,読書傾向';

// 作家クラウドデータを取得
$generator = new SakkaCloudGenerator();

// 自動更新は無効化（cronで処理するため）
// if ($generator->needsUpdate()) {
//     $generator->generate();
//     $generator->generateSimple();
//     $generator->clearCache();
// }

// データ取得（キャッシュがあればそれを使用）
$authors = $generator->getPopularAuthors(150); // 上位150名の作家を取得

// データがない場合は初回生成を実行
if (empty($authors)) {
    // テーブルが存在するか確認
    $table_exists = $g_db->getOne("SHOW TABLES LIKE 'b_author_stats_cache'");
    
    if (!$table_exists) {
        // テーブルがない場合は作成
        $create_sql = "
            CREATE TABLE IF NOT EXISTS b_author_stats_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                author VARCHAR(255) NOT NULL,
                book_count INT NOT NULL DEFAULT 0,
                reader_count INT NOT NULL DEFAULT 0,
                review_count INT NOT NULL DEFAULT 0,
                average_rating DECIMAL(3,2) DEFAULT NULL,
                last_read_date DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_author_unique (author),
                INDEX idx_book_count (book_count),
                INDEX idx_reader_count (reader_count),
                INDEX idx_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $g_db->query($create_sql);
        
        // b_sakka_cloudテーブルも作成
        $create_sakka_sql = "
            CREATE TABLE IF NOT EXISTS b_sakka_cloud (
                id INT AUTO_INCREMENT PRIMARY KEY,
                author VARCHAR(255) NOT NULL,
                author_count INT NOT NULL DEFAULT 0,
                updated DATETIME NOT NULL,
                INDEX idx_author (author),
                INDEX idx_count (author_count),
                INDEX idx_updated (updated)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $g_db->query($create_sakka_sql);
    }
    
    // 初回データ生成（同期的に実行）
    $generator->generate();
    $generator->generateSimple(); // 旧版互換用
    $authors = $generator->getPopularAuthors(150);
}

// 統計情報を計算
$total_authors = count($authors);
$total_books = 0;
$total_readers = 0;

if (!empty($authors)) {
    $total_books = array_sum(array_column($authors, 'book_count'));
    $total_readers = array_sum(array_column($authors, 'reader_count'));
}

// テンプレートを読み込み
include(getTemplatePath('t_sakka_cloud_modern.php'));
?>