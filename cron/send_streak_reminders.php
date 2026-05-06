<?php
/**
 * 読書ストリーク維持リマインダー送信
 *
 * 条件:
 *   - streak_reminder_enabled = 1
 *   - push購読 (b_push_subscriptions) が1件以上存在
 *   - ストリーク継続中（昨日まで連続記録あり、または今日既に記録あり）
 *   - 本日まだ記録なし（記録済みなら送らない）
 *   - 本日未送信（重複防止）
 *
 * crontab（21:00 JST に実行）:
 * 0 21 * * * cd /path/to/readnest && php cron/send_streak_reminders.php >> /var/log/readnest/streak_reminders.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

date_default_timezone_set('Asia/Tokyo');

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/library/database.php';
require_once dirname(__DIR__) . '/library/push_helper.php';

$g_db = DB_Connect();
if (!$g_db || DB::isError($g_db)) {
    error_log('[StreakReminder] Database connection failed');
    exit(1);
}

$start = microtime(true);
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

error_log('[StreakReminder] Starting at ' . date('Y-m-d H:i:s'));

// 対象ユーザー: streak_reminder_enabled かつ push購読あり かつ 本日未送信
$users_sql = "
    SELECT DISTINCT u.user_id
    FROM b_user u
    INNER JOIN b_push_subscriptions ps ON ps.user_id = u.user_id
    LEFT JOIN b_streak_reminder_log sl ON sl.user_id = u.user_id AND sl.sent_date = ?
    WHERE u.streak_reminder_enabled = 1
      AND u.status = 1
      AND sl.user_id IS NULL
";
$candidates = $g_db->getAll($users_sql, [$today], DB_FETCHMODE_ASSOC);
if (DB::isError($candidates) || empty($candidates)) {
    error_log('[StreakReminder] No candidates');
    exit(0);
}

$reading_events = '(' . READING_NOW . ',' . READING_FINISH . ',4)'; // 4 = 進捗更新

$checked = 0;
$skipped_already_recorded = 0;
$skipped_no_streak = 0;
$sent = 0;
$send_failed = 0;

foreach ($candidates as $row) {
    $user_id = (int)$row['user_id'];
    $checked++;

    // 本日既に記録あり → 送らない
    $today_count = $g_db->getOne(
        "SELECT COUNT(*) FROM b_book_event
         WHERE user_id = ? AND DATE(event_date) = ? AND event IN " . $reading_events,
        [$user_id, $today]
    );
    if (!DB::isError($today_count) && (int)$today_count > 0) {
        $skipped_already_recorded++;
        continue;
    }

    // 昨日の記録あり = ストリーク継続中
    $yesterday_count = $g_db->getOne(
        "SELECT COUNT(*) FROM b_book_event
         WHERE user_id = ? AND DATE(event_date) = ? AND event IN " . $reading_events,
        [$user_id, $yesterday]
    );
    if (DB::isError($yesterday_count) || (int)$yesterday_count === 0) {
        $skipped_no_streak++;
        continue;
    }

    // ストリーク日数を概算（最大30日まで遡って表示用）
    $streak = 1;
    $check_date = $yesterday;
    for ($i = 0; $i < 30; $i++) {
        $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
        $cnt = $g_db->getOne(
            "SELECT COUNT(*) FROM b_book_event
             WHERE user_id = ? AND DATE(event_date) = ? AND event IN " . $reading_events,
            [$user_id, $check_date]
        );
        if (DB::isError($cnt) || (int)$cnt === 0) break;
        $streak++;
    }

    $payload = [
        'title' => '📚 今日の記録、まだですね',
        'body' => '連続' . $streak . '日達成中。1ページでも記録すれば継続できます。',
        'url' => '/bookshelf.php?status=' . READING_NOW,
        'tag' => 'streak-reminder',
    ];

    $success_count = sendPushToUser($user_id, $payload);
    if ($success_count > 0) {
        $sent++;
        $g_db->query(
            "INSERT IGNORE INTO b_streak_reminder_log (user_id, sent_date) VALUES (?, ?)",
            [$user_id, $today]
        );
    } else {
        $send_failed++;
    }
}

$elapsed = round(microtime(true) - $start, 2);
error_log(sprintf(
    '[StreakReminder] Done in %ss: checked=%d sent=%d skipped_recorded=%d skipped_no_streak=%d failed=%d',
    $elapsed, $checked, $sent, $skipped_already_recorded, $skipped_no_streak, $send_failed
));
