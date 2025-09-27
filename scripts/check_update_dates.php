#!/usr/bin/env php
<?php
/**
 * update_dateの整合性をチェックするスクリプト
 * 読書ログと本の更新日の不整合を検出します
 */

// 設定ファイルを読み込み
require_once(dirname(__DIR__) . '/modern_config.php');

echo "========================================\n";
echo "ReadNest update_date 整合性チェック\n";
echo "========================================\n\n";

// データベース接続確認
if (!$g_db) {
    die("エラー: データベースに接続できません\n");
}

// 1. 統計情報を取得
echo "1. 統計情報:\n";

// 全本の数
$total_books_sql = "SELECT COUNT(*) FROM b_book_list";
$total_books = $g_db->getOne($total_books_sql);
echo "   総本数: {$total_books}冊\n";

// 読書ログがある本の数
$books_with_logs_sql = "SELECT COUNT(DISTINCT book_id) FROM b_reading_log";
$books_with_logs = $g_db->getOne($books_with_logs_sql);
echo "   読書ログがある本: {$books_with_logs}冊\n";

// update_dateがNULLの本
$null_update_sql = "SELECT COUNT(*) FROM b_book_list WHERE update_date IS NULL";
$null_updates = $g_db->getOne($null_update_sql);
echo "   update_dateがNULLの本: {$null_updates}冊\n\n";

// 2. 不整合チェック
echo "2. 不整合チェック:\n";

// 読書ログより古いupdate_dateを持つ本
$inconsistent_sql = "
    SELECT 
        bl.book_id,
        bl.user_id,
        bl.name,
        bl.update_date,
        MAX(rl.date) as last_log_date,
        DATEDIFF(MAX(rl.date), bl.update_date) as days_diff
    FROM b_book_list bl
    INNER JOIN b_reading_log rl ON bl.book_id = rl.book_id
    WHERE 
        rl.event_type IN ('reading_start', 'reading_progress', 'reading_finish')
        AND (
            bl.update_date IS NULL 
            OR DATE(bl.update_date) < DATE(rl.date)
        )
    GROUP BY bl.book_id
    HAVING days_diff > 0 OR bl.update_date IS NULL
    ORDER BY days_diff DESC
    LIMIT 20
";

$result = $g_db->query($inconsistent_sql);
if (DB::isError($result)) {
    die("エラー: " . $result->getMessage() . "\n");
}

$inconsistent_books = [];
while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
    $inconsistent_books[] = $row;
}

if (count($inconsistent_books) === 0) {
    echo "   ✅ 不整合は見つかりませんでした。\n";
} else {
    echo "   ⚠️  不整合が見つかりました（最大20件表示）:\n\n";
    
    printf("   %-8s %-8s %-30s %-10s %-10s %-8s\n", 
           "book_id", "user_id", "書名", "update_date", "最新ログ", "差(日)");
    echo "   " . str_repeat("-", 84) . "\n";
    
    foreach ($inconsistent_books as $book) {
        printf("   %-8d %-8d %-30s %-10s %-10s %-8s\n",
            $book['book_id'],
            $book['user_id'],
            mb_substr($book['name'], 0, 20) . (mb_strlen($book['name']) > 20 ? '...' : ''),
            $book['update_date'] ? date('Y-m-d', strtotime($book['update_date'])) : 'NULL',
            date('Y-m-d', strtotime($book['last_log_date'])),
            $book['days_diff'] !== null ? $book['days_diff'] : 'N/A'
        );
    }
}

// 3. 不整合の総数を取得
$count_inconsistent_sql = "
    SELECT COUNT(DISTINCT bl.book_id) as count
    FROM b_book_list bl
    INNER JOIN b_reading_log rl ON bl.book_id = rl.book_id
    WHERE 
        rl.event_type IN ('reading_start', 'reading_progress', 'reading_finish')
        AND (
            bl.update_date IS NULL 
            OR DATE(bl.update_date) < DATE(rl.date)
        )
";

$inconsistent_count = $g_db->getOne($count_inconsistent_sql);

echo "\n3. サマリー:\n";
echo "   " . str_repeat("=", 50) . "\n";
echo "   不整合のある本の総数: {$inconsistent_count}冊\n";
echo "   " . str_repeat("=", 50) . "\n";

if ($inconsistent_count > 0) {
    echo "\n💡 修正するには以下のコマンドを実行してください:\n";
    echo "   php " . dirname(__DIR__) . "/scripts/fix_update_dates.php\n";
}

echo "\n";
exit(0);
?>