<?php
/**
 * 読書統計関数
 */

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
        'daily_books' => [],
        'cumulative_pages' => []
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
    
    return $stats;
}
?>