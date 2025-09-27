<?php
if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
    die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<style>
.heatmap-cell {
    width: 24px;
    height: 24px;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.2s;
}

.heatmap-cell:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* analysis-card styles removed - using Tailwind classes instead */

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #10b981;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
}

@media (prefers-color-scheme: dark) {
    .dark .stat-label {
        color: #9ca3af;
    }
}

.progress-ring {
    transform: rotate(-90deg);
}

.progress-ring-circle {
    transition: stroke-dashoffset 0.5s ease;
}
</style>

<!-- ヘッダーセクション -->
<section class="bg-gradient-to-r from-purple-500 to-indigo-600 dark:from-gray-800 dark:to-gray-700 text-white py-4 sm:py-6 md:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-1 sm:mb-2">
                    <i class="fas fa-chart-line mr-2 sm:mr-3 text-lg sm:text-2xl"></i>
                    読書ペース分析
                </h1>
                <p class="text-sm sm:text-lg md:text-xl text-white opacity-90">
                    あなたの読書習慣を詳細に分析します
                </p>
            </div>
        </div>
    </div>
</section>

<!-- サマリーセクション -->
<section class="py-6 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 月間目標セクション -->
        <?php if (isset($monthly_goal_info) && $monthly_goal_info['goal'] > 0): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex flex-col sm:flex-row items-center justify-between">
                <div class="mb-4 sm:mb-0">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        <i class="fas fa-bullseye text-indigo-600 mr-2"></i>
                        今月の読書目標
                    </h3>
                    <div class="flex items-center gap-4">
                        <div>
                            <span class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?php echo $books_read; ?></span>
                            <span class="text-lg text-gray-600 dark:text-gray-400"> / <?php echo $monthly_goal_info['goal']; ?>冊</span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            達成率: <?php echo round(($books_read / $monthly_goal_info['goal']) * 100); ?>%
                        </div>
                    </div>
                </div>
                <div class="text-center sm:text-right">
                    <div class="text-sm text-gray-600 mb-1">月間目標達成に必要:</div>
                    <div class="flex items-center justify-center sm:justify-end">
                        <span class="font-semibold text-lg">
                            <?php if ($pace_status === 'completed'): ?>
                                <span class="text-green-600">達成済み！</span>
                            <?php elseif ($required_pace >= 1): ?>
                                日<?php echo number_format($required_pace, 1); ?>冊
                            <?php elseif ($required_pace > 0): ?>
                                <?php echo ceil(1 / $required_pace); ?>日に1冊
                            <?php else: ?>
                                達成済み
                            <?php endif; ?>
                        </span>
                        <?php if ($pace_status !== 'completed'): ?>
                        <span class="ml-3">
                            <?php if ($pace_status === 'ahead'): ?>
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span class="text-green-600 text-xs ml-1">順調</span>
                            <?php elseif ($pace_status === 'behind'): ?>
                                <i class="fas fa-exclamation-circle text-yellow-500"></i>
                                <span class="text-yellow-600 text-xs ml-1">要加速</span>
                            <?php else: ?>
                                <i class="fas fa-minus-circle text-blue-500"></i>
                                <span class="text-blue-600 text-xs ml-1">標準</span>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        現在のペース: 日<?php echo number_format($current_pace, 1); ?>冊
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- 読書タイプ -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="text-4xl mb-3">
                    <?php if ($habits_summary['reading_type'] === '朝型'): ?>
                        <i class="fas fa-sun text-yellow-500"></i>
                    <?php elseif ($habits_summary['reading_type'] === '夜型'): ?>
                        <i class="fas fa-moon text-indigo-600"></i>
                    <?php else: ?>
                        <i class="fas fa-cloud-sun text-blue-500"></i>
                    <?php endif; ?>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">あなたの読書タイプ</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $habits_summary['reading_type']; ?></p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    最も活発な時間帯：
                    <?php foreach ($habits_summary['active_hours'] as $i => $hour): ?>
                        <?php echo $hour['hour']; ?>時<?php echo $i < count($habits_summary['active_hours']) - 1 ? '、' : ''; ?>
                    <?php endforeach; ?>
                </p>
            </div>
            
            <!-- 年間予測 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="text-4xl mb-3">
                    <i class="fas fa-book text-emerald-500"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">年間読書予測</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $pace_prediction['prediction_30days']; ?>冊</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    現在のペース：月<?php echo $pace_prediction['monthly_pace_30days']; ?>冊
                </p>
            </div>
            
            <!-- 完読率 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="text-4xl mb-3">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">総合完読率</h3>
                <?php 
                $completion_rate = $completion_analysis['overall']['total'] > 0 
                    ? round($completion_analysis['overall']['completed'] / $completion_analysis['overall']['total'] * 100) 
                    : 0;
                ?>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $completion_rate; ?>%</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    <?php echo $completion_analysis['overall']['completed']; ?>冊完読 / <?php echo $completion_analysis['overall']['total']; ?>冊
                </p>
            </div>
        </div>
    </div>
</section>

<!-- メインコンテンツ -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 時間帯別読書パターン -->
        <div class="mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <i class="fas fa-clock text-purple-600"></i>
                    時間帯別読書パターン（過去90日）
                </h3>
                
                <div class="overflow-x-auto">
                    <div class="w-full flex justify-center">
                    <div class="inline-block">
                        <!-- 時間ラベル -->
                        <div class="flex mb-2">
                            <div class="w-12"></div>
                            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                            <div class="w-6 text-center text-xs text-gray-600 dark:text-gray-400">
                                <?php echo $hour; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <!-- ヒートマップ -->
                        <?php 
                        $day_labels = ['', '日', '月', '火', '水', '木', '金', '土'];
                        $max_count = 0;
                        foreach ($hourly_pattern as $hour_data) {
                            foreach ($hour_data as $count) {
                                $max_count = max($max_count, $count);
                            }
                        }
                        ?>
                        
                        <?php for ($dow = 1; $dow <= 7; $dow++): ?>
                        <div class="flex items-center <?php echo $dow === 1 ? 'mt-4' : ''; ?>">
                            <div class="w-12 text-right pr-2 text-sm text-gray-600 dark:text-gray-400">
                                <?php echo $day_labels[$dow]; ?>
                            </div>
                            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                            <?php
                            $count = $hourly_pattern[$hour][$dow] ?? 0;
                            $intensity = $max_count > 0 ? ($count / $max_count) : 0;
                            
                            $bg_class = 'bg-gray-100 dark:bg-gray-800';
                            if ($intensity > 0) {
                                if ($intensity > 0.8) $bg_class = 'bg-purple-600 dark:bg-purple-500';
                                elseif ($intensity > 0.6) $bg_class = 'bg-purple-500 dark:bg-purple-600';
                                elseif ($intensity > 0.4) $bg_class = 'bg-purple-400 dark:bg-purple-700';
                                elseif ($intensity > 0.2) $bg_class = 'bg-purple-300 dark:bg-purple-800';
                                else $bg_class = 'bg-purple-200 dark:bg-purple-900';
                            }
                            ?>
                            <div class="heatmap-cell <?php echo $bg_class; ?> relative group"
                                 data-count="<?php echo $count; ?>">
                                <?php if ($count > 0): ?>
                                <div class="absolute <?php echo $dow === 1 ? 'top-full mt-2' : 'bottom-full mb-2'; ?> left-1/2 transform -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                                    <?php echo $day_labels[$dow]; ?>曜 <?php echo $hour; ?>時: <?php echo $count; ?>回
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <?php endfor; ?>
                        
                        <!-- 凡例 -->
                        <div class="mt-4 flex items-center justify-center gap-4 text-sm">
                            <span class="text-gray-600 dark:text-gray-400">少ない</span>
                            <div class="flex items-center gap-1">
                                <div class="w-4 h-4 bg-gray-100 dark:bg-gray-800 rounded"></div>
                                <div class="w-4 h-4 bg-purple-200 dark:bg-purple-900 rounded"></div>
                                <div class="w-4 h-4 bg-purple-300 dark:bg-purple-800 rounded"></div>
                                <div class="w-4 h-4 bg-purple-400 dark:bg-purple-700 rounded"></div>
                                <div class="w-4 h-4 bg-purple-500 dark:bg-purple-600 rounded"></div>
                                <div class="w-4 h-4 bg-purple-600 dark:bg-purple-500 rounded"></div>
                            </div>
                            <span class="text-gray-600 dark:text-gray-400">多い</span>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 曜日別読書傾向 -->
        <div class="mb-8">
            <!-- 曜日別読書傾向 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <i class="fas fa-calendar-week text-blue-600"></i>
                    曜日別読書傾向
                </h3>
                
                <?php
                $max_events = 0;
                foreach ($weekly_trend as $day) {
                    $max_events = max($max_events, $day['avg_events_per_day']);
                }
                ?>
                
                <div class="space-y-3">
                    <?php foreach ($weekly_trend as $day): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-8 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <?php echo $day['day_name']; ?>
                        </div>
                        <div class="flex-1">
                            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-6 relative overflow-hidden">
                                <?php $width = $max_events > 0 ? ($day['avg_events_per_day'] / $max_events * 100) : 0; ?>
                                <div class="bg-blue-500 h-full rounded-full transition-all duration-500"
                                     style="width: <?php echo $width; ?>%"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                    平均<?php echo $day['avg_events_per_day']; ?>回/日
                                </div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 w-20 text-right">
                            <?php echo $day['book_count']; ?>冊
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php
                // 平日と週末の比較
                $weekday_avg = 0;
                $weekend_avg = 0;
                $weekday_count = 0;
                $weekend_count = 0;
                
                foreach ($weekly_trend as $day) {
                    if ($day['day_of_week'] == 1 || $day['day_of_week'] == 7) {
                        $weekend_avg += $day['avg_events_per_day'];
                        $weekend_count++;
                    } else {
                        $weekday_avg += $day['avg_events_per_day'];
                        $weekday_count++;
                    }
                }
                
                $weekday_avg = $weekday_count > 0 ? round($weekday_avg / $weekday_count, 1) : 0;
                $weekend_avg = $weekend_count > 0 ? round($weekend_avg / $weekend_count, 1) : 0;
                ?>
                
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">平日平均</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100"><?php echo $weekday_avg; ?>回/日</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">週末平均</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100"><?php echo $weekend_avg; ?>回/日</p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- 完読率分析 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- 全体の完読率 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-green-600"></i>
                    読書状況の内訳
                </h3>
                
                <div class="relative w-48 h-48 mx-auto">
                    <?php
                    $total = $completion_analysis['overall']['total'];
                    $completed = $completion_analysis['overall']['completed'];
                    $reading = $completion_analysis['overall']['reading'];
                    $not_started = $completion_analysis['overall']['not_started'];
                    
                    $completed_pct = $total > 0 ? ($completed / $total * 100) : 0;
                    $reading_pct = $total > 0 ? ($reading / $total * 100) : 0;
                    $not_started_pct = $total > 0 ? ($not_started / $total * 100) : 0;
                    ?>
                    
                    <canvas id="completionChart" width="192" height="192"></canvas>
                </div>
                
                <div class="mt-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">完読</span>
                        </div>
                        <span class="text-sm font-medium"><?php echo $completed; ?>冊</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">読書中</span>
                        </div>
                        <span class="text-sm font-medium"><?php echo $reading; ?>冊</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">未読</span>
                        </div>
                        <span class="text-sm font-medium"><?php echo $not_started; ?>冊</span>
                    </div>
                </div>
            </div>
            
            
            <!-- ページ数別完読率 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <i class="fas fa-book-open text-purple-600"></i>
                    ページ数別完読率
                </h3>
                
                <?php if (!empty($completion_analysis['by_pages'])): ?>
                <div class="space-y-3">
                    <?php foreach ($completion_analysis['by_pages'] as $category): ?>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 dark:text-gray-300"><?php echo $category['page_category']; ?></span>
                            <span class="font-medium"><?php echo $category['completion_rate']; ?>%</span>
                        </div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                            <div class="bg-purple-500 h-full rounded-full transition-all duration-500"
                                 style="width: <?php echo $category['completion_rate']; ?>%"></div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <?php echo $category['completed']; ?>/<?php echo $category['total']; ?>冊
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-600 dark:text-gray-400 text-center py-8">
                    データがまだ不足しています
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 読書サイクルと予測 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 読書サイクル分析 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <i class="fas fa-sync-alt text-teal-600"></i>
                    読書サイクル分析
                </h3>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center p-4 bg-teal-50 dark:bg-teal-900/30 rounded-lg">
                        <div class="stat-value text-teal-600">
                            <?php echo $cycle_analysis['avg_streak_length']; ?>日
                        </div>
                        <div class="stat-label">平均連続日数</div>
                    </div>
                    <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/30 rounded-lg">
                        <div class="stat-value text-orange-600">
                            <?php echo $cycle_analysis['avg_break_length']; ?>日
                        </div>
                        <div class="stat-label">平均休憩期間</div>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 dark:text-gray-300">最長連続記録</span>
                            <span class="font-bold text-teal-600">
                                <?php echo $cycle_analysis['max_streak_length']; ?>日
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 dark:text-gray-300">読書頻度</span>
                            <span class="font-medium">
                                <?php echo round($cycle_analysis['reading_frequency']); ?>%
                            </span>
                        </div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                            <div class="bg-teal-500 h-full rounded-full transition-all duration-500"
                                 style="width: <?php echo min(100, $cycle_analysis['reading_frequency']); ?>%"></div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            過去180日間の読書日の割合
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($cycle_analysis['streak_distribution'])): ?>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">連続日数の分布</p>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        <?php 
                        ksort($cycle_analysis['streak_distribution']);
                        foreach ($cycle_analysis['streak_distribution'] as $days => $count): 
                        ?>
                        <span class="inline-block mr-3 mb-1">
                            <?php echo $days; ?>日連続: <?php echo $count; ?>回
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 読書ペース予測 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-line text-emerald-600"></i>
                    読書ペース予測
                </h3>
                
                <div class="mb-4">
                    <div class="text-center p-4 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">今年の予測読書数</div>
                        <div class="text-3xl font-bold text-emerald-600 mb-2">
                            <?php echo $pace_prediction['prediction_30days']; ?>冊
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            現在<?php echo $pace_prediction['current_year_total']; ?>冊 
                            (残り<?php echo $pace_prediction['days_remaining']; ?>日)
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">予測モデル別</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">過去30日ベース</span>
                                <span class="font-medium"><?php echo $pace_prediction['prediction_30days']; ?>冊</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">過去90日ベース</span>
                                <span class="font-medium"><?php echo $pace_prediction['prediction_90days']; ?>冊</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">今年の平均ベース</span>
                                <span class="font-medium"><?php echo $pace_prediction['prediction_year_avg']; ?>冊</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">月間ペース</h4>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded p-2">
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    <?php echo $pace_prediction['monthly_pace_30days']; ?>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">過去30日</div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded p-2">
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    <?php echo $pace_prediction['monthly_pace_90days']; ?>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">過去90日</div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded p-2">
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    <?php echo $pace_prediction['monthly_pace_year']; ?>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">今年平均</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 完読率チャート
const ctx = document.getElementById('completionChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['完読', '読書中', '未読'],
        datasets: [{
            data: [
                <?php echo $completion_analysis['overall']['completed']; ?>,
                <?php echo $completion_analysis['overall']['reading']; ?>,
                <?php echo $completion_analysis['overall']['not_started']; ?>
            ],
            backgroundColor: [
                '#10b981',
                '#3b82f6',
                '#eab308'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

<?php
$d_content = ob_get_clean();

// 読書インサイトから呼ばれている場合はコンテンツのみ表示
if (isset($view_mode) && $view_mode === 'pace') {
    echo $d_content;
} else {
    // 単独ページとして表示する場合はベーステンプレートを使用
    include(getTemplatePath('t_base.php'));
}
?>