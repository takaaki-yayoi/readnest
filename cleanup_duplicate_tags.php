<?php
/**
 * 重複タグのクリーンアップスクリプト
 * 実行前に必ずバックアップを取ってください
 */

require_once('modern_config.php');

// ログインチェック
$login_flag = checkLogin();
if (!$login_flag) {
    die("ログインしてください");
}

$user_id = $_SESSION['AUTH_USER'];

// 安全のため、GETパラメータで確認
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>重複タグのクリーンアップ</title>
        <style>
            body { font-family: sans-serif; margin: 40px; }
            .warning { background: #ffe4e1; padding: 20px; border: 2px solid #ff6b6b; margin: 20px 0; }
            .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 20px 0; }
            button { padding: 10px 20px; font-size: 16px; margin: 10px; cursor: pointer; }
            .danger { background: #ff4444; color: white; border: none; }
            .safe { background: #4CAF50; color: white; border: none; }
        </style>
    </head>
    <body>
        <h1>重複タグのクリーンアップ</h1>
        
        <div class="warning">
            <h2>⚠️ 警告</h2>
            <p>このスクリプトは<strong>重複しているタグを削除</strong>します。</p>
            <p>実行前に必ず<strong>データベースのバックアップ</strong>を取ってください。</p>
        </div>
        
        <?php
        // 現在の状況を表示
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    COUNT(DISTINCT CONCAT(book_id, ':', tag_name)) as unique_combinations
                FROM b_book_tags 
                WHERE user_id = ?";
        $result = $g_db->getAll($sql, array($user_id), DB_FETCHMODE_ASSOC);
        
        if (!DB::isError($result)) {
            $total = $result[0]['total_records'];
            $unique = $result[0]['unique_combinations'];
            $duplicates = $total - $unique;
            
            echo "<div class='info'>";
            echo "<h3>現在の状況（あなたのデータ）:</h3>";
            echo "<ul>";
            echo "<li>総レコード数: <strong>" . number_format($total) . "</strong></li>";
            echo "<li>ユニークな組み合わせ: <strong>" . number_format($unique) . "</strong></li>";
            echo "<li>削除予定の重複レコード: <strong style='color: red;'>" . number_format($duplicates) . "</strong></li>";
            echo "</ul>";
            echo "</div>";
        }
        ?>
        
        <div class="info">
            <h3>処理内容:</h3>
            <ol>
                <li>同じ本（book_id）に同じタグ（tag_name）が複数ある場合、最も古いもの1つだけを残します</li>
                <li>削除前後のレコード数を記録します</li>
                <li>処理は元に戻せません</li>
            </ol>
        </div>
        
        <form method="get">
            <button type="button" onclick="if(confirm('本当にバックアップを取りましたか？')) { window.location.href='?confirm=yes'; }" class="danger">
                重複を削除する（<?php echo number_format($duplicates); ?>件）
            </button>
            <button type="button" onclick="window.location.href='/bookshelf.php';" class="safe">
                キャンセル
            </button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// クリーンアップ実行
echo "<!DOCTYPE html><html><head><title>クリーンアップ実行中</title></head><body>";
echo "<h1>重複タグのクリーンアップを実行中...</h1>";

// 実行前のカウント
$sql = "SELECT COUNT(*) as count FROM b_book_tags WHERE user_id = ?";
$before_count = $g_db->getOne($sql, array($user_id));
echo "<p>処理前のレコード数: " . number_format($before_count) . "</p>";

// 重複削除の実行
// まず、保持するレコードのIDを特定
$sql = "CREATE TEMPORARY TABLE keep_tags AS
        SELECT MIN(CONCAT(user_id, ':', book_id, ':', tag_name, ':', created_at)) as keep_key
        FROM b_book_tags
        WHERE user_id = ?
        GROUP BY user_id, book_id, tag_name";
$result = $g_db->query($sql, array($user_id));

if (DB::isError($result)) {
    echo "<p style='color: red;'>エラー: " . $result->getMessage() . "</p>";
    echo "<p>別の方法で削除を試みます...</p>";
    
    // 別の方法：各組み合わせごとに最古の1つを残して削除
    $sql = "DELETE t1 FROM b_book_tags t1
            INNER JOIN (
                SELECT user_id, book_id, tag_name, MIN(created_at) as min_created
                FROM b_book_tags
                WHERE user_id = ?
                GROUP BY user_id, book_id, tag_name
                HAVING COUNT(*) > 1
            ) t2 ON t1.user_id = t2.user_id 
                AND t1.book_id = t2.book_id 
                AND t1.tag_name = t2.tag_name
                AND t1.created_at > t2.min_created
            WHERE t1.user_id = ?";
    
    $result = $g_db->query($sql, array($user_id, $user_id));
    
    if (DB::isError($result)) {
        echo "<p style='color: red;'>削除エラー: " . $result->getMessage() . "</p>";
        exit;
    }
} else {
    // 一時テーブルを使った削除
    $sql = "DELETE FROM b_book_tags 
            WHERE user_id = ? 
            AND CONCAT(user_id, ':', book_id, ':', tag_name, ':', created_at) NOT IN (
                SELECT keep_key FROM keep_tags
            )";
    $result = $g_db->query($sql, array($user_id));
    
    // 一時テーブルを削除
    $g_db->query("DROP TEMPORARY TABLE IF EXISTS keep_tags");
}

// 実行後のカウント
$sql = "SELECT COUNT(*) as count FROM b_book_tags WHERE user_id = ?";
$after_count = $g_db->getOne($sql, array($user_id));
echo "<p>処理後のレコード数: " . number_format($after_count) . "</p>";

$deleted = $before_count - $after_count;
echo "<p style='color: green; font-size: 20px;'>✅ " . number_format($deleted) . " 件の重複レコードを削除しました</p>";

// 検証
$sql = "SELECT tag_name, COUNT(*) as count, COUNT(DISTINCT book_id) as unique_books
        FROM b_book_tags 
        WHERE user_id = ? 
        GROUP BY tag_name 
        ORDER BY count DESC 
        LIMIT 5";
$result = $g_db->getAll($sql, array($user_id), DB_FETCHMODE_ASSOC);

if (!DB::isError($result) && !empty($result)) {
    echo "<h3>クリーンアップ後の上位タグ:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>タグ名</th><th>使用回数</th><th>本の数</th></tr>";
    foreach ($result as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['tag_name']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . $row['unique_books'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<p><a href='/bookshelf.php'>本棚に戻る</a></p>";
echo "</body></html>";

// キャッシュをクリア
require_once(dirname(__FILE__) . '/library/cache.php');
$cache = getCache();
$cache->delete('user_tags_' . md5((string)$user_id));
$cache->delete('bookshelf_stats_' . md5((string)$user_id));
?>