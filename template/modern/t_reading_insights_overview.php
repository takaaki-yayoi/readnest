<?php
// 概要タブ用のコンテンツ（元の読書統計の内容）

// Chart.js用のデータ準備
// 年別データの処理
$yearly_labels = [];
$yearly_books = [];
$yearly_pages = [];
$yearly_cumulative_books = [];
$yearly_cumulative_pages = [];
$cumulative_books_total = 0;
$cumulative_pages_total = 0;

foreach ($stats['yearly_data'] as $year => $data) {
    $yearly_labels[] = $year;
    $books = isset($data['books']) ? $data['books'] : 0;
    $pages = isset($data['pages']) ? $data['pages'] : 0;
    
    $yearly_books[] = $books;
    $yearly_pages[] = $pages;
    
    // 累積値を計算
    $cumulative_books_total += $books;
    $cumulative_pages_total += $pages;
    $yearly_cumulative_books[] = $cumulative_books_total;
    $yearly_cumulative_pages[] = $cumulative_pages_total;
}

// 月別データ
$monthly_labels = [];
$monthly_books = [];
$monthly_pages = [];
foreach ($stats['monthly_data'] as $month => $data) {
    $monthly_labels[] = $month;
    $monthly_books[] = isset($data['books']) ? $data['books'] : 0;
    $monthly_pages[] = isset($data['pages']) ? $data['pages'] : 0;
}

// 日別データ
$daily_dates = array_keys($stats['daily_pages']);
$daily_labels = array_map(function($date) {
    return date('n/j', strtotime($date));
}, $daily_dates);
$daily_pages = array_values($stats['daily_pages']);
$daily_books = array_values($stats['daily_books']);
$cumulative_pages = array_values($stats['cumulative_pages']);
$cumulative_books = array_values($stats['cumulative_books']);
?>

<!-- サマリーカード -->
<div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-book text-2xl text-blue-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-xs font-medium text-gray-500">総登録数</p>
                <p class="text-xl font-bold text-gray-900"><?php echo number_format($stats['total_books']); ?>冊</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-2xl text-green-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-xs font-medium text-gray-500">読了</p>
                <p class="text-xl font-bold text-gray-900"><?php echo number_format($stats['finished_books']); ?>冊</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-book-reader text-2xl text-orange-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-xs font-medium text-gray-500">読書中</p>
                <p class="text-xl font-bold text-gray-900"><?php echo number_format($stats['reading_books']); ?>冊</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-file-alt text-2xl text-purple-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-xs font-medium text-gray-500">総ページ数</p>
                <p class="text-xl font-bold text-gray-900"><?php echo number_format($stats['total_pages']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- 読書冊数グラフ -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold mb-4">📚 読書冊数</h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div>
            <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-alt text-green-500 mr-2"></i>年別</h3>
            <div style="height: 250px;"><canvas id="yearlyBooksChart"></canvas></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar text-blue-500 mr-2"></i>月別（過去12ヶ月）</h3>
            <div style="height: 250px;"><canvas id="monthlyBooksChart"></canvas></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-day text-purple-500 mr-2"></i>日別（過去30日）</h3>
            <div style="height: 250px;"><canvas id="dailyBooksChart"></canvas></div>
        </div>
    </div>
</div>

<!-- 読書ページ数グラフ -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold mb-4">📖 読書ページ数</h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div>
            <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-alt text-green-500 mr-2"></i>年別</h3>
            <div style="height: 250px;"><canvas id="yearlyPagesChart"></canvas></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar text-blue-500 mr-2"></i>月別（過去12ヶ月）</h3>
            <div style="height: 250px;"><canvas id="monthlyPagesChart"></canvas></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-day text-purple-500 mr-2"></i>日別（過去30日）</h3>
            <div style="height: 250px;"><canvas id="dailyPagesChart"></canvas></div>
        </div>
    </div>
</div>

<!-- 累積グラフ -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold mb-4">📈 累積推移</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <h3 class="text-sm font-semibold mb-3">累積冊数（過去30日）</h3>
            <div style="height: 250px;"><canvas id="cumulativeBooksChart"></canvas></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold mb-3">累積ページ数（過去30日）</h3>
            <div style="height: 250px;"><canvas id="cumulativePagesChart"></canvas></div>
        </div>
    </div>
</div>

<!-- 評価分布 -->
<?php if (!empty($stats['rating_distribution'])): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold mb-4">⭐ 評価分布</h2>
    <div class="grid grid-cols-5 gap-4">
        <?php for ($rating = 5; $rating >= 1; $rating--): ?>
        <div class="text-center">
            <div class="text-2xl mb-2"><?php echo str_repeat('★', $rating); ?></div>
            <div class="text-3xl font-bold text-gray-800">
                <?php echo $stats['rating_distribution'][$rating] ?? 0; ?>
            </div>
            <div class="text-sm text-gray-500">冊</div>
        </div>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- AIクラスタサマリー（追加機能） -->
<?php if (!empty($clusters)): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-semibold mb-4">🤖 AIが発見した読書パターン</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach (array_slice($clusters, 0, 3) as $cluster): ?>
        <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
            <h3 class="font-semibold mb-2"><?php echo html($cluster['name']); ?></h3>
            <div class="text-sm text-gray-600 mb-2">
                <?php echo $cluster['size']; ?>冊 | ★<?php echo number_format($cluster['avg_rating'], 1); ?>
            </div>
            <?php if (!empty($cluster['keywords'])): ?>
            <div class="flex flex-wrap gap-1">
                <?php foreach (array_slice($cluster['keywords'], 0, 3) as $keyword): ?>
                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded">
                    <?php echo html($keyword); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (count($clusters) > 3): ?>
    <div class="text-center mt-4">
        <a href="?mode=clusters<?php echo !$is_my_insights ? '&user=' . $user_id : ''; ?>" 
           class="text-purple-600 hover:text-purple-800">
            全<?php echo count($clusters); ?>パターンを見る →
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
// Chart.js グラフ設定
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { precision: 0 }
        }
    }
};

// 年別読書冊数
new Chart(document.getElementById('yearlyBooksChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($yearly_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($yearly_books); ?>,
            backgroundColor: 'rgba(34, 197, 94, 0.8)'
        }]
    },
    options: chartOptions
});

// 月別読書冊数
new Chart(document.getElementById('monthlyBooksChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($m) {
            return date('n月', strtotime($m));
        }, $monthly_labels)); ?>,
        datasets: [{
            data: <?php echo json_encode($monthly_books); ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.8)'
        }]
    },
    options: chartOptions
});

// 日別読書冊数
new Chart(document.getElementById('dailyBooksChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($daily_books); ?>,
            backgroundColor: 'rgba(147, 51, 234, 0.8)'
        }]
    },
    options: chartOptions
});

// 年別ページ数
new Chart(document.getElementById('yearlyPagesChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($yearly_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($yearly_pages); ?>,
            borderColor: 'rgba(34, 197, 94, 1)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4
        }]
    },
    options: chartOptions
});

// 月別ページ数
new Chart(document.getElementById('monthlyPagesChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($m) {
            return date('n月', strtotime($m));
        }, $monthly_labels)); ?>,
        datasets: [{
            data: <?php echo json_encode($monthly_pages); ?>,
            borderColor: 'rgba(59, 130, 246, 1)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
        }]
    },
    options: chartOptions
});

// 日別ページ数
new Chart(document.getElementById('dailyPagesChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($daily_pages); ?>,
            borderColor: 'rgba(147, 51, 234, 1)',
            backgroundColor: 'rgba(147, 51, 234, 0.1)',
            tension: 0.4
        }]
    },
    options: chartOptions
});

// 累積冊数
new Chart(document.getElementById('cumulativeBooksChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($cumulative_books); ?>,
            borderColor: 'rgba(251, 146, 60, 1)',
            backgroundColor: 'rgba(251, 146, 60, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: chartOptions
});

// 累積ページ数
new Chart(document.getElementById('cumulativePagesChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($cumulative_pages); ?>,
            borderColor: 'rgba(236, 72, 153, 1)',
            backgroundColor: 'rgba(236, 72, 153, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: chartOptions
});
</script>