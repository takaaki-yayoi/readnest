<?php
/**
 * X (Twitter) 投稿ログ・コスト管理ヘルパー（フェーズ1）
 *
 * - x_post_log テーブルへの記録（コスト集計・重複調査・将来のリトライ照合の土台）
 * - URLを付与するかどうかの判定（URLは従量課金が13倍のため）
 * - 月間予算の超過判定・推定残高の算出
 * - 管理者アラート（1日1回に抑制）
 *
 * DBアクセスはグローバル $g_db（database.php の PDO ラッパ）を利用する。
 * 単価・予算・対象イベント等の設定は config/x_api.php を参照。
 */

require_once dirname(__DIR__) . '/config/x_api.php';

/**
 * 指定イベントに書籍URLを付与すべきか。
 * URLは X_URL_EVENTS ビットマスクで許可されたイベントのみ付与する。
 *
 * @param int $event_bit X_EVENT_* のビット値
 * @return bool
 */
function xShouldAttachUrl($event_bit) {
    if (!defined('X_URL_EVENTS') || !$event_bit) {
        return false;
    }
    return (X_URL_EVENTS & $event_bit) !== 0;
}

/**
 * x_api.php の $event_type を X_EVENT_* のビット値へ変換する。
 * $event_type は READING_NOW/READING_FINISH（読書ステータス定数）または
 * 'review' / 'progress'（文字列）が渡される。
 *
 * @param mixed $event_type
 * @return int 対応する X_EVENT_* ビット（不明なら0）
 */
function xEventBitFor($event_type) {
    if ($event_type === 'review') {
        return defined('X_EVENT_REVIEW') ? X_EVENT_REVIEW : 8;
    }
    if ($event_type === 'progress') {
        return defined('X_EVENT_READING_PROGRESS') ? X_EVENT_READING_PROGRESS : 16;
    }
    if (defined('READING_NOW') && $event_type === READING_NOW) {
        return defined('X_EVENT_READING_NOW') ? X_EVENT_READING_NOW : 2;
    }
    if (defined('READING_FINISH') && $event_type === READING_FINISH) {
        return defined('X_EVENT_READING_FINISH') ? X_EVENT_READING_FINISH : 4;
    }
    return 0;
}

/**
 * $event_type を x_post_log 用の短い識別子に変換する。
 *
 * @param mixed $event_type
 * @return string
 */
function xEventLabel($event_type) {
    if ($event_type === 'review') {
        return 'review';
    }
    if ($event_type === 'progress') {
        return 'progress';
    }
    if (defined('READING_NOW') && $event_type === READING_NOW) {
        return 'start';
    }
    if (defined('READING_FINISH') && $event_type === READING_FINISH) {
        return 'finish';
    }
    return 'other';
}

/**
 * 1投稿あたりの推定コスト（USD）。
 *
 * @param bool $has_url URLを含むか
 * @return float
 */
function xEstimatePostCost($has_url) {
    if ($has_url) {
        return defined('X_COST_URL_USD') ? (float)X_COST_URL_USD : 0.20;
    }
    return defined('X_COST_TEXT_USD') ? (float)X_COST_TEXT_USD : 0.015;
}

/**
 * postTweet() の戻り値が成功かどうか。
 * XOAuthV2/XApiClient はどちらも ['success' => bool, ...] を返す。
 * （配列は常に truthy なので、必ず 'success' を評価すること）
 *
 * @param mixed $result
 * @return bool
 */
function xPostSucceeded($result) {
    return is_array($result) && !empty($result['success']);
}

/**
 * postTweet() の戻り値から tweet_id を取り出す。
 * 成功時レスポンスは ['success'=>true, 'data'=>['data'=>['id'=>...]]]。
 *
 * @param mixed $result
 * @return string|null
 */
function xExtractTweetId($result) {
    if (is_array($result) && isset($result['data']['data']['id'])) {
        return (string)$result['data']['data']['id'];
    }
    return null;
}

/**
 * 投稿レコードを pending で作成し、ログIDを返す。
 *
 * @return int|null 挿入した行のID（失敗時 null）
 */
function xPostLogInsert($user_id, $book_id, $event_type, $event_bit, $target, $has_url) {
    global $g_db;
    if (!isset($g_db) || !$g_db) {
        return null;
    }
    $sql = "INSERT INTO x_post_log
                (user_id, book_id, event_type, event_bit, target, has_url, cost_usd, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
    $params = [
        (int)$user_id,
        $book_id !== null ? (int)$book_id : null,
        xEventLabel($event_type),
        (int)$event_bit,
        $target,
        $has_url ? 1 : 0,
        xEstimatePostCost($has_url),
        date('Y-m-d H:i:s'),
    ];
    $res = $g_db->query($sql, $params);
    if (DB::isError($res)) {
        error_log('[X post_log] insert failed: ' . $res->getMessage());
        return null;
    }
    return $g_db->lastInsertId();
}

/**
 * 投稿レコードの結果を更新する。
 */
function xPostLogUpdate($log_id, $status, $tweet_id = null, $http_code = null, $error_message = null) {
    global $g_db;
    if (!$log_id || !isset($g_db) || !$g_db) {
        return false;
    }
    $sql = "UPDATE x_post_log SET status = ?, tweet_id = ?, http_code = ?, error_message = ? WHERE id = ?";
    $params = [
        $status,
        $tweet_id,
        $http_code !== null ? (int)$http_code : null,
        $error_message !== null ? mb_substr((string)$error_message, 0, 250) : null,
        (int)$log_id,
    ];
    $res = $g_db->query($sql, $params);
    if (DB::isError($res)) {
        error_log('[X post_log] update failed: ' . $res->getMessage());
        return false;
    }
    return true;
}

/**
 * 指定日時以降の成功投稿コスト合計（USD）。
 *
 * @param string $since 'Y-m-d H:i:s'
 * @return float
 */
function xSpendSince($since) {
    global $g_db;
    if (!isset($g_db) || !$g_db) {
        return 0.0;
    }
    $sql = "SELECT SUM(cost_usd) FROM x_post_log WHERE status = 'success' AND created_at >= ?";
    $v = $g_db->getOne($sql, [$since]);
    if (DB::isError($v)) {
        error_log('[X post_log] spend query failed: ' . $v->getMessage());
        return 0.0;
    }
    return (float)$v;
}

/**
 * 当月（暦月）の推定投稿コスト（USD）。
 *
 * @return float
 */
function xMonthlySpendUsd() {
    return xSpendSince(date('Y-m-01 00:00:00'));
}

/**
 * 月間予算を超過しているか。予算未設定(<=0)なら常に false。
 *
 * @return bool
 */
function xBudgetExceeded() {
    if (!defined('X_MONTHLY_BUDGET_USD') || X_MONTHLY_BUDGET_USD <= 0) {
        return false;
    }
    return xMonthlySpendUsd() >= X_MONTHLY_BUDGET_USD;
}

/**
 * プリペイド残高の推定（USD）= チャージ総額 - チャージ以降の成功コスト合計。
 *
 * @return float
 */
function xEstimatedBalanceUsd() {
    $budget = defined('X_PREPAID_BUDGET_USD') ? (float)X_PREPAID_BUDGET_USD : 0.0;
    $since = defined('X_PREPAID_SINCE') ? X_PREPAID_SINCE . ' 00:00:00' : '1970-01-01 00:00:00';
    return $budget - xSpendSince($since);
}

/**
 * 管理者にアラートメールを送る（同一 $key につき1日1回に抑制）。
 *
 * @param string $key   抑制用キー（'budget','balance' 等）
 * @param string $subject
 * @param string $body
 * @return bool 送信したら true
 */
function xAlertAdmin($key, $subject, $body) {
    $flag_dir = dirname(__DIR__) . '/logs';
    $safe_key = preg_replace('/[^a-z0-9_]/i', '', (string)$key);
    $flag = $flag_dir . '/x_alert_' . $safe_key . '_' . date('Ymd') . '.flag';

    // 本日すでに同一キーで送信済みならスキップ
    if (is_dir($flag_dir) && file_exists($flag)) {
        return false;
    }

    mb_language('Japanese');
    mb_internal_encoding('UTF-8');
    $headers  = "From: ReadNest Monitor <noreply@readnest.jp>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $to = defined('X_ALERT_EMAIL') ? X_ALERT_EMAIL : 'admin@readnest.jp';

    $sent = @mb_send_mail($to, '[ReadNest][X] ' . $subject, $body, $headers);

    if (is_dir($flag_dir)) {
        @file_put_contents($flag, date('Y-m-d H:i:s') . ' ' . $subject . "\n");
    }
    return $sent;
}
