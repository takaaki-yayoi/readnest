#!/usr/bin/env php
<?php
/**
 * 読書進捗更新時にupdate_dateが更新されていない本の日付を修正するスクリプト
 * 
 * 対象：b_reading_logテーブルに進捗記録があるのに、
 *      b_book_listのupdate_dateが古いままになっている本
 */

// 設定ファイルを読み込み
require_once(dirname(__DIR__) . '/modern_config.php');

echo "========================================\n";
echo "ReadNest update_date 修正スクリプト\n";
echo "========================================\n\n";

// データベース接続確認
if (!$g_db) {
    die("エラー: データベースに接続できません\n");
}

// 1. 問題のあるレコードを検出
echo "1. 問題のあるレコードを検出中...\n";

$detect_sql = "
    SELECT 
        bl.book_id,
        bl.user_id,
        bl.name as book_name,
        bl.update_date,
        bl.current_page,
        bl.status,
        MAX(rl.date) as last_log_date,
        COUNT(rl.id) as log_count
    FROM b_book_list bl
    INNER JOIN b_reading_log rl ON bl.book_id = rl.book_id
    WHERE 
        rl.event_type IN ('reading_start', 'reading_progress', 'reading_finish')
        AND (
            bl.update_date IS NULL 
            OR DATE(bl.update_date) < DATE(rl.date)
        )
    GROUP BY bl.book_id
    ORDER BY bl.user_id, bl.book_id
";

$result = $g_db->query($detect_sql);
if (DB::isError($result)) {
    die("エラー: " . $result->getMessage() . "\n");
}

$affected_books = [];
while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
    $affected_books[] = $row;
}

$total_count = count($affected_books);

if ($total_count === 0) {
    echo "✅ 修正が必要なレコードはありません。\n";
    exit(0);
}

echo "⚠️  修正が必要なレコード: {$total_count}件\n\n";

// 2. 詳細を表示
echo "2. 影響を受ける本の詳細:\n";
echo str_repeat("-", 80) . "\n";
printf("%-8s %-8s %-30s %-10s %-10s\n", "book_id", "user_id", "書名", "現在の更新日", "最新ログ日");
echo str_repeat("-", 80) . "\n";

foreach ($affected_books as $book) {
    printf(
        "%-8d %-8d %-30s %-10s %-10s\n",
        $book['book_id'],
        $book['user_id'],
        mb_substr($book['book_name'], 0, 20) . (mb_strlen($book['book_name']) > 20 ? '...' : ''),
        $book['update_date'] ? date('Y-m-d', strtotime($book['update_date'])) : 'NULL',
        date('Y-m-d', strtotime($book['last_log_date']))
    );
}

echo str_repeat("-", 80) . "\n\n";

// 3. 修正の確認
echo "3. 修正を実行しますか？\n";
echo "   これらの本のupdate_dateを最新の読書ログの日付に更新します。\n";
echo "   続行する場合は 'yes' と入力してください: ";

$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));
fclose($handle);

if (strtolower($input) !== 'yes') {
    echo "\n❌ 修正をキャンセルしました。\n";
    exit(0);
}

// 4. 修正を実行
echo "\n4. 修正を実行中...\n";

$success_count = 0;
$error_count = 0;

foreach ($affected_books as $book) {
    // 各本の最新のイベント日時を取得して更新
    $update_sql = "
        UPDATE b_book_list bl
        SET bl.update_date = (
            SELECT MAX(rl.date)
            FROM b_reading_log rl
            WHERE rl.book_id = ?
            AND rl.event_type IN ('reading_start', 'reading_progress', 'reading_finish')
        )
        WHERE bl.book_id = ?
    ";
    
    $update_result = $g_db->query($update_sql, [$book['book_id'], $book['book_id']]);
    
    if (DB::isError($update_result)) {
        echo "   ❌ book_id {$book['book_id']}: エラー - " . $update_result->getMessage() . "\n";
        $error_count++;
    } else {
        echo "   ✅ book_id {$book['book_id']}: 更新完了 (update_date = {$book['last_log_date']})\n";
        $success_count++;
    }
}

// 5. 結果サマリー
echo "\n========================================\n";
echo "修正完了\n";
echo "========================================\n";
echo "✅ 成功: {$success_count}件\n";
if ($error_count > 0) {
    echo "❌ エラー: {$error_count}件\n";
}
echo "\n";

// 6. 追加の推奨事項
echo "📝 推奨事項:\n";
echo "   1. お気に入りページや本棚ページで更新日が正しく表示されているか確認してください\n";
echo "   2. 今後は読書進捗の更新時に自動的にupdate_dateが更新されます\n";
echo "\n";

exit($error_count > 0 ? 1 : 0);
?>