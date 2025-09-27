<?php
/**
 * お気に入りテーブルにsort_order列を追加するマイグレーション
 */

require_once('modern_config.php');

echo "Starting migration: Add sort_order to b_book_favorites\n";

// 1. sort_order列が既に存在するかチェック
$check_column_sql = "SHOW COLUMNS FROM b_book_favorites LIKE 'sort_order'";
$result = $g_db->query($check_column_sql);

if (!DB::isError($result) && $result->numRows() > 0) {
    echo "Column 'sort_order' already exists. Skipping column creation.\n";
} else {
    // 2. sort_order列を追加
    $add_column_sql = "ALTER TABLE b_book_favorites ADD COLUMN sort_order INT DEFAULT 0 AFTER is_public";
    $result = $g_db->query($add_column_sql);
    
    if (DB::isError($result)) {
        die("Error adding sort_order column: " . $result->getMessage() . "\n");
    }
    echo "Added sort_order column successfully.\n";
    
    // 3. インデックスを追加
    $add_index_sql = "ALTER TABLE b_book_favorites ADD INDEX idx_user_sort (user_id, sort_order)";
    $result = $g_db->query($add_index_sql);
    
    if (DB::isError($result)) {
        echo "Warning: Could not add index (may already exist): " . $result->getMessage() . "\n";
    } else {
        echo "Added index idx_user_sort successfully.\n";
    }
}

// 4. 既存データのsort_orderを初期化
echo "Initializing sort_order for existing data...\n";

// ユーザーごとにsort_orderを設定
$users_sql = "SELECT DISTINCT user_id FROM b_book_favorites";
$users = $g_db->getAll($users_sql, null, DB_FETCHMODE_ASSOC);

if (!DB::isError($users)) {
    foreach ($users as $user) {
        $user_id = $user['user_id'];
        
        // ユーザーのお気に入りを取得（created_at順）
        $favorites_sql = sprintf(
            "SELECT id FROM b_book_favorites WHERE user_id = %d ORDER BY created_at DESC",
            (int)$user_id
        );
        $favorites = $g_db->getAll($favorites_sql, null, DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($favorites)) {
            $sort_order = 1;
            foreach ($favorites as $favorite) {
                $update_sql = sprintf(
                    "UPDATE b_book_favorites SET sort_order = %d WHERE id = %d",
                    $sort_order,
                    (int)$favorite['id']
                );
                $g_db->query($update_sql);
                $sort_order++;
            }
            echo "Updated sort_order for user_id: $user_id (total: " . count($favorites) . " items)\n";
        }
    }
}

echo "Migration completed successfully!\n";
?>