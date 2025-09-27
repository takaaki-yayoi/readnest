<?php
/**
 * 統計情報画面
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

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

$page_title = '統計情報';

// 期間フィルター
$period = $_GET['period'] ?? '30days';

// 期間に応じた条件を生成（Unixタイムスタンプ用）
$date_condition = '';
$timestamp_condition = 0;
switch ($period) {
    case '7days':
        $timestamp_condition = time() - (7 * 24 * 60 * 60);
        $date_condition = "DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $period_label = '過去7日間';
        break;
    case '30days':
        $timestamp_condition = time() - (30 * 24 * 60 * 60);
        $date_condition = "DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $period_label = '過去30日間';
        break;
    case '90days':
        $timestamp_condition = time() - (90 * 24 * 60 * 60);
        $date_condition = "DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $period_label = '過去90日間';
        break;
    case '1year':
        $timestamp_condition = time() - (365 * 24 * 60 * 60);
        $date_condition = "DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $period_label = '過去1年間';
        break;
    case 'all':
        $timestamp_condition = 0;
        $date_condition = "'1970-01-01'";
        $period_label = '全期間';
        break;
}

// ユーザー統計
$user_stats = [];
$user_stats['total'] = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL"),
    0
);

$user_stats['new'] = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE regist_date >= ? AND regist_date IS NOT NULL", [$timestamp_condition]),
    0
);

$user_stats['active'] = safeDbResult(
    $g_db->getOne("SELECT COUNT(DISTINCT user_id) FROM b_book_event WHERE event_date >= ?", [$timestamp_condition]),
    0
);

// 書籍統計
$book_stats = [];
$book_stats['total'] = safeDbResult(
    $g_db->getOne("SELECT COUNT(DISTINCT book_id) FROM b_book_list"),
    0
);

// b_book_listにはregist_dateカラムがない可能性があるので、create_dateを使用
$book_stats['added'] = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE create_date >= ?", [$timestamp_condition]),
    0
);

// 読書ステータス別統計
$status_stats_sql = "SELECT status, COUNT(*) as count FROM b_book_list GROUP BY status";
$status_stats = safeDbResult(
    $g_db->getAll($status_stats_sql, null, DB_FETCHMODE_ASSOC),
    []
);

// 読了統計（eventフィールドを使用）
$finished_stats = [];
// 読了イベントとREAD_BEFOREステータスの本の合計
$finished_stats['total'] = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_book_event WHERE event = ?", [READING_FINISH]),
    0
) + safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE status = ?", [READ_BEFORE]),
    0
);

// 期間内の読了数（イベントとfinished_dateを考慮）
$event_count = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_book_event WHERE event = ? AND event_date >= ?", [READING_FINISH, $date_condition]),
    0
);
$finished_date_count = safeDbResult(
    $g_db->getOne("SELECT COUNT(*) FROM b_book_list WHERE status IN (?, ?) AND finished_date >= DATE(?)", [READING_FINISH, READ_BEFORE, $date_condition]),
    0
);
$finished_stats['period'] = max($event_count, $finished_date_count);

// 日別の新規ユーザー数（グラフ用）- DATETIME型対応
$daily_users_sql = "SELECT DATE(regist_date) as date, COUNT(*) as count 
                    FROM b_user 
                    WHERE regist_date >= ? AND regist_date IS NOT NULL
                    GROUP BY DATE(regist_date) 
                    ORDER BY date";
// タイムスタンプをDATETIME形式に変換
$date_condition = date('Y-m-d H:i:s', $timestamp_condition);
$daily_users = safeDbResult(
    $g_db->getAll($daily_users_sql, [$date_condition], DB_FETCHMODE_ASSOC),
    []
);

// 日別の読了数（グラフ用）- DATETIME型対応
$daily_finished_sql = "SELECT DATE(event_date) as date, COUNT(*) as count 
                       FROM b_book_event 
                       WHERE event = ? AND event_date >= ?
                       GROUP BY DATE(event_date) 
                       ORDER BY date";
$daily_finished = safeDbResult(
    $g_db->getAll($daily_finished_sql, [READING_FINISH, $date_condition], DB_FETCHMODE_ASSOC),
    []
);

// 人気の本ランキング（name, amazon_id, authorを使用）
$popular_books_sql = "SELECT bl.book_id, bl.name as title, bl.author, COUNT(*) as reader_count 
                      FROM b_book_list bl 
                      GROUP BY bl.book_id, bl.name, bl.author 
                      ORDER BY reader_count DESC 
                      LIMIT 10";
$popular_books = safeDbResult(
    $g_db->getAll($popular_books_sql, null, DB_FETCHMODE_ASSOC),
    []
);

// アクティブユーザーランキング（イベントとfinished_dateを考慮）
$active_users_sql = "SELECT user_id, nickname, SUM(read_count) as read_count FROM (
                        -- イベントベースの集計
                        SELECT u.user_id, u.nickname, COUNT(DISTINCT be.book_id) as read_count 
                        FROM b_user u 
                        JOIN b_book_event be ON u.user_id = be.user_id 
                        WHERE be.event = ? AND be.event_date >= ?
                        GROUP BY u.user_id, u.nickname
                        
                        UNION ALL
                        
                        -- finished_dateベースの集計
                        SELECT u.user_id, u.nickname, COUNT(DISTINCT bl.book_id) as read_count
                        FROM b_user u
                        JOIN b_book_list bl ON u.user_id = bl.user_id
                        WHERE bl.status IN (?, ?) AND bl.finished_date >= DATE(?)
                        GROUP BY u.user_id, u.nickname
                     ) as combined
                     GROUP BY user_id, nickname
                     ORDER BY read_count DESC 
                     LIMIT 10";
$active_users = safeDbResult(
    $g_db->getAll($active_users_sql, [READING_FINISH, $timestamp_condition, READING_FINISH, READ_BEFORE, $date_condition], DB_FETCHMODE_ASSOC),
    []
);

include('layout/header.php');
?>

<!-- 期間フィルター -->
<div class="mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center space-x-4">
            <span class="text-sm font-medium text-gray-700">期間:</span>
            <div class="flex space-x-2">
                <a href="?period=7days" 
                   class="px-3 py-1 rounded <?php echo $period === '7days' ? 'bg-admin-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    7日間
                </a>
                <a href="?period=30days" 
                   class="px-3 py-1 rounded <?php echo $period === '30days' ? 'bg-admin-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    30日間
                </a>
                <a href="?period=90days" 
                   class="px-3 py-1 rounded <?php echo $period === '90days' ? 'bg-admin-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    90日間
                </a>
                <a href="?period=1year" 
                   class="px-3 py-1 rounded <?php echo $period === '1year' ? 'bg-admin-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    1年間
                </a>
                <a href="?period=all" 
                   class="px-3 py-1 rounded <?php echo $period === 'all' ? 'bg-admin-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    全期間
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 統計カード -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">総ユーザー数</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo safeNumber($user_stats['total']); ?></p>
                <p class="text-sm text-green-600 mt-1">
                    +<?php echo safeNumber($user_stats['new']); ?> (<?php echo safeHtml($period_label); ?>)
                </p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-users text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">アクティブユーザー</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo safeNumber($user_stats['active']); ?></p>
                <p class="text-sm text-gray-500 mt-1"><?php echo safeHtml($period_label); ?></p>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-user-check text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">総書籍数</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo safeNumber($book_stats['total']); ?></p>
                <p class="text-sm text-green-600 mt-1">
                    +<?php echo safeNumber($book_stats['added']); ?> (<?php echo safeHtml($period_label); ?>)
                </p>
            </div>
            <div class="p-3 bg-purple-100 rounded-full">
                <i class="fas fa-book text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">読了数</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo safeNumber($finished_stats['total']); ?></p>
                <p class="text-sm text-green-600 mt-1">
                    +<?php echo safeNumber($finished_stats['period']); ?> (<?php echo safeHtml($period_label); ?>)
                </p>
            </div>
            <div class="p-3 bg-amber-100 rounded-full">
                <i class="fas fa-book-reader text-amber-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- グラフ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">新規ユーザー推移</h3>
        <div style="height: 250px;">
            <canvas id="userChart"></canvas>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">読了数推移</h3>
        <div style="height: 250px;">
            <canvas id="finishedChart"></canvas>
        </div>
    </div>
</div>

<!-- 読書ステータス分布 -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">読書ステータス分布</h3>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <?php
        $status_labels = [
            BUY_SOMEDAY => 'いつか買う',
            NOT_STARTED => '積読',
            READING_NOW => '読書中',
            READING_FINISH => '読了',
            READ_BEFORE => '昔読んだ'
        ];
        $status_colors = [
            BUY_SOMEDAY => 'bg-gray-100 text-gray-800',
            NOT_STARTED => 'bg-yellow-100 text-yellow-800',
            READING_NOW => 'bg-blue-100 text-blue-800',
            READING_FINISH => 'bg-green-100 text-green-800',
            READ_BEFORE => 'bg-purple-100 text-purple-800'
        ];
        
        foreach ($status_stats as $stat):
            $status = $stat['status'];
            $count = $stat['count'];
            $label = $status_labels[$status] ?? '不明';
            $color = $status_colors[$status] ?? 'bg-gray-100 text-gray-800';
        ?>
        <div class="text-center">
            <div class="<?php echo $color; ?> rounded-lg p-4">
                <p class="text-2xl font-bold"><?php echo safeNumber($count); ?></p>
                <p class="text-sm mt-1"><?php echo safeHtml($label); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ランキング -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">人気の本 TOP10</h3>
        </div>
        <div class="p-6">
            <?php if (empty($popular_books)): ?>
                <p class="text-gray-500 text-center">データがありません</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($popular_books as $index => $book): ?>
                    <div class="flex items-center space-x-3">
                        <span class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-sm font-medium">
                            <?php echo $index + 1; ?>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                <?php echo safeHtml($book['title']); ?>
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                <?php echo safeHtml($book['author'] ?? '著者不明'); ?>
                            </p>
                        </div>
                        <span class="text-sm text-gray-600">
                            <?php echo safeNumber($book['reader_count']); ?>人
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">アクティブユーザー TOP10</h3>
            <p class="text-sm text-gray-500 mt-1"><?php echo $period_label; ?>の読了数</p>
        </div>
        <div class="p-6">
            <?php if (empty($active_users)): ?>
                <p class="text-gray-500 text-center">データがありません</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($active_users as $index => $user): ?>
                    <div class="flex items-center space-x-3">
                        <span class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-sm font-medium">
                            <?php echo $index + 1; ?>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo safeHtml($user['nickname']); ?>
                            </p>
                        </div>
                        <span class="text-sm text-gray-600">
                            <?php echo safeNumber($user['read_count']); ?>冊
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// グラフデータの準備
const userDates = <?php echo json_encode(array_column($daily_users, 'date')); ?>;
const userCounts = <?php echo json_encode(array_column($daily_users, 'count')); ?>;

const finishedDates = <?php echo json_encode(array_column($daily_finished, 'date')); ?>;
const finishedCounts = <?php echo json_encode(array_column($daily_finished, 'count')); ?>;

// 新規ユーザーグラフ
const userCtx = document.getElementById('userChart').getContext('2d');
new Chart(userCtx, {
    type: 'line',
    data: {
        labels: userDates,
        datasets: [{
            label: '新規ユーザー数',
            data: userCounts,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    precision: 0
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

// 読了数グラフ
const finishedCtx = document.getElementById('finishedChart').getContext('2d');
new Chart(finishedCtx, {
    type: 'line',
    data: {
        labels: finishedDates,
        datasets: [{
            label: '読了数',
            data: finishedCounts,
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    precision: 0
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
</script>

<?php include('layout/footer.php'); ?>