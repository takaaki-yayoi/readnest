<?php
/**
 * タグテーブルのインデックス追加ツール
 * 人気のタグ機能のパフォーマンス改善用
 */

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

$page_title = 'タグインデックス追加';
$current_page = 'add_tag_indexes';

$message = '';
$error = '';

// インデックス追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_indexes'])) {
    try {
        set_time_limit(300); // 5分のタイムアウト設定
        
        $results = [];
        
        // 1. user_id, tag_name の複合インデックス
        $sql1 = "ALTER TABLE b_book_tags ADD INDEX idx_user_tag (user_id, tag_name)";
        $result1 = $g_db->query($sql1);
        if (DB::isError($result1)) {
            $results[] = "idx_user_tag: " . $result1->getMessage();
        } else {
            $results[] = "idx_user_tag: 追加成功";
        }
        
        // 2. tag_name, user_id の複合インデックス
        $sql2 = "ALTER TABLE b_book_tags ADD INDEX idx_tag_user (tag_name, user_id)";
        $result2 = $g_db->query($sql2);
        if (DB::isError($result2)) {
            $results[] = "idx_tag_user: " . $result2->getMessage();
        } else {
            $results[] = "idx_tag_user: 追加成功";
        }
        
        // 3. b_userテーブルのインデックス
        $sql3 = "ALTER TABLE b_user ADD INDEX idx_diary_status (diary_policy, status)";
        $result3 = $g_db->query($sql3);
        if (DB::isError($result3)) {
            $results[] = "idx_diary_status: " . $result3->getMessage();
        } else {
            $results[] = "idx_diary_status: 追加成功";
        }
        
        // 4. テーブル統計の更新
        $g_db->query("ANALYZE TABLE b_book_tags");
        $g_db->query("ANALYZE TABLE b_user");
        
        $message = "インデックスの追加が完了しました:\n" . implode("\n", $results);
        
    } catch (Exception $e) {
        $error = "エラーが発生しました: " . $e->getMessage();
    }
}

// 現在のインデックス状況を確認
$current_indexes = [];
$tag_indexes = $g_db->getAll("SHOW INDEXES FROM b_book_tags");
if (!DB::isError($tag_indexes)) {
    foreach ($tag_indexes as $idx) {
        $current_indexes[] = $idx['Key_name'] . ' (' . $idx['Column_name'] . ')';
    }
}

include('layout/header.php');
?>

<div class="space-y-6">
    <?php include('layout/utility_menu.php'); ?>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6">タグインデックス追加</h1>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <pre><?php echo htmlspecialchars($message); ?></pre>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold mb-2 flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                重要な注意事項
            </h2>
            <ul class="list-disc ml-6 space-y-1 text-sm">
                <li>インデックスの追加には時間がかかる場合があります（データ量による）</li>
                <li>実行中は b_book_tags テーブルがロックされる可能性があります</li>
                <li>必ずバックアップを取得してから実行してください</li>
            </ul>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <h3 class="font-semibold mb-2">現在のインデックス:</h3>
            <?php if (empty($current_indexes)): ?>
                <p class="text-gray-600">インデックス情報を取得できませんでした</p>
            <?php else: ?>
                <ul class="list-disc ml-6 text-sm">
                    <?php foreach ($current_indexes as $idx): ?>
                        <li><?php echo htmlspecialchars($idx); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <h3 class="font-semibold mb-2">追加されるインデックス:</h3>
            <ul class="list-disc ml-6 space-y-1 text-sm">
                <li><code class="bg-gray-100 px-1">idx_user_tag (user_id, tag_name)</code> - ユーザーごとのタグ検索を高速化</li>
                <li><code class="bg-gray-100 px-1">idx_tag_user (tag_name, user_id)</code> - タグ名での集計を高速化</li>
                <li><code class="bg-gray-100 px-1">idx_diary_status (diary_policy, status)</code> - 公開ユーザーのフィルタリングを高速化</li>
            </ul>
        </div>
        
        <form method="POST" onsubmit="return confirm('インデックスを追加しますか？実行には時間がかかる場合があります。');">
            <button type="submit" name="add_indexes" value="1" 
                    class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus-circle mr-2"></i>インデックスを追加
            </button>
        </form>
        
        <div class="mt-6 pt-6 border-t">
            <p class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                インデックス追加後は、<a href="/admin/diagnose_popular_tags.php" class="text-blue-600 hover:underline">診断ツール</a>で改善を確認してください。
            </p>
        </div>
    </div>
</div>

<?php include('layout/footer.php'); ?>