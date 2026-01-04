<?php
/**
 * 月間レポート通知生成cronジョブ
 *
 * 毎月1日に前月のレポート通知を全アクティブユーザーに送信
 *
 * crontab:
 * 0 9 1 * * cd /path/to/readnest && php cron/generate_monthly_notifications.php >> /var/log/readnest/monthly_notifications.log 2>&1
 */

// CLIでの実行を確認
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/notification_helpers.php');
require_once(dirname(__DIR__) . '/library/monthly_report_generator.php');

// データベース接続
$g_db = DB_Connect();
if (!$g_db || DB::isError($g_db)) {
    error_log('[Monthly Notifications] Database connection failed');
    exit(1);
}

// 対象年月（前月）
$target_year = (int)date('Y', strtotime('last month'));
$target_month = (int)date('n', strtotime('last month'));

// 開始ログ
$start_time = microtime(true);
error_log("[Monthly Notifications] Starting generation for {$target_year}/{$target_month} at " . date('Y-m-d H:i:s'));

try {
    // アクティブユーザーを取得（前月に読書活動があったユーザー）
    $sql = "SELECT DISTINCT u.user_id, u.nickname
            FROM b_user u
            INNER JOIN b_book_list bl ON u.user_id = bl.user_id
            WHERE u.status = 1
            AND (
                -- 前月に読了した本がある
                (bl.finished_date >= ? AND bl.finished_date < ?)
                OR
                -- 前月にupdate_dateが更新された本がある（読書活動あり）
                (bl.update_date >= ? AND bl.update_date < ?)
            )
            ORDER BY u.user_id";

    $start_date = sprintf('%04d-%02d-01', $target_year, $target_month);
    $end_date = date('Y-m-01', strtotime($start_date . ' +1 month'));

    $users = $g_db->getAll($sql, [$start_date, $end_date, $start_date, $end_date]);

    if (DB::isError($users)) {
        throw new Exception('Failed to get active users: ' . $users->getMessage());
    }

    error_log("[Monthly Notifications] Found " . count($users) . " active users");

    $generator = new MonthlyReportGenerator();
    $success_count = 0;
    $skip_count = 0;
    $error_count = 0;

    foreach ($users as $user) {
        try {
            $user_id = (int)$user['user_id'];

            // レポートデータを取得
            $report_data = $generator->getReportData($user_id, $target_year, $target_month);

            // データがない場合はスキップ
            if (!$report_data['has_data'] || $report_data['statistics']['books_finished'] == 0) {
                $skip_count++;
                continue;
            }

            // 通知を作成
            $result = createMonthlyReportNotification($user_id, $target_year, $target_month, $report_data);

            if ($result) {
                $success_count++;
            } else {
                // 重複の場合もfalseが返るので、スキップとしてカウント
                $skip_count++;
            }

        } catch (Exception $e) {
            error_log("[Monthly Notifications] Error for user {$user['user_id']}: " . $e->getMessage());
            $error_count++;
        }
    }

    $execution_time = round(microtime(true) - $start_time, 2);
    error_log("[Monthly Notifications] Completed in {$execution_time}s - Success: {$success_count}, Skipped: {$skip_count}, Errors: {$error_count}");

    // 実行ログをデータベースに記録
    $log_sql = "INSERT INTO b_cron_log (
        cron_type,
        status,
        message,
        execution_time,
        created_at
    ) VALUES (?, ?, ?, ?, ?)";

    $g_db->query($log_sql, [
        'generate_monthly_notifications',
        'success',
        "Generated {$success_count} notifications for {$target_year}/{$target_month}",
        $execution_time * 1000,
        time()
    ]);

} catch (Exception $e) {
    error_log('[Monthly Notifications] Fatal error: ' . $e->getMessage());

    // エラーログを記録
    if (isset($g_db)) {
        $log_sql = "INSERT INTO b_cron_log (
            cron_type,
            status,
            message,
            execution_time,
            created_at
        ) VALUES (?, ?, ?, ?, ?)";

        $g_db->query($log_sql, [
            'generate_monthly_notifications',
            'error',
            $e->getMessage(),
            0,
            time()
        ]);
    }

    exit(1);
}

error_log("[Monthly Notifications] Job finished successfully");
