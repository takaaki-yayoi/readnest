<?php
/**
 * キャッシュクリア機能
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/cache.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');

// ページタイトルを設定
$page_title = 'キャッシュ管理';

$message = '';
$error = '';

// キャッシュクリア処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_cache') {
    try {
        $cache = getCache();
        
        if (isset($_POST['cache_key'])) {
            // 特定のキャッシュをクリア
            $cache->delete($_POST['cache_key']);
            $message = 'キャッシュ「' . htmlspecialchars($_POST['cache_key']) . '」をクリアしました。';
        } else {
            // すべてのキャッシュをクリア
            $cache->clear();
            $message = 'すべてのキャッシュをクリアしました。';
        }
    } catch (Exception $e) {
        $error = 'キャッシュクリアに失敗しました: ' . $e->getMessage();
    }
}

// キャッシュ統計情報を取得
try {
    $cache = getCache();
    $stats = $cache->getStats();
} catch (Exception $e) {
    // エラー時のデフォルト値
    $stats = [
        'total_files' => 0,
        'valid_count' => 0,
        'expired_count' => 0,
        'total_size' => 0
    ];
    $error = 'キャッシュ統計の取得に失敗しました: ' . $e->getMessage();
}

// キャッシュキーのリスト
$cacheKeys = [
    'site_statistics_v1' => '統計情報（24時間）',
    'new_reviews_v3' => '新着レビュー（10分）',
    'popular_reading_books_v1' => '人気の本（1時間）',
    'popular_reading_books_v1_backup' => '人気の本（バックアップ・3時間）',
    'recent_activities_formatted_v3' => '最新の活動（5分）',
    'recent_activities_formatted_v3_backup' => '最新の活動（バックアップ・30分）',
    'popular_tags_v1' => '人気のタグ（30分）',
    'user_ranking_this_month' => 'ユーザーランキング（今月）',
    'user_ranking_total' => 'ユーザーランキング（総合）',
    'latest_announcement_v1' => '最新のお知らせ（5分）',
];

// レイアウトヘッダーを読み込み
include('layout/header.php');
?>

<div class="space-y-6">
    <?php include('layout/submenu.php'); ?>

            <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- キャッシュ統計 -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-chart-pie text-purple-500 mr-2"></i>
                    キャッシュ統計
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">総ファイル数</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total_files']; ?></p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">有効なキャッシュ</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $stats['valid_count']; ?></p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">期限切れ</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $stats['expired_count']; ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">総サイズ</p>
                        <p class="text-2xl font-bold text-gray-600"><?php echo number_format($stats['total_size'] / 1024, 2); ?> KB</p>
                    </div>
                </div>
            </div>

            <!-- 個別キャッシュクリア -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-list text-indigo-500 mr-2"></i>
                    個別キャッシュ管理
                </h2>
                <div class="space-y-3">
                    <?php foreach ($cacheKeys as $key => $label): ?>
                    <?php 
                    // キャッシュの存在チェック
                    $exists = false;
                    $data = null;
                    try {
                        $data = $cache->get($key);
                        $exists = ($data !== false);
                    } catch (Exception $e) {
                        // エラーは無視
                    }
                    ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900"><?php echo $label; ?></p>
                            <p class="text-sm text-gray-600">キー: <?php echo $key; ?></p>
                            <p class="text-xs text-gray-500">
                                ステータス: 
                                <?php if ($exists): ?>
                                    <span class="text-green-600 font-medium">
                                        <i class="fas fa-check-circle"></i> キャッシュあり
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">
                                        <i class="fas fa-times-circle"></i> キャッシュなし
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <form method="post" class="inline">
                            <input type="hidden" name="action" value="clear_cache">
                            <input type="hidden" name="cache_key" value="<?php echo $key; ?>">
                            <button type="submit" 
                                    class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors <?php echo !$exists ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                    <?php echo !$exists ? 'disabled' : ''; ?>>
                                <i class="fas fa-trash mr-2"></i>クリア
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- すべてクリア -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-broom text-red-500 mr-2"></i>
                    すべてのキャッシュをクリア
                </h2>
                <p class="text-gray-600 mb-4">
                    すべてのキャッシュファイルを削除します。この操作は取り消せません。
                </p>
                <form method="post" onsubmit="return confirm('本当にすべてのキャッシュをクリアしますか？');">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash-alt mr-2"></i>
                        すべてのキャッシュをクリア
                    </button>
                </form>
            </div>
</div>

<?php include('layout/footer.php'); ?>