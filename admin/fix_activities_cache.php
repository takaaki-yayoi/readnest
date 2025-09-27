<?php
declare(strict_types=1);

/**
 * 活動キャッシュ修正スクリプト
 * cache_warmerの修正後、既存のキャッシュをクリアして再生成
 */

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(dirname(__DIR__) . '/library/cache.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

// キャッシュインスタンスを取得
$cache = getCache();

// HTMLヘッダー
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>活動キャッシュ修正 - ReadNest Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <style>
        .status-box {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <h1>活動キャッシュ修正</h1>
        <?php include('layout/utility_menu.php'); ?>
        
        <div class="status-box">
            <h2>処理状況</h2>
            <?php
            try {
                // 1. 既存のキャッシュをクリア
                echo "<p class='info'>1. 既存の活動キャッシュをクリア中...</p>";
                
                $cacheKeys = [
                    'recent_activities_formatted_v3',
                    'recent_activities_formatted_v3_backup'
                ];
                
                foreach ($cacheKeys as $key) {
                    if ($cache->delete($key)) {
                        echo "<p class='success'>  ✓ {$key} を削除しました</p>";
                    } else {
                        echo "<p class='error'>  ✗ {$key} の削除に失敗しました</p>";
                    }
                }
                
                // 2. cache_warmerを実行して新しいキャッシュを生成
                echo "<p class='info'>2. cache_warmerを実行中...</p>";
                
                // cache_warmerのコードの一部を直接実行
                require_once(dirname(__DIR__) . '/library/nickname_helpers.php');
                require_once(dirname(__DIR__) . '/library/date_helpers.php');
                
                // 最新の活動を取得（cache_warmerと同じクエリ）
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
                            case 0:
                                $event_type = 'いつか買うリストに追加';
                                break;
                            case 1:
                                $event_type = '読書開始';
                                break;
                            case 2:
                                $event_type = '読書中';
                                break;
                            case 3:
                                $event_type = '読了';
                                break;
                            case 4:
                                $event_type = '既読';
                                break;
                            default:
                                $event_type = '更新';
                        }
                        
                        // ニックネームヘルパーを使用して安全なニックネームを取得
                        $safe_nickname = isset($event['nickname']) && isValidNickname($event['nickname']) 
                            ? $event['nickname'] 
                            : generateDefaultNickname($event['user_id']);
                        
                        // ユーザー写真URLの決定
                        $user_photo_url = '/img/no-image-user.png';
                        if ($event['user_photo'] && $event['user_photo'] != '' && $event['user_photo'] != '0') {
                            $user_photo_url = "https://readnest.jp/display_profile_photo.php?user_id={$event['user_id']}&mode=thumbnail";
                        }
                        
                        $activities[] = array(
                            'type' => $event_type,
                            'user_id' => $event['user_id'],
                            'user_name' => $safe_nickname,
                            'user_photo' => $user_photo_url,
                            'book_id' => $event['book_id'],
                            'book_title' => isset($event['book_title']) ? $event['book_title'] : 'タイトル不明',
                            'book_image' => isset($event['book_image_url']) && $event['book_image_url'] ? $event['book_image_url'] : '/img/no-image-book.png',
                            'activity_date' => formatDate($event['event_date'], 'Y年n月j日 H:i'),
                            'memo' => isset($event['memo']) ? $event['memo'] : '',
                            'page' => isset($event['page']) ? $event['page'] : 0
                        );
                    }
                    
                    echo "<p class='success'>  ✓ " . count($activities) . " 件の活動を取得しました</p>";
                    
                    // キャッシュに保存
                    if ($cache->set('recent_activities_formatted_v3', $activities, 300)) {
                        echo "<p class='success'>  ✓ キャッシュを正常に保存しました</p>";
                        
                        // バックアップも作成
                        if ($cache->set('recent_activities_formatted_v3_backup', $activities, 1800)) {
                            echo "<p class='success'>  ✓ バックアップキャッシュも保存しました</p>";
                        }
                    } else {
                        echo "<p class='error'>  ✗ キャッシュの保存に失敗しました</p>";
                    }
                    
                    // サンプルデータを表示
                    echo "<h3>キャッシュされたデータのサンプル（最初の3件）:</h3>";
                    echo "<pre>";
                    foreach (array_slice($activities, 0, 3) as $i => $activity) {
                        echo "活動 " . ($i + 1) . ":\n";
                        echo "  ユーザー名: " . $activity['user_name'] . " (user_id: " . $activity['user_id'] . ")\n";
                        echo "  アクティビティ: " . $activity['type'] . "\n";
                        echo "  本のタイトル: " . $activity['book_title'] . "\n";
                        echo "  日時: " . $activity['activity_date'] . "\n\n";
                    }
                    echo "</pre>";
                    
                } else {
                    echo "<p class='error'>  ✗ 活動データの取得に失敗しました</p>";
                    if (DB::isError($event_data)) {
                        echo "<p class='error'>  エラー: " . $event_data->getMessage() . "</p>";
                    }
                }
                
                // 3. 完全なcache_warmerの実行を促す
                echo "<p class='info'>3. 完全なキャッシュ更新</p>";
                echo "<p>上記の処理で活動キャッシュを修正しました。完全なキャッシュ更新を行う場合は、以下のボタンをクリックしてください：</p>";
                
            } catch (Exception $e) {
                echo "<p class='error'>エラーが発生しました: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="/cron/cache_warmer.php" target="_blank" class="btn btn-primary">完全なcache_warmerを実行</a>
            <a href="/admin/cron_status.php" class="btn btn-secondary">Cron実行状況を確認</a>
            <a href="/" target="_blank" class="btn btn-secondary">トップページで確認</a>
        </div>
        
        <p style="margin-top: 20px;"><a href="/admin/">管理画面に戻る</a></p>
    </div>
</body>
</html>