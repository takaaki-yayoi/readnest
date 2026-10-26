<?php
/**
 * パスワードと自動ログインキーの保存・照合（library/database.php から読み込む）
 *
 * パスワード:
 *   以前はソルトなしのSHA1（40桁の16進）で保存していた。新規保存は password_hash()。
 *   既存のSHA1は次回ログイン時に自動で置き換える。ログインしない利用者の分は
 *   admin/apply_password_hash_migration.php が「sha1$」+ password_hash(SHA1値) に包んで保護する。
 *
 * 自動ログインキー:
 *   以前は予測しやすい乱数で作ったキーをDBに平文保存していた。
 *   新規発行は random_bytes で作り、DBには SHA-256 ハッシュだけを保存する。
 *   既存の40桁のキー（平文保存）は、使われたときにハッシュへ置き換える。
 *
 * どちらも b_user の列幅が足りない（SHA1時代の40文字）うちに新形式を書き込むと
 * 途中で切れてログインできなくなるため、列幅を確認し、足りない間は従来の形式で保存する。
 */

const READNEST_LEGACY_SHA1_PREFIX = 'sha1$';

/**
 * b_user の列幅（文字数）を返す。取得できない場合は0
 */
function authColumnLength(string $column): int {
    global $g_db;
    static $lengths = null;

    if ($lengths === null) {
        $lengths = [];
        $rows = $g_db->getAll(
            "SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'b_user' AND COLUMN_NAME IN ('password', 'autologin')",
            [],
            DB_FETCHMODE_ASSOC
        );
        if (!DB::isError($rows)) {
            foreach ($rows as $row) {
                $lengths[$row['COLUMN_NAME']] = (int)$row['CHARACTER_MAXIMUM_LENGTH'];
            }
        }
    }
    return $lengths[$column] ?? 0;
}

/**
 * password 列に password_hash() の結果（将来のアルゴリズム変更も見込んで255文字）を保存できるか
 */
function passwordColumnReady(): bool {
    return authColumnLength('password') >= 255;
}

/**
 * autologin 列にキーのSHA-256（64文字）を保存できるか
 */
function autologinColumnReady(): bool {
    return authColumnLength('autologin') >= 64;
}

function isLegacySha1Hash(?string $stored): bool {
    return is_string($stored) && preg_match('/^[0-9a-f]{40}$/i', $stored) === 1;
}

/**
 * 保存用のパスワードハッシュを作る
 */
function hashUserPassword(string $password): string {
    if (!passwordColumnReady()) {
        return sha1($password); // 列幅が足りない間は従来形式（移行スクリプト実行後に新形式になる）
    }
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 入力されたパスワードが保存済みハッシュと一致するか
 */
function verifyUserPassword(string $password, ?string $stored): bool {
    if ($password === '' || $stored === null || $stored === '') {
        return false;
    }
    if (isLegacySha1Hash($stored)) {
        return hash_equals(strtolower($stored), sha1($password));
    }
    if (strpos($stored, READNEST_LEGACY_SHA1_PREFIX) === 0) {
        return password_verify(sha1($password), substr($stored, strlen(READNEST_LEGACY_SHA1_PREFIX)));
    }
    return password_verify($password, $stored);
}

/**
 * 照合に成功したパスワードが旧形式（または古いコスト）なら新形式で保存し直す
 */
function upgradeUserPasswordIfNeeded(int $user_id, string $password, string $stored): void {
    global $g_db;

    if (!passwordColumnReady()) {
        return;
    }
    $needs = isLegacySha1Hash($stored)
        || strpos($stored, READNEST_LEGACY_SHA1_PREFIX) === 0
        || password_needs_rehash($stored, PASSWORD_DEFAULT);
    if (!$needs) {
        return;
    }
    $result = $g_db->query('UPDATE b_user SET password = ? WHERE user_id = ?', [password_hash($password, PASSWORD_DEFAULT), $user_id]);
    if (DB::isError($result)) {
        error_log('Password rehash failed for user ' . $user_id . ': ' . $result->getMessage());
    }
}

/**
 * 新しい自動ログインキーを発行してDBに保存し、Cookieに入れる値を返す
 */
function issueAutoLoginKey(int $user_id): string {
    global $g_db;

    if (autologinColumnReady()) {
        $key = bin2hex(random_bytes(32));
        $stored = hash('sha256', $key);
    } else {
        // 列幅が足りない間は従来どおり40文字のキーを平文で保存（乱数だけ安全なものにする）
        $key = bin2hex(random_bytes(20));
        $stored = $key;
    }

    $result = $g_db->query('UPDATE b_user SET autologin = ? WHERE user_id = ?', [$stored, $user_id]);
    if (DB::isError($result)) {
        trigger_error($result->getMessage());
    }
    return $key;
}

/**
 * Cookieの自動ログインキーからユーザーIDを引く
 */
function findUserByAutoLoginKey(?string $key) {
    global $g_db;

    if (!is_string($key) || !preg_match('/^[0-9a-f]{40}$|^[0-9a-f]{64}$/', $key)) {
        return null;
    }

    $user_id = $g_db->getOne('SELECT user_id FROM b_user WHERE autologin = ?', [hash('sha256', $key)]);
    if (DB::isError($user_id)) {
        trigger_error($user_id->getMessage());
        return null;
    }
    if ($user_id) {
        return $user_id;
    }

    // 旧形式（40桁のキーを平文保存）。64桁のCookieは平文照合しない
    // （DBが漏れたときにハッシュ値そのものをCookieにして使われるのを防ぐ）
    if (strlen($key) !== 40) {
        return null;
    }
    $user_id = $g_db->getOne('SELECT user_id FROM b_user WHERE autologin = ?', [$key]);
    if (DB::isError($user_id) || !$user_id) {
        return null;
    }
    if (autologinColumnReady()) {
        // 同じCookieのまま使えるよう、DB側だけハッシュに置き換える
        $g_db->query('UPDATE b_user SET autologin = ? WHERE user_id = ? AND autologin = ?', [hash('sha256', $key), $user_id, $key]);
    }
    return $user_id;
}

/**
 * ユーザーの自動ログインキーを無効にする（ログアウト・パスワード変更時）
 */
function revokeAutoLoginKey(int $user_id): void {
    global $g_db;

    $result = $g_db->query("UPDATE b_user SET autologin = '' WHERE user_id = ?", [$user_id]);
    if (DB::isError($result)) {
        error_log('Failed to revoke autologin key for user ' . $user_id . ': ' . $result->getMessage());
    }
}
