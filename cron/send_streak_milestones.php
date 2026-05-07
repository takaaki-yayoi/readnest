<?php
/**
 * ストリーク節目祝い通知
 *
 * 7日 / 14日 / 30日 / 50日 / 100日 / 200日 / 365日 / 500日 / 1000日 達成時に
 * その日の夜にお祝いpush通知を送る。記録忘れリマインダーと違い、ポジティブな通知。
 *
 * 条件:
 *   - その日（今日）に読書記録あり
 *   - 当日終了時点でのストリーク日数が節目（MILESTONES）に該当
 *   - 同節目を本人が以前に達成済みでない（b_streak_milestone_log）
 *   - opted-in（streak_reminder_enabled = 1）
 *
 * crontab（22:00 JST に実行。ほとんどの記録が終わっている時刻）:
 * 0 22 * * * cd /path/to/readnest && /usr/bin/php8.2 cron/send_streak_milestones.php >> /var/log/readnest/streak_milestones.log 2>&1
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
    error_log('[StreakMilestone] Database connection failed');
    exit(1);
}

// 同節目を1ユーザーにつき一度だけ送るためのログテーブル
$g_db->query("
    CREATE TABLE IF NOT EXISTS b_streak_milestone_log (
        user_id INT NOT NULL,
        milestone INT NOT NULL,
        achieved_date DATE NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, milestone)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
");

const MILESTONES = [7, 14, 30, 50, 100, 200, 365, 500, 1000];

$start = microtime(true);
$today = date('Y-m-d');
$reading_events = '(' . READING_NOW . ',' . READING_FINISH . ',4)'; // 4 = 進捗更新

error_log('[StreakMilestone] Starting at ' . date('Y-m-d H:i:s'));

// 今日記録があり、push購読中、opted-in なユーザー
$users_sql = "
    SELECT DISTINCT u.user_id
    FROM b_user u
    INNER JOIN b_book_event be ON be.user_id = u.user_id
        AND DATE(be.event_date) = ?
        AND be.event IN " . $reading_events . "
    INNER JOIN b_push_subscriptions ps ON ps.user_id = u.user_id
    WHERE u.streak_reminder_enabled = 1
      AND u.status = 1
";
$candidates = $g_db->getAll($users_sql, [$today], DB_FETCHMODE_ASSOC);
if (DB::isError($candidates) || empty($candidates)) {
    error_log('[StreakMilestone] No candidates');
    exit(0);
}

$checked = 0;
$sent = 0;
$no_milestone = 0;
$already_celebrated = 0;
$send_failed = 0;

foreach ($candidates as $row) {
    $user_id = (int)$row['user_id'];
    $checked++;

    // 今日からさかのぼって連続記録日数を計算（最大1100日）
    $streak = 0;
    $check_date = $today;
    for ($i = 0; $i < 1100; $i++) {
        $cnt = $g_db->getOne(
            "SELECT COUNT(*) FROM b_book_event
             WHERE user_id = ? AND DATE(event_date) = ? AND event IN " . $reading_events,
            [$user_id, $check_date]
        );
        if (DB::isError($cnt) || (int)$cnt === 0) break;
        $streak++;
        $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
    }

    // 節目に該当しなければスキップ
    if (!in_array($streak, MILESTONES, true)) {
        $no_milestone++;
        continue;
    }

    // 既に同節目を祝っていればスキップ
    $already = $g_db->getOne(
        "SELECT COUNT(*) FROM b_streak_milestone_log WHERE user_id = ? AND milestone = ?",
        [$user_id, $streak]
    );
    if (!DB::isError($already) && (int)$already > 0) {
        $already_celebrated++;
        continue;
    }

    // 節目別のメッセージ
    $emoji = $streak >= 365 ? '💎' : ($streak >= 100 ? '🏆' : ($streak >= 30 ? '🔥' : '✨'));
    $title = "{$emoji} {$streak}日連続達成！";
    $body = $streak >= 365
        ? "1年以上の継続、本当にすごい。読書がすっかり日課になりましたね"
        : ($streak >= 100
            ? "100日の壁を超えました。確かな習慣になっています"
            : ($streak >= 30
                ? "1ヶ月連続。ここからが習慣の本番です"
                : "勢いに乗ってきました。この調子で。"));

    $success_count = sendPushIfOptedIn($user_id, [
        'title' => $title,
        'body' => $body,
        'url' => '/reading_calendar.php',
        'tag' => 'streak-milestone-' . $streak,
    ]);

    if ($success_count > 0) {
        $sent++;
        $g_db->query(
            "INSERT IGNORE INTO b_streak_milestone_log (user_id, milestone, achieved_date) VALUES (?, ?, ?)",
            [$user_id, $streak, $today]
        );
    } else {
        $send_failed++;
    }
}

$elapsed = round(microtime(true) - $start, 2);
error_log(sprintf(
    '[StreakMilestone] Done in %ss: checked=%d sent=%d no_milestone=%d already_celebrated=%d failed=%d',
    $elapsed, $checked, $sent, $no_milestone, $already_celebrated, $send_failed
));
