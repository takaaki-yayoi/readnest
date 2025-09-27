<?php
// 読書ペース分析から読書インサイトへリダイレクト
header('Location: /reading_insights.php?mode=pace');
exit;

$user_id = $_SESSION['AUTH_USER'];
$user_info = getUserInformation($user_id);

// 各種分析データを取得
$hourly_pattern = getHourlyReadingPattern($user_id);
$weekly_trend = getWeeklyReadingTrend($user_id);
$speed_by_genre = getReadingSpeedByGenre($user_id);
$completion_analysis = getCompletionRateAnalysis($user_id);
$cycle_analysis = getReadingCycleAnalysis($user_id);
$pace_prediction = predictReadingPace($user_id);
$habits_summary = getReadingHabitsSummary($user_id);

// 月間目標情報を取得
require_once('library/monthly_goals.php');
$current_year = (int)date('Y');
$current_month = (int)date('n');
$monthly_goal_info = getMonthlyGoal($user_id, $current_year, $current_month);

// 今月の読書データを取得
$days_passed = (int)date('j');
$days_in_month = (int)date('t');
$days_remaining = $days_in_month - $days_passed;

// 今月の読了冊数を取得
$books_read = getMonthlyAchievement($user_id, $current_year, $current_month);

// 現在の読書ペース計算
$current_pace = $days_passed > 0 ? round($books_read / $days_passed, 2) : 0;

// 目標達成に必要なペース計算
$required_pace = 0;
$pace_status = 'normal';
if (isset($monthly_goal_info) && $monthly_goal_info['goal'] > 0) {
    $remaining_books = $monthly_goal_info['goal'] - $books_read;
    if ($remaining_books > 0 && $days_remaining > 0) {
        $required_pace = round($remaining_books / $days_remaining, 2);
        
        // ペース状態の判定
        if ($current_pace >= $required_pace * 1.1) {
            $pace_status = 'ahead';
        } elseif ($current_pace < $required_pace * 0.9) {
            $pace_status = 'behind';
        }
    } elseif ($remaining_books <= 0) {
        $pace_status = 'completed';
    }
}

// ページタイトル
$d_site_title = '読書ペース分析 - ReadNest';

// メタ情報
$g_meta_description = '読書習慣を詳細に分析。時間帯別パターン、読書速度、完読率など様々な角度から読書ペースを可視化します。';
$g_meta_keyword = '読書分析,読書ペース,読書習慣,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_reading_pace_analysis.php'));
?>