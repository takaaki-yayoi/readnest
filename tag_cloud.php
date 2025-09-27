<?php
/**
 * タグクラウドページ（リニューアル版）
 * 全ユーザーのタグを視覚的に表示
 */

declare(strict_types=1);

require_once('config.php');
require_once('library/database.php');
require_once('library/cache.php');
require_once('library/security.php');
require_once('library/site_settings.php');

// セッション開始（既に開始されていない場合のみ）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$g_db = DB_Connect();
if (!$g_db || DB::isError($g_db)) {
    error_log("Database connection failed in tag_cloud.php");
    header('Location: /error.php');
    exit;
}

// キャッシュインスタンス取得
$cache = getCache();

// ユーザー情報を取得
$user_info = null;
if (isset($_SESSION['AUTH_USER'])) {
    $user_info = getUserInformation($_SESSION['AUTH_USER']);
}

// ページタイトル
$page_title = 'みんなのタグクラウド - ReadNest';

// タグデータを取得
$cache_key = 'tag_cloud_all_v2';
$cache_ttl = 3600; // 1時間
$tags = $cache->get($cache_key);

if ($tags === false) {
    // キャッシュがない場合はデータベースから取得
    
    // まずb_popular_tags_cacheテーブルから取得を試みる
    $cache_table_exists = $g_db->getOne("SHOW TABLES LIKE 'b_popular_tags_cache'");
    
    if ($cache_table_exists) {
        // キャッシュテーブルから全タグを取得（上位500個）
        $tags = $g_db->getAll("
            SELECT tag_name, user_count as count
            FROM b_popular_tags_cache
            ORDER BY user_count DESC
            LIMIT 500
        ", [], DB_FETCHMODE_ASSOC);
    } else {
        // フォールバック：直接集計（負荷が高い）
        $tags = $g_db->getAll("
            SELECT tag_name, COUNT(DISTINCT user_id) as count
            FROM b_book_tags
            WHERE tag_name IS NOT NULL AND tag_name != ''
            GROUP BY tag_name
            HAVING COUNT(DISTINCT user_id) >= 2
            ORDER BY count DESC
            LIMIT 200
        ", [], DB_FETCHMODE_ASSOC);
    }
    
    if (!DB::isError($tags) && !empty($tags)) {
        // キャッシュに保存
        $cache->set($cache_key, $tags, $cache_ttl);
    } else {
        $tags = [];
    }
}

// タグの最大・最小カウントを取得（フォントサイズ調整用）
$max_count = 0;
$min_count = PHP_INT_MAX;
foreach ($tags as $tag) {
    $count = (int)$tag['count'];
    if ($count > $max_count) $max_count = $count;
    if ($count < $min_count) $min_count = $count;
}

// フォントサイズの計算関数
function calculateFontSize($count, $min, $max) {
    if ($max == $min) return 16; // すべて同じカウントの場合
    
    $min_size = 12;
    $max_size = 36;
    
    $ratio = ($count - $min) / ($max - $min);
    return round($min_size + ($max_size - $min_size) * $ratio);
}

// 色の計算関数（人気度に応じて色を変える）
function calculateColor($count, $min, $max) {
    if ($max == $min) return 'text-purple-600 dark:text-purple-400';

    $ratio = ($count - $min) / ($max - $min);

    if ($ratio > 0.8) return 'text-purple-900 dark:text-purple-300';
    if ($ratio > 0.6) return 'text-purple-700 dark:text-purple-400';
    if ($ratio > 0.4) return 'text-purple-600 dark:text-purple-400';
    if ($ratio > 0.2) return 'text-purple-500 dark:text-purple-400';
    return 'text-purple-400 dark:text-purple-500';
}

// h()関数はlibrary/html_helper.phpで定義済み
require_once('library/html_helper.php');

// 出力バッファリング開始
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">みんなのタグクラウド</h1>
        <p class="text-gray-600 dark:text-gray-400">
            ReadNestユーザーが使用している人気のタグを大きさで表現しています。
            大きいタグほど多くのユーザーが使用しています。
        </p>
    </div>

    <?php if (!empty($tags)): ?>
        <!-- タグクラウド -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <div class="text-center">
                <?php foreach ($tags as $tag): ?>
                    <?php 
                    $font_size = calculateFontSize($tag['count'], $min_count, $max_count);
                    $color_class = calculateColor($tag['count'], $min_count, $max_count);
                    ?>
                    <a href="/search_book_by_tag.php?tag=<?php echo urlencode($tag['tag_name']); ?>" 
                       class="inline-block m-2 hover:opacity-70 transition-opacity <?php echo $color_class; ?>"
                       style="font-size: <?php echo $font_size; ?>px;"
                       title="<?php echo h($tag['tag_name']); ?> (<?php echo number_format($tag['count']); ?>人が使用)">
                        <?php echo h($tag['tag_name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 統計情報 -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?php echo count($tags); ?></div>
                <div class="text-gray-600 dark:text-gray-400 mt-2">タグの種類</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?php echo number_format($max_count); ?></div>
                <div class="text-gray-600 dark:text-gray-400 mt-2">最も人気のタグ使用者数</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                    <?php 
                    $total_users = $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE status = 1 AND diary_policy = 1");
                    echo number_format($total_users);
                    ?>
                </div>
                <div class="text-gray-600 dark:text-gray-400 mt-2">公開ユーザー数</div>
            </div>
        </div>

        <!-- 人気タグランキング -->
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">人気タグ TOP 20</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $top_tags = array_slice($tags, 0, 20);
                foreach ($top_tags as $index => $tag): 
                ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <div class="flex items-center">
                            <span class="text-lg font-bold text-gray-400 mr-3"><?php echo $index + 1; ?>.</span>
                            <a href="/search_book_by_tag.php?tag=<?php echo urlencode($tag['tag_name']); ?>"
                               class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium">
                                <?php echo h($tag['tag_name']); ?>
                            </a>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo number_format($tag['count']); ?>人</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-8 text-center">
            <p class="text-gray-600 dark:text-gray-400">タグデータを読み込めませんでした。</p>
        </div>
    <?php endif; ?>

    <!-- 戻るボタン -->
    <div class="mt-8 text-center">
        <a href="/" class="inline-block bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-6 py-3 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>トップページに戻る
        </a>
    </div>
</div>

<?php
$d_content = ob_get_clean();
$d_title = $page_title;

// テンプレートを読み込み
require_once('template/modern/t_base.php');
?>