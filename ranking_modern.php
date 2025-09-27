<?php
/**
 * モダンランキングページ
 * ReadNest - あなたの読書の巣
 * PHP 8.2対応・モダンテンプレート使用
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// レベル表示関連
require_once(dirname(__FILE__) . '/library/achievement_system.php');
require_once(dirname(__FILE__) . '/library/level_display_helper.php');

// ページタイトル設定
$d_site_title = "読書ランキング - ReadNest";

// メタ情報
$g_meta_description = "ReadNestの読書ランキング。今月と全期間の読書量ランキングを確認して、読書仲間と競い合いましょう。";
$g_meta_keyword = "読書ランキング,読書量,本,ランキング,ReadNest";

// ログイン状態を確認
$login_flag = checkLogin();
$user_id = $login_flag ? $_SESSION['AUTH_USER'] : null;
$d_nickname = $login_flag ? getNickname($user_id) : '';

// ソートキーの処理
$sort_key = $_GET['sort_key'] ?? 'read_books_month';
if (!in_array($sort_key, ['read_books_total', 'read_books_month'])) {
    $sort_key = 'read_books_month';
}

// 期間の表示名
$period_name = ($sort_key === 'read_books_month') ? '今月' : '全期間';
$period_description = ($sort_key === 'read_books_month') ? 
    '今月読み終わった本の冊数でランキングしています' : 
    '累計で読み終わった本の冊数でランキングしています';

try {
    global $g_db;
    
    // ランキングデータを取得
    $ranking_sql = "
        SELECT 
            u.user_id,
            u.nickname,
            u.user_photo as photo,
            u.photo_state,
            u.diary_policy,
            COALESCE(stats.{$sort_key}, 0) as score
        FROM b_user u
        LEFT JOIN b_user_reading_stat stats ON u.user_id = stats.user_id
        WHERE u.status = 1 AND u.diary_policy = 1
        ORDER BY score DESC, u.user_id ASC
        LIMIT 50
    ";
    
    $ranking_data = $g_db->getAll($ranking_sql);
    if(DB::isError($ranking_data)) {
        $ranking_data = array();
    }
    
    // ユーザーレベル情報を一括取得
    $user_levels = array();
    if (!empty($ranking_data)) {
        $user_ids = array_column($ranking_data, 'user_id');
        $user_levels = getUsersLevels($user_ids);
    }
    
    // 今月読んだ本の詳細を取得（今月ランキングの場合）
    $user_books = array();
    if ($sort_key === 'read_books_month' && !empty($ranking_data)) {
        foreach ($ranking_data as $user) {
            if ($user['score'] > 0) {
                $books_sql = "
                    SELECT DISTINCT bl.book_id, b.title, b.image_url
                    FROM b_book_list bl
                    JOIN b_book b ON bl.book_id = b.book_id
                    WHERE bl.user_id = ? 
                    AND bl.status = ?
                    AND MONTH(bl.updated_at) = MONTH(CURRENT_DATE())
                    AND YEAR(bl.updated_at) = YEAR(CURRENT_DATE())
                    ORDER BY bl.updated_at DESC
                    LIMIT 10
                ";
                
                $books = $g_db->getAll($books_sql, array($user['user_id'], READING_FINISH));
                if(!DB::isError($books)) {
                    $user_books[$user['user_id']] = $books;
                }
            }
        }
    }
    
    // 統計情報を取得
    $stats_sql = "
        SELECT 
            COUNT(DISTINCT u.user_id) as total_users,
            AVG(COALESCE(stats.{$sort_key}, 0)) as avg_books,
            MAX(COALESCE(stats.{$sort_key}, 0)) as max_books
        FROM b_user u
        LEFT JOIN b_user_reading_stat stats ON u.user_id = stats.user_id
        WHERE u.status = 1 AND u.diary_policy = 1
    ";
    
    $stats_result = $g_db->getRow($stats_sql);
    if(!DB::isError($stats_result)) {
        $total_users = intval($stats_result['total_users']);
        $avg_books = round(floatval($stats_result['avg_books']), 1);
        $max_books = intval($stats_result['max_books']);
    } else {
        $total_users = 0;
        $avg_books = 0.0;
        $max_books = 0;
    }
    
} catch (Exception $e) {
    error_log('Ranking page error: ' . $e->getMessage());
    $ranking_data = array();
    $user_books = array();
    $total_users = 0;
    $avg_books = 0.0;
    $max_books = 0;
}

// テンプレート用データを準備
$d_content = '';
ob_start();
?>

<!-- ランキングページコンテンツ -->
<div class="bg-readnest-beige min-h-screen">
    <!-- ヘッダーセクション -->
    <div class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl font-bold mb-4">
                    📊 読書ランキング
                </h1>
                <p class="text-xl text-white opacity-90 mb-6">
                    <?php echo html($period_description); ?>
                </p>
                
                <!-- 期間切り替えタブ -->
                <div class="inline-flex rounded-lg bg-white bg-opacity-20 p-1">
                    <a href="/ranking.php?sort_key=read_books_month" 
                       class="px-6 py-2 text-sm font-medium rounded-md transition-all <?php echo $sort_key === 'read_books_month' ? 'bg-white text-readnest-primary shadow-sm' : 'text-white hover:bg-white hover:bg-opacity-10'; ?>">
                        今月
                    </a>
                    <a href="/ranking.php?sort_key=read_books_total" 
                       class="px-6 py-2 text-sm font-medium rounded-md transition-all <?php echo $sort_key === 'read_books_total' ? 'bg-white text-readnest-primary shadow-sm' : 'text-white hover:bg-white hover:bg-opacity-10'; ?>">
                        全期間
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 統計情報 -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-readnest-primary"><?php echo number_format($total_users); ?></div>
                <div class="text-gray-600 mt-2">参加ユーザー</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-readnest-accent"><?php echo $avg_books; ?></div>
                <div class="text-gray-600 mt-2">平均読書冊数</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-readnest-primary"><?php echo number_format($max_books); ?></div>
                <div class="text-gray-600 mt-2">最高読書冊数</div>
            </div>
        </div>
        
        <!-- ランキングリスト -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-readnest-primary text-white">
                <h2 class="text-xl font-bold"><?php echo html($period_name); ?>の読書ランキング</h2>
            </div>
            
            <?php if (empty($ranking_data)): ?>
                <div class="p-8 text-center text-gray-500">
                    <div class="text-4xl mb-4">📚</div>
                    <p>まだランキングデータがありません</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php 
                    $current_rank = 1;
                    $previous_score = null;
                    $rank_offset = 0;
                    
                    foreach ($ranking_data as $index => $user): 
                        $score = intval($user['score']);
                        
                        // スコアが0の場合はランキング表示を終了
                        if ($score <= 0) break;
                        
                        // 同点処理
                        if ($previous_score !== null && $score !== $previous_score) {
                            $current_rank = $index + 1;
                        }
                        
                        $rank_class = '';
                        $rank_icon = '';
                        if ($current_rank === 1) {
                            $rank_class = 'text-yellow-600';
                            $rank_icon = '🥇';
                        } elseif ($current_rank === 2) {
                            $rank_class = 'text-gray-500';
                            $rank_icon = '🥈';
                        } elseif ($current_rank === 3) {
                            $rank_class = 'text-yellow-800';
                            $rank_icon = '🥉';
                        } else {
                            $rank_class = 'text-gray-700';
                            $rank_icon = '';
                        }
                        
                        $previous_score = $score;
                    ?>
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center space-x-4">
                                <!-- 順位 -->
                                <div class="flex-shrink-0 w-12 text-center">
                                    <div class="text-2xl font-bold <?php echo $rank_class; ?>">
                                        <?php echo $rank_icon; ?><?php echo $current_rank; ?>
                                    </div>
                                </div>
                                
                                <!-- プロフィール画像 -->
                                <div class="flex-shrink-0">
                                    <?php if ($user['photo'] && $user['photo_state'] == PHOTO_REGISTER_STATE): ?>
                                        <img class="w-12 h-12 rounded-full object-cover border-2 border-readnest-primary" 
                                             src="https://readnest.jp/display_profile_photo.php?user_id=<?php echo $user['user_id']; ?>&mode=icon" 
                                             alt="<?php echo html($user['nickname']); ?>">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-full bg-readnest-primary text-white flex items-center justify-center font-bold">
                                            <?php echo html(mb_substr($user['nickname'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- ユーザー情報 -->
                                <div class="flex-grow">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?php if ($user['diary_policy'] == 1): ?>
                                                <a href="/profile.php?user_id=<?php echo $user['user_id']; ?>" 
                                                   class="hover:text-readnest-primary transition-colors">
                                                    <?php echo html($user['nickname']); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo html($user['nickname']); ?>
                                            <?php endif; ?>
                                        </h3>
                                        <?php if (isset($user_levels[$user['user_id']])): ?>
                                            <?php echo getLevelBadgeHtml($user_levels[$user['user_id']], 'sm'); ?>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-readnest-accent text-white">
                                            <?php echo $score; ?>冊
                                        </span>
                                    </div>
                                    
                                    <!-- 今月読んだ本（今月ランキングの場合） -->
                                    <?php if ($sort_key === 'read_books_month' && isset($user_books[$user['user_id']]) && !empty($user_books[$user['user_id']])): ?>
                                        <div class="mt-3">
                                            <div class="flex space-x-2 overflow-x-auto pb-2">
                                                <?php foreach ($user_books[$user['user_id']] as $book): ?>
                                                    <a href="/book/<?php echo $book['book_id']; ?>" 
                                                       class="flex-shrink-0" 
                                                       title="<?php echo html($book['title']); ?>">
                                                        <img src="<?php echo html($book['image_url'] ?: '/img/noimage.jpg'); ?>" 
                                                             alt="<?php echo html($book['title']); ?>"
                                                             class="w-12 h-16 object-cover rounded shadow-sm hover:shadow-md transition-shadow"
                                                             onerror="this.src='/img/noimage.jpg'">
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 参加方法 -->
        <?php if (!$login_flag): ?>
            <div class="mt-8 bg-white rounded-lg shadow-md p-6 text-center">
                <h3 class="text-xl font-bold text-gray-900 mb-4">ランキングに参加しませんか？</h3>
                <p class="text-gray-600 mb-6">ReadNestに登録して、あなたも読書ランキングに参加しましょう！</p>
                <a href="/register.php" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-readnest-primary hover:bg-readnest-accent transition-colors">
                    無料で始める
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// モダンテンプレートを使用してページを表示
include(getTemplatePath('t_base.php'));
?>