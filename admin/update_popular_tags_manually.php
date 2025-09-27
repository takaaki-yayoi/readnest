<?php
declare(strict_types=1);

require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

// 実行結果を格納
$results = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // cronスクリプトを実行
        $script_path = dirname(__DIR__) . '/cron/update_popular_tags_cache.php';
        
        // PHPコマンドでスクリプトを実行
        $output = [];
        $return_var = 0;
        exec("php " . escapeshellarg($script_path) . " 2>&1", $output, $return_var);
        
        $results['execution_output'] = implode("\n", $output);
        $results['return_code'] = $return_var;
        
        if ($return_var === 0) {
            $results['status'] = 'success';
            $results['message'] = '人気タグキャッシュの更新が完了しました。';
        } else {
            $results['status'] = 'error';
            $results['message'] = '更新中にエラーが発生しました。';
        }
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        $results['status'] = 'error';
        $results['message'] = 'エラー: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>人気タグキャッシュ手動更新 - ReadNest管理画面</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 3px;
            text-decoration: none;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary {
            background: #007bff;
        }
        .btn:hover {
            opacity: 0.9;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            white-space: pre-wrap;
            font-size: 12px;
        }
        .output-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            padding: 15px;
            margin: 15px 0;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>人気タグキャッシュ手動更新</h1>
        
        <?php if (!empty($results)): ?>
            <?php if ($results['status'] === 'success'): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($results['message']); ?>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($results['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($results['execution_output'])): ?>
                <h2>実行ログ</h2>
                <div class="output-box">
                    <pre><?php echo htmlspecialchars($results['execution_output']); ?></pre>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (empty($results)): ?>
            <p>このツールは人気タグキャッシュを手動で更新します。通常はcronで自動実行されますが、必要に応じて手動で実行できます。</p>
            
            <form method="post" onsubmit="return confirm('人気タグキャッシュを更新しますか？処理には時間がかかる場合があります。')">
                <button type="submit" class="btn btn-primary">キャッシュを更新</button>
            </form>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="diagnose_popular_tags_new.php" class="btn btn-primary">診断画面に戻る</a>
            <a href="index.php" class="btn btn-primary">管理画面トップに戻る</a>
        </div>
    </div>
</body>
</html>