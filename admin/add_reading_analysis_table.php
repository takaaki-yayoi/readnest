<?php
/**
 * 読書傾向分析結果を保存するテーブルを作成
 */

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/session.php');
require_once(dirname(__DIR__) . '/library/database.php');
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
    <title>読書傾向分析テーブル作成 - ReadNest Admin</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>読書傾向分析テーブル作成</h1>
        <?php include('layout/utility_menu.php'); ?>
        
        <?php
        // データベース接続
        $g_db = DB_Connect();
        if (!$g_db || DB::isError($g_db)) {
            echo '<div class="error">データベース接続に失敗しました。</div>';
            echo '</div></body></html>';
            exit;
        }
        
        try {
            // 読書傾向分析テーブルを作成
            $create_table_sql = "
            CREATE TABLE IF NOT EXISTS `b_reading_analysis` (
                `analysis_id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` varchar(128) NOT NULL COMMENT 'ユーザーID',
                `analysis_type` varchar(50) NOT NULL DEFAULT 'trend' COMMENT '分析タイプ（trend, challenge等）',
                `analysis_content` TEXT NOT NULL COMMENT '分析結果（Markdown形式）',
                `is_public` tinyint(1) NOT NULL DEFAULT 0 COMMENT '公開フラグ（0:非公開, 1:公開）',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
                PRIMARY KEY (`analysis_id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_analysis_type` (`analysis_type`),
                KEY `idx_is_public` (`is_public`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='読書傾向分析結果'";
            
            $result = $g_db->query($create_table_sql);
            
            if (DB::isError($result)) {
                throw new Exception('テーブル作成エラー: ' . $result->getMessage());
            }
            
            echo '<div class="success">読書傾向分析テーブル（b_reading_analysis）を正常に作成しました。</div>';
            
            // テーブル構造を表示
            echo '<h2>作成されたテーブル構造</h2>';
            echo '<pre>';
            echo "テーブル名: b_reading_analysis\n\n";
            echo "カラム:\n";
            echo "- analysis_id: 分析ID（自動採番）\n";
            echo "- user_id: ユーザーID\n";
            echo "- analysis_type: 分析タイプ（trend=傾向分析, challenge=チャレンジ等）\n";
            echo "- analysis_content: 分析結果（Markdown形式のテキスト）\n";
            echo "- is_public: 公開フラグ（0=非公開, 1=公開）\n";
            echo "- created_at: 作成日時\n";
            echo "- updated_at: 更新日時\n";
            echo '</pre>';
            
            // インデックス情報
            echo '<h2>インデックス</h2>';
            echo '<pre>';
            echo "- PRIMARY KEY: analysis_id\n";
            echo "- INDEX: user_id（ユーザー別検索用）\n";
            echo "- INDEX: analysis_type（分析タイプ別検索用）\n";
            echo "- INDEX: is_public（公開/非公開フィルタ用）\n";
            echo "- INDEX: created_at（日付順ソート用）\n";
            echo '</pre>';
            
        } catch (Exception $e) {
            echo '<div class="error">エラーが発生しました: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <a href="/admin/" class="button">管理画面に戻る</a>
    </div>
</body>
</html>