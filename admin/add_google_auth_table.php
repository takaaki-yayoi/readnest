<?php
/**
 * Google認証用テーブルを追加するマイグレーション
 */

// エラー表示を有効化（デバッグ用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// セッションがまだ開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 管理者チェック
if (!isset($_SESSION['AUTH_USER'])) {
    die('ログインが必要です。<a href="/login.php">ログインページへ</a>');
}

// 管理者ユーザーIDをチェック（12 = icotfeels）
$admin_user_ids = ['12', 'icotfeels']; // 管理者のユーザーID
if (!in_array($_SESSION['AUTH_USER'], $admin_user_ids)) {
    die('管理者権限が必要です。');
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Google認証テーブル追加 - ReadNest Admin</title>
    <style>
        body { font-family: sans-serif; background: #f0f0f0; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .success { color: green; background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { color: red; background: #ffebee; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .sql { background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Google認証テーブル追加</h1>
        <?php include('layout/utility_menu.php'); ?>
        
        <?php if (isset($_POST['execute'])): ?>
            <?php
            try {
                // まず、b_userテーブルのuser_idの型を確認
                $check_sql = "SHOW COLUMNS FROM b_user LIKE 'user_id'";
                $column_info = $g_db->getRow($check_sql);
                
                if (DB::isError($column_info)) {
                    throw new Exception("b_userテーブルの確認エラー: " . $column_info->getMessage());
                }
                
                // Google認証情報を格納するテーブル（外部キー制約なしで作成）
                $sql1 = "
                CREATE TABLE IF NOT EXISTS b_google_auth (
                    auth_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(50) NOT NULL,
                    google_id VARCHAR(255) NOT NULL UNIQUE,
                    google_email VARCHAR(255) NOT NULL,
                    google_name VARCHAR(255),
                    google_picture TEXT,
                    access_token TEXT,
                    refresh_token TEXT,
                    token_expires_at DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_google_id (google_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                // データベース接続確認
                if (!isset($g_db) || !$g_db) {
                    throw new Exception("データベース接続が確立されていません");
                }
                
                $result1 = $g_db->query($sql1);
                
                if (DB::isError($result1)) {
                    throw new Exception("テーブル作成エラー: " . $result1->getMessage());
                }
                
                // b_userテーブルにGoogle認証フラグを追加（カラムの最後に追加）
                $sql2 = "ALTER TABLE b_user ADD COLUMN IF NOT EXISTS google_auth_enabled TINYINT(1) DEFAULT 0";
                $result2 = $g_db->query($sql2);
                
                if (DB::isError($result2)) {
                    // カラムが既に存在する場合はエラーを無視
                    if (!strpos($result2->getMessage(), 'Duplicate column')) {
                        throw new Exception("カラム追加エラー: " . $result2->getMessage());
                    }
                }
                
                echo '<div class="success">Google認証テーブルの作成が完了しました。</div>';
                
            } catch (Exception $e) {
                echo '<div class="error">エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        <?php else: ?>
            <p>Google認証機能のためのテーブルを作成します。</p>
            
            <h2>作成されるテーブル</h2>
            <div class="sql">
CREATE TABLE b_google_auth (
    auth_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    google_id VARCHAR(255) NOT NULL UNIQUE,
    google_email VARCHAR(255) NOT NULL,
    google_name VARCHAR(255),
    google_picture TEXT,
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_google_id (google_id)
)

ALTER TABLE b_user ADD COLUMN google_auth_enabled TINYINT(1) DEFAULT 0
            </div>
            
            <form method="POST">
                <button type="submit" name="execute" value="1" class="btn" 
                        onclick="return confirm('Google認証テーブルを作成しますか？')">
                    実行
                </button>
            </form>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="/admin/" class="btn">管理画面へ戻る</a>
        </div>
    </div>
</body>
</html>