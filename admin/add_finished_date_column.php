<?php
/**
 * 読了日カラム追加マイグレーション
 * b_book_listテーブルにfinished_dateカラムを追加
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

$page_title = '読了日カラム追加';

include('layout/header.php');
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">読了日カラム追加マイグレーション</h2>
        
        <?php
        $errors = [];
        $success = false;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute'])) {
            try {
                // 1. カラムが既に存在するかチェック
                $check_sql = "SHOW COLUMNS FROM b_book_list LIKE 'finished_date'";
                $exists = $g_db->getOne($check_sql);
                
                if ($exists) {
                    $errors[] = "finished_dateカラムは既に存在します。";
                } else {
                    // 2. カラムを追加
                    $alter_sql = "ALTER TABLE b_book_list ADD COLUMN finished_date DATE DEFAULT NULL AFTER update_date";
                    $result = $g_db->query($alter_sql);
                    
                    if (DB::isError($result)) {
                        $errors[] = "カラム追加エラー: " . $result->getMessage();
                    } else {
                        // 3. 既存データの初期化（読了状態の本の更新日を読了日として設定）
                        $init_sql = "
                            UPDATE b_book_list 
                            SET finished_date = DATE(update_date) 
                            WHERE status IN (?, ?) 
                            AND finished_date IS NULL
                        ";
                        $init_result = $g_db->query($init_sql, [READING_FINISH, READ_BEFORE]);
                        
                        if (DB::isError($init_result)) {
                            $errors[] = "初期データ設定エラー: " . $init_result->getMessage();
                        } else {
                            $affected_rows = $g_db->affectedRows();
                            $success = true;
                        }
                    }
                }
                
            } catch (Exception $e) {
                $errors[] = "エラーが発生しました: " . $e->getMessage();
            }
        }
        
        // 現在の状態をチェック
        $column_exists = false;
        try {
            $check_sql = "SHOW COLUMNS FROM b_book_list LIKE 'finished_date'";
            $exists = $g_db->getOne($check_sql);
            $column_exists = !empty($exists);
        } catch (Exception $e) {
            // エラーは無視
        }
        ?>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <p>✓ finished_dateカラムを追加しました。</p>
                <?php if (isset($affected_rows)): ?>
                    <p>✓ <?php echo number_format($affected_rows); ?>件の読了済み本に読了日を設定しました。</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="mb-6">
            <h3 class="font-semibold mb-2">現在の状態</h3>
            <p class="text-sm text-gray-600">
                finished_dateカラム: 
                <span class="font-medium <?php echo $column_exists ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $column_exists ? '存在します' : '存在しません'; ?>
                </span>
            </p>
        </div>
        
        <?php if (!$column_exists): ?>
            <div class="mb-6">
                <h3 class="font-semibold mb-2">実行内容</h3>
                <ol class="list-decimal list-inside text-sm text-gray-700 space-y-1">
                    <li>b_book_listテーブルにfinished_date (DATE型) カラムを追加</li>
                    <li>既存の読了済み本（status = 2 または 3）のupdate_dateをfinished_dateに設定</li>
                </ol>
            </div>
            
            <form method="POST" onsubmit="return confirm('読了日カラムを追加しますか？');">
                <button type="submit" name="execute" value="1" 
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    実行する
                </button>
            </form>
        <?php else: ?>
            <p class="text-green-600 font-medium">
                ✓ マイグレーションは既に完了しています。
            </p>
        <?php endif; ?>
        
        <div class="mt-6 pt-6 border-t">
            <a href="/admin/" class="text-blue-600 hover:text-blue-800">
                ← 管理画面に戻る
            </a>
        </div>
    </div>
</div>

<?php include('layout/footer.php'); ?>