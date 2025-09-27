<?php
/**
 * 読書ペース分析ライブラリ
 */

declare(strict_types=1);

/**
 * 時間帯別読書パターンを取得
 */
function getHourlyReadingPattern($user_id, $days = 90): array {
    global $g_db;
    
    $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $sql = "
        SELECT 
            HOUR(event_date) as hour,
            DAYOFWEEK(event_date) as day_of_week,
            COUNT(*) as event_count,
            COUNT(DISTINCT DATE(event_date)) as day_count
        FROM b_book_event
        WHERE user_id = ?
        AND event_date >= ?
        AND event IN (?, ?, ?)
        GROUP BY HOUR(event_date), DAYOFWEEK(event_date)
        ORDER BY hour, day_of_week
    ";
    
    $result = $g_db->getAll($sql, 
        [$user_id, $start_date, READING_NOW, READING_FINISH, 4], 
        DB_FETCHMODE_ASSOC
    );
    
    if (DB::isError($result)) {
        error_log('getHourlyReadingPattern error: ' . $result->getMessage());
        $result = [];
    }
    
    // 24時間 x 7曜日の配列を初期化
    $pattern = [];
    for ($hour = 0; $hour < 24; $hour++) {
        for ($dow = 1; $dow <= 7; $dow++) {
            $pattern[$hour][$dow] = 0;
        }
    }
    
    // データを配列に格納
    foreach ($result as $row) {
        $pattern[$row['hour']][$row['day_of_week']] = $row['event_count'];
    }
    
    return $pattern;
}

/**
 * 曜日別読書傾向を取得
 */
function getWeeklyReadingTrend($user_id, $days = 90): array {
    global $g_db;
    
    $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $sql = "
        SELECT 
            DAYOFWEEK(event_date) as day_of_week,
            COUNT(*) as event_count,
            COUNT(DISTINCT DATE(event_date)) as day_count,
            COUNT(DISTINCT book_id) as book_count
        FROM b_book_event
        WHERE user_id = ?
        AND event_date >= ?
        AND event IN (?, ?, ?)
        GROUP BY DAYOFWEEK(event_date)
        ORDER BY day_of_week
    ";
    
    $result = $g_db->getAll($sql, 
        [$user_id, $start_date, READING_NOW, READING_FINISH, 4], 
        DB_FETCHMODE_ASSOC
    );
    
    if (DB::isError($result)) {
        error_log('getWeeklyReadingTrend error: ' . $result->getMessage());
        return [];
    }
    
    $trend = [];
    $day_names = ['', '日', '月', '火', '水', '木', '金', '土'];
    
    foreach ($result as $row) {
        $trend[] = [
            'day_of_week' => $row['day_of_week'],
            'day_name' => $day_names[$row['day_of_week']],
            'event_count' => $row['event_count'],
            'day_count' => $row['day_count'],
            'book_count' => $row['book_count'],
            'avg_events_per_day' => $row['day_count'] > 0 ? round($row['event_count'] / $row['day_count'], 1) : 0
        ];
    }
    
    return $trend;
}

/**
 * 読書速度分析（ジャンル別） - ジャンル機能は無効化
 */
function getReadingSpeedByGenre($user_id, $days = 180): array {
    // ジャンル機能は無効化されているため、空の配列を返す
    return [];
}

/**
 * 完読率とパターン分析
 */
function getCompletionRateAnalysis($user_id): array {
    global $g_db;
    
    // 全体の完読率（読了 vs 読書中 vs 未読）
    $sql = "
        SELECT 
            COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as completed,
            COUNT(CASE WHEN status = ? THEN 1 END) as not_started,
            COUNT(CASE WHEN status = ? THEN 1 END) as reading,
            COUNT(*) as total
        FROM b_book_list
        WHERE user_id = ?
        AND status IN (?, ?, ?, ?)
    ";
    
    $stats = $g_db->getRow($sql, 
        [READING_FINISH, READ_BEFORE, NOT_STARTED, READING_NOW, $user_id, READING_FINISH, READ_BEFORE, NOT_STARTED, READING_NOW], 
        DB_FETCHMODE_ASSOC
    );
    
    if (DB::isError($stats)) {
        error_log('getCompletionRateAnalysis stats error: ' . $stats->getMessage());
        $stats = ['completed' => 0, 'not_started' => 0, 'reading' => 0, 'total' => 0];
    }
    
    // ジャンル別完読率（ジャンル機能は無効化）
    $by_genre = [];
    
    // ページ数別完読率
    $sql = "
        SELECT 
            CASE 
                WHEN total_page <= 200 THEN '薄い本 (〜200p)'
                WHEN total_page <= 400 THEN '普通の本 (201-400p)'
                WHEN total_page <= 600 THEN '厚い本 (401-600p)'
                ELSE '超厚い本 (600p〜)'
            END as page_category,
            COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as completed,
            COUNT(CASE WHEN status = ? THEN 1 END) as not_started,
            COUNT(*) as total,
            ROUND(COUNT(CASE WHEN status IN (?, ?) THEN 1 END) * 100.0 / COUNT(*), 1) as completion_rate
        FROM b_book_list
        WHERE user_id = ?
        AND status IN (?, ?, ?, ?)
        AND total_page > 0
        GROUP BY page_category
        ORDER BY 
            CASE page_category
                WHEN '薄い本 (〜200p)' THEN 1
                WHEN '普通の本 (201-400p)' THEN 2
                WHEN '厚い本 (401-600p)' THEN 3
                ELSE 4
            END
    ";
    
    $by_pages = $g_db->getAll($sql, 
        [READING_FINISH, READ_BEFORE, NOT_STARTED, READING_FINISH, READ_BEFORE, $user_id, READING_FINISH, READ_BEFORE, NOT_STARTED, READING_NOW], 
        DB_FETCHMODE_ASSOC
    );
    
    if (DB::isError($by_pages)) {
        error_log('getCompletionRateAnalysis by_pages error: ' . $by_pages->getMessage());
        $by_pages = [];
    }
    
    return [
        'overall' => $stats,
        'by_genre' => $by_genre,
        'by_pages' => $by_pages
    ];
}

/**
 * 読書サイクル分析
 */
function getReadingCycleAnalysis($user_id, $days = 180): array {
    global $g_db;
    
    $start_date = date('Y-m-d', strtotime("-{$days} days"));
    
    // 日別の読書活動を取得
    $sql = "
        SELECT 
            DATE(event_date) as reading_date,
            COUNT(*) as event_count
        FROM b_book_event
        WHERE user_id = ?
        AND event_date >= ?
        AND event IN (?, ?, ?)
        GROUP BY DATE(event_date)
        ORDER BY reading_date
    ";
    
    $daily_events = $g_db->getAll($sql, 
        [$user_id, $start_date, READING_NOW, READING_FINISH, 4], 
        DB_FETCHMODE_ASSOC
    );
    
    if (DB::isError($daily_events)) {
        error_log('getReadingCycleAnalysis error: ' . $daily_events->getMessage());
        $daily_events = [];
    }
    
    // 連続記録と休憩期間を分析
    $streaks = [];
    $breaks = [];
    $current_streak = 0;
    $last_date = null;
    
    foreach ($daily_events as $event) {
        $date = new DateTime($event['reading_date']);
        
        if ($last_date === null) {
            $current_streak = 1;
        } else {
            $diff = $date->diff($last_date)->days;
            
            if ($diff === 1) {
                // 連続
                $current_streak++;
            } else {
                // 休憩期間があった
                if ($current_streak > 0) {
                    $streaks[] = $current_streak;
                }
                if ($diff > 1) {
                    $breaks[] = $diff - 1;
                }
                $current_streak = 1;
            }
        }
        
        $last_date = $date;
    }
    
    // 最後の連続記録を追加
    if ($current_streak > 0) {
        $streaks[] = $current_streak;
    }
    
    // 統計を計算
    $stats = [
        'avg_streak_length' => !empty($streaks) ? round(array_sum($streaks) / count($streaks), 1) : 0,
        'max_streak_length' => !empty($streaks) ? max($streaks) : 0,
        'avg_break_length' => !empty($breaks) ? round(array_sum($breaks) / count($breaks), 1) : 0,
        'reading_frequency' => count($daily_events) / $days * 100, // パーセンテージ
        'streak_distribution' => array_count_values($streaks)
    ];
    
    return $stats;
}

/**
 * 読書ペース予測
 */
function predictReadingPace($user_id): array {
    global $g_db;
    
    // 過去90日の実績
    $sql = "
        SELECT 
            COUNT(DISTINCT bl.book_id) as books_90days
        FROM b_book_list bl
        WHERE bl.user_id = ?
        AND bl.status = ?
        AND bl.finished_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    ";
    
    $books_90days = $g_db->getOne($sql, [$user_id, READING_FINISH]);
    if (DB::isError($books_90days)) {
        error_log('predictReadingPace 90days error: ' . $books_90days->getMessage());
        $books_90days = 0;
    }
    
    // 過去30日の実績
    $sql = "
        SELECT 
            COUNT(DISTINCT bl.book_id) as books_30days
        FROM b_book_list bl
        WHERE bl.user_id = ?
        AND bl.status = ?
        AND bl.finished_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    
    $books_30days = $g_db->getOne($sql, [$user_id, READING_FINISH]);
    if (DB::isError($books_30days)) {
        error_log('predictReadingPace 30days error: ' . $books_30days->getMessage());
        $books_30days = 0;
    }
    
    // 今年の実績
    $sql = "
        SELECT 
            COUNT(DISTINCT bl.book_id) as books_this_year
        FROM b_book_list bl
        WHERE bl.user_id = ?
        AND bl.status = ?
        AND YEAR(bl.finished_date) = YEAR(NOW())
    ";
    
    $books_this_year = $g_db->getOne($sql, [$user_id, READING_FINISH]);
    if (DB::isError($books_this_year)) {
        error_log('predictReadingPace this_year error: ' . $books_this_year->getMessage());
        $books_this_year = 0;
    }
    
    // 予測計算
    $days_passed = date('z') + 1; // 今年の経過日数
    $days_remaining = 365 - $days_passed;
    
    // 3つの予測方法
    $pace_90days = $books_90days / 90; // 90日平均
    $pace_30days = $books_30days / 30; // 30日平均
    $pace_year = $books_this_year / $days_passed; // 今年の平均
    
    $predictions = [
        'current_year_total' => $books_this_year,
        'days_passed' => $days_passed,
        'days_remaining' => $days_remaining,
        'prediction_90days' => round($books_this_year + ($pace_90days * $days_remaining)),
        'prediction_30days' => round($books_this_year + ($pace_30days * $days_remaining)),
        'prediction_year_avg' => round($books_this_year + ($pace_year * $days_remaining)),
        'monthly_pace_90days' => round($pace_90days * 30, 1),
        'monthly_pace_30days' => round($pace_30days * 30, 1),
        'monthly_pace_year' => round($pace_year * 30, 1)
    ];
    
    return $predictions;
}

/**
 * 読書習慣の総合分析
 */
function getReadingHabitsSummary($user_id): array {
    global $g_db;
    
    // 最も活発な時間帯
    $sql = "
        SELECT 
            HOUR(event_date) as hour,
            COUNT(*) as event_count
        FROM b_book_event
        WHERE user_id = ?
        AND event_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        AND event IN (?, ?, ?)
        GROUP BY HOUR(event_date)
        ORDER BY event_count DESC
        LIMIT 3
    ";
    
    $active_hours = $g_db->getAll($sql, 
        [$user_id, READING_NOW, READING_FINISH, 4], 
        DB_FETCHMODE_ASSOC
    );
    
    if (DB::isError($active_hours)) {
        error_log('getReadingHabitsSummary active_hours error: ' . $active_hours->getMessage());
        $active_hours = [];
    }
    
    // 最も活発な曜日
    $sql = "
        SELECT 
            DAYOFWEEK(event_date) as day_of_week,
            COUNT(*) as event_count
        FROM b_book_event
        WHERE user_id = ?
        AND event_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        AND event IN (?, ?, ?)
        GROUP BY DAYOFWEEK(event_date)
        ORDER BY event_count DESC
        LIMIT 1
    ";
    
    $active_day = $g_db->getRow($sql, 
        [$user_id, READING_NOW, READING_FINISH, 4], 
        DB_FETCHMODE_ASSOC
    );
    
    if (DB::isError($active_day)) {
        error_log('getReadingHabitsSummary active_day error: ' . $active_day->getMessage());
        $active_day = null;
    }
    
    $day_names = ['', '日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'];
    
    return [
        'active_hours' => $active_hours,
        'active_day' => $active_day ? $day_names[$active_day['day_of_week']] : '不明',
        'reading_type' => determineReadingType($active_hours)
    ];
}

/**
 * 読書タイプを判定
 */
function determineReadingType($active_hours): string {
    if (empty($active_hours)) {
        return '不定期型';
    }
    
    $morning_count = 0;
    $evening_count = 0;
    
    foreach ($active_hours as $hour_data) {
        $hour = $hour_data['hour'];
        if ($hour >= 5 && $hour <= 9) {
            $morning_count++;
        } elseif ($hour >= 20 || $hour <= 2) {
            $evening_count++;
        }
    }
    
    if ($morning_count >= 2) {
        return '朝型';
    } elseif ($evening_count >= 2) {
        return '夜型';
    } else {
        return '昼型';
    }
}
?>