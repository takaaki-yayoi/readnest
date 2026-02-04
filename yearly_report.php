<?php
/**
 * 年間読書レポートページ
 * 年別の読書統計と読了本リストを表示
 * 公開設定のユーザーはログインなしでも閲覧可能
 */

require_once('modern_config.php');
require_once(__DIR__ . '/library/yearly_report_generator.php');
require_once(__DIR__ . '/library/monthly_report_generator.php'); // getXShareUrl関数のため

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

// 年パラメータの取得とバリデーション
$current_year = (int)date('Y');

$year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;

// バリデーション
if ($year < 2000 || $year > $current_year + 1) {
    $year = $current_year;
}

// 未来の年は現在年にリダイレクト
$user_path = !$is_my_report ? "/user/{$target_user_id}" : "";
if ($year > $current_year) {
    header("Location: /report/{$current_year}{$user_path}");
    exit;
}

// レポートデータを取得
$generator = new YearlyReportGenerator();
$report_data = $generator->getReportData($target_user_id, $year);

// 利用可能な年リストを取得（ドロップダウン用）
$available_years = $generator->getAvailableYears($target_user_id);

// 保存済み要約を取得（自分のレポートまたは公開要約）
$saved_summary = null;
if (function_exists('getYearlyReportSummary')) {
    $saved_summary = getYearlyReportSummary($target_user_id, $year, !$is_my_report);
}

// ユーザー情報
$user_info = getUserInformation($target_user_id);
$user_nickname = getNickname($target_user_id);

// 前年・次年の計算
$prev_year = $year - 1;
$next_year = $year + 1;

// 次年が未来かどうか
$is_next_future = ($next_year > $current_year);

// ページメタ情報
if ($is_my_report) {
    $d_site_title = "{$year}年の読書レポート - ReadNest";
    $g_meta_description = "あなたの{$year}年の読書記録をまとめたレポートです。年間読了冊数、読んだページ数、月別推移などを確認できます。";
} else {
    $d_site_title = "{$display_nickname}さんの{$year}年の読書レポート - ReadNest";
    $g_meta_description = "{$display_nickname}さんの{$year}年の読書記録をまとめたレポートです。";
}
$g_meta_keyword = '読書記録,年間レポート,読書統計,ReadNest';

// OGP画像（動的生成）
$g_og_image = "https://readnest.jp/og-image/report/{$year}/{$target_user_id}.png";

// テンプレートを読み込み
include(getTemplatePath('t_yearly_report.php'));
?>
