<?php
/**
 * Apply X OAuth Migration Script
 * Run this to add X OAuth fields to the b_user table
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    die('管理者としてログインしてください');
}

echo "<h1>X OAuth Migration</h1>";
include('layout/utility_menu.php');

// PDO接続を取得
$pdo = null;
if ($g_db instanceof DB_PDO) {
    // リフレクションを使ってプライベートプロパティにアクセス
    $reflection = new ReflectionClass($g_db);
    $property = $reflection->getProperty('pdo');
    $property->setAccessible(true);
    $pdo = $property->getValue($g_db);
} elseif ($g_db instanceof PDO) {
    $pdo = $g_db;
} else {
    die("PDO接続を取得できません");
}

echo "<h2>Checking current b_user table structure...</h2>";

// Check if columns already exist
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM b_user LIKE 'x_oauth_token'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ X OAuth columns already exist in b_user table.</p>";
        echo "<p>Migration has already been applied.</p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error checking table structure: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Applying migration...</h2>";

// Read migration SQL
$migration_sql = file_get_contents(dirname(__DIR__) . '/sql/add_x_oauth_to_user.sql');
if (!$migration_sql) {
    die("Failed to read migration file");
}

// Split by semicolon to execute multiple statements
$statements = array_filter(array_map('trim', explode(';', $migration_sql)));

$success = true;
foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    try {
        echo "<p>Executing: <code>" . htmlspecialchars(substr($statement, 0, 100)) . "...</code></p>";
        $pdo->exec($statement);
        echo "<p style='color: green;'>✓ Success</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
        $success = false;
        break;
    }
}

if ($success) {
    echo "<h2 style='color: green;'>Migration completed successfully!</h2>";
    echo "<p>The following columns have been added to b_user table:</p>";
    echo "<ul>";
    echo "<li>x_oauth_token - X OAuth access token</li>";
    echo "<li>x_oauth_token_secret - X OAuth access token secret</li>";
    echo "<li>x_screen_name - X account screen name</li>";
    echo "<li>x_user_id - X user ID</li>";
    echo "<li>x_connected_at - When X account was connected</li>";
    echo "<li>x_post_enabled - Whether to post to user X account</li>";
    echo "<li>x_post_events - Bitmask for which events to post</li>";
    echo "</ul>";
} else {
    echo "<h2 style='color: red;'>Migration failed!</h2>";
    echo "<p>Please check the error messages above and fix any issues before retrying.</p>";
}
?>

<div style="margin-top: 20px;">
    <a href="/admin/index.php">← Back to Admin</a>
</div>