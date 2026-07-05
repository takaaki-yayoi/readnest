<?php
/**
 * X APIプリペイド残高・月間予算の監視cronジョブ（フェーズ1）
 *
 * x_post_log の成功コスト合計から「推定残高」「当月推定消費」を算出し、
 * 閾値を下回った／予算を超過した場合に管理者へアラートする。
 * （残高ゼロで投稿がサイレント停止するのを防ぐのが目的）
 *
 * crontab 例（毎日9時）:
 * 0 9 * * * cd /path/to/readnest && php cron/x_balance_check.php >> logs/x_balance_check.log 2>&1
 *   ※ logs/ ディレクトリは書き込み可にしておくこと。
 */

// CLIでの実行を確認
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続
$g_db = DB_Connect();
if (!$g_db || DB::isError($g_db)) {
    error_log('[X Balance Check] Database connection failed');
    exit(1);
}

require_once(dirname(__DIR__) . '/library/x_post_log.php');

$balance   = xEstimatedBalanceUsd();
$monthly   = xMonthlySpendUsd();
$threshold = defined('X_BALANCE_ALERT_THRESHOLD_USD') ? (float)X_BALANCE_ALERT_THRESHOLD_USD : 5.0;
$budget    = defined('X_MONTHLY_BUDGET_USD') ? (float)X_MONTHLY_BUDGET_USD : 0.0;

echo sprintf(
    "[%s] X推定残高: \$%.2f / 当月推定消費: \$%.2f / 残高閾値: \$%.2f / 月予算: \$%.2f\n",
    date('Y-m-d H:i:s'), $balance, $monthly, $threshold, $budget
);

// 残高が閾値割れ
if ($balance <= $threshold) {
    $body = "X APIのプリペイド推定残高が閾値を下回りました。\n\n"
          . sprintf("推定残高: \$%.2f（閾値 \$%.2f）\n", $balance, $threshold)
          . sprintf("当月推定消費: \$%.2f\n", $monthly)
          . "\nチャージ後は config/x_api.php の X_PREPAID_BUDGET_USD と X_PREPAID_SINCE を更新してください。";
    xAlertAdmin('balance', 'X API残高が閾値を下回りました', $body);
    echo "  -> 残高アラートを送信（または本日送信済みのため抑制）\n";
}

// 月間予算を超過
if ($budget > 0 && $monthly >= $budget) {
    $body = "当月のX投稿コスト推定が月間予算を超えました。\n\n"
          . sprintf("当月推定消費: \$%.2f（予算 \$%.2f）\n", $monthly, $budget)
          . "現在は自動投稿がスキップされています。";
    xAlertAdmin('budget', 'X投稿の月間予算を超過', $body);
    echo "  -> 予算超過アラートを送信（または本日送信済みのため抑制）\n";
}

exit(0);
