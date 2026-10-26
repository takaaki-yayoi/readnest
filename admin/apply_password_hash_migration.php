<?php
/**
 * パスワード・自動ログインキーの保存形式の移行（library/auth_helpers.php 参照）
 *
 * 1. b_user.password / autologin の列幅を広げる（SHA1時代の40文字のままだと新形式が入らない）
 * 2. 平文保存の自動ログインキー（40桁）を SHA-256 に置き換える（Cookieはそのまま使える）
 * 3. ログインしていない利用者のSHA1パスワードを「sha1$」+ password_hash(SHA1値) に包む
 *    （パスワード自体は分からないので、SHA1値をさらにbcryptで保護する。次回ログイン時に通常の形式になる）
 *
 * 何度実行しても安全。3は時間がかかるため1回の実行で約20秒分ずつ処理する。
 */

declare(strict_types=1);

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

if (!isAdmin()) {
    die('管理者としてログインしてください');
}

const LEGACY_SHA1_REGEXP = '^[0-9a-fA-F]{40}$';

function migrationColumns(): array {
    global $g_db;
    $rows = $g_db->getAll(
        "SELECT COLUMN_NAME, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'b_user' AND COLUMN_NAME IN ('password', 'autologin')",
        [],
        DB_FETCHMODE_ASSOC
    );
    $columns = [];
    if (!DB::isError($rows)) {
        foreach ($rows as $row) {
            $columns[$row['COLUMN_NAME']] = $row;
        }
    }
    return $columns;
}

function migrationCount(string $sql): int {
    global $g_db;
    $n = $g_db->getOne($sql);
    return DB::isError($n) ? -1 : (int)$n;
}

function migrationStatus(): array {
    return [
        'columns' => migrationColumns(),
        'legacy_passwords' => migrationCount("SELECT COUNT(*) FROM b_user WHERE password REGEXP '" . LEGACY_SHA1_REGEXP . "'"),
        'wrapped_passwords' => migrationCount("SELECT COUNT(*) FROM b_user WHERE password LIKE 'sha1$%'"),
        'modern_passwords' => migrationCount('SELECT COUNT(*) FROM b_user WHERE password LIKE \'$2%\' OR password LIKE \'$argon2%\''),
        'legacy_autologin' => migrationCount("SELECT COUNT(*) FROM b_user WHERE autologin REGEXP '" . LEGACY_SHA1_REGEXP . "'"),
        'hashed_autologin' => migrationCount("SELECT COUNT(*) FROM b_user WHERE autologin REGEXP '^[0-9a-f]{64}$'"),
    ];
}

/**
 * 列を VARCHAR(255) に広げる（NULL可否と既定値は維持する）
 */
function widenColumn(array $column): string {
    global $g_db;
    if ((int)$column['CHARACTER_MAXIMUM_LENGTH'] >= 255) {
        return "{$column['COLUMN_NAME']}: 既に {$column['COLUMN_TYPE']}";
    }
    $sql = "ALTER TABLE b_user MODIFY `{$column['COLUMN_NAME']}` VARCHAR(255)";
    $sql .= ($column['IS_NULLABLE'] === 'YES') ? ' NULL' : ' NOT NULL';
    if ($column['COLUMN_DEFAULT'] !== null) {
        $sql .= ' DEFAULT ' . $g_db->quote((string)$column['COLUMN_DEFAULT']);
    }
    $result = $g_db->query($sql);
    if (DB::isError($result)) {
        return "{$column['COLUMN_NAME']}: 失敗 " . $result->getMessage();
    }
    return "{$column['COLUMN_NAME']}: {$column['COLUMN_TYPE']} → VARCHAR(255)";
}

$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') {
    @set_time_limit(60);

    // 1. 列幅
    foreach (migrationColumns() as $column) {
        $messages[] = widenColumn($column);
    }
    $columns = migrationColumns();
    $password_ready = (int)($columns['password']['CHARACTER_MAXIMUM_LENGTH'] ?? 0) >= 255;
    $autologin_ready = (int)($columns['autologin']['CHARACTER_MAXIMUM_LENGTH'] ?? 0) >= 64;

    // 2. 自動ログインキー（Cookie側は元のキーのままなので、DB側をそのハッシュにすれば照合できる）
    if ($autologin_ready) {
        $result = $g_db->query("UPDATE b_user SET autologin = SHA2(LOWER(autologin), 256) WHERE autologin REGEXP '" . LEGACY_SHA1_REGEXP . "'");
        $messages[] = DB::isError($result)
            ? '自動ログインキー: 失敗 ' . $result->getMessage()
            : '自動ログインキー: 平文のキーをハッシュに置き換えました';
    } else {
        $messages[] = '自動ログインキー: 列幅が足りないため未実施';
    }

    // 3. SHA1パスワードを包む（約20秒分ずつ）
    if ($password_ready) {
        $deadline = microtime(true) + 20;
        $wrapped = 0;
        while (microtime(true) < $deadline) {
            $rows = $g_db->getAll(
                "SELECT user_id, password FROM b_user WHERE password REGEXP '" . LEGACY_SHA1_REGEXP . "' LIMIT 100",
                [],
                DB_FETCHMODE_ASSOC
            );
            if (DB::isError($rows) || empty($rows)) {
                break;
            }
            foreach ($rows as $row) {
                $new = 'sha1$' . password_hash(strtolower($row['password']), PASSWORD_DEFAULT);
                // 同時に本人がログインして新形式に置き換わっていた場合は上書きしない
                $g_db->query('UPDATE b_user SET password = ? WHERE user_id = ? AND password = ?', [$new, $row['user_id'], $row['password']]);
                $wrapped++;
                if (microtime(true) >= $deadline) {
                    break;
                }
            }
        }
        $messages[] = "SHA1パスワード: {$wrapped}件を保護しました";
    } else {
        $messages[] = 'SHA1パスワード: 列幅が足りないため未実施';
    }
}

$status = migrationStatus();
?>
<h1>パスワード・自動ログインキーの移行</h1>
<?php include('layout/utility_menu.php'); ?>

<?php if ($messages): ?>
<h2>実行結果</h2>
<ul>
    <?php foreach ($messages as $m): ?>
    <li><?php echo htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<h2>現在の状態</h2>
<table border="1" cellpadding="6" style="border-collapse: collapse;">
    <tr><th>項目</th><th>値</th><th>目標</th></tr>
    <?php foreach (['password', 'autologin'] as $name): $c = $status['columns'][$name] ?? null; ?>
    <tr>
        <td>b_user.<?php echo $name; ?> の型</td>
        <td><?php echo $c ? htmlspecialchars($c['COLUMN_TYPE'] . ($c['IS_NULLABLE'] === 'YES' ? ' NULL' : ' NOT NULL'), ENT_QUOTES, 'UTF-8') : '取得できません'; ?></td>
        <td>varchar(255)</td>
    </tr>
    <?php endforeach; ?>
    <tr><td>SHA1のままのパスワード</td><td><?php echo $status['legacy_passwords']; ?></td><td>0</td></tr>
    <tr><td>SHA1を包んで保護したパスワード</td><td><?php echo $status['wrapped_passwords']; ?></td><td>（次回ログインで通常形式へ）</td></tr>
    <tr><td>通常形式（password_hash）のパスワード</td><td><?php echo $status['modern_passwords']; ?></td><td></td></tr>
    <tr><td>平文の自動ログインキー</td><td><?php echo $status['legacy_autologin']; ?></td><td>0</td></tr>
    <tr><td>ハッシュ化した自動ログインキー</td><td><?php echo $status['hashed_autologin']; ?></td><td></td></tr>
</table>

<form method="post" style="margin-top: 20px;">
    <input type="hidden" name="action" value="apply">
    <button type="submit">移行を実行する<?php echo $status['legacy_passwords'] > 0 ? '（残りがあれば続きから）' : ''; ?></button>
</form>
<p>何度実行しても安全です。SHA1のままのパスワードが0になるまで繰り返してください（1回で約20秒分を処理します）。</p>

<div style="margin-top: 20px;">
    <a href="/admin/index.php">← Back to Admin</a>
</div>
