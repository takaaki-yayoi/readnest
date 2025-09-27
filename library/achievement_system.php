<?php
/**
 * 読書達成度・モチベーションシステム
 */

declare(strict_types=1);

/**
 * 連続記録のマイルストーンを取得
 */
function getStreakMilestone(int $streak): array {
    $milestones = [
        3 => ['title' => '読書習慣スタート', 'icon' => 'seedling', 'color' => 'green'],
        7 => ['title' => '1週間達成', 'icon' => 'fire', 'color' => 'orange'],
        14 => ['title' => '2週間達成', 'icon' => 'fire-flame-curved', 'color' => 'orange'],
        21 => ['title' => '3週間達成', 'icon' => 'fire-flame-simple', 'color' => 'red'],
        30 => ['title' => '1ヶ月達成', 'icon' => 'medal', 'color' => 'yellow'],
        50 => ['title' => '50日達成', 'icon' => 'trophy', 'color' => 'purple'],
        100 => ['title' => '100日達成', 'icon' => 'crown', 'color' => 'gold'],
        150 => ['title' => '150日達成', 'icon' => 'star', 'color' => 'yellow'],
        200 => ['title' => '200日達成', 'icon' => 'award', 'color' => 'orange'],
        300 => ['title' => '300日達成', 'icon' => 'gem', 'color' => 'purple'],
        365 => ['title' => '1年達成', 'icon' => 'crown', 'color' => 'indigo'],
        500 => ['title' => '500日達成', 'icon' => 'trophy', 'color' => 'gold'],
        730 => ['title' => '2年達成', 'icon' => 'crown', 'color' => 'red'],
        1000 => ['title' => '1000日達成', 'icon' => 'gem', 'color' => 'purple'],
    ];
    
    $current = null;
    $next = null;
    $progress = 0;
    
    foreach ($milestones as $days => $milestone) {
        if ($streak >= $days) {
            $current = array_merge($milestone, ['days' => $days]);
        } else if (!$next) {
            $next = array_merge($milestone, ['days' => $days]);
            // 次のマイルストーンまでの進捗率
            $prev_days = $current ? $current['days'] : 0;
            $progress = round((($streak - $prev_days) / ($days - $prev_days)) * 100);
            break;
        }
    }
    
    return [
        'current' => $current,
        'next' => $next,
        'progress' => $progress,
        'days_to_next' => $next ? $next['days'] - $streak : 0
    ];
}

/**
 * 読書ページ数に基づくレベルを計算
 */
function getReadingLevel(int $total_pages): array {
    // レベル計算（100ページごとに1レベル、難易度は徐々に上昇）
    $level = 1;
    $required_pages = 100;
    $total_required = 0;
    
    while ($total_pages >= $total_required + $required_pages) {
        $total_required += $required_pages;
        $level++;
        // レベルが上がるごとに必要ページ数が増加
        $required_pages = 100 + ($level - 1) * 20;
    }
    
    // 現在のレベルでの進捗
    $current_level_pages = $total_pages - $total_required;
    $progress = round(($current_level_pages / $required_pages) * 100);
    
    // 称号システム
    $titles = [
        1 => ['name' => '読書初心者', 'icon' => 'book-open-reader', 'color' => 'gray'],
        5 => ['name' => '本の虫', 'icon' => 'book', 'color' => 'blue'],
        10 => ['name' => '読書家', 'icon' => 'book-bookmark', 'color' => 'green'],
        20 => ['name' => '博識者', 'icon' => 'graduation-cap', 'color' => 'purple'],
        30 => ['name' => '賢者', 'icon' => 'scroll', 'color' => 'indigo'],
        50 => ['name' => '読書マスター', 'icon' => 'medal', 'color' => 'yellow'],
        75 => ['name' => '読書の達人', 'icon' => 'trophy', 'color' => 'orange'],
        100 => ['name' => '読書の神', 'icon' => 'crown', 'color' => 'red'],
    ];
    
    $current_title = null;
    foreach ($titles as $required_level => $title) {
        if ($level >= $required_level) {
            $current_title = $title;
        }
    }
    
    return [
        'level' => $level,
        'progress' => $progress,
        'current_pages' => $current_level_pages,
        'required_pages' => $required_pages,
        'total_pages' => $total_pages,
        'title' => $current_title,
        'next_level_pages' => $required_pages - $current_level_pages
    ];
}

/**
 * 今月の読書ペースを評価
 */
function getMonthlyPaceRating(int $books_read, int $days_passed): array {
    // 月間目標を4冊と仮定
    $monthly_goal = 4;
    $days_in_month = date('t');
    
    // 現在のペース（1日あたりの冊数）
    $current_pace = $days_passed > 0 ? $books_read / $days_passed : 0;
    
    // 目標達成に必要なペース
    $required_pace = $monthly_goal / $days_in_month;
    
    // ペース評価
    $pace_ratio = $required_pace > 0 ? $current_pace / $required_pace : 0;
    
    if ($pace_ratio >= 1.5) {
        $rating = ['status' => '絶好調', 'icon' => 'rocket', 'color' => 'purple'];
    } elseif ($pace_ratio >= 1.2) {
        $rating = ['status' => '好調', 'icon' => 'thumbs-up', 'color' => 'green'];
    } elseif ($pace_ratio >= 0.8) {
        $rating = ['status' => '順調', 'icon' => 'smile', 'color' => 'blue'];
    } elseif ($pace_ratio >= 0.5) {
        $rating = ['status' => 'もう少し', 'icon' => 'battery-half', 'color' => 'yellow'];
    } else {
        $rating = ['status' => '頑張ろう', 'icon' => 'dumbbell', 'color' => 'orange'];
    }
    
    return array_merge($rating, [
        'pace_ratio' => $pace_ratio,
        'books_read' => $books_read,
        'projected_monthly' => round($current_pace * $days_in_month, 1)
    ]);
}

/**
 * 複数ユーザーのレベル情報を一括取得（パフォーマンス最適化）
 */
function getUsersLevels(array $user_ids): array {
    if (empty($user_ids)) {
        return [];
    }
    
    global $g_db;
    
    // ユーザーIDをエスケープ
    $user_ids = array_map('intval', $user_ids);
    $user_ids_str = implode(',', $user_ids);
    
    // 各ユーザーの総読書ページ数を一括取得
    $sql = "SELECT user_id, SUM(total_page) as total_pages 
            FROM b_book_list 
            WHERE user_id IN ($user_ids_str) 
            AND status IN (?, ?) 
            AND total_page > 0 
            GROUP BY user_id";
    
    $result = $g_db->getAll($sql, [READING_FINISH, READ_BEFORE], DB_FETCHMODE_ASSOC);
    
    $levels = [];
    if (!DB::isError($result)) {
        foreach ($result as $row) {
            $levels[$row['user_id']] = getReadingLevel(intval($row['total_pages']));
        }
    }
    
    // 結果がないユーザーはレベル1
    foreach ($user_ids as $user_id) {
        if (!isset($levels[$user_id])) {
            $levels[$user_id] = getReadingLevel(0);
        }
    }
    
    return $levels;
}

/**
 * 励ましメッセージを生成
 */
function getMotivationalMessage(int $streak, int $level, array $pace_rating): string {
    $messages = [];
    
    // 連続記録に基づくメッセージ
    if ($streak >= 1000) {
        $messages[] = "1000日連続達成！読書の伝説です！";
    } elseif ($streak >= 730) {
        $messages[] = "2年連続達成！読書への情熱が素晴らしいです！";
    } elseif ($streak >= 500) {
        $messages[] = "500日連続！読書マスターの称号にふさわしいです！";
    } elseif ($streak >= 365) {
        $messages[] = "1年連続達成！365日読書を続けるなんて凄いです！";
    } elseif ($streak >= 300) {
        $messages[] = "300日連続！もうすぐ1年です！";
    } elseif ($streak >= 200) {
        $messages[] = "200日連続達成！読書が完全に習慣化されています！";
    } elseif ($streak >= 150) {
        $messages[] = "150日連続！半年近く続いています！";
    } elseif ($streak >= 100) {
        $messages[] = "100日連続達成！素晴らしい習慣です！";
    } elseif ($streak >= 30) {
        $messages[] = "1ヶ月連続！読書が生活の一部になっていますね";
    } elseif ($streak >= 21) {
        $messages[] = "3週間連続！もうすぐ1ヶ月です！";
    } elseif ($streak >= 14) {
        $messages[] = "2週間連続！習慣が定着してきました";
    } elseif ($streak >= 7) {
        $messages[] = "1週間連続！この調子で続けましょう";
    } elseif ($streak >= 3) {
        $messages[] = "連続記録更新中！明日も読書を楽しみましょう";
    }
    
    // レベルに基づくメッセージ
    if ($level >= 50) {
        $messages[] = "レベル{$level}到達！読書の達人の域に達しています";
    } elseif ($level >= 20) {
        $messages[] = "レベル{$level}！知識の幅が確実に広がっています";
    } elseif ($level >= 10) {
        $messages[] = "レベル{$level}達成！読書習慣が身についてきました";
    }
    
    // ペースに基づくメッセージ
    if ($pace_rating['status'] === '絶好調') {
        $messages[] = "今月は絶好調！この勢いを維持しましょう";
    } elseif ($pace_rating['status'] === '好調') {
        $messages[] = "良いペースです！目標達成が見えてきました";
    }
    
    // ランダムに1つ選択
    return !empty($messages) ? $messages[array_rand($messages)] : "今日も読書を楽しみましょう！";
}