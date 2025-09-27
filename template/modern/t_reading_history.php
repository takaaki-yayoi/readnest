<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

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

$yearly_labels = json_encode($yearly_labels);
$yearly_books = json_encode($yearly_books);
$yearly_pages = json_encode($yearly_pages);
$yearly_cumulative_books = json_encode($yearly_cumulative_books);
$yearly_cumulative_pages = json_encode($yearly_cumulative_pages);

// 月別データ（古い順から新しい順へ）と累積値の計算
$monthly_labels = [];
$monthly_books = [];
$monthly_pages = [];
$monthly_cumulative_books = [];
$monthly_cumulative_pages = [];
$cumulative_books_total = 0;
$cumulative_pages_total = 0;

foreach ($stats['monthly_data'] as $month => $data) {
    $monthly_labels[] = $month;
    $books = isset($data['books']) ? $data['books'] : 0;
    $pages = isset($data['pages']) ? $data['pages'] : 0;
    
    $monthly_books[] = $books;
    $monthly_pages[] = $pages;
    
    // 累積値を計算
    $cumulative_books_total += $books;
    $cumulative_pages_total += $pages;
    $monthly_cumulative_books[] = $cumulative_books_total;
    $monthly_cumulative_pages[] = $cumulative_pages_total;
}

$monthly_labels = json_encode($monthly_labels);
$monthly_books = json_encode($monthly_books);
$monthly_pages = json_encode($monthly_pages);
$monthly_cumulative_books = json_encode($monthly_cumulative_books);
$monthly_cumulative_pages = json_encode($monthly_cumulative_pages);

// 日別データ（日付ラベル付き）
$daily_dates = array_keys($stats['daily_pages']);
$daily_labels = json_encode(array_map(function($date) {
    return date('n/j', strtotime($date));
}, $daily_dates));
$daily_dates_json = json_encode($daily_dates);
$daily_pages = json_encode(array_values($stats['daily_pages']));
$daily_books = json_encode(array_values($stats['daily_books']));

// 累積データ
$cumulative_pages = isset($stats['cumulative_pages']) ? json_encode(array_values($stats['cumulative_pages'])) : '[]';
$cumulative_books = isset($stats['cumulative_books']) ? json_encode(array_values($stats['cumulative_books'])) : '[]';

$rating_keys = array_keys($stats['rating_distribution']);
$rating_labels = json_encode(array_map(function($r) { return $r . '★'; }, $rating_keys));
$rating_values = json_encode(array_values($stats['rating_distribution']));
$rating_keys_json = json_encode($rating_keys);

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="/bookshelf.php" class="text-gray-500 hover:text-gray-700">本棚</a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-700">読書履歴・統計</span>
                </li>
            </ol>
        </nav>

        <!-- ヘッダー -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-chart-line text-blue-500 mr-2"></i>読書履歴・統計
            </h1>
            <p class="text-gray-600">
                あなたの読書記録を詳細に分析
            </p>
        </div>

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

        <!-- グラフセクション -->
        <div class="space-y-8 mb-8">
            <!-- 読書冊数 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">読書冊数</h2>
                <p class="text-sm text-gray-600 mb-6">
                    <i class="fas fa-hand-pointer mr-1"></i>
                    グラフの棒をクリックすると、その期間に読了した本の一覧が表示されます
                </p>
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
            
            <!-- 読書ページ数 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-6">読書ページ数</h2>
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
            
            <!-- 評価分布 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">評価分布</h2>
                <p class="text-sm text-gray-600 mb-6">
                    <i class="fas fa-hand-pointer mr-1"></i>
                    グラフの各セクションをクリックすると、その評価の本の一覧が表示されます
                </p>
                <div class="max-w-md mx-auto">
                    <div style="height: 300px;"><canvas id="ratingChart"></canvas></div>
                </div>
            </div>
        </div>

        <!-- 最近読んだ本 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">
                <i class="fas fa-history text-gray-600 mr-2"></i>最近読了した本
            </h2>
            <?php if (!empty($recent_books)): ?>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                <?php foreach ($recent_books as $book): ?>
                <a href="/book/<?php echo $book['book_id']; ?>" class="group">
                    <div class="aspect-[3/4] bg-gray-100 rounded overflow-hidden shadow-sm group-hover:shadow-md transition-shadow">
                        <img src="<?php echo html($book['image_url'] ?? $book['repo_image'] ?? '/img/no-image-book.png'); ?>" 
                             alt="<?php echo html($book['name']); ?>" 
                             class="w-full h-full object-cover">
                    </div>
                    <p class="mt-1 text-xs font-medium text-gray-900 line-clamp-1 group-hover:text-blue-600">
                        <?php echo html(mb_substr($book['name'], 0, 15)); ?>
                    </p>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500">まだ読了した本がありません</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* グラフのキャンバスにカーソルポインタを表示 */
#yearlyBooksChart,
#monthlyBooksChart,
#dailyBooksChart,
#ratingChart {
    cursor: pointer;
}
</style>

<!-- 本一覧モーダル -->
<div id="bookListModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 id="modalTitle" class="text-lg font-semibold"></h3>
                <button onclick="closeBookListModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="modalContent" class="p-4 overflow-y-auto" style="max-height: calc(80vh - 120px);">
                <!-- 動的にコンテンツが挿入されます -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// モーダル関連の関数
function showBookListModal(title, books) {
    const modal = document.getElementById('bookListModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    modalTitle.textContent = title;
    
    if (books.length === 0) {
        modalContent.innerHTML = '<p class="text-gray-500 text-center py-8">該当する本がありません</p>';
    } else {
        let html = '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">';
        books.forEach(book => {
            const imageUrl = book.image_url || book.repo_image || '/img/no-image-book.png';
            const bookName = book.name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
            html += `
                <a href="/book/${book.book_id}" class="group">
                    <div class="aspect-[3/4] bg-gray-100 rounded overflow-hidden shadow-sm group-hover:shadow-md transition-shadow">
                        <img src="${imageUrl}" 
                             alt="${bookName}" 
                             class="w-full h-full object-cover">
                    </div>
                    <p class="mt-1 text-xs font-medium text-gray-900 line-clamp-2 group-hover:text-blue-600">
                        ${bookName}
                    </p>
                    ${book.finished_date ? `<p class="text-xs text-gray-500">${book.finished_date}</p>` : ''}
                </a>
            `;
        });
        html += '</div>';
        modalContent.innerHTML = html;
    }
    
    modal.classList.remove('hidden');
}

function closeBookListModal() {
    document.getElementById('bookListModal').classList.add('hidden');
}

// モーダル外クリックで閉じる
document.getElementById('bookListModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBookListModal();
    }
});

// Ajaxで本一覧を取得
function fetchBooksByPeriod(period, value, callback) {
    fetch(`/api/get_books_by_period.php?period=${period}&value=${value}`)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => {
            console.error('Error:', error);
            callback([]);
        });
}

function fetchBooksByRating(rating, callback) {
    fetch(`/api/get_books_by_rating.php?rating=${rating}`)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => {
            console.error('Error:', error);
            callback([]);
        });
}
// 年別読書冊数グラフ
const yearlyBooksCtx = document.getElementById('yearlyBooksChart').getContext('2d');
const yearlyBooksChart = new Chart(yearlyBooksCtx, {
    type: 'bar',
    data: {
        labels: <?php echo $yearly_labels; ?>,
        datasets: [{
            label: '読了冊数',
            data: <?php echo $yearly_books; ?>,
            backgroundColor: 'rgba(34, 197, 94, 0.5)',
            borderColor: 'rgba(34, 197, 94, 1)',
            borderWidth: 1,
            order: 2
        }, {
            label: '累積冊数',
            data: <?php echo $yearly_cumulative_books; ?>,
            type: 'line',
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: 'rgb(34, 197, 94)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                ticks: {
                    stepSize: 10
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            }
        },
        onClick: (event, elements) => {
            if (elements.length > 0 && elements[0].datasetIndex === 0) {
                const index = elements[0].index;
                const year = yearlyBooksChart.data.labels[index];
                fetchBooksByPeriod('year', year, (books) => {
                    showBookListModal(`${year}年に読了した本`, books);
                });
            }
        }
    }
});

// 月別読書冊数グラフ
const monthlyBooksCtx = document.getElementById('monthlyBooksChart').getContext('2d');
const monthlyBooksChart = new Chart(monthlyBooksCtx, {
    type: 'bar',
    data: {
        labels: <?php echo $monthly_labels; ?>,
        datasets: [{
            label: '読了冊数',
            data: <?php echo $monthly_books; ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1,
            order: 2
        }, {
            label: '累積冊数',
            data: <?php echo $monthly_cumulative_books; ?>,
            type: 'line',
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: 'rgb(59, 130, 246)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            }
        },
        onClick: (event, elements) => {
            if (elements.length > 0 && elements[0].datasetIndex === 0) {
                const index = elements[0].index;
                const month = monthlyBooksChart.data.labels[index];
                fetchBooksByPeriod('month', month, (books) => {
                    showBookListModal(`${month}に読了した本`, books);
                });
            }
        }
    }
});

// 日別読書冊数グラフ（累積付き）
const dailyBooksCtx = document.getElementById('dailyBooksChart').getContext('2d');
const dailyBooksChart = new Chart(dailyBooksCtx, {
    type: 'bar',
    data: {
        labels: <?php echo $daily_labels; ?>,
        datasets: [{
            label: '読了冊数',
            data: <?php echo $daily_books; ?>,
            backgroundColor: 'rgba(147, 51, 234, 0.5)',
            borderColor: 'rgba(147, 51, 234, 1)',
            borderWidth: 1,
            yAxisID: 'y',
            order: 2
        }, {
            label: '累積冊数',
            data: <?php echo $cumulative_books; ?>,
            type: 'line',
            borderColor: 'rgb(168, 85, 247)',
            backgroundColor: 'rgba(168, 85, 247, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: 'rgb(168, 85, 247)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                },
                title: {
                    display: true,
                    text: '冊数'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        },
        onClick: (event, elements) => {
            if (elements.length > 0 && elements[0].datasetIndex === 0) {
                const index = elements[0].index;
                const dailyDates = <?php echo $daily_dates_json; ?>;
                const date = dailyDates[index];
                fetchBooksByPeriod('day', date, (books) => {
                    const formattedDate = new Date(date).toLocaleDateString('ja-JP');
                    showBookListModal(`${formattedDate}に読了した本`, books);
                });
            }
        }
    }
});

// 年別ページ数グラフ（累積付き）
const yearlyPagesCtx = document.getElementById('yearlyPagesChart').getContext('2d');
new Chart(yearlyPagesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo $yearly_labels; ?>,
        datasets: [{
            label: '総ページ数',
            data: <?php echo $yearly_pages; ?>,
            backgroundColor: 'rgba(34, 197, 94, 0.5)',
            borderColor: 'rgba(34, 197, 94, 1)',
            borderWidth: 1,
            order: 2
        }, {
            label: '累積ページ',
            data: <?php echo $yearly_cumulative_pages; ?>,
            type: 'line',
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: 'rgb(34, 197, 94)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.dataset.label === '累積ページ') {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + 'ページ';
                        }
                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + 'ページ';
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString();
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString();
                    }
                },
                title: {
                    display: true,
                    text: '累積'
                }
            }
        }
    }
});

// 月別ページ数グラフ（累積付き）
const monthlyPagesCtx = document.getElementById('monthlyPagesChart').getContext('2d');
new Chart(monthlyPagesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo $monthly_labels; ?>,
        datasets: [{
            label: 'ページ数',
            data: <?php echo $monthly_pages; ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1,
            order: 2
        }, {
            label: '累積ページ',
            data: <?php echo $monthly_cumulative_pages; ?>,
            type: 'line',
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: 'rgb(59, 130, 246)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            }
        }
    }
});

// 日別ページ数グラフ（累積付き）
const dailyPagesCtx = document.getElementById('dailyPagesChart').getContext('2d');
new Chart(dailyPagesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo $daily_labels; ?>,
        datasets: [{
            label: 'ページ数',
            data: <?php echo $daily_pages; ?>,
            backgroundColor: 'rgba(147, 51, 234, 0.5)',
            borderColor: 'rgba(147, 51, 234, 1)',
            borderWidth: 1,
            yAxisID: 'y',
            order: 2
        }, {
            label: '累積ページ',
            data: <?php echo $cumulative_pages; ?>,
            type: 'line',
            borderColor: 'rgb(168, 85, 247)',
            backgroundColor: 'rgba(168, 85, 247, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: 'rgb(168, 85, 247)',
            yAxisID: 'y1',
            order: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'ページ数'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: '累積'
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        }
    }
});

// 評価分布グラフ
const ratingCtx = document.getElementById('ratingChart').getContext('2d');
const ratingChart = new Chart(ratingCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo $rating_labels; ?>,
        datasets: [{
            data: <?php echo $rating_values; ?>,
            backgroundColor: [
                'rgba(251, 191, 36, 0.8)',
                'rgba(251, 146, 60, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(34, 197, 94, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                const ratingKeys = <?php echo $rating_keys_json; ?>;
                const rating = ratingKeys[index]; // 実際の評価値を取得
                fetchBooksByRating(rating, (books) => {
                    showBookListModal(`評価${rating}★の本`, books);
                });
            }
        }
    }
});
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
require_once(getTemplatePath('t_base.php'));
?>