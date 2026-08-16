<?php
/**
 * さらに最適化されたデータベース関数
 * パフォーマンスボトルネックを解消
 */

/**
 * 人気の本を高速に取得（最適化版）
 * 元のクエリと互換性を保ちつつ最適化
 */
function getPopularBooksOptimized($limit = 9) {
    global $g_db;
    
    // 元のクエリに近い形で最適化
    // diary_policyとstatusのチェックを含む
    $sql = "
        SELECT 
            MIN(bl.book_id) as book_id,
            bl.name as title,
            bl.image_url,
            MIN(bl.amazon_id) as amazon_id,
            COUNT(DISTINCT bl.user_id) as bookmark_count
        FROM b_book_list bl
        INNER JOIN b_user u ON bl.user_id = u.user_id
        WHERE u.diary_policy = 1 
            AND u.status = 1
            AND bl.name IS NOT NULL 
            AND bl.name != ''
            AND bl.image_url IS NOT NULL
            AND bl.image_url != ''
            AND bl.image_url NOT LIKE '%noimage%'
            AND bl.image_url NOT LIKE '%no-image%'
        GROUP BY bl.name, bl.image_url
        HAVING COUNT(DISTINCT bl.user_id) > 0
        ORDER BY bookmark_count DESC, MAX(bl.update_date) DESC
        LIMIT ?
    ";
    
    $result = $g_db->getAll(
        $sql, 
        array(intval($limit)), 
        DB_FETCHMODE_ASSOC
    );
    
    if(DB::isError($result)) {
        return array();
    }
    
    return $result;
}

/**
 * 新着レビューを超高速で取得
 * サブクエリを使用してさらに最適化
 */
function getNewReviewsUltraOptimized($limit = 5) {
    global $g_db;
    
    // 最新のレビューを持つbook_list IDを先に取得
    $sql = "
        SELECT 
            bl.book_id,
            bl.name,
            bl.image_url,
            bl.memo,
            bl.rating,
            bl.memo_updated,
            bl.update_date,
            bl.user_id,
            u.nickname,
            u.photo
        FROM (
            SELECT book_list_id
            FROM b_book_list
            WHERE memo IS NOT NULL AND memo != ''
            ORDER BY update_date DESC
            LIMIT ?
        ) AS latest
        INNER JOIN b_book_list bl ON bl.book_list_id = latest.book_list_id
        INNER JOIN b_user u ON bl.user_id = u.user_id
        WHERE u.diary_policy = 1
    ";
    
    $result = $g_db->getAll($sql, array(intval($limit) * 2), DB_FETCHMODE_ASSOC); // 非公開ユーザーを考慮して2倍取得
    
    if(DB::isError($result)) {
        return array();
    }
    
    // 結果を整形（最大$limit件まで）
    $reviews = array();
    $count = 0;
    foreach ($result as $row) {
        if ($count >= $limit) break;
        
        $reviews[] = array(
            'book_id' => $row['book_id'],
            'name' => $row['name'],
            'image_url' => $row['image_url'],
            'memo' => $row['memo'],
            'rating' => intval($row['rating']),
            'memo_updated' => $row['memo_updated'],
            'update_date' => $row['update_date'],
            'user_id' => $row['user_id'],
            'nickname' => $row['nickname'],
            'user_photo_url' => !empty($row['photo']) 
                ? '/user_photo/' . $row['user_id'] . '/' . $row['photo']
                : '/img/no-image-user.png'
        );
        $count++;
    }
    
    return $reviews;
}

/**
 * 統計情報を事前計算して保存
 * cronで定期的に実行することを想定
 */
function preCalculatePopularBooks() {
    global $g_db;
    
    // 集計テーブルが存在しない場合は作成
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS b_popular_books_cache (
            book_id INT PRIMARY KEY,
            title VARCHAR(255),
            image_url VARCHAR(500),
            amazon_id VARCHAR(100),
            user_count INT,
            last_update DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_count (user_count DESC),
            INDEX idx_last_update (last_update DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $create_result = $g_db->query($create_table_sql);
    if (DB::isError($create_result)) {
        error_log('[preCalculatePopularBooks] CREATE TABLE 失敗: ' . $create_result->getMessage());
        return false;
    }

    // CREATE TABLE IF NOT EXISTS は既存テーブルのスキーマを更新しない。
    // 古いスキーマで作られた環境では後から CREATE 文に足したカラム
    // （amazon_id など）が存在せず、INSERT だけが毎回失敗して
    // テーブルが空のままになっていた。不足カラムをここで補う。
    $required_columns = array(
        'title'       => 'VARCHAR(255)',
        'image_url'   => 'VARCHAR(500)',
        'amazon_id'   => 'VARCHAR(100)',
        'user_count'  => 'INT',
        'last_update' => 'DATETIME',
    );

    $existing_columns = $g_db->getAll("SHOW COLUMNS FROM b_popular_books_cache", array(), DB_FETCHMODE_ASSOC);
    if (DB::isError($existing_columns)) {
        error_log('[preCalculatePopularBooks] SHOW COLUMNS 失敗: ' . $existing_columns->getMessage());
        return false;
    }

    $existing_names = array_column($existing_columns, 'Field');
    foreach ($required_columns as $column => $definition) {
        if (in_array($column, $existing_names, true)) {
            continue;
        }
        $alter_result = $g_db->query("ALTER TABLE b_popular_books_cache ADD COLUMN {$column} {$definition}");
        if (DB::isError($alter_result)) {
            error_log("[preCalculatePopularBooks] ALTER TABLE ADD {$column} 失敗: " . $alter_result->getMessage());
            return false;
        }
        error_log("[preCalculatePopularBooks] 不足していたカラム {$column} を追加しました");
    }

    // user_count の索引も無ければ張る（推薦候補プールが ORDER BY user_count DESC で引く）
    $indexes = $g_db->getAll("SHOW INDEX FROM b_popular_books_cache WHERE Key_name = 'idx_user_count'", array(), DB_FETCHMODE_ASSOC);
    if (!DB::isError($indexes) && empty($indexes)) {
        $index_result = $g_db->query("ALTER TABLE b_popular_books_cache ADD INDEX idx_user_count (user_count DESC)");
        if (DB::isError($index_result)) {
            // 索引が無くても集計自体は成立するので処理は続行する
            error_log('[preCalculatePopularBooks] idx_user_count 作成失敗: ' . $index_result->getMessage());
        } else {
            error_log('[preCalculatePopularBooks] idx_user_count を追加しました');
        }
    }

    // 既存のデータをクリア
    $truncate_result = $g_db->query("TRUNCATE TABLE b_popular_books_cache");
    if (DB::isError($truncate_result)) {
        error_log('[preCalculatePopularBooks] TRUNCATE 失敗: ' . $truncate_result->getMessage());
        return false;
    }

    // 集計データを挿入
    // 元のクエリと同じロジックを使用（statusチェック追加）
    $insert_sql = "
        INSERT INTO b_popular_books_cache (book_id, title, image_url, amazon_id, user_count, last_update)
        SELECT 
            MIN(bl.book_id) as book_id,
            bl.name as title,
            bl.image_url,
            MIN(bl.amazon_id) as amazon_id,
            COUNT(DISTINCT bl.user_id) as user_count,
            MAX(bl.update_date) as last_update
        FROM b_book_list bl
        INNER JOIN b_user u ON bl.user_id = u.user_id
        WHERE u.diary_policy = 1 
            AND u.status = 1
            AND bl.name IS NOT NULL 
            AND bl.name != ''
            AND bl.image_url IS NOT NULL
            AND bl.image_url != ''
            AND bl.image_url NOT LIKE '%noimage%'
            AND bl.image_url NOT LIKE '%no-image%'
        GROUP BY bl.name, bl.image_url
        HAVING COUNT(DISTINCT bl.user_id) > 0
    ";
    
    $result = $g_db->query($insert_sql);

    if (DB::isError($result)) {
        // 以前はエラー内容を捨てて false を返すだけだったため、
        // 「Failed to update popular books cache」としか分からず原因を追えなかった。
        error_log('[preCalculatePopularBooks] INSERT 失敗: ' . $result->getMessage());
        return false;
    }

    return true;
}

/**
 * 直近30日間でみんなが読んでいる本を取得
 * 直近30日に読書活動（読了イベント・ステータス更新）がある本を人気順で返す
 */
function getPopularBooksRecent($limit = 9) {
    global $g_db;

    $since = date('Y-m-d H:i:s', strtotime('-30 days'));

    $sql = "
        SELECT
            MIN(bl.book_id) as book_id,
            bl.name as title,
            bl.image_url,
            MIN(bl.amazon_id) as amazon_id,
            COUNT(DISTINCT bl.user_id) as bookmark_count
        FROM b_book_list bl
        INNER JOIN b_user u ON bl.user_id = u.user_id
        WHERE u.diary_policy = 1
            AND u.status = 1
            AND bl.name IS NOT NULL
            AND bl.name != ''
            AND bl.image_url IS NOT NULL
            AND bl.image_url != ''
            AND bl.image_url NOT LIKE '%noimage%'
            AND bl.image_url NOT LIKE '%no-image%'
            AND bl.update_date >= ?
        GROUP BY bl.name, bl.image_url
        HAVING COUNT(DISTINCT bl.user_id) > 0
        ORDER BY bookmark_count DESC, MAX(bl.update_date) DESC
        LIMIT ?
    ";

    $result = $g_db->getAll(
        $sql,
        array($since, intval($limit)),
        DB_FETCHMODE_ASSOC
    );

    if (DB::isError($result)) {
        error_log("getPopularBooksRecent failed: " . $result->getMessage());
        return array();
    }

    return $result;
}

/**
 * 事前計算された人気の本を取得
 */
function getPopularBooksFromCache($limit = 9) {
    global $g_db;
    
    $sql = "
        SELECT 
            book_id,
            title,
            image_url,
            amazon_id,
            user_count as bookmark_count
        FROM b_popular_books_cache
        ORDER BY user_count DESC, last_update DESC
        LIMIT ?
    ";
    
    $result = $g_db->getAll($sql, array(intval($limit)), DB_FETCHMODE_ASSOC);
    
    if(DB::isError($result)) {
        // フォールバック：通常のクエリを使用
        return getPopularBooksOptimized($limit);
    }
    
    return $result;
}
?>