<?php
/**
 * 月間目標設定ページ
 */

declare(strict_types=1);

require_once('modern_config.php');
require_once('library/monthly_goals.php');
require_once('library/form_helpers.php');

// ログインチェック
if (!isset($_SESSION['AUTH_USER'])) {
    header('Location: /');
    exit;
}

$user_id = $_SESSION['AUTH_USER'];
$user_info = getUserInformation($user_id);
$current_year = (int)date('Y');
$message = '';
$error = '';

// フォーム処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'set_auto') {
        // 自動配分に切り替え
        if (switchToAutoMonthlyGoals($user_id)) {
            $message = '月間目標を自動配分に変更しました。';
        } else {
            $error = '設定の変更に失敗しました。';
        }
    } elseif ($action === 'set_custom') {
        // カスタム目標を保存
        $monthly_goals = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthly_goals[$month] = isset($_POST["month_$month"]) ? (int)$_POST["month_$month"] : 0;
        }
        
        if (saveCustomMonthlyGoals($user_id, $monthly_goals)) {
            $message = 'カスタム月間目標を保存しました。';
        } else {
            $error = '目標の保存に失敗しました。';
        }
    }
}

// 現在の設定を取得
$yearly_goal = isset($user_info['books_per_year']) ? (int)$user_info['books_per_year'] : 0;
$goal_type = $user_info['monthly_goal_type'] ?? 'auto';
$auto_monthly_goal = $yearly_goal > 0 ? ceil($yearly_goal / 12) : 0;

// カスタム目標を取得
$custom_goals = [];
if ($goal_type === 'custom' && !empty($user_info['custom_monthly_goals'])) {
    $custom_goals = json_decode($user_info['custom_monthly_goals'], true);
}

// 今年の実績を取得
$achievements = [];
for ($month = 1; $month <= 12; $month++) {
    $goal_info = getMonthlyGoal($user_id, $current_year, $month);
    $achieved = getMonthlyAchievement($user_id, $current_year, $month);
    
    $achievements[$month] = [
        'goal' => $goal_info['goal'],
        'achieved' => $achieved,
        'progress' => calculateMonthlyProgress($achieved, $goal_info['goal'])
    ];
    
    // 達成状況を保存
    saveMonthlyAchievement($user_id, $current_year, $month, $goal_info['goal'], $achieved);
}

// 連続達成月数
$consecutive_months = getConsecutiveAchievedMonths($user_id);

// ページタイトル
$d_site_title = '月間目標設定 - ReadNest';

// テンプレート用の追加CSS
$d_additional_head = <<<HTML
<style>
.month-goal-input {
    width: 60px;
    text-align: center;
}

.progress-bar {
    height: 20px;
    background-color: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background-color: #10b981;
    transition: width 0.3s ease;
}

.achieved-badge {
    background-color: #fbbf24;
    color: #92400e;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}
</style>
HTML;

// テンプレートを読み込み
include(getTemplatePath('t_monthly_goals.php'));
?>