<?php
/**
 * 読書カレンダーページ
 * 読書習慣の可視化と習慣形成をサポート
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$user_id = $_SESSION['AUTH_USER'];
$user_info = getUserInformation($user_id);
$d_nickname = getNickname($user_id);

// 表示モードの取得（calendar または heatmap）
$view_mode = isset($_GET['view']) && $_GET['view'] === 'heatmap' ? 'heatmap' : 'calendar';

// 年月の取得（デフォルトは今月）
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

// 月の範囲チェック
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// カレンダー用のデータ取得
if ($view_mode === 'calendar') {
    $start_date = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $end_date = date('Y-m-d 23:59:59', strtotime('last day of ' . $year . '-' . $month));
} else {
    // ヒートマップは年間データを取得
    $start_date = sprintf('%04d-01-01 00:00:00', $year);
    $end_date = sprintf('%04d-12-31 23:59:59', $year);
}

// この月の読書記録を取得（本の情報も含む）
// finished_dateがある場合はそれも含める
$reading_days_sql = "
    SELECT 
        reading_date,
        SUM(book_count) as book_count,
        SUM(event_count) as event_count,
        GROUP_CONCAT(DISTINCT book_ids ORDER BY reading_date DESC) as book_ids,
        GROUP_CONCAT(DISTINCT book_names ORDER BY reading_date DESC SEPARATOR '|||') as book_names,
        GROUP_CONCAT(DISTINCT book_images ORDER BY reading_date DESC SEPARATOR '|||') as book_images
    FROM (
        -- イベントからの読書記録
        SELECT 
            DATE(be.event_date) as reading_date,
            COUNT(DISTINCT be.book_id) as book_count,
            COUNT(*) as event_count,
            GROUP_CONCAT(DISTINCT bl.book_id ORDER BY be.event_date DESC) as book_ids,
            GROUP_CONCAT(DISTINCT bl.name ORDER BY be.event_date DESC SEPARATOR '|||') as book_names,
            GROUP_CONCAT(DISTINCT bl.image_url ORDER BY be.event_date DESC SEPARATOR '|||') as book_images
        FROM b_book_event be
        INNER JOIN b_book_list bl ON be.book_id = bl.book_id AND be.user_id = bl.user_id
        WHERE be.user_id = ? 
        AND be.event_date >= ? 
        AND be.event_date <= ?
        AND be.event IN (?, ?, ?)
        GROUP BY DATE(be.event_date)
        
        UNION ALL
        
        -- finished_dateからの読了記録
        SELECT 
            bl.finished_date as reading_date,
            1 as book_count,
            1 as event_count,
            bl.book_id as book_ids,
            bl.name as book_names,
            bl.image_url as book_images
        FROM b_book_list bl
        WHERE bl.user_id = ?
        AND bl.finished_date >= DATE(?)
        AND bl.finished_date <= DATE(?)
        AND bl.status IN (?, ?)
        AND bl.finished_date IS NOT NULL
    ) as combined_reading
    GROUP BY reading_date
";

$reading_days = $g_db->getAll($reading_days_sql, 
    [
        // イベントのパラメータ
        $user_id, $start_date, $end_date, READING_NOW, READING_FINISH, 4, // 4 = 進捗更新
        // finished_dateのパラメータ
        $user_id, $start_date, $end_date, READING_FINISH, READ_BEFORE
    ], 
    DB_FETCHMODE_ASSOC
);

// 読書日のマップを作成（本の情報も含む）
$reading_map = [];
foreach ($reading_days as $day) {
    $book_ids = explode(',', $day['book_ids'] ?? '');
    $book_names = explode('|||', $day['book_names'] ?? '');
    $book_images = explode('|||', $day['book_images'] ?? '');
    
    $books = [];
    for ($i = 0; $i < min(3, count($book_ids ?? [])); $i++) { // 最大3冊まで
        if (isset($book_ids[$i])) {
            $books[] = [
                'id' => $book_ids[$i],
                'name' => $book_names[$i] ?? '',
                'image' => $book_images[$i] ?? ''
            ];
        }
    }
    
    $reading_map[$day['reading_date']] = [
        'event_count' => $day['event_count'],
        'book_count' => $day['book_count'],
        'books' => $books
    ];
}

// 今月の統計を計算
$total_reading_days = count($reading_days ?? []);
$current_streak = calculateCurrentStreak($user_id);
$longest_streak = calculateLongestStreak($user_id, $year, $month);

// ヒートマップデータの取得（ヒートマップビューの場合）
$heatmap_data = null;
if ($view_mode === 'heatmap') {
    $heatmap_data = getYearlyReadingData($user_id, $year);
}

// 今日読んでいる本を取得
$today = date('Y-m-d');
$today_books_sql = "
    SELECT DISTINCT bl.book_id, bl.name, bl.author, bl.image_url, bl.current_page, bl.total_page, bl.status
    FROM b_book_list bl
    WHERE bl.user_id = ? 
    AND bl.status IN (?, ?)
    AND bl.update_date >= ?
    ORDER BY bl.update_date DESC
    LIMIT 5
";
$today_books = $g_db->getAll($today_books_sql, 
    [$user_id, READING_NOW, READING_FINISH, $today . ' 00:00:00'], 
    DB_FETCHMODE_ASSOC
);

// 読書中の本を取得（今日更新されていないもの）
$reading_books_sql = "
    SELECT bl.book_id, bl.name, bl.author, bl.image_url, bl.current_page, bl.total_page
    FROM b_book_list bl
    WHERE bl.user_id = ? 
    AND bl.status = ?
    AND (bl.update_date < ? OR bl.update_date IS NULL)
    ORDER BY bl.update_date DESC
    LIMIT 3
";
$reading_books = $g_db->getAll($reading_books_sql, 
    [$user_id, READING_NOW, $today . ' 00:00:00'], 
    DB_FETCHMODE_ASSOC
);

// ヒートマップ用のデータ取得関数
function getYearlyReadingData($user_id, $year) {
    global $g_db;
    
    $sql = "
        SELECT 
            DATE(event_date) as reading_date,
            COUNT(DISTINCT book_id) as book_count,
            COUNT(*) as event_count
        FROM b_book_event
        WHERE user_id = ?
        AND YEAR(event_date) = ?
        AND event IN (?, ?, ?)
        GROUP BY DATE(event_date)
        ORDER BY reading_date
    ";
    
    $result = $g_db->getAll($sql, 
        [$user_id, $year, READING_NOW, READING_FINISH, 4], 
        DB_FETCHMODE_ASSOC
    );
    
    // 日付をキーとした配列に変換
    $data = [];
    foreach ($result as $row) {
        $data[$row['reading_date']] = [
            'book_count' => $row['book_count'],
            'event_count' => $row['event_count']
        ];
    }
    
    return $data;
}

// 読書連続記録を計算する関数
function calculateCurrentStreak($user_id) {
    global $g_db;
    
    $today = date('Y-m-d');
    $streak = 0;
    
    // 今日から遡って連続記録を確認
    for ($i = 0; $i < 365; $i++) {
        $check_date = date('Y-m-d', strtotime("-{$i} days"));
        $sql = "SELECT COUNT(*) FROM b_book_event 
                WHERE user_id = ? 
                AND DATE(event_date) = ? 
                AND event IN (?, ?, ?)";
        
        $count = $g_db->getOne($sql, [$user_id, $check_date, READING_NOW, READING_FINISH, 4]);
        
        if ($count > 0) {
            $streak++;
        } else {
            // 今日読んでいない場合は昨日からカウント
            if ($i == 0) {
                continue;
            }
            break;
        }
    }
    
    return $streak;
}

// 最長連続記録を計算
function calculateLongestStreak($user_id, $year, $month) {
    global $g_db;
    
    // この月の読書日を取得
    $sql = "SELECT DISTINCT DATE(event_date) as reading_date
            FROM b_book_event 
            WHERE user_id = ? 
            AND YEAR(event_date) = ? 
            AND MONTH(event_date) = ?
            AND event IN (?, ?, ?)
            ORDER BY reading_date";
    
    $result = $g_db->getAll($sql, [$user_id, $year, $month, READING_NOW, READING_FINISH, 4], DB_FETCHMODE_ASSOC);
    
    if (empty($result)) {
        return 0;
    }
    
    // 日付の配列を作成
    $dates = array_column($result, 'reading_date');
    
    if (count($dates) <= 1) {
        return count($dates ?? []);
    }
    
    $max_streak = 1;
    $current = 1;
    
    for ($i = 1; $i < count($dates ?? []); $i++) {
        $prev_date = new DateTime($dates[$i - 1]);
        $curr_date = new DateTime($dates[$i]);
        $diff = $curr_date->diff($prev_date)->days;
        
        if ($diff == 1) {
            $current++;
            $max_streak = max($max_streak, $current);
        } else {
            $current = 1;
        }
    }
    
    return $max_streak;
}

// ページタイトル
$d_site_title = "読書カレンダー - ReadNest";

// メタ情報
$g_meta_description = "読書習慣を視覚化する読書カレンダー。毎日の読書記録を確認して、読書習慣を身につけましょう。";
$g_meta_keyword = "読書カレンダー,読書習慣,読書記録,ReadNest";

// テンプレートを読み込み
include(getTemplatePath('t_reading_calendar.php'));
?>