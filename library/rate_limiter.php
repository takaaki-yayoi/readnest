<?php
/**
 * レート制限ライブラリ
 * API呼び出しやフォーム送信の頻度を制限
 */

declare(strict_types=1);

// 二重読み込み防止
if (defined('RATE_LIMITER_LOADED')) {
    return;
}
define('RATE_LIMITER_LOADED', true);

/**
 * レート制限チェック
 * @param string $identifier ユーザーIDやIPアドレス
 * @param string $action アクション名（例: 'book_search', 'book_add'）
 * @param int $max_attempts 制限時間内の最大試行回数
 * @param int $window_seconds 制限時間（秒）
 * @return bool 制限内ならtrue、制限超過ならfalse
 */
function checkRateLimit(string $identifier, string $action, int $max_attempts = 10, int $window_seconds = 60): bool {
    global $g_memcache;
    
    // Memcacheが利用できない場合はセッションベースで簡易実装
    if (!isset($g_memcache) || !$g_memcache) {
        return checkRateLimitSession($identifier, $action, $max_attempts, $window_seconds);
    }
    
    $key = "rate_limit:{$action}:{$identifier}";
    $current_time = time();
    
    // 現在の試行回数を取得
    $attempts = $g_memcache->get($key);
    
    if ($attempts === false) {
        // 初回アクセス
        $g_memcache->set($key, 1, 0, $window_seconds);
        return true;
    }
    
    if ($attempts >= $max_attempts) {
        // 制限超過
        return false;
    }
    
    // カウントを増やす
    $g_memcache->increment($key, 1);
    return true;
}

/**
 * セッションベースのレート制限（フォールバック）
 */
function checkRateLimitSession(string $identifier, string $action, int $max_attempts, int $window_seconds): bool {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $key = "rate_limit_{$action}_{$identifier}";
    $current_time = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'window_start' => $current_time
        ];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    // ウィンドウが期限切れの場合はリセット
    if ($current_time - $data['window_start'] > $window_seconds) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'window_start' => $current_time
        ];
        return true;
    }
    
    // 制限チェック
    if ($data['attempts'] >= $max_attempts) {
        return false;
    }
    
    // カウントを増やす
    $_SESSION[$key]['attempts']++;
    return true;
}

/**
 * 残り時間を取得（秒）
 */
function getRateLimitRemainingTime(string $identifier, string $action, int $window_seconds = 60): int {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $key = "rate_limit_{$action}_{$identifier}";
    
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    
    $data = $_SESSION[$key];
    $elapsed = time() - $data['window_start'];
    $remaining = $window_seconds - $elapsed;
    
    return max(0, $remaining);
}

/**
 * IPアドレスベースのレート制限
 */
function checkRateLimitByIP(string $action, int $max_attempts = 10, int $window_seconds = 60): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return checkRateLimit($ip, $action, $max_attempts, $window_seconds);
}

/**
 * ユーザーIDベースのレート制限
 */
function checkRateLimitByUser(int $user_id, string $action, int $max_attempts = 10, int $window_seconds = 60): bool {
    return checkRateLimit("user_{$user_id}", $action, $max_attempts, $window_seconds);
}

/**
 * 組み合わせレート制限（IPとユーザーID両方）
 */
function checkRateLimitCombined(int $user_id, string $action, int $max_attempts = 10, int $window_seconds = 60): bool {
    $ip_check = checkRateLimitByIP($action, $max_attempts, $window_seconds);
    $user_check = checkRateLimitByUser($user_id, $action, $max_attempts, $window_seconds);
    
    return $ip_check && $user_check;
}