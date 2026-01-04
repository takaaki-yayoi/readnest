<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// メインコンテンツを生成
ob_start();

// Chart.js用のデータ準備
$daily_labels = [];
$daily_pages = [];
$daily_books = [];
foreach ($report_data['daily_activity'] as $day) {
    $daily_labels[] = $day['day'] . '日';
    $daily_pages[] = $day['pages'];
    $daily_books[] = $day['books_finished'];
}

$stats = $report_data['statistics'];
$books = $report_data['books'];
?>

<style>
/* 月間レポート用カスタムスタイル */
.book-cover-wrapper {
    position: relative;
    width: 100%;
    padding-bottom: 150%;
    background: #f0f0f0;
    overflow: hidden;
}

.book-cover-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: white;
}

.dark .book-cover-img {
    background: rgb(31, 41, 55);
}

.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.book-item {
    transition: transform 0.2s ease;
}

.book-item:hover {
    transform: translateY(-4px);
}
</style>

<?php
// URLパス用
$url_user_path = !$is_my_report ? "/{$target_user_id}" : "";
?>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- ヘッダー -->
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold mb-2 text-gray-800 dark:text-gray-100">
            <i class="fas fa-calendar-alt mr-2 text-readnest-accent"></i>
            <?php if (!$is_my_report): ?>
            <?php echo html($display_nickname); ?>さんの月間読書レポート
            <?php else: ?>
            月間読書レポート
            <?php endif; ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
            <?php if (!$is_my_report): ?>
            <?php echo html($display_nickname); ?>さんの読書記録
            <?php else: ?>
            あなたの読書記録を月別に振り返り
            <?php endif; ?>
        </p>
    </div>

    <!-- 月ナビゲーション -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <div class="flex items-center justify-between">
            <a href="/report/<?php echo $prev_year; ?>/<?php echo $prev_month; ?><?php echo $url_user_path; ?>"
               class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                <i class="fas fa-chevron-left mr-2"></i>
                <span class="hidden sm:inline"><?php echo $prev_year; ?>年<?php echo $prev_month; ?>月</span>
                <span class="sm:hidden">前月</span>
            </a>

            <div class="text-center">
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-gray-100">
                    <?php echo $year; ?>年<?php echo $month; ?>月
                </h2>
                <?php if ($year == $current_year && $month == $current_month): ?>
                <span class="text-xs text-readnest-accent font-medium">今月</span>
                <?php endif; ?>
            </div>

            <?php if (!$is_next_future): ?>
            <a href="/report/<?php echo $next_year; ?>/<?php echo $next_month; ?><?php echo $url_user_path; ?>"
               class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                <span class="hidden sm:inline"><?php echo $next_year; ?>年<?php echo $next_month; ?>月</span>
                <span class="sm:hidden">次月</span>
                <i class="fas fa-chevron-right ml-2"></i>
            </a>
            <?php else: ?>
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 text-gray-400 rounded-lg cursor-not-allowed">
                <span class="hidden sm:inline">次月</span>
                <span class="sm:hidden">次月</span>
                <i class="fas fa-chevron-right ml-2"></i>
            </div>
            <?php endif; ?>
        </div>

        <!-- カレンダーへのリンク（自分のレポートのみ表示） -->
        <?php if ($is_my_report): ?>
        <div class="mt-3 text-center">
            <a href="/reading_calendar.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>"
               class="inline-flex items-center text-sm text-readnest-accent hover:text-readnest-primary transition-colors">
                <i class="fas fa-calendar-check mr-1"></i>この月のカレンダーを見る
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($report_data['has_data']): ?>

    <!-- 統計カード -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- 読了冊数 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-readnest-accent">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-book-reader text-2xl text-readnest-accent"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">読了冊数</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo $stats['books_finished']; ?><span class="text-sm font-normal ml-1">冊</span></p>
                </div>
            </div>
        </div>

        <!-- ページ数 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-file-alt text-2xl text-blue-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">読んだページ</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo number_format($stats['pages_read']); ?><span class="text-sm font-normal ml-1">p</span></p>
                </div>
            </div>
        </div>

        <!-- 日平均 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chart-line text-2xl text-purple-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">日平均</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo $stats['daily_average']; ?><span class="text-sm font-normal ml-1">p/日</span></p>
                </div>
            </div>
        </div>

        <!-- 目標達成 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 <?php echo $stats['goal_achieved'] ? 'border-green-500' : 'border-yellow-500'; ?>">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <?php if ($stats['goal_achieved']): ?>
                    <i class="fas fa-trophy text-2xl text-green-500"></i>
                    <?php elseif ($stats['goal'] > 0): ?>
                    <i class="fas fa-bullseye text-2xl text-yellow-500"></i>
                    <?php else: ?>
                    <i class="fas fa-flag text-2xl text-gray-400"></i>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">目標達成</p>
                    <?php if ($stats['goal'] > 0): ?>
                    <p class="text-2xl font-bold <?php echo $stats['goal_achieved'] ? 'text-green-600' : 'text-gray-800 dark:text-gray-100'; ?>">
                        <?php echo round($stats['goal_progress']); ?><span class="text-sm font-normal ml-1">%</span>
                    </p>
                    <p class="text-xs text-gray-400"><?php echo $stats['books_finished']; ?>/<?php echo $stats['goal']; ?>冊</p>
                    <?php else: ?>
                    <p class="text-lg text-gray-400">未設定</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 日別読書グラフ -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            <i class="fas fa-chart-bar mr-2 text-readnest-accent"></i>日別読書記録
        </h3>
        <div class="h-48 md:h-64">
            <canvas id="dailyChart"></canvas>
        </div>
    </div>

    <!-- 読了本リスト -->
    <?php if (!empty($books)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            <i class="fas fa-book mr-2 text-readnest-accent"></i>読了した本（<?php echo count($books); ?>冊）
        </h3>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($books as $book): ?>
            <a href="/book/<?php echo html($book['book_id']); ?>" class="book-item block group">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    <div class="book-cover-wrapper">
                        <?php
                        $bookCoverUrl = !empty($book['image_url']) ? $book['image_url'] : '/img/no-image-book.png';
                        ?>
                        <img src="<?php echo html($bookCoverUrl); ?>"
                             alt="<?php echo html($book['name']); ?>"
                             class="book-cover-img"
                             loading="lazy"
                             onerror="this.src='/img/no-image-book.png'">
                    </div>
                    <div class="p-2">
                        <div class="text-xs font-medium line-clamp-2 text-gray-800 dark:text-gray-100 group-hover:text-readnest-accent transition-colors">
                            <?php echo html($book['name']); ?>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 mt-1">
                            <?php echo html($book['author']); ?>
                        </div>
                        <?php if (!empty($book['rating']) && $book['rating'] > 0): ?>
                        <div class="text-yellow-500 text-xs mt-1">
                            <?php echo str_repeat('★', (int)$book['rating']); ?><?php echo str_repeat('☆', 5 - (int)$book['rating']); ?>
                        </div>
                        <?php endif; ?>
                        <div class="text-xs text-gray-400 mt-1">
                            <?php
                            $finished = $book['finished_date'] ?? $book['update_date'];
                            if ($finished) {
                                echo date('n/j', strtotime($finished)) . '読了';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- シェアセクション（自分のレポートのみ表示） -->
    <?php if ($is_my_report): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
            <i class="fas fa-share-alt mr-2 text-readnest-accent"></i>レポートをシェア
        </h3>

        <?php
        $share_text = "{$year}年{$month}月は{$stats['books_finished']}冊読みました！";
        if ($stats['pages_read'] > 0) {
            $share_text .= "（{$stats['pages_read']}ページ）";
        }
        $share_url = "https://readnest.jp/report/{$year}/{$month}/{$target_user_id}";
        $x_share_url = getXShareUrl($share_text, $share_url, ['読書記録', 'ReadNest']);
        $og_image_url = "/og-image/report/{$year}/{$month}/{$target_user_id}.png";
        ?>

        <div class="flex flex-wrap gap-3 items-center">
            <!-- Xシェアボタン -->
            <button id="xShareBtn"
                    data-share-url="<?php echo html($x_share_url); ?>"
                    data-og-image="<?php echo html($og_image_url); ?>"
                    class="flex items-center px-4 py-2 bg-black hover:bg-gray-800 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
                <span id="xShareBtnText">Xでシェア</span>
            </button>

            <!-- 画像ダウンロードリンク -->
            <a href="<?php echo html($og_image_url); ?>"
               download="reading_report_<?php echo $year; ?>_<?php echo $month; ?>.png"
               class="flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-lg transition-colors">
                <i class="fas fa-download mr-2"></i>画像を保存
            </a>
        </div>

        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            <i class="fas fa-info-circle mr-1"></i>シェア時に画像を自動生成します（初回は数秒かかります）
        </p>
    </div>
    <?php endif; ?>

    <?php else: ?>

    <!-- データなしの場合 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
        <div class="text-gray-400 mb-4">
            <i class="fas fa-book-open text-6xl"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-300 mb-2">
            この月の読書記録はありません
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">
            <?php if ($is_my_report): ?>
            <?php echo $year; ?>年<?php echo $month; ?>月に読了した本はまだ登録されていません。
            <?php else: ?>
            <?php echo html($display_nickname); ?>さんの<?php echo $year; ?>年<?php echo $month; ?>月の読書記録はありません。
            <?php endif; ?>
        </p>
        <?php if ($is_my_report): ?>
        <a href="/search.php" class="inline-flex items-center px-4 py-2 bg-readnest-primary hover:bg-readnest-accent text-white rounded-lg transition-colors">
            <i class="fas fa-plus mr-2"></i>本を登録する
        </a>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<script>
// Chart.js設定
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($report_data['has_data']): ?>
    const ctx = document.getElementById('dailyChart');
    if (ctx) {
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#e5e7eb' : '#374151';
        const gridColor = isDark ? '#374151' : '#e5e7eb';

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($daily_labels); ?>,
                datasets: [{
                    label: 'ページ数',
                    data: <?php echo json_encode($daily_pages); ?>,
                    backgroundColor: 'rgba(56, 161, 130, 0.6)',
                    borderColor: 'rgba(56, 161, 130, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + 'ページ';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor,
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 15
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor,
                            stepSize: 50
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // Xシェアボタン処理
    const xShareBtn = document.getElementById('xShareBtn');
    if (xShareBtn) {
        xShareBtn.addEventListener('click', async function() {
            const shareUrl = this.dataset.shareUrl;
            const ogImageUrl = this.dataset.ogImage;
            const btnText = document.getElementById('xShareBtnText');
            const originalText = btnText.textContent;

            // ボタンを無効化して状態表示
            this.disabled = true;
            btnText.textContent = '画像を準備中...';

            try {
                // OGP画像を事前に取得（キャッシュ生成）
                const response = await fetch(ogImageUrl, { method: 'HEAD' });

                if (response.ok) {
                    // 画像生成完了、X画面を開く
                    window.open(shareUrl, '_blank', 'noopener,noreferrer');
                } else {
                    // HEADが失敗した場合はGETで試す
                    await fetch(ogImageUrl);
                    window.open(shareUrl, '_blank', 'noopener,noreferrer');
                }
            } catch (error) {
                console.error('OGP image prefetch error:', error);
                // エラーでもシェア画面は開く
                window.open(shareUrl, '_blank', 'noopener,noreferrer');
            } finally {
                this.disabled = false;
                btnText.textContent = originalText;
            }
        });
    }
});
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>
