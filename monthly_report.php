<?php
/**
 * 月間読書レポートページ
 * 月別の読書統計と読了本リストを表示
 * 公開設定のユーザーはログインなしでも閲覧可能
 */

require_once('modern_config.php');
require_once(__DIR__ . '/library/monthly_report_generator.php');

// ログインチェック
$login_flag = checkLogin();
$mine_user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;

// 表示するユーザーID（userパラメータがあればそのユーザー、なければ自分）
$target_user_id = isset($_GET['user']) ? (int)$_GET['user'] : ($mine_user_id ? (int)$mine_user_id : null);

// ユーザーIDがない場合はログインページへ
if (!$target_user_id) {
    header('Location: /');
    exit;
}

// 自分のレポートかどうか
$is_my_report = ($mine_user_id && $target_user_id == $mine_user_id);

// 他人のレポートの場合は公開設定を確認
if (!$is_my_report) {
    $target_user = getUserInformation($target_user_id);
    if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
        // 非公開またはユーザーが存在しない
        header('Location: /');
        exit;
    }
    $display_nickname = getNickname($target_user_id);
} else {
    $display_nickname = getNickname($target_user_id);
}

// 年・月パラメータの取得とバリデーション
$current_year = (int)date('Y');
$current_month = (int)date('n');

$year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $current_month;

// バリデーション
if ($month < 1 || $month > 12) {
    $month = $current_month;
}
if ($year < 2015 || $year > $current_year + 1) {
    $year = $current_year;
}

// 未来の月は現在月にリダイレクト
$user_path = !$is_my_report ? "/{$target_user_id}" : "";
if ($year > $current_year || ($year == $current_year && $month > $current_month)) {
    header("Location: /report/{$current_year}/{$current_month}{$user_path}");
    exit;
}

// レポートデータを取得
$generator = new MonthlyReportGenerator();
$report_data = $generator->getReportData($target_user_id, $year, $month);

// 利用可能な年月リストを取得（ドロップダウン用）
$available_months = $generator->getAvailableMonths($target_user_id);

// 保存済み要約を取得（自分のレポートまたは公開要約）
$saved_summary = null;
if (function_exists('getMonthlyReportSummary')) {
    $saved_summary = getMonthlyReportSummary($target_user_id, $year, $month, !$is_my_report);
}

// ユーザー情報
$user_info = getUserInformation($target_user_id);
$user_nickname = getNickname($target_user_id);

// 前月・次月の計算
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year = $year - 1;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year = $year + 1;
}

// 次月が未来かどうか
$is_next_future = ($next_year > $current_year) || ($next_year == $current_year && $next_month > $current_month);

// ページメタ情報
if ($is_my_report) {
    $d_site_title = "{$year}年{$month}月の読書レポート - ReadNest";
    $g_meta_description = "あなたの{$year}年{$month}月の読書記録をまとめたレポートです。読了冊数、読んだページ数、目標達成状況などを確認できます。";
} else {
    $d_site_title = "{$display_nickname}さんの{$year}年{$month}月の読書レポート - ReadNest";
    $g_meta_description = "{$display_nickname}さんの{$year}年{$month}月の読書記録をまとめたレポートです。";
}
$g_meta_keyword = '読書記録,月間レポート,読書統計,ReadNest';

// OGP画像（動的生成）
$g_og_image = "https://readnest.jp/og-image/report/{$year}/{$month}/{$target_user_id}.png";

// テンプレートを読み込み
include(getTemplatePath('t_monthly_report.php'));
?>
