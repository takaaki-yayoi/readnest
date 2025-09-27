<?php
/**
 * 人気の本の集計テーブルを更新（管理者向けブラウザ版）
 */

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/session.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/database_optimized_v2.php');
require_once(dirname(__DIR__) . '/admin/admin_helpers.php');

// 管理者メールアドレスのリスト
define('ADMIN_EMAILS', [
    'admin@readnest.jp',
    'icotfeels@gmail.com'
]);

// 管理者認証をチェック
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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>人気の本 集計更新 - ReadNest Admin</title>
    <style>
        body { font-family: sans-serif; background: #f0f0f0; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
        .button:hover { background: #0056b3; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .book-image { width: 50px; height: 70px; object-fit: cover; }
    </style>
</head>
<body>
    <div class="container">
        <h1>人気の本 集計テーブル更新</h1>
        <?php include('layout/utility_menu.php'); ?>
        
        <?php
        // データベース接続
        $g_db = DB_Connect();
        if (!$g_db || DB::isError($g_db)) {
            echo '<div class="error">データベース接続に失敗しました。</div>';
            echo '</div></body></html>';
            exit;
        }
        
        // 更新開始
        $start_time = microtime(true);
        echo '<div class="info">集計テーブルの更新を開始します...</div>';
        flush();
        
        try {
            // 集計テーブルを更新
            if (preCalculatePopularBooks()) {
                $execution_time = round(microtime(true) - $start_time, 2);
                echo '<div class="success">人気の本の集計テーブルを正常に更新しました。（実行時間: ' . $execution_time . '秒）</div>';
                
                // 更新されたデータを表示
                echo '<h2>更新された人気の本 TOP 10</h2>';
                $popular_books = getPopularBooksFromCache(10);
                
                if (!empty($popular_books)) {
                    echo '<table>';
                    echo '<tr><th>順位</th><th>画像</th><th>タイトル</th><th>読者数</th></tr>';
                    $rank = 1;
                    foreach ($popular_books as $book) {
                        echo '<tr>';
                        echo '<td>' . $rank++ . '</td>';
                        echo '<td><img src="' . htmlspecialchars($book['image_url']) . '" class="book-image" onerror="this.src=\'/img/noimage.jpg\'"></td>';
                        echo '<td><a href="/book/' . $book['book_id'] . '">' . htmlspecialchars($book['title']) . '</a></td>';
                        echo '<td>' . $book['bookmark_count'] . '人</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="info">人気の本データがありません。</div>';
                }
                
                // 実行ログをデータベースに記録
                $log_sql = "INSERT INTO b_cron_log (
                    cron_type, 
                    status, 
                    message, 
                    execution_time, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?)";
                
                $g_db->query($log_sql, [
                    'update_popular_books',
                    'success',
                    'Popular books cache updated successfully via admin panel',
                    $execution_time * 1000, // ミリ秒に変換
                    time()
                ]);
                
            } else {
                echo '<div class="error">人気の本の集計テーブルの更新に失敗しました。</div>';
                
                // エラーログを記録
                $g_db->query($log_sql, [
                    'update_popular_books',
                    'error',
                    'Failed to update popular books cache via admin panel',
                    0,
                    time()
                ]);
            }
        } catch (Exception $e) {
            echo '<div class="error">エラーが発生しました: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        ?>
        
        <a href="/admin/" class="button">管理画面に戻る</a>
        <a href="/admin/cron_status.php" class="button">実行ログを確認</a>
        <a href="/" class="button">トップページで確認</a>
    </div>
</body>
</html>