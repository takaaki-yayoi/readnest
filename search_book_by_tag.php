<?php
/**
 * タグによる書籍検索（最適化版）
 * サマリーテーブルとキャッシュを活用
 */

declare(strict_types=1);

require_once('config.php');
require_once('library/database.php');
require_once('library/cache.php');
require_once('library/site_settings.php');

// セッション開始（既に開始されていない場合のみ）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$g_db = DB_Connect();
if (!$g_db || DB::isError($g_db)) {
    error_log("Database connection failed in search_book_by_tag.php");
    header('Location: /error.php');
    exit;
}

// キャッシュインスタンス取得
$cache = getCache();

// パラメータを取得・サニタイズ
$tag = isset($_GET['tag']) ? trim((string)$_GET['tag']) : '';
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// セキュリティ: ページ番号の上限設定
$max_page = 100;
if ($page > $max_page) {
    header('Location: /search_book_by_tag.php?tag=' . urlencode($tag));
    exit;
}

// タグが空の場合はトップページへリダイレクト
if ($tag === '') {
    header('Location: /');
    exit;
}

// キャッシュキーの生成
$cache_key_count = 'tag_search_count_' . md5($tag);
$cache_key_books = 'tag_search_books_' . md5($tag . '_' . $page);
$cache_ttl = 1800; // 30分

// 1. まずサマリーテーブルをチェック（最速）
$summary_exists = false;
$total_count = 0;
$books = [];
$from_summary = false;


// サマリーテーブルの存在確認
$summary_table_exists = $g_db->getOne("
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'b_tag_search_summary'
");

if ($summary_table_exists) {
    // サマリーから取得を試みる
    $summary_count = $g_db->getOne("
        SELECT COUNT(*) 
        FROM b_tag_search_summary 
        WHERE tag_name = ?
    ", [$tag]);
    
    if ($summary_count > 0) {
        $from_summary = true;
        $total_count = $summary_count;
        
        // 書籍データもサマリーから取得
        // PEAR DBではLIMIT/OFFSETを直接埋め込む必要がある
        $books_sql = sprintf("
            SELECT 
                book_id,
                title,
                author,
                image_url,
                isbn,
                avg_rating,
                reader_count
            FROM b_tag_search_summary
            WHERE tag_name = ?
            ORDER BY reader_count DESC, last_update DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset);
        
        $books = $g_db->getAll($books_sql, [$tag], DB_FETCHMODE_ASSOC);
        
        if (DB::isError($books)) {
            error_log("Summary table query error: " . $books->getMessage());
            $books = [];
        }
    }
}

// 2. サマリーになければキャッシュをチェック
if (!$from_summary) {
    // カウントを取得
    $total_count = $cache->get($cache_key_count);
    
    if ($total_count === false) {
        // 3. キャッシュもなければデータベースから取得
        try {
            $count_sql = "
                SELECT COUNT(DISTINCT bl.book_id)
                FROM b_book_tags bt
                INNER JOIN b_book_list bl ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
                INNER JOIN b_user u ON bt.user_id = u.user_id
                WHERE bt.tag_name = ?
                AND bl.name IS NOT NULL 
                AND bl.name != ''
                AND u.diary_policy = 1
                AND u.status = 1
            ";
            
            $total_count = $g_db->getOne($count_sql, array($tag));
            
            if (!DB::isError($total_count)) {
                $total_count = (int)$total_count;
                // キャッシュに保存
                $cache->set($cache_key_count, $total_count, $cache_ttl);
            } else {
                $total_count = 0;
                error_log("Count query error: " . $total_count->getMessage());
            }
        } catch (Exception $e) {
            error_log("Search count error: " . $e->getMessage());
            $total_count = 0;
        }
    }
    
    // 書籍データの取得（サマリーテーブルから取得していない場合）
    if (empty($books)) {
        // キャッシュから取得を試みる
        $books = $cache->get($cache_key_books);
        
        if ($books === false) {
            try {
                // PEAR DBではLIMIT/OFFSETを直接埋め込む必要がある
                $books_sql = sprintf("
                    SELECT 
                        bl.book_id,
                        bl.name as title,
                        bl.author,
                        bl.image_url,
                        bl.isbn,
                        bl.rating as avg_rating,
                        COUNT(DISTINCT bt.user_id) as reader_count,
                        MAX(bl.update_date) as last_update
                    FROM b_book_tags bt
                    INNER JOIN b_book_list bl ON bt.book_id = bl.book_id AND bt.user_id = bl.user_id
                    INNER JOIN b_user u ON bt.user_id = u.user_id
                    WHERE bt.tag_name = ?
                    AND bl.name IS NOT NULL 
                    AND bl.name != ''
                    AND u.diary_policy = 1
                    AND u.status = 1
                    GROUP BY bl.book_id, bl.name, bl.author, bl.image_url, bl.isbn, bl.rating
                    ORDER BY reader_count DESC, last_update DESC
                    LIMIT %d OFFSET %d
                ", $per_page, $offset);
            
                $books = $g_db->getAll($books_sql, array($tag), DB_FETCHMODE_ASSOC);
                
                if (!DB::isError($books)) {
                    // キャッシュに保存
                    $cache->set($cache_key_books, $books, $cache_ttl);
                } else {
                    $books = array();
                    error_log("Books query error: " . $books->getMessage());
                }
            } catch (Exception $e) {
                error_log("Search books error: " . $e->getMessage());
                $books = array();
            }
        }
    }
}


// ページング計算
$total_pages = ceil($total_count / $per_page);

// 人気のタグを取得（高速版）
$popular_tags = [];
if (isPopularTagsEnabled()) {
    // まずb_popular_tags_cacheから取得を試みる
    $cache_table_exists = $g_db->getOne("SHOW TABLES LIKE 'b_popular_tags_cache'");
    
    if ($cache_table_exists) {
        $popular_tags = $g_db->getAll("
            SELECT tag_name, user_count 
            FROM b_popular_tags_cache 
            ORDER BY user_count DESC 
            LIMIT 20
        ", [], DB_FETCHMODE_ASSOC);
    }
    
    // キャッシュテーブルが空の場合はメモリキャッシュから
    if (empty($popular_tags)) {
        $popular_tags_cache_key = 'popular_tags_sidebar';
        $popular_tags = $cache->get($popular_tags_cache_key);
        
        if ($popular_tags === false) {
            // 最後の手段として直接取得（負荷高）
            require_once('library/database.php');
            $popular_tags = getPopularTags(20);
            
            if (!empty($popular_tags)) {
                $cache->set($popular_tags_cache_key, $popular_tags, 3600);
            }
        }
    }
}

// ユーザー情報を取得
$user_info = null;
if (isset($_SESSION['AUTH_USER'])) {
    require_once('library/database.php');
    $user_info = getUserInformation($_SESSION['AUTH_USER']);
}

// h()関数はlibrary/html_helper.phpで定義済み
require_once('library/html_helper.php');

// ページタイトル
$page_title = h($tag) . 'のタグが付いた本一覧 - ReadNest';

// キャッシュからの取得フラグを設定
$from_cache = false;
if (!$from_summary && !empty($books)) {
    // サマリーテーブルからではなく、書籍データが存在する = キャッシュまたはDBから取得
    $cache_test = $cache->get($cache_key_books);
    if ($cache_test !== false && json_encode($cache_test) === json_encode($books)) {
        $from_cache = true;
    }
}


// html()関数はlibrary/security.phpで定義済み
require_once('library/security.php');

// 出力バッファリング開始
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            「<?php echo html($tag); ?>」のタグが付いた本
        </h1>
        <p class="mt-2 text-gray-600">
            <?php echo number_format($total_count); ?>冊の本が見つかりました
            <?php if ($from_summary): ?>
                <span class="text-xs text-green-600 ml-2">(高速サマリーから取得)</span>
            <?php elseif ($from_cache): ?>
                <span class="text-xs text-blue-600 ml-2">(キャッシュから取得)</span>
            <?php endif; ?>
        </p>
    </div>

    <?php if (!empty($books)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($books as $book): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <a href="/book_detail/<?php echo html($book['book_id']); ?>" class="block">
                        <div class="flex p-4">
                            <?php if (!empty($book['image_url']) && strpos($book['image_url'], 'noimage') === false): ?>
                                <img src="<?php echo html($book['image_url']); ?>" 
                                     alt="<?php echo html($book['title']); ?>" 
                                     class="w-20 h-28 object-cover mr-4 flex-shrink-0">
                            <?php else: ?>
                                <div class="w-20 h-28 bg-gray-200 mr-4 flex-shrink-0 flex items-center justify-center">
                                    <span class="text-gray-400 text-xs">No Image</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 truncate">
                                    <?php echo html($book['title']); ?>
                                </h3>
                                <?php if (!empty($book['author'])): ?>
                                    <p class="text-sm text-gray-600 mt-1 truncate">
                                        <?php echo html($book['author']); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="mt-2 text-xs text-gray-500">
                                    <span><?php echo number_format($book['reader_count']); ?>人が登録</span>
                                    <?php if (!empty($book['avg_rating']) && floatval($book['avg_rating']) > 0): ?>
                                        <span class="ml-2">★<?php echo number_format(floatval($book['avg_rating']), 1); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex justify-center">
                <nav class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?tag=<?php echo urlencode($tag); ?>&p=<?php echo $page - 1; ?>" 
                           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded">前へ</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="px-4 py-2 bg-readnest-primary text-white rounded"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?tag=<?php echo urlencode($tag); ?>&p=<?php echo $i; ?>" 
                               class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?tag=<?php echo urlencode($tag); ?>&p=<?php echo $page + 1; ?>" 
                           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded">次へ</a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="bg-gray-100 rounded-lg p-8 text-center">
            <p class="text-gray-600">このタグが付いた本は見つかりませんでした。</p>
        </div>
    <?php endif; ?>

    <?php if (!empty($popular_tags)): ?>
        <div class="mt-12">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">人気のタグ</h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($popular_tags as $popular_tag): ?>
                    <a href="?tag=<?php echo urlencode($popular_tag['tag_name']); ?>" 
                       class="inline-block px-4 py-2 bg-gray-100 hover:bg-readnest-primary hover:text-white rounded-full text-gray-700 transition-all duration-300">
                        <?php echo html($popular_tag['tag_name']); ?>
                        <span class="text-xs opacity-60 ml-1">(<?php echo $popular_tag['user_count']; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$d_content = ob_get_clean();
$d_title = $page_title;

// テンプレートを読み込み
require_once('template/modern/t_base.php');
?>