<?php
/**
 * DateTime Migration - Web Interface
 * データベースの日付フィールドをUnix timestampからDATETIME型に移行
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

// $g_dbは既に初期化済み
if (DB::isError($g_db)) {
    die("データベース接続エラー: " . $g_db->getMessage());
}

// アクションの処理
$message = '';
$error = '';
$step = isset($_POST['step']) ? $_POST['step'] : 'check';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'check':
                $step = 'confirm';
                break;
            case 'migrate':
                if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
                    $step = 'execute';
                } else {
                    $error = 'マイグレーションを実行するには確認チェックボックスをオンにしてください。';
                    $step = 'confirm';
                }
                break;
        }
    }
}

// 現在の状態を確認
$stats = [];

// b_userテーブルの状態確認
$user_stats = $g_db->getRow("SELECT COUNT(*) as total, 
    SUM(CASE WHEN create_date = 2147483647 THEN 1 ELSE 0 END) as invalid_dates 
    FROM b_user", array(), DB_FETCHMODE_ASSOC);
if (!DB::isError($user_stats)) {
    $stats['b_user'] = [
        'total' => $user_stats['total'],
        'invalid' => $user_stats['invalid_dates']
    ];
}

// b_book_listテーブルの状態確認
$book_total = $g_db->getOne("SELECT COUNT(*) as total FROM b_book_list");
if (!DB::isError($book_total)) {
    $stats['b_book_list'] = [
        'total' => $book_total
    ];
}

// b_book_eventテーブルの状態確認
$event_total = $g_db->getOne("SELECT COUNT(*) as total FROM b_book_event");
if (!DB::isError($event_total)) {
    $stats['b_book_event'] = [
        'total' => $event_total
    ];
}

// カラムの型をチェック
$column_types = [];
$tables = [
    'b_user' => ['create_date', 'regist_date'],
    'b_book_list' => ['update_date'],
    'b_book_event' => ['event_date']
];

foreach ($tables as $table => $columns) {
    foreach ($columns as $column) {
        $column_info = $g_db->getRow("SHOW COLUMNS FROM $table LIKE '$column'", array(), DB_FETCHMODE_ASSOC);
        if (!DB::isError($column_info) && $column_info) {
            $column_types[$table][$column] = $column_info['Type'];
        }
    }
}

// マイグレーション実行
if ($step === 'execute') {
    $migration_log = [];
    $success = true;
    
    try {
        // トランザクション開始
        $g_db->beginTransaction();
        // b_userテーブルの変換
        $migration_log[] = "b_userテーブルの変換を開始...";
        
        // create_dateカラムが既にDATETIMEでないかチェック
        if (!stripos($column_types['b_user']['create_date'], 'datetime')) {
            $g_db->query("ALTER TABLE b_user ADD COLUMN create_date_new DATETIME DEFAULT NULL");
            $g_db->query("UPDATE b_user SET create_date_new = FROM_UNIXTIME(create_date) 
                        WHERE create_date IS NOT NULL AND create_date > 0 AND create_date < 2147483647");
            $g_db->query("UPDATE b_user SET create_date_new = NOW() 
                        WHERE create_date = 2147483647 OR create_date IS NULL OR create_date <= 0");
            $g_db->query("ALTER TABLE b_user DROP COLUMN create_date");
            $g_db->query("ALTER TABLE b_user CHANGE COLUMN create_date_new create_date DATETIME NOT NULL");
            $migration_log[] = "✓ create_dateカラムを変換しました";
        } else {
            $migration_log[] = "- create_dateカラムは既にDATETIME型です";
        }
        
        // regist_dateカラムが既にDATETIMEでないかチェック
        if (!stripos($column_types['b_user']['regist_date'], 'datetime')) {
            $g_db->query("ALTER TABLE b_user ADD COLUMN regist_date_new DATETIME DEFAULT NULL");
            $g_db->query("UPDATE b_user SET regist_date_new = FROM_UNIXTIME(regist_date) 
                        WHERE regist_date IS NOT NULL AND regist_date > 0 AND regist_date < 2147483647");
            $g_db->query("UPDATE b_user SET regist_date_new = create_date 
                        WHERE regist_date = 2147483647 OR regist_date IS NULL OR regist_date <= 0");
            $g_db->query("ALTER TABLE b_user DROP COLUMN regist_date");
            $g_db->query("ALTER TABLE b_user CHANGE COLUMN regist_date_new regist_date DATETIME NOT NULL");
            $migration_log[] = "✓ regist_dateカラムを変換しました";
        } else {
            $migration_log[] = "- regist_dateカラムは既にDATETIME型です";
        }
        
        // b_book_listテーブルの変換
        $migration_log[] = "\nb_book_listテーブルの変換を開始...";
        
        if (!stripos($column_types['b_book_list']['update_date'], 'datetime')) {
            $g_db->query("ALTER TABLE b_book_list ADD COLUMN update_date_new DATETIME DEFAULT NULL");
            
            // Unix timestampの場合
            $g_db->query("UPDATE b_book_list SET update_date_new = FROM_UNIXTIME(CAST(update_date AS UNSIGNED))
                        WHERE update_date REGEXP '^[0-9]+$' 
                        AND CAST(update_date AS UNSIGNED) > 0 
                        AND CAST(update_date AS UNSIGNED) < 2147483647");
            
            // DATETIME文字列の場合
            $g_db->query("UPDATE b_book_list SET update_date_new = STR_TO_DATE(update_date, '%Y-%m-%d %H:%i:%s')
                        WHERE update_date REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}'");
            
            // 無効な値の場合は現在時刻
            $g_db->query("UPDATE b_book_list SET update_date_new = NOW() WHERE update_date_new IS NULL");
            
            $g_db->query("ALTER TABLE b_book_list DROP COLUMN update_date");
            $g_db->query("ALTER TABLE b_book_list CHANGE COLUMN update_date_new update_date DATETIME NOT NULL");
            $migration_log[] = "✓ update_dateカラムを変換しました";
        } else {
            $migration_log[] = "- update_dateカラムは既にDATETIME型です";
        }
        
        // b_book_eventテーブルの変換
        $migration_log[] = "\nb_book_eventテーブルの変換を開始...";
        
        if (!stripos($column_types['b_book_event']['event_date'], 'datetime')) {
            $g_db->query("ALTER TABLE b_book_event ADD COLUMN event_date_new DATETIME DEFAULT NULL");
            $g_db->query("UPDATE b_book_event SET event_date_new = FROM_UNIXTIME(event_date)
                        WHERE event_date IS NOT NULL AND event_date > 0 AND event_date < 2147483647");
            $g_db->query("UPDATE b_book_event SET event_date_new = NOW()
                        WHERE event_date >= 2147483647 OR event_date IS NULL OR event_date <= 0");
            $g_db->query("ALTER TABLE b_book_event DROP COLUMN event_date");
            $g_db->query("ALTER TABLE b_book_event CHANGE COLUMN event_date_new event_date DATETIME NOT NULL");
            
            // インデックスの再作成
            $g_db->query("CREATE INDEX IF NOT EXISTS idx_event_date_datetime ON b_book_event(event_date)");
            $migration_log[] = "✓ event_dateカラムを変換しました";
        } else {
            $migration_log[] = "- event_dateカラムは既にDATETIME型です";
        }
        
        // コミット
        $g_db->commit();
        $migration_log[] = "\n✅ マイグレーションが正常に完了しました！";
        $message = implode("\n", $migration_log);
        
    } catch (Exception $e) {
        // ロールバック
        $g_db->rollback();
        $success = false;
        $error = "エラーが発生しました: " . $e->getMessage();
        $migration_log[] = "\n❌ エラー: " . $e->getMessage();
        $migration_log[] = "マイグレーションをロールバックしました。";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DateTime Migration - ReadNest Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .status-table th, .status-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .status-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .button-primary {
            background-color: #007bff;
            color: white;
        }
        .button-danger {
            background-color: #dc3545;
            color: white;
        }
        .button-secondary {
            background-color: #6c757d;
            color: white;
        }
        .button:hover {
            opacity: 0.8;
        }
        .checkbox-wrapper {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .migration-log {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .type-info {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ReadNest DateTime Migration</h1>
        
        <?php include('layout/submenu.php'); ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($message && $step === 'execute'): ?>
        <div class="alert alert-success">
            <h3>マイグレーション結果</h3>
            <div class="migration-log"><?php echo htmlspecialchars($message); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($step === 'check' || $step === 'confirm'): ?>
        <div class="alert alert-warning">
            <strong>⚠️ 重要:</strong> このマイグレーションはデータベース構造を変更します。
            実行前に必ずデータベースのバックアップを取得してください。
        </div>
        
        <h2>現在のデータベース状態</h2>
        <table class="status-table">
            <tr>
                <th>テーブル</th>
                <th>カラム</th>
                <th>現在の型</th>
                <th>レコード数</th>
                <th>備考</th>
            </tr>
            <tr>
                <td rowspan="2">b_user</td>
                <td>create_date</td>
                <td class="type-info"><?php echo isset($column_types['b_user']['create_date']) ? $column_types['b_user']['create_date'] : '不明'; ?></td>
                <td rowspan="2"><?php echo isset($stats['b_user']) ? $stats['b_user']['total'] : '0'; ?></td>
                <td rowspan="2">
                    <?php if (isset($stats['b_user']['invalid']) && $stats['b_user']['invalid'] > 0): ?>
                        <span style="color: red;">無効な日付: <?php echo $stats['b_user']['invalid']; ?>件</span>
                    <?php else: ?>
                        正常
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>regist_date</td>
                <td class="type-info"><?php echo isset($column_types['b_user']['regist_date']) ? $column_types['b_user']['regist_date'] : '不明'; ?></td>
            </tr>
            <tr>
                <td>b_book_list</td>
                <td>update_date</td>
                <td class="type-info"><?php echo isset($column_types['b_book_list']['update_date']) ? $column_types['b_book_list']['update_date'] : '不明'; ?></td>
                <td><?php echo isset($stats['b_book_list']) ? $stats['b_book_list']['total'] : '0'; ?></td>
                <td>-</td>
            </tr>
            <tr>
                <td>b_book_event</td>
                <td>event_date</td>
                <td class="type-info"><?php echo isset($column_types['b_book_event']['event_date']) ? $column_types['b_book_event']['event_date'] : '不明'; ?></td>
                <td><?php echo isset($stats['b_book_event']) ? $stats['b_book_event']['total'] : '0'; ?></td>
                <td>-</td>
            </tr>
        </table>
        
        <h2>マイグレーション内容</h2>
        <ul>
            <li>Unix timestamp (INT型) を MySQL DATETIME型に変換</li>
            <li>無効な値（2147483647）を現在時刻に修正</li>
            <li>2038年問題を回避</li>
            <li>データの一貫性を確保</li>
        </ul>
        <?php endif; ?>
        
        <?php if ($step === 'check'): ?>
        <form method="post">
            <input type="hidden" name="action" value="check">
            <button type="submit" class="button button-primary">マイグレーション内容を確認</button>
            <a href="/admin/" class="button button-secondary">キャンセル</a>
        </form>
        <?php endif; ?>
        
        <?php if ($step === 'confirm'): ?>
        <form method="post">
            <input type="hidden" name="action" value="migrate">
            <div class="checkbox-wrapper">
                <label>
                    <input type="checkbox" name="confirm" value="yes" required>
                    <strong>データベースのバックアップを取得済みであることを確認しました</strong>
                </label>
            </div>
            <button type="submit" class="button button-danger">マイグレーションを実行</button>
            <a href="datetime_migration.php" class="button button-secondary">戻る</a>
        </form>
        <?php endif; ?>
        
        <?php if ($step === 'execute' && isset($success) && $success): ?>
        <h2>次のステップ</h2>
        <ol>
            <li>アプリケーションの動作を確認してください</li>
            <li>ユーザー登録、本の登録、読書進捗の記録が正常に動作することを確認</li>
            <li>問題が発生した場合は、バックアップから復元してください</li>
        </ol>
        <a href="/admin/" class="button button-primary">管理画面に戻る</a>
        <?php endif; ?>
    </div>
</body>
</html>