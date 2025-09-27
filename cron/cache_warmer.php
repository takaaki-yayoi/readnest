<?php
/**
 * キャッシュウォーマー
 * 定期的にキャッシュを更新してパフォーマンスを向上
 * PHP 5.6以上対応版
 */

// 実行環境をチェック
$is_cli = (php_sapi_name() === 'cli');
$is_browser = !$is_cli;

// ブラウザからの実行の場合、管理者認証を確認
if ($is_browser) {
    // 設定を先に読み込み
    require_once(dirname(__DIR__) . '/config.php');
    require_once(dirname(__DIR__) . '/library/session.php');
    require_once(dirname(__DIR__) . '/library/database.php');
    require_once(dirname(__DIR__) . '/admin/admin_helpers.php');
    
    // 管理者メールアドレスのリスト（admin_auth.phpから）
    define('ADMIN_EMAILS', [
        'admin@readnest.jp',
        'icotfeels@gmail.com'
    ]);
    
    // 管理者認証をチェック（SessionClassを使わない方法）
    $is_admin = false;
    if (isset($_SESSION['AUTH_USER'])) {
        $user_id = $_SESSION['AUTH_USER'];
        $user_info = getUserInformation($user_id);
        
        if ($user_info && in_array($user_info['email'], ADMIN_EMAILS, true)) {
            $is_admin = true;
        }
    }
    
    if (!$is_admin) {
        header('HTTP/1.1 403 Forbidden');
        die('<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>アクセス拒否 - ReadNest</title>
    <style>
        body { font-family: sans-serif; background: #f0f0f0; padding: 50px; text-align: center; }
        .container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
        h1 { color: #d00; }
        p { color: #666; margin: 20px 0; }
        a { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>アクセス拒否</h1>
        <p>このページは管理者のみアクセス可能です。</p>
        <p>管理者アカウントでログインしてください。</p>
        <a href="/admin/">管理画面に戻る</a>
    </div>
</body>
</html>');
    }
    
    // HTML出力の開始
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Cache Warmer - ReadNest</title>
    <style>
        body { font-family: monospace; background: #f0f0f0; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        h1 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cache Warmer 実行中...</h1>
        <pre>';
    
    // バッファリングを無効化して即座に出力
    ob_implicit_flush(true);
    ob_end_flush();
}

// 実行時間制限を設定（10分）
set_time_limit(600);

// 設定を読み込み（ブラウザの場合は既に読み込み済み）
if ($is_cli) {
    require_once(dirname(__DIR__) . '/config.php');
}
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/cache.php');
require_once(dirname(__DIR__) . '/library/database_optimized.php');

// データベース接続
$g_db = DB_Connect();
if (!$g_db || DB::isError($g_db)) {
    error_log('[Cache Warmer] Database connection failed');
    exit(1);
}

// 出力関数
function output($message, $type = 'info') {
    global $is_browser;
    
    if ($is_browser) {
        $class = '';
        $prefix = '';
        switch($type) {
            case 'success':
                $class = 'success';
                $prefix = '✓ ';
                break;
            case 'error':
                $class = 'error';
                $prefix = '✗ ';
                break;
            case 'info':
            default:
                $class = 'info';
                $prefix = '• ';
                break;
        }
        echo "<span class='{$class}'>{$prefix}{$message}</span>\n";
        flush();
    } else {
        echo $message . "\n";
    }
}

// キャッシュ更新開始をログに記録
$start_time = microtime(true);
output('[Cache Warmer] Starting cache warming at ' . date('Y-m-d H:i:s'), 'info');
error_log('[Cache Warmer] Starting cache warming at ' . date('Y-m-d H:i:s'));

// キャッシュインスタンスを取得
$cache = getCache();

// 更新するキャッシュの定義
$cacheJobs = [
    [
        'key' => 'site_statistics_v1',
        'ttl' => 86400, // 24時間
        'description' => 'サイト統計情報',
        'generator' => function() use ($g_db) {
            $stats_sql = "
                SELECT 
                    (SELECT COUNT(*) FROM b_user WHERE regist_date IS NOT NULL) as total_users,
                    (SELECT COUNT(DISTINCT book_id) FROM b_book_list) as total_books,
                    (SELECT COUNT(*) FROM b_book_list WHERE memo != '' AND memo IS NOT NULL) as total_reviews,
                    (SELECT SUM(CASE WHEN status = ? THEN total_page ELSE current_page END) 
                     FROM b_book_list WHERE current_page > 0) as total_pages_read
            ";
            
            $stats_result = $g_db->getRow($stats_sql, array(READING_FINISH), DB_FETCHMODE_ASSOC);
            
            if(DB::isError($stats_result)) {
                throw new Exception('Failed to fetch statistics');
            }
            
            return [
                'total_users' => isset($stats_result['total_users']) ? intval($stats_result['total_users']) : 0,
                'total_books' => isset($stats_result['total_books']) ? intval($stats_result['total_books']) : 0,
                'total_reviews' => isset($stats_result['total_reviews']) ? intval($stats_result['total_reviews']) : 0,
                'total_pages_read' => isset($stats_result['total_pages_read']) ? intval($stats_result['total_pages_read']) : 0
            ];
        }
    ],
    [
        'key' => 'new_reviews_v3',
        'ttl' => 600, // 10分
        'description' => '新着レビュー',
        'generator' => function() use ($g_db) {
            // 最適化された関数を使用
            $new_review_data = getNewReviewOptimized('', 5);
            $new_reviews = array();
            
            if ($new_review_data && !DB::isError($new_review_data)) {
                foreach ($new_review_data as $review) {
                    if (!empty($review['book_id']) && !empty($review['name'])) {
                        $new_reviews[] = array(
                            'book_id' => $review['book_id'],
                            'book_title' => $review['name'],
                            'comment' => isset($review['memo']) ? $review['memo'] : '',
                            'rating' => isset($review['rating']) ? intval($review['rating']) : 0,
                            'user_id' => $review['user_id'],
                            'nickname' => isset($review['nickname']) ? $review['nickname'] : '名無しさん',
                            'user_photo' => isset($review['user_photo']) && $review['user_photo'] && $review['user_photo'] != '0' 
                                ? "https://readnest.jp/display_profile_photo.php?user_id={$review['user_id']}&mode=thumbnail"
                                : '/img/no-image-user.png',
                            'created_at' => isset($review['memo_updated']) ? 
                                (is_string($review['memo_updated']) ? strtotime($review['memo_updated']) : $review['memo_updated']) : 
                                time(),
                            'image_url' => isset($review['image_url']) && $review['image_url'] ? $review['image_url'] : '/img/noimage.jpg'
                        );
                    }
                }
            }
            
            return $new_reviews;
        }
    ],
    [
        'key' => 'popular_reading_books_v1',
        'ttl' => 3600, // 1時間
        'description' => '人気の本',
        'generator' => function() use ($g_db) {
            // 最適化された関数を使用
            if (file_exists(dirname(__DIR__) . '/library/database_optimized_v2.php')) {
                require_once(dirname(__DIR__) . '/library/database_optimized_v2.php');
                if (function_exists('getPopularBooksFromCache')) {
                    return getPopularBooksFromCache(9);
                } elseif (function_exists('getPopularBooksOptimized')) {
                    return getPopularBooksOptimized(9);
                }
            }
            
            // フォールバック
            $reading_books_sql = "
                SELECT 
                    MIN(bl.book_id) as book_id,
                    bl.name as title,
                    bl.image_url,
                    COUNT(DISTINCT bl.user_id) as bookmark_count
                FROM b_book_list bl
                INNER JOIN b_user u ON bl.user_id = u.user_id
                WHERE u.diary_policy = 1 
                    AND bl.name IS NOT NULL 
                    AND bl.name != ''
                    AND bl.image_url IS NOT NULL
                    AND bl.image_url != ''
                    AND bl.image_url NOT LIKE '%noimage%'
                GROUP BY bl.name, bl.image_url
                HAVING COUNT(DISTINCT bl.user_id) > 0
                ORDER BY bookmark_count DESC, MAX(bl.update_date) DESC
                LIMIT 9
            ";
            
            $reading_books = $g_db->getAll($reading_books_sql, array(), DB_FETCHMODE_ASSOC);
            
            if(DB::isError($reading_books)) {
                throw new Exception('Failed to fetch popular books');
            }
            
            return $reading_books;
        }
    ],
    [
        'key' => 'recent_activities_formatted_v3',
        'ttl' => 300, // 5分
        'description' => '最新の活動',
        'generator' => function() use ($g_db) {
            // 最新の活動を直接取得（index.phpと同じクエリ構造）
            $activities_sql = "
                SELECT 
                    be.event_id,
                    be.book_id,
                    be.user_id,
                    be.event_date,
                    be.event,
                    be.page,
                    be.memo,
                    bl.name as book_title,
                    bl.image_url as book_image_url,
                    u.nickname,
                    u.photo as user_photo,
                    u.status as user_status
                FROM b_book_event be
                LEFT JOIN b_book_list bl ON be.book_id = bl.book_id AND be.user_id = bl.user_id
                INNER JOIN b_user u ON be.user_id = u.user_id
                WHERE u.diary_policy = 1
                    AND u.status = 1
                    AND be.event IN (2, 3)
                ORDER BY be.event_date DESC
                LIMIT 10
            ";
            
            $event_data = $g_db->getAll($activities_sql, array(), DB_FETCHMODE_ASSOC);
            $activities = array();
            
            if ($event_data && !DB::isError($event_data)) {
                foreach ($event_data as $event) {
                    $event_type = '';
                    switch ($event['event']) {
                        case BUY_SOMEDAY:
                            $event_type = 'いつか買うリストに追加';
                            break;
                        case NOT_STARTED:
                            $event_type = '読書開始';
                            break;
                        case READING_NOW:
                            $event_type = '読書中';
                            break;
                        case READING_FINISH:
                            $event_type = '読了';
                            break;
                        case READ_BEFORE:
                            $event_type = '既読';
                            break;
                        default:
                            $event_type = '更新';
                    }
                    
                    // ニックネームヘルパーを使用して安全なニックネームを取得
                    require_once(dirname(__DIR__) . '/library/nickname_helpers.php');
                    require_once(dirname(__DIR__) . '/library/date_helpers.php');
                    $safe_nickname = isset($event['nickname']) && isValidNickname($event['nickname']) 
                        ? $event['nickname'] 
                        : generateDefaultNickname($event['user_id']);
                    
                    // ユーザー写真URLの決定
                    $user_photo_url = '/img/no-image-user.png';
                    if ($event['user_photo'] && $event['user_photo'] != '' && $event['user_photo'] != '0') {
                        $user_photo_url = "https://readnest.jp/display_profile_photo.php?user_id={$event['user_id']}&mode=thumbnail";
                    }
                    
                    $activities[] = array(
                        'type' => $event_type, // index.phpとテンプレートが期待するフィールド名
                        'user_id' => $event['user_id'],
                        'user_name' => $safe_nickname, // index.phpとテンプレートが期待するフィールド名
                        'user_photo' => $user_photo_url,
                        'book_id' => $event['book_id'],
                        'book_title' => isset($event['book_title']) ? $event['book_title'] : 'タイトル不明',
                        'book_image' => isset($event['book_image_url']) && $event['book_image_url'] ? $event['book_image_url'] : '/img/no-image-book.png',
                        'activity_date' => formatDate($event['event_date'], 'Y年n月j日 H:i'),
                        'memo' => isset($event['memo']) ? $event['memo'] : '',
                        'page' => isset($event['page']) ? $event['page'] : 0
                    );
                }
            }
            
            return $activities;
        }
    ],
    [
        'key' => 'popular_tags_v1',
        'ttl' => 1800, // 30分
        'description' => '人気のタグ',
        'timeout' => 120, // 2分のタイムアウト
        'skip_if_disabled' => true, // 機能が無効の場合はスキップ
        'generator' => function() use ($g_db) {
            require_once(dirname(__DIR__) . '/library/database.php');
            require_once(dirname(__DIR__) . '/library/site_settings.php');
            
            // 人気のタグ機能が無効の場合は空の配列を返す
            if (!isPopularTagsEnabled()) {
                return [];
            }
            
            $tags = getPopularTags(20);
            return DB::isError($tags) ? [] : $tags;
        }
    ],
    [
        'key' => 'user_ranking_this_month',
        'ttl' => 3600, // 1時間
        'description' => 'ユーザーランキング（今月）',
        'generator' => function() use ($g_db) {
            require_once(dirname(__DIR__) . '/library/database.php');
            
            $ranking = getUserRanking('read_books_month');
            return DB::isError($ranking) ? [] : $ranking;
        }
    ],
    [
        'key' => 'user_ranking_total',
        'ttl' => 3600, // 1時間
        'description' => 'ユーザーランキング（総合）',
        'generator' => function() use ($g_db) {
            require_once(dirname(__DIR__) . '/library/database.php');
            
            $ranking = getUserRanking('read_books_total');
            return DB::isError($ranking) ? [] : $ranking;
        }
    ],
    [
        'key' => 'latest_announcement_v1',
        'ttl' => 300, // 5分
        'description' => '最新のお知らせ',
        'generator' => function() use ($g_db) {
            $sql = "
                SELECT id as announcement_id, title, content, created
                FROM b_announcement
                ORDER BY created DESC
                LIMIT 1
            ";
            
            $result = $g_db->getRow($sql, array(), DB_FETCHMODE_ASSOC);
            
            if (DB::isError($result)) {
                throw new Exception('Failed to fetch latest announcement');
            }
            
            return $result ?: null;
        }
    ]
];

// 実行結果を記録
$results = [];
$success_count = 0;
$error_count = 0;

// 各キャッシュを更新
foreach ($cacheJobs as $job) {
    $job_start = microtime(true);
    
    output("Processing: " . $job['key'] . " (" . $job['description'] . ")... ", 'info');
    
    try {
        // 機能が無効の場合はスキップ
        if (isset($job['skip_if_disabled']) && $job['skip_if_disabled']) {
            require_once(dirname(__DIR__) . '/library/site_settings.php');
            if ($job['key'] === 'popular_tags_v1' && !isPopularTagsEnabled()) {
                output("SKIPPED (disabled)", 'info');
                continue;
            }
        }
        // ジョブごとのタイムアウトを設定（デフォルト30秒）
        $job_timeout = isset($job['timeout']) ? $job['timeout'] : 30;
        $timeout_start = time();
        
        // データを生成
        $data = $job['generator']();
        
        // タイムアウトチェック
        if (time() - $timeout_start > $job_timeout) {
            throw new Exception('Job timeout (' . $job_timeout . ' seconds)');
        }
        
        // キャッシュに保存
        if ($cache->set($job['key'], $data, $job['ttl'])) {
            $success_count++;
            $status = 'success';
            $message = 'Cache updated successfully';
            output("SUCCESS", 'success');
        } else {
            $error_count++;
            $status = 'error';
            $message = 'Failed to save to cache';
            output("FAILED (save error)", 'error');
        }
        
    } catch (Exception $e) {
        $error_count++;
        $status = 'error';
        $message = $e->getMessage();
        error_log('[Cache Warmer] Error updating ' . $job['key'] . ': ' . $message);
        output("ERROR: " . $message, 'error');
    }
    
    $job_time = round((microtime(true) - $job_start) * 1000, 2);
    
    $results[] = [
        'key' => $job['key'],
        'description' => $job['description'],
        'status' => $status,
        'message' => $message,
        'execution_time' => $job_time
    ];
}

// 実行ログをデータベースに記録
try {
    // テーブルの存在を確認
    $table_check = $g_db->getOne("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'b_cron_log'");
    
    if ($table_check == 0) {
        // テーブルが存在しない場合は作成
        $create_table_sql = "
        CREATE TABLE IF NOT EXISTS `b_cron_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cron_type` varchar(50) NOT NULL COMMENT 'cronの種類',
            `status` enum('success','error','partial') NOT NULL DEFAULT 'success' COMMENT '実行ステータス',
            `message` text COMMENT '実行メッセージ',
            `execution_time` int(11) DEFAULT NULL COMMENT '実行時間（ミリ秒）',
            `created_at` int(11) NOT NULL COMMENT '実行日時',
            PRIMARY KEY (`id`),
            KEY `idx_cron_type` (`cron_type`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='cron実行ログ'";
        
        $g_db->query($create_table_sql);
        output("Created b_cron_log table.", 'info');
    }
    
    $log_sql = "INSERT INTO b_cron_log (
        cron_type, 
        status, 
        message, 
        execution_time, 
        created_at
    ) VALUES (?, ?, ?, ?, ?)";
    
    $total_time = round((microtime(true) - $start_time) * 1000, 2);
    $status = ($error_count === 0) ? 'success' : (($success_count === 0) ? 'error' : 'partial');
    
    // メッセージを簡潔にして、詳細はログファイルに
    $message = "Updated {$success_count} caches successfully, {$error_count} errors.";
    if ($error_count > 0) {
        $error_keys = [];
        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                $error_keys[] = $result['key'];
            }
        }
        $message .= " Failed caches: " . implode(', ', $error_keys);
    }
    
    // 詳細はログファイルに記録
    error_log('[Cache Warmer] Detailed results: ' . json_encode($results));
    
    $result = $g_db->query($log_sql, [
        'cache_warmer',
        $status,
        $message,
        $total_time,
        time()
    ]);
    
    if (DB::isError($result)) {
        throw new Exception('Database error: ' . $result->getMessage());
    }
    
    output("Logged to database successfully.", 'success');
    
    // 30日以上前のログを削除
    $cleanup_sql = "DELETE FROM b_cron_log WHERE created_at < ?";
    $thirty_days_ago = time() - (30 * 24 * 60 * 60);
    $cleanup_result = $g_db->query($cleanup_sql, [$thirty_days_ago]);
    
    if (!DB::isError($cleanup_result)) {
        $deleted_count = $g_db->affectedRows();
        if ($deleted_count > 0) {
            output("Cleaned up {$deleted_count} old log entries.", 'info');
        }
    }
    
} catch (Exception $e) {
    error_log('[Cache Warmer] Failed to log execution: ' . $e->getMessage());
    output("Failed to log to database: " . $e->getMessage(), 'error');
}

// 終了ログ
$total_time = round(microtime(true) - $start_time, 2);
error_log('[Cache Warmer] Completed in ' . $total_time . ' seconds. Success: ' . $success_count . ', Errors: ' . $error_count);

// サマリーを表示
output("\n=== Cache Warmer Summary ===", 'info');
output("Total time: " . $total_time . " seconds", 'info');
output("Success: " . $success_count . " caches", 'success');
output("Errors: " . $error_count . " caches", $error_count > 0 ? 'error' : 'info');
output("\nDetailed results:", 'info');
foreach ($results as $result) {
    $type = $result['status'] === 'success' ? 'success' : 'error';
    output(sprintf("- %-30s: %-7s (%s ms)", 
        $result['key'], 
        $result['status'], 
        $result['execution_time']
    ), $type);
    if ($result['status'] === 'error') {
        output("  Error: " . $result['message'], 'error');
    }
}

// ブラウザの場合はHTMLを閉じる
if ($is_browser) {
    echo '</pre>';
    echo '<p style="margin-top: 20px;">';
    echo '<a href="/admin/cron_status.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">実行ログを確認</a>';
    echo '</p>';
    echo '</div></body></html>';
}

// エラーがあった場合は終了コード1で終了
exit($error_count > 0 ? 1 : 0);