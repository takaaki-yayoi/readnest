<?php
/**
 * 読書インサイト - 統合分析ページ
 * embeddingベースの分析と従来の読書マップを統合
 */

require_once('modern_config.php');
require_once(__DIR__ . '/library/reading_trend_analyzer.php');
require_once(__DIR__ . '/library/embedding_analyzer.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    header('Location: /');
    exit;
}

$mine_user_id = $_SESSION['AUTH_USER'];

// 表示するユーザーID（プロフィール表示の場合）
$user_id = isset($_GET['user']) ? (int)$_GET['user'] : (int)$mine_user_id;
$is_my_insights = ($user_id == $mine_user_id);

// 他人のデータの場合は公開設定を確認
if (!$is_my_insights) {
    $target_user = getUserInformation($user_id);
    if (!$target_user || $target_user['diary_policy'] != 1 || $target_user['status'] != 1) {
        header("Location: /");
        exit;
    }
    $display_nickname = getNickname($user_id);
    $d_site_title = $display_nickname . 'さんの読書インサイト - ReadNest';
} else {
    $display_nickname = getNickname($user_id);
    $d_site_title = '読書インサイト - ReadNest';
}

// 表示モードの切り替え
$view_mode = $_GET['mode'] ?? 'overview'; // overview, map, pace, clusters

// 分析器のインスタンス作成
$trend_analyzer = new ReadingTrendAnalyzer();
$embedding_analyzer = new EmbeddingAnalyzer();

// 基本統計を取得
$summary = $trend_analyzer->getUserReadingSummary($user_id);

// embeddingベースの分析
$clusters = [];
$diversity_score = 0;

if ($view_mode === 'clusters' || $view_mode === 'overview') {
    // キャッシュキー
    $cache_key = "reading_insights_clusters_{$user_id}_" . date('Ymd');
    $cache_file = "/tmp/{$cache_key}.json";
    
    // キャッシュが存在し、新しい場合は使用
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < 3600)) {
        $cached_data = json_decode(file_get_contents($cache_file), true);
        $clusters = $cached_data['clusters'] ?? [];
        $diversity_score = $cached_data['diversity_score'] ?? 0;
    } else {
        // クラスタリング分析
        $clusters = $embedding_analyzer->analyzeUserReadingClusters($user_id, 6);
        
        // 多様性スコア
        $diversity_score = $embedding_analyzer->calculateEmbeddingDiversity($user_id);
        
        // キャッシュに保存
        file_put_contents($cache_file, json_encode([
            'clusters' => $clusters,
            'diversity_score' => $diversity_score,
            'generated_at' => time()
        ]));
    }
}

// 読書統計データを取得（overview用）
if ($view_mode === 'overview') {
    // 全体の統計データを取得
    function getReadingStats($user_id) {
        global $g_db;
        
        $stats = [
            'total_books' => 0,
            'total_pages' => 0,
            'finished_books' => 0,
            'reading_books' => 0,
            'monthly_data' => [],
            'yearly_data' => [],
            'rating_distribution' => [],
            'daily_pages' => [],
            'daily_books' => [],
            'cumulative_pages' => [],
            'cumulative_books' => []
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
        
        // 日別データ（過去30日）
        for ($i = 29; $i >= 0; $i--) {
            $target_date = date('Y-m-d', strtotime("-$i days"));
            
            // ページ数
            $daily_pages_sql = "SELECT SUM(page) as pages 
                               FROM b_book_event 
                               WHERE user_id = ? 
                               AND DATE(event_date) = ?";
            
            $result = $g_db->getOne($daily_pages_sql, [$user_id, $target_date]);
            $stats['daily_pages'][$target_date] = !DB::isError($result) && $result ? (int)$result : 0;
            
            // 冊数（その日に読了した本の数）
            $daily_books_sql = "SELECT COUNT(*) as count 
                               FROM b_book_list 
                               WHERE user_id = ? 
                               AND status = " . READING_FINISH . "
                               AND finished_date = ?";
            
            $result = $g_db->getOne($daily_books_sql, [$user_id, $target_date]);
            $stats['daily_books'][$target_date] = !DB::isError($result) && $result ? (int)$result : 0;
        }
        
        // 累積データを計算
        $cumulative_pages_total = 0;
        $cumulative_books_total = 0;
        
        foreach ($stats['daily_pages'] as $date => $pages) {
            $cumulative_pages_total += $pages;
            $stats['cumulative_pages'][$date] = $cumulative_pages_total;
            
            $cumulative_books_total += $stats['daily_books'][$date];
            $stats['cumulative_books'][$date] = $cumulative_books_total;
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
            for ($i = 1; $i <= 5; $i++) {
                $stats['rating_distribution'][$i] = 0;
            }
            foreach ($result as $row) {
                $stats['rating_distribution'][$row['rating']] = (int)$row['count'];
            }
        }
        
        return $stats;
    }
    
    $stats = getReadingStats($user_id);
}

// 読書ペース分析データを取得
if ($view_mode === 'pace') {
    require_once(__DIR__ . '/library/reading_pace_analysis.php');
    $hourly_pattern = getHourlyReadingPattern($user_id);
    $weekly_trend = getWeeklyReadingTrend($user_id);
    $speed_by_genre = getReadingSpeedByGenre($user_id);
    $completion_analysis = getCompletionRateAnalysis($user_id);
    $cycle_analysis = getReadingCycleAnalysis($user_id);
    $pace_prediction = predictReadingPace($user_id);
    $habits_summary = getReadingHabitsSummary($user_id);
    
    // 月間目標情報を取得
    require_once(__DIR__ . '/library/monthly_goals.php');
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
}

// 類似読者を探す（自分のデータの場合のみ）
$similar_readers = [];
if ($is_my_insights) {
    $similar_readers = $trend_analyzer->findSimilarReaders($user_id, 5);
}

// ページメタ情報
$g_meta_description = 'AI分析による読書インサイト。レビューの内容から読書傾向を自動分類し、新たな発見をサポートします。';
$g_meta_keyword = '読書インサイト,AI分析,読書傾向,レビュー分析,ReadNest';

// テンプレートを読み込み
include(getTemplatePath('t_reading_insights.php'));
?>