<?php
/**
 * 書籍の説明文を一括更新するバッチ処理
 * cronで定期実行することを想定
 */

// 設定を読み込み
require_once(dirname(dirname(__FILE__)) . '/modern_config.php');
require_once(dirname(dirname(__FILE__)) . '/library/book_description_updater.php');

// コマンドラインから実行されているか確認
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

echo "=== Book Description Update Batch ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// 更新する本の数（API制限を考慮）
$limit = isset($argv[1]) ? (int)$argv[1] : 50;
echo "Updating up to {$limit} books...\n\n";

try {
    $updater = new BookDescriptionUpdater();
    
    // 説明文がない本を更新
    $results = $updater->updateMissingDescriptions($limit);
    
    echo "Results:\n";
    echo "- Total processed: {$results['total']}\n";
    echo "- Success: {$results['success']}\n";
    echo "- Failed: {$results['failed']}\n";
    
    if (!empty($results['details'])) {
        echo "\nDetails:\n";
        foreach ($results['details'] as $detail) {
            $status = $detail['success'] ? '✓' : '✗';
            echo "  [{$status}] {$detail['asin']}\n";
        }
    }
    
    // 統計情報を表示
    $stats_sql = "
        SELECT 
            COUNT(*) as total_books,
            COUNT(description) as books_with_description,
            ROUND(COUNT(description) * 100.0 / COUNT(*), 2) as coverage_percentage
        FROM b_book_repository
    ";
    
    $stats = $g_db->getRow($stats_sql, [], DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($stats)) {
        echo "\n=== Database Statistics ===\n";
        echo "Total books: {$stats['total_books']}\n";
        echo "Books with description: {$stats['books_with_description']}\n";
        echo "Coverage: {$stats['coverage_percentage']}%\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
?>