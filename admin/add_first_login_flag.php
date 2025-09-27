<?php
/**
 * b_userテーブルにfirst_loginフラグを追加するマイグレーション
 */

declare(strict_types=1);

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    die('Access denied');
}

$page_title = '初回ログインフラグ追加';

// エラーメッセージ格納用
$errors = [];
$success_messages = [];

// マイグレーション実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // first_login列を追加（デフォルト1 = 初回ログイン）
    $sql = "ALTER TABLE b_user ADD COLUMN IF NOT EXISTS first_login TINYINT(1) DEFAULT 1 COMMENT '初回ログインフラグ（1=初回、0=2回目以降）'";
    
    $result = $g_db->query($sql);
    
    if (DB::isError($result)) {
        $errors[] = "first_login列の追加に失敗しました: " . $result->getMessage();
    } else {
        $success_messages[] = "first_login列を追加しました";
        
        // 既存ユーザーは初回ログイン済みとして更新
        $update_sql = "UPDATE b_user SET first_login = 0 WHERE regist_date IS NOT NULL";
        $update_result = $g_db->query($update_sql);
        
        if (DB::isError($update_result)) {
            $errors[] = "既存ユーザーの更新に失敗しました: " . $update_result->getMessage();
        } else {
            $affected = $g_db->affectedRows();
            $success_messages[] = "既存ユーザー {$affected} 件を初回ログイン済みに更新しました";
        }
    }
}

// 現在の状態を確認
$check_sql = "SHOW COLUMNS FROM b_user LIKE 'first_login'";
$check_result = $g_db->getRow($check_sql);
$has_column = !DB::isError($check_result) && $check_result !== null;

// 統計情報
$stats = [];
if ($has_column) {
    $stats['first_login_users'] = $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE first_login = 1");
    $stats['returning_users'] = $g_db->getOne("SELECT COUNT(*) FROM b_user WHERE first_login = 0");
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ReadNest Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
        }
        .status {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        button {
            background-color: #4a90e2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #357abd;
        }
        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4a90e2;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php include('layout/utility_menu.php'); ?>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($success_messages)): ?>
            <?php foreach ($success_messages as $message): ?>
                <div class="success"><?php echo htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="status">
            <h3>現在の状態</h3>
            <p>first_login列: <?php echo $has_column ? '<strong style="color: green;">存在します</strong>' : '<strong style="color: red;">存在しません</strong>'; ?></p>
            
            <?php if ($has_column && !empty($stats)): ?>
                <table>
                    <tr>
                        <th>ユーザー種別</th>
                        <th>件数</th>
                    </tr>
                    <tr>
                        <td>初回ログインユーザー</td>
                        <td><?php echo number_format((int)$stats['first_login_users']); ?></td>
                    </tr>
                    <tr>
                        <td>2回目以降のユーザー</td>
                        <td><?php echo number_format((int)$stats['returning_users']); ?></td>
                    </tr>
                </table>
            <?php endif; ?>
        </div>
        
        <?php if (!$has_column): ?>
            <form method="post" onsubmit="return confirm('first_login列を追加しますか？');">
                <p>b_userテーブルにfirst_login列を追加します。</p>
                <p>この列は新規ユーザーの初回ログインを検出し、AIアシスタントによるオンボーディングを提供するために使用されます。</p>
                <button type="submit">マイグレーションを実行</button>
            </form>
        <?php else: ?>
            <p style="color: green;">✓ マイグレーションは完了しています。</p>
        <?php endif; ?>
        
        <a href="/admin/" class="back-link">← 管理画面に戻る</a>
    </div>
</body>
</html>