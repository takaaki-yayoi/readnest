<?php
/**
 * 月間目標管理ライブラリ
 */

declare(strict_types=1);

/**
 * ユーザーの月間目標を取得
 * @param string|int $user_id ユーザーID
 * @param int $year 年
 * @param int $month 月
 * @return array 月間目標情報
 */
function getMonthlyGoal($user_id, int $year, int $month): array {
    // user_idを文字列に変換
    $user_id = (string)$user_id;
    global $g_db;
    
    // ユーザー情報を取得
    $user_info = getUserInformation($user_id);
    if (!$user_info) {
        return ['goal' => 0, 'type' => 'auto'];
    }
    
    $yearly_goal = isset($user_info['books_per_year']) ? (int)$user_info['books_per_year'] : 0;
    $goal_type = $user_info['monthly_goal_type'] ?? 'auto';
    
    if ($goal_type === 'custom' && !empty($user_info['custom_monthly_goals'])) {
        // カスタム月間目標
        $custom_goals = json_decode($user_info['custom_monthly_goals'], true);
        if (isset($custom_goals[$month])) {
            return [
                'goal' => (int)$custom_goals[$month],
                'type' => 'custom'
            ];
        }
    }
    
    // 自動計算（年間目標を12で割る）
    $monthly_goal = $yearly_goal > 0 ? (int)ceil($yearly_goal / 12) : 0;
    
    return [
        'goal' => $monthly_goal,
        'type' => 'auto'
    ];
}

/**
 * 月間目標の実績を取得
 * @param string|int $user_id ユーザーID
 * @param int $year 年
 * @param int $month 月
 * @return int 読了冊数
 */
function getMonthlyAchievement($user_id, int $year, int $month): int {
    // user_idを文字列に変換
    $user_id = (string)$user_id;
    global $g_db;
    
    // 月の開始日と終了日を計算
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = date('Y-m-d', strtotime("$start_date +1 month"));
    
    $sql = "SELECT COUNT(*) FROM b_book_list 
            WHERE user_id = ? 
            AND status IN (?, ?)
            AND (
                (finished_date IS NOT NULL AND finished_date >= ? AND finished_date < ?)
                OR
                (finished_date IS NULL AND update_date >= ? AND update_date < ?)
            )";
    
    $count = $g_db->getOne($sql, [
        $user_id, 
        READING_FINISH, 
        READ_BEFORE,
        $start_date,
        $end_date,
        $start_date,
        $end_date
    ]);
    
    return DB::isError($count) ? 0 : (int)$count;
}

/**
 * 月間目標達成状況を保存
 * @param string|int $user_id ユーザーID
 * @param int $year 年
 * @param int $month 月
 * @param int $goal 目標冊数
 * @param int $achieved 実績冊数
 */
function saveMonthlyAchievement($user_id, int $year, int $month, int $goal, int $achieved): bool {
    // user_idを文字列に変換
    $user_id = (string)$user_id;
    global $g_db;
    
    $achieved_date = null;
    if ($achieved >= $goal && $goal > 0) {
        // 目標達成した場合、達成日を記録
        $achieved_date = date('Y-m-d H:i:s');
    }
    
    $sql = "INSERT INTO b_monthly_goal_achievements 
            (user_id, year, month, goal, achieved, achieved_date) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            goal = VALUES(goal),
            achieved = VALUES(achieved),
            achieved_date = VALUES(achieved_date),
            updated_at = CURRENT_TIMESTAMP";
    
    $result = $g_db->query($sql, [
        $user_id,
        $year,
        $month,
        $goal,
        $achieved,
        $achieved_date
    ]);
    
    return !DB::isError($result);
}

/**
 * 年間の月間目標達成状況を取得
 * @param string|int $user_id ユーザーID
 * @param int $year 年
 * @return array 月別の達成状況
 */
function getYearlyMonthlyAchievements($user_id, int $year): array {
    // user_idを文字列に変換
    $user_id = (string)$user_id;
    global $g_db;
    
    $sql = "SELECT month, goal, achieved, achieved_date 
            FROM b_monthly_goal_achievements 
            WHERE user_id = ? AND year = ?
            ORDER BY month";
    
    $results = $g_db->getAll($sql, [$user_id, $year], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($results)) {
        return [];
    }
    
    // 月ごとのデータを整形
    $achievements = [];
    for ($month = 1; $month <= 12; $month++) {
        $achievements[$month] = [
            'goal' => 0,
            'achieved' => 0,
            'achieved_date' => null,
            'is_achieved' => false
        ];
    }
    
    foreach ($results as $row) {
        $month = (int)$row['month'];
        $achievements[$month] = [
            'goal' => (int)$row['goal'],
            'achieved' => (int)$row['achieved'],
            'achieved_date' => $row['achieved_date'],
            'is_achieved' => $row['achieved'] >= $row['goal'] && $row['goal'] > 0
        ];
    }
    
    return $achievements;
}

/**
 * カスタム月間目標を保存
 * @param string|int $user_id ユーザーID
 * @param array $monthly_goals 月別の目標（1-12月）
 * @return bool 成功/失敗
 */
function saveCustomMonthlyGoals($user_id, array $monthly_goals): bool {
    // user_idを文字列に変換
    $user_id = (string)$user_id;
    global $g_db;
    
    // 検証: 12ヶ月分の目標が必要
    $validated_goals = [];
    for ($month = 1; $month <= 12; $month++) {
        $validated_goals[$month] = isset($monthly_goals[$month]) ? max(0, (int)$monthly_goals[$month]) : 0;
    }
    
    $json_goals = json_encode($validated_goals);
    
    $sql = "UPDATE b_user SET 
            monthly_goal_type = 'custom',
            custom_monthly_goals = ?
            WHERE user_id = ?";
    
    $result = $g_db->query($sql, [$json_goals, $user_id]);
    
    return !DB::isError($result);
}

/**
 * 自動月間目標に切り替え
 * @param string|int $user_id ユーザーID
 * @return bool 成功/失敗
 */
function switchToAutoMonthlyGoals($user_id): bool {
    // user_idを文字列に変換
    $user_id = (string)$user_id;
    global $g_db;
    
    $sql = "UPDATE b_user SET 
            monthly_goal_type = 'auto',
            custom_monthly_goals = NULL
            WHERE user_id = ?";
    
    $result = $g_db->query($sql, [$user_id]);
    
    return !DB::isError($result);
}

/**
 * 月間目標の進捗率を計算
 * @param int|float $achieved 実績
 * @param int|float $goal 目標
 * @return float 進捗率（%）
 */
function calculateMonthlyProgress($achieved, $goal): float {
    // 整数に変換
    $achieved = (int)$achieved;
    $goal = (int)$goal;
    if ($goal <= 0) {
        return 0.0;
    }
    
    return min(100.0, ($achieved / $goal) * 100);
}

/**
 * 連続達成月数を取得
 * @param string|int $user_id ユーザーID
 * @return int 連続達成月数
 */
function getConsecutiveAchievedMonths($user_id): int {
    // user_idを文字列に変換
    $user_id = (string)$user_id;
    global $g_db;
    
    // 過去12ヶ月分の達成状況を取得
    $sql = "SELECT year, month, goal, achieved 
            FROM b_monthly_goal_achievements 
            WHERE user_id = ?
            AND achieved_date IS NOT NULL
            AND goal > 0
            ORDER BY year DESC, month DESC
            LIMIT 12";
    
    $results = $g_db->getAll($sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    if (DB::isError($results) || empty($results)) {
        return 0;
    }
    
    $consecutive = 0;
    $current_date = new DateTime();
    
    foreach ($results as $row) {
        $achievement_date = new DateTime("{$row['year']}-{$row['month']}-01");
        
        // 今月または先月から始まっているか確認
        if ($consecutive === 0) {
            $diff = $current_date->diff($achievement_date);
            $months_diff = ($diff->y * 12) + $diff->m;
            
            if ($months_diff > 1) {
                break; // 2ヶ月以上前なら連続ではない
            }
        }
        
        if ($row['achieved'] >= $row['goal']) {
            $consecutive++;
        } else {
            break;
        }
    }
    
    return $consecutive;
}