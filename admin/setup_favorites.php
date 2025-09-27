<?php
/**
 * お気に入り機能のセットアップスクリプト
 * ブラウザでアクセスして実行してください
 */

require_once('../modern_config.php');

// 管理者権限チェック（必要に応じて調整）
$login_flag = checkLogin();
if (!$login_flag) {
    die('ログインが必要です');
}

echo "<h1>お気に入り機能セットアップ</h1>";

// テーブル作成SQL
$sqls = [
    "CREATE TABLE IF NOT EXISTS b_book_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        is_public TINYINT(1) DEFAULT 1 COMMENT '1: 公開, 0: 非公開',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_book (user_id, book_id),
        KEY idx_user_id (user_id),
        KEY idx_book_id (book_id),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ユーザーのお気に入り本'",
    
    "CREATE INDEX IF NOT EXISTS idx_favorites_public ON b_book_favorites(is_public, created_at)"
];

$success = true;
foreach ($sqls as $sql) {
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    $result = $g_db->query($sql);
    
    if (DB::isError($result)) {
        echo "<p style='color: red;'>エラー: " . $result->getMessage() . "</p>";
        $success = false;
    } else {
        echo "<p style='color: green;'>✓ 成功</p>";
    }
}

if ($success) {
    echo "<h2 style='color: green;'>セットアップが完了しました！</h2>";
    echo "<p><a href='/bookshelf.php'>本棚に戻る</a></p>";
} else {
    echo "<h2 style='color: red;'>一部のセットアップに失敗しました</h2>";
}
?>