<?php
/**
 * レベル分布管理画面
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/achievement_system.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

$page_title = 'レベル分布';

// レベル別ユーザー数を取得
$level_distribution_sql = "
    SELECT 
        CASE 
            WHEN total_pages = 0 THEN 0
            WHEN total_pages < 100 THEN 1
            ELSE FLOOR(1 + (total_pages - 100) / (100 + (FLOOR(1 + (total_pages - 100) / (100 + (FLOOR(1 + (total_pages - 100) / 120) - 1) * 20)) - 1) * 20))
        END as calculated_level,
        COUNT(*) as user_count
    FROM (
        SELECT 
            u.user_id,
            COALESCE(SUM(bl.total_page), 0) as total_pages
        FROM b_user u
        LEFT JOIN b_book_list bl ON u.user_id = bl.user_id 
            AND bl.status IN (?, ?) 
            AND bl.total_page > 0
        WHERE u.status = 1
        GROUP BY u.user_id
    ) as user_pages
    GROUP BY calculated_level
    ORDER BY calculated_level ASC
";

$level_distribution = $g_db->getAll($level_distribution_sql, [READING_FINISH, READ_BEFORE], DB_FETCHMODE_ASSOC);

// より簡単な方法でレベル分布を取得
$simplified_sql = "
    SELECT 
        u.user_id,
        u.nickname,
        u.diary_policy,
        COALESCE(SUM(bl.total_page), 0) as total_pages
    FROM b_user u
    LEFT JOIN b_book_list bl ON u.user_id = bl.user_id 
        AND bl.status IN (?, ?) 
        AND bl.total_page > 0
    WHERE u.status = 1
    GROUP BY u.user_id
    ORDER BY total_pages DESC
";

$all_users = $g_db->getAll($simplified_sql, [READING_FINISH, READ_BEFORE], DB_FETCHMODE_ASSOC);

// レベル分布を計算
$level_counts = [];
$title_counts = [
    '読書初心者' => 0,    // Lv.1-4
    '本の虫' => 0,       // Lv.5-9
    '読書家' => 0,       // Lv.10-19
    '博識者' => 0,       // Lv.20-29
    '賢者' => 0,         // Lv.30-49
    '読書マスター' => 0,  // Lv.50-74
    '読書の達人' => 0,    // Lv.75-99
    '読書の神' => 0       // Lv.100+
];

$level_ranges = [
    '1-9' => 0,
    '10-19' => 0,
    '20-29' => 0,
    '30-49' => 0,
    '50-74' => 0,
    '75-99' => 0,
    '100+' => 0
];

$total_users = 0;
$active_readers = 0; // レベル2以上
$high_level_users = []; // レベル100以上のユーザー

foreach ($all_users as $user) {
    $total_pages = intval($user['total_pages']);
    $level_info = getReadingLevel($total_pages);
    $level = $level_info['level'];
    
    $total_users++;
    
    if ($level >= 2) {
        $active_readers++;
    }
    
    // レベル100以上のユーザーを記録
    if ($level >= 100) {
        $high_level_users[] = [
            'user_id' => $user['user_id'],
            'nickname' => $user['nickname'],
            'level' => $level,
            'total_pages' => $total_pages,
            'is_public' => $user['diary_policy'] == 1
        ];
    }
    
    // 個別レベルカウント
    if (!isset($level_counts[$level])) {
        $level_counts[$level] = 0;
    }
    $level_counts[$level]++;
    
    // 称号別カウント
    if ($level < 5) {
        $title_counts['読書初心者']++;
    } elseif ($level < 10) {
        $title_counts['本の虫']++;
    } elseif ($level < 20) {
        $title_counts['読書家']++;
    } elseif ($level < 30) {
        $title_counts['博識者']++;
    } elseif ($level < 50) {
        $title_counts['賢者']++;
    } elseif ($level < 75) {
        $title_counts['読書マスター']++;
    } elseif ($level < 100) {
        $title_counts['読書の達人']++;
    } else {
        $title_counts['読書の神']++;
    }
    
    // レベル範囲別カウント
    if ($level < 10) {
        $level_ranges['1-9']++;
    } elseif ($level < 20) {
        $level_ranges['10-19']++;
    } elseif ($level < 30) {
        $level_ranges['20-29']++;
    } elseif ($level < 50) {
        $level_ranges['30-49']++;
    } elseif ($level < 75) {
        $level_ranges['50-74']++;
    } elseif ($level < 100) {
        $level_ranges['75-99']++;
    } else {
        $level_ranges['100+']++;
    }
}

// グラフ用データの準備
$chart_labels = [];
$chart_data = [];
for ($i = 1; $i <= 100; $i++) {
    if (isset($level_counts[$i]) && $level_counts[$i] > 0) {
        $chart_labels[] = "Lv.$i";
        $chart_data[] = $level_counts[$i];
    }
}

// 100以上のレベルもグラフに追加
foreach ($level_counts as $level => $count) {
    if ($level > 100) {
        $chart_labels[] = "Lv.$level";
        $chart_data[] = $count;
    }
}

include('layout/header.php');
?>

<div class="max-w-7xl mx-auto">
    <!-- 統計サマリー -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">総ユーザー数</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_users); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-book-reader text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">アクティブ読者</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($active_readers); ?></p>
                    <p class="text-xs text-gray-500">レベル2以上</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-percentage text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">アクティブ率</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php echo $total_users > 0 ? round($active_readers / $total_users * 100, 1) : 0; ?>%
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-full">
                    <i class="fas fa-crown text-yellow-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">レベル100以上</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo count($high_level_users); ?></p>
                    <p class="text-xs text-gray-500">読書の神</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- レベル分布グラフ -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">レベル分布グラフ</h3>
        <div class="h-64">
            <canvas id="levelDistributionChart"></canvas>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- 称号別分布 -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-900">称号別分布</h3>
            </div>
            <div class="p-6">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-gray-600 text-sm">
                            <th class="pb-3">称号</th>
                            <th class="pb-3 text-center">レベル</th>
                            <th class="pb-3 text-right">人数</th>
                            <th class="pb-3 text-right">割合</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $titles = [
                            ['name' => '読書初心者', 'range' => '1-4', 'icon' => 'book-open-reader', 'color' => 'gray'],
                            ['name' => '本の虫', 'range' => '5-9', 'icon' => 'book', 'color' => 'blue'],
                            ['name' => '読書家', 'range' => '10-19', 'icon' => 'book-bookmark', 'color' => 'green'],
                            ['name' => '博識者', 'range' => '20-29', 'icon' => 'graduation-cap', 'color' => 'purple'],
                            ['name' => '賢者', 'range' => '30-49', 'icon' => 'scroll', 'color' => 'indigo'],
                            ['name' => '読書マスター', 'range' => '50-74', 'icon' => 'medal', 'color' => 'yellow'],
                            ['name' => '読書の達人', 'range' => '75-99', 'icon' => 'trophy', 'color' => 'orange'],
                            ['name' => '読書の神', 'range' => '100+', 'icon' => 'crown', 'color' => 'red']
                        ];
                        foreach ($titles as $title):
                            $count = $title_counts[$title['name']];
                            $percentage = $total_users > 0 ? round($count / $total_users * 100, 1) : 0;
                        ?>
                        <tr class="border-t">
                            <td class="py-3">
                                <div class="flex items-center">
                                    <i class="fas fa-<?php echo $title['icon']; ?> text-<?php echo $title['color']; ?>-500 mr-2"></i>
                                    <?php echo $title['name']; ?>
                                </div>
                            </td>
                            <td class="py-3 text-center text-sm text-gray-600">
                                Lv.<?php echo $title['range']; ?>
                            </td>
                            <td class="py-3 text-right">
                                <?php echo number_format($count); ?>
                            </td>
                            <td class="py-3 text-right text-sm text-gray-600">
                                <?php echo $percentage; ?>%
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- レベル範囲別分布 -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-900">レベル範囲別分布</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach ($level_ranges as $range => $count): 
                        $percentage = $total_users > 0 ? round($count / $total_users * 100, 1) : 0;
                    ?>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">レベル <?php echo $range; ?></span>
                            <span class="text-sm text-gray-600"><?php echo number_format($count); ?>人 (<?php echo $percentage; ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 高レベルユーザー一覧 -->
    <?php if (!empty($high_level_users)): ?>
    <div class="bg-white rounded-lg shadow mt-8">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">レベル100以上のユーザー</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-gray-600 text-sm border-b">
                            <th class="pb-3">ユーザー</th>
                            <th class="pb-3 text-center">レベル</th>
                            <th class="pb-3 text-right">総読書ページ数</th>
                            <th class="pb-3 text-center">公開設定</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // レベルの高い順にソート
                        usort($high_level_users, function($a, $b) {
                            return $b['level'] - $a['level'];
                        });
                        
                        foreach ($high_level_users as $user): 
                        ?>
                        <tr class="border-t">
                            <td class="py-3">
                                <?php if ($user['is_public']): ?>
                                    <a href="/profile.php?user_id=<?php echo $user['user_id']; ?>" 
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-800">
                                        <?php echo htmlspecialchars($user['nickname']); ?>
                                        <i class="fas fa-external-link-alt text-xs ml-1"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($user['nickname']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-crown mr-1"></i>
                                    Lv.<?php echo $user['level']; ?>
                                </span>
                            </td>
                            <td class="py-3 text-right">
                                <?php echo number_format($user['total_pages']); ?>
                            </td>
                            <td class="py-3 text-center">
                                <?php if ($user['is_public']): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                        公開
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        非公開
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// レベル分布グラフ
const ctx = document.getElementById('levelDistributionChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'ユーザー数',
            data: <?php echo json_encode($chart_data); ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

<?php include('layout/footer.php'); ?>