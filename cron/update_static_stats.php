<?php
/**
 * 静的統計情報更新スクリプト
 * index_simple.php用の統計データを定期的に更新
 * cronで1時間ごとに実行することを推奨
 */

declare(strict_types=1);

// エラーレポーティング
error_reporting(E_ALL);
ini_set('display_errors', '1');

// スクリプトのベースディレクトリを設定
$base_dir = dirname(dirname(__FILE__));
chdir($base_dir);

// 設定ファイル読み込み
require_once('config.php');
require_once('library/database.php');

// データベース接続
$db = DB_Connect();
if (DB::isError($db)) {
    error_log("Failed to connect to database: " . $db->getMessage());
    exit(1);
}

try {
    // 統計情報を取得
    $stats_sql = "
        SELECT 
            (SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL) as total_users,
            (SELECT COUNT(DISTINCT book_id) FROM b_book_list) as total_books,
            (SELECT COUNT(*) FROM b_book_list WHERE memo != '' AND memo IS NOT NULL) as total_reviews,
            (SELECT SUM(CASE WHEN status IN (?, ?) THEN total_page ELSE current_page END) 
             FROM b_book_list WHERE current_page > 0) as total_pages_read
    ";
    
    $stats_result = $db->getRow($stats_sql, array(READING_FINISH, READ_BEFORE), DB_FETCHMODE_ASSOC);
    
    if(DB::isError($stats_result)) {
        error_log("Failed to get statistics: " . $stats_result->getMessage());
        exit(1);
    }
    
    $total_users = intval($stats_result['total_users'] ?? 0);
    $total_books = intval($stats_result['total_books'] ?? 0);
    $total_reviews = intval($stats_result['total_reviews'] ?? 0);
    $total_pages_read = intval($stats_result['total_pages_read'] ?? 0);
    
    // 統計ファイルの内容を生成
    $stats_content = "<?php
/**
 * 静的統計情報
 * 最終更新: " . date('Y-m-d H:i:s') . "
 */

// 静的な統計情報（定期的に更新）
\$static_stats = [
    'total_users' => " . $total_users . ",
    'total_books' => " . $total_books . ",
    'total_reviews' => " . $total_reviews . ",
    'total_pages_read' => " . $total_pages_read . ",
    'last_updated' => " . time() . "
];
";
    
    // ファイルに書き込み
    $stats_file = $base_dir . '/data/static_stats.php';
    
    // dataディレクトリが存在しない場合は作成
    if (!is_dir($base_dir . '/data')) {
        mkdir($base_dir . '/data', 0755);
    }
    
    if (file_put_contents($stats_file, $stats_content) === false) {
        error_log("Failed to write statistics file");
        exit(1);
    }
    
    echo "Statistics updated successfully:\n";
    echo "- Total users: " . number_format($total_users) . "\n";
    echo "- Total books: " . number_format($total_books) . "\n";
    echo "- Total reviews: " . number_format($total_reviews) . "\n";
    echo "- Total pages read: " . number_format($total_pages_read) . "\n";
    echo "- Updated at: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    error_log("Error updating statistics: " . $e->getMessage());
    exit(1);
}

// データベース接続を閉じる
$db->disconnect();
?>