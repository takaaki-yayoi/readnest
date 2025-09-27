<?php
/**
 * タグ検索サマリーテーブル更新
 * 人気タグの検索結果を事前計算してb_tag_search_summaryテーブルに保存
 */

declare(strict_types=1);

// CLIからの実行のみ許可
if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

$start_time = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Starting tag search summary update...\n";

try {
    $g_db = DB_Connect();
    if (!$g_db || DB::isError($g_db)) {
        throw new Exception("Database connection failed");
    }
    
    // 1. サマリーテーブルが存在しない場合は作成
    $create_sql = "
        CREATE TABLE IF NOT EXISTS b_tag_search_summary (
            tag_name VARCHAR(255) NOT NULL,
            book_id INT NOT NULL,
            title VARCHAR(255),
            author VARCHAR(255),
            image_url TEXT,
            isbn VARCHAR(20),
            avg_rating DECIMAL(3,2),
            reader_count INT,
            last_update DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (tag_name, book_id),
            INDEX idx_tag_reader (tag_name, reader_count DESC),
            INDEX idx_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $result = $g_db->query($create_sql);
    if (DB::isError($result)) {
        throw new Exception("Failed to create summary table: " . $result->getMessage());
    }
    
    // 2. 更新対象のタグを取得（上位100タグ）
    $popular_tags_sql = "
        SELECT tag_name, COUNT(DISTINCT user_id) as user_count
        FROM b_book_tags bt
        INNER JOIN b_user u ON bt.user_id = u.user_id
        WHERE u.diary_policy = 1 
        AND u.status = 1
        AND tag_name IS NOT NULL 
        AND tag_name != ''
        GROUP BY tag_name
        ORDER BY user_count DESC
        LIMIT 100
    ";
    
    $popular_tags = $g_db->getAll($popular_tags_sql, [], DB_FETCHMODE_ASSOC);
    if (DB::isError($popular_tags)) {
        throw new Exception("Failed to get popular tags: " . $popular_tags->getMessage());
    }
    
    echo "Found " . count($popular_tags) . " popular tags to process\n";
    
    // 3. 各タグのサマリーを更新
    $processed = 0;
    $errors = 0;
    
    foreach ($popular_tags as $tag_info) {
        $tag = $tag_info['tag_name'];
        echo "Processing tag: $tag (users: {$tag_info['user_count']})... ";
        
        // まず既存のレコードを削除
        $delete_sql = "DELETE FROM b_tag_search_summary WHERE tag_name = ?";
        $g_db->query($delete_sql, [$tag]);
        
        // 新しいサマリーを挿入
        $insert_sql = "
            INSERT INTO b_tag_search_summary 
            (tag_name, book_id, title, author, image_url, isbn, avg_rating, reader_count, last_update)
            SELECT 
                ?, 
                bl.book_id,
                bl.name,
                bl.author,
                bl.image_url,
                bl.isbn,
                bl.rating,
                COUNT(DISTINCT bt.user_id) as reader_count,
                MAX(bl.update_date) as last_update
            FROM b_book_tags bt
            INNER JOIN b_book_list bl ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
            INNER JOIN b_user u ON bt.user_id = u.user_id
            WHERE bt.tag_name = ?
            AND bl.name IS NOT NULL 
            AND bl.name != ''
            AND u.diary_policy = 1
            AND u.status = 1
            GROUP BY bl.book_id, bl.name, bl.author, bl.image_url, bl.isbn, bl.rating
            ORDER BY reader_count DESC, MAX(bl.update_date) DESC
            LIMIT 200
        ";
        
        $result = $g_db->query($insert_sql, [$tag, $tag]);
        
        if (DB::isError($result)) {
            echo "ERROR: " . $result->getMessage() . "\n";
            $errors++;
        } else {
            $affected = $g_db->affectedRows();
            echo "OK ($affected books)\n";
            $processed++;
        }
        
        // 負荷軽減のため少し待つ
        usleep(100000); // 0.1秒
    }
    
    // 4. 古いエントリを削除（30日以上更新されていないもの）
    $cleanup_sql = "
        DELETE FROM b_tag_search_summary 
        WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    $g_db->query($cleanup_sql);
    
    // 5. 統計情報を表示
    $total_records = $g_db->getOne("SELECT COUNT(*) FROM b_tag_search_summary");
    $unique_tags = $g_db->getOne("SELECT COUNT(DISTINCT tag_name) FROM b_tag_search_summary");
    
    $elapsed = round(microtime(true) - $start_time, 2);
    
    echo "\n";
    echo "=== Summary ===\n";
    echo "Processed: $processed tags\n";
    echo "Errors: $errors\n";
    echo "Total records: $total_records\n";
    echo "Unique tags: $unique_tags\n";
    echo "Elapsed time: {$elapsed}s\n";
    echo "[" . date('Y-m-d H:i:s') . "] Tag search summary update completed.\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>