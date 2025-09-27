/**
 * 読書進捗グラフ表示
 * Chart.js v4を使用
 */

// グラフの初期化
async function initReadingProgressChart(bookId, containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Chart container not found:', containerId);
        return;
    }
    
    // ローディング表示
    container.innerHTML = `
        <div class="flex items-center justify-center h-64">
            <div class="text-center">
                <div class="spinner mx-auto mb-4"></div>
                <p class="text-gray-600">グラフを読み込み中...</p>
            </div>
        </div>
    `;
    
    try {
        // APIからデータ取得
        const response = await fetch(`/api/reading_progress_api.php?book_id=${bookId}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // キャンバス要素を作成
        container.innerHTML = '<canvas id="readingChart"></canvas>';
        const ctx = document.getElementById('readingChart').getContext('2d');
        
        // Chart.jsの設定
        const config = {
            type: 'line',
            data: data.chart,
            options: {
                ...data.options,
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    ...data.options.plugins,
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const page = context.parsed.y;
                                const totalPage = data.book.total_page;
                                const percentage = Math.round((page / totalPage) * 100);
                                return `${page}ページ (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        };
        
        // グラフを描画
        new Chart(ctx, config);
        
    } catch (error) {
        console.error('Chart loading error:', error);
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-700">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    グラフの読み込みに失敗しました
                </p>
            </div>
        `;
    }
}

// 読書統計グラフ（月別）
async function initMonthlyStatsChart(userId, year, containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Chart container not found:', containerId);
        return;
    }
    
    try {
        // APIからデータ取得
        const response = await fetch(`/api/monthly_stats_api.php?user_id=${userId}&year=${year}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // キャンバス要素を作成
        container.innerHTML = '<canvas id="monthlyChart"></canvas>';
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        
        // Chart.jsの設定
        const config = {
            type: 'bar',
            data: {
                labels: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
                datasets: [
                    {
                        label: '読了',
                        data: data.finished,
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    },
                    {
                        label: '読書中',
                        data: data.reading,
                        backgroundColor: 'rgba(250, 204, 21, 0.8)',
                        borderColor: 'rgb(250, 204, 21)',
                        borderWidth: 1
                    },
                    {
                        label: '積読',
                        data: data.notStarted,
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: `${year}年の読書統計`
                    },
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: '月'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '冊数'
                        },
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        };
        
        // グラフを描画
        new Chart(ctx, config);
        
    } catch (error) {
        console.error('Chart loading error:', error);
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-700">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    グラフの読み込みに失敗しました
                </p>
            </div>
        `;
    }
}

// ジャンル別統計グラフ（ドーナツチャート）
async function initGenreStatsChart(userId, containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Chart container not found:', containerId);
        return;
    }
    
    try {
        // APIからデータ取得
        const response = await fetch(`/api/genre_stats_api.php?user_id=${userId}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // キャンバス要素を作成
        container.innerHTML = '<canvas id="genreChart"></canvas>';
        const ctx = document.getElementById('genreChart').getContext('2d');
        
        // カラーパレット
        const colors = [
            'rgba(34, 197, 94, 0.8)',
            'rgba(59, 130, 246, 0.8)',
            'rgba(236, 72, 153, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(168, 85, 247, 0.8)',
            'rgba(20, 184, 166, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(107, 114, 128, 0.8)'
        ];
        
        // Chart.jsの設定
        const config = {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: colors.slice(0, data.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'ジャンル別読書統計'
                    },
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    const dataset = data.datasets[0];
                                    const total = dataset.data.reduce((a, b) => a + b, 0);
                                    
                                    return data.labels.map((label, i) => {
                                        const value = dataset.data[i];
                                        const percentage = Math.round((value / total) * 100);
                                        
                                        return {
                                            text: `${label} (${percentage}%)`,
                                            fillStyle: dataset.backgroundColor[i],
                                            strokeStyle: dataset.borderColor,
                                            lineWidth: dataset.borderWidth,
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value}冊 (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        };
        
        // グラフを描画
        new Chart(ctx, config);
        
    } catch (error) {
        console.error('Chart loading error:', error);
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-700">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    グラフの読み込みに失敗しました
                </p>
            </div>
        `;
    }
}