<?php
// 読書統計から読書インサイトへリダイレクト
header('Location: /reading_insights.php?mode=overview');
exit;

$mine_user_id = $_SESSION['AUTH_USER'];
$d_nickname = getNickname($mine_user_id);

global $g_db;

// 期間パラメータは削除（常に全期間を表示）

// 統計データを取得する関数
function getReadingStats($user_id) {
    global $g_db;
    
    $stats = [
        'total_books' => 0,
        'total_pages' => 0,
        'finished_books' => 0,
        'reading_books' => 0,
        'monthly_data' => [],
        'yearly_data' => [],
        'genre_data' => [],
        'rating_distribution' => [],
        'daily_pages' => [],
        'daily_books' => [], // 日別読書冊数を追加
        'cumulative_pages' => [] // 累積ページ数
    ];
    
    // 全体の統計（全期間）
    $total_sql = "SELECT 
        COUNT(*) as total_books,
        SUM(CASE WHEN status = " . READING_FINISH . " THEN 1 ELSE 0 END) as finished_books,
        SUM(CASE WHEN status = " . READING_NOW . " THEN 1 ELSE 0 END) as reading_books
        FROM b_book_list WHERE user_id = ?";
    $result = $g_db->getRow($total_sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($result)) {
        $stats['total_books'] = (int)$result['total_books'];
        $stats['finished_books'] = (int)$result['finished_books'];
        $stats['reading_books'] = (int)$result['reading_books'];
    }
    
    // 読了ページ数の合計
    $pages_sql = "SELECT SUM(bl.total_page) as total_pages 
                  FROM b_book_list bl 
                  WHERE bl.user_id = ? 
                  AND bl.status = " . READING_FINISH;
    $result = $g_db->getOne($pages_sql, [$user_id]);
    
    if (!DB::isError($result)) {
        $stats['total_pages'] = (int)$result;
    }
    
    // 年別データ（全期間：最初の本から現在まで）
    $first_book_sql = "SELECT MIN(finished_date) as first_date 
                      FROM b_book_list 
                      WHERE user_id = ? 
                      AND status = " . READING_FINISH . "
                      AND finished_date IS NOT NULL";
    $first_date = $g_db->getOne($first_book_sql, [$user_id]);
    
    if ($first_date && !DB::isError($first_date)) {
        $start_year = (int)date('Y', strtotime($first_date));
        $end_year = (int)date('Y');
        
        // 年別の読書冊数とページ数を取得
        for ($year = $start_year; $year <= $end_year; $year++) {
            $year_start = "$year-01-01";
            $year_end = "$year-12-31";
            
            $yearly_sql = "SELECT 
                           COUNT(*) as count,
                           COALESCE(SUM(total_page), 0) as pages
                           FROM b_book_list 
                           WHERE user_id = ? 
                           AND status = " . READING_FINISH . "
                           AND finished_date >= ? 
                           AND finished_date <= ?";
            
            $result = $g_db->getRow($yearly_sql, [$user_id, $year_start, $year_end], DB_FETCHMODE_ASSOC);
            
            if (!DB::isError($result)) {
                $stats['yearly_data'][$year] = [
                    'books' => (int)$result['count'],
                    'pages' => (int)$result['pages']
                ];
            }
        }
    }
    
    // 月別データ（過去12ヶ月）
    for ($i = 11; $i >= 0; $i--) {
        $target_month = date('Y-m', strtotime("-$i months"));
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));
        
        $monthly_sql = "SELECT COUNT(*) as books, SUM(bl.total_page) as pages
                        FROM b_book_list bl
                        WHERE bl.user_id = ? 
                        AND bl.status = " . READING_FINISH . "
                        AND bl.finished_date >= ? 
                        AND bl.finished_date <= ?";
        
        $result = $g_db->getRow($monthly_sql, [
            $user_id,
            $month_start,
            $month_end
        ], DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($result)) {
            $stats['monthly_data'][$target_month] = [
                'books' => (int)$result['books'],
                'pages' => (int)$result['pages']
            ];
        }
    }
    
    // 評価分布（全期間）
    $rating_sql = "SELECT rating, COUNT(*) as count 
                   FROM b_book_list 
                   WHERE user_id = ? 
                   AND rating > 0 
                   GROUP BY rating 
                   ORDER BY rating DESC";
    $result = $g_db->getAll($rating_sql, [$user_id], DB_FETCHMODE_ASSOC);
    
    if (!DB::isError($result)) {
        // 1-5の評価すべてを初期化
        for ($i = 1; $i <= 5; $i++) {
            $stats['rating_distribution'][$i] = 0;
        }
        // 実際のデータで上書き
        foreach ($result as $row) {
            $stats['rating_distribution'][$row['rating']] = (int)$row['count'];
        }
    }
    
    // 日別読書ページ数と冊数（過去30日）
    for ($i = 29; $i >= 0; $i--) {
        $target_date = date('Y-m-d', strtotime("-$i days"));
        
        // ページ数
        $daily_pages_sql = "SELECT SUM(page) as pages 
                           FROM b_book_event 
                           WHERE user_id = ? 
                           AND DATE(event_date) = ?";
        
        $result = $g_db->getOne($daily_pages_sql, [$user_id, $target_date]);
        if (!DB::isError($result) && $result) {
            $stats['daily_pages'][$target_date] = (int)$result;
        } else {
            $stats['daily_pages'][$target_date] = 0;
        }
        
        // 冊数（その日に読了した本の数）
        $daily_books_sql = "SELECT COUNT(*) as count 
                           FROM b_book_list 
                           WHERE user_id = ? 
                           AND status = " . READING_FINISH . "
                           AND finished_date = ?";
        
        $result = $g_db->getOne($daily_books_sql, [$user_id, $target_date]);
        if (!DB::isError($result) && $result) {
            $stats['daily_books'][$target_date] = (int)$result;
        } else {
            $stats['daily_books'][$target_date] = 0;
        }
    }
    
    // 累積ページ数と累積冊数の計算
    $cumulative_pages_total = 0;
    $cumulative_books_total = 0;
    
    // 30日分の累積データを作成
    for ($i = 29; $i >= 0; $i--) {
        $target_date = date('Y-m-d', strtotime("-$i days"));
        
        // ページ数の累積
        if (isset($stats['daily_pages'][$target_date])) {
            $cumulative_pages_total += $stats['daily_pages'][$target_date];
        }
        $stats['cumulative_pages'][$target_date] = $cumulative_pages_total;
        
        // 冊数の累積
        if (isset($stats['daily_books'][$target_date])) {
            $cumulative_books_total += $stats['daily_books'][$target_date];
        }
        $stats['cumulative_books'][$target_date] = $cumulative_books_total;
    }
    
    return $stats;
}

// データ取得
$stats = getReadingStats($mine_user_id);

// 最近読んだ本を取得
$recent_books_sql = "SELECT bl.*, br.image_url as repo_image
                     FROM b_book_list bl
                     LEFT JOIN b_book_repository br ON bl.amazon_id = br.asin
                     WHERE bl.user_id = ?
                     AND bl.status = " . READING_FINISH . "
                     ORDER BY bl.finished_date DESC
                     LIMIT 10";

$recent_books = $g_db->getAll($recent_books_sql, [$mine_user_id], DB_FETCHMODE_ASSOC);
if (DB::isError($recent_books)) {
    $recent_books = [];
}

// ページタイトル
$d_site_title = '読書履歴・統計 - ReadNest';

// テンプレートを使用
include(getTemplatePath('t_reading_history.php'));
?>