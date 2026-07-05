<?php
/**
 * Apply x_post_log Migration Script
 * x_post_log テーブル（X投稿ログ）を作成する。
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

echo "<h1>x_post_log Migration</h1>";
include('layout/utility_menu.php');

// PDO接続を取得
$pdo = null;
if ($g_db instanceof DB_PDO) {
    $reflection = new ReflectionClass($g_db);
    $property = $reflection->getProperty('pdo');
    $property->setAccessible(true);
    $pdo = $property->getValue($g_db);
} elseif ($g_db instanceof PDO) {
    $pdo = $g_db;
} else {
    die("PDO接続を取得できません");
}

echo "<h2>Checking current table...</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'x_post_log'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ x_post_log テーブルは既に存在します。</p>";
        echo "<p>Migration has already been applied.</p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h2>Applying migration...</h2>";

$migration_sql = file_get_contents(dirname(__DIR__) . '/sql/create_x_post_log.sql');
if (!$migration_sql) {
    die("Failed to read migration file");
}

$statements = array_filter(array_map('trim', explode(';', $migration_sql)));

$success = true;
$executed = 0;
foreach ($statements as $statement) {
    // 各文からコメント行（-- で始まる行）を除去してから実行する。
    // （先頭にコメントがあると文全体がスキップされる不具合を防ぐ）
    $lines = array_filter(explode("\n", $statement), function ($line) {
        return strpos(trim($line), '--') !== 0;
    });
    $statement = trim(implode("\n", $lines));
    if ($statement === '') {
        continue;
    }
    try {
        echo "<p>Executing: <code>" . htmlspecialchars(substr($statement, 0, 100)) . "...</code></p>";
        $pdo->exec($statement);
        $executed++;
        echo "<p style='color: green;'>✓ Success</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        $success = false;
        break;
    }
}

// 実際にテーブルが作成されたかを検証する（成功表示が実態と食い違わないように）
$table_exists = false;
try {
    $chk = $pdo->query("SHOW TABLES LIKE 'x_post_log'");
    $table_exists = ($chk && $chk->rowCount() > 0);
} catch (PDOException $e) {
    $table_exists = false;
}

if ($success && $executed > 0 && $table_exists) {
    echo "<h2 style='color: green;'>Migration completed successfully!</h2>";
    echo "<p>x_post_log テーブルを作成しました。</p>";
} else {
    echo "<h2 style='color: red;'>Migration failed!</h2>";
    if ($executed === 0) {
        echo "<p>実行されたSQL文がありませんでした。SQLファイルを確認してください。</p>";
    } elseif (!$table_exists) {
        echo "<p>SQLは実行されましたが x_post_log テーブルが見つかりません。</p>";
    }
}
?>

<div style="margin-top: 20px;">
    <a href="/admin/index.php">← Back to Admin</a>
</div>
