<?php
/**
 * サイト設定管理画面
 */

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');

// データベース接続を先に初期化
$g_db = DB_Connect();

// 管理者認証を読み込み
require_once(__DIR__ . '/admin_auth.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者認証チェック
if (!isAdmin()) {
    http_response_code(403);
    include('403.php');
    exit;
}

// エラーメッセージ
$error_message = '';
$success_message = '';

// 設定の保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    try {
        // 設定値を取得
        $show_latest_activities = isset($_POST['show_latest_activities']) ? 1 : 0;
        $show_new_reviews = isset($_POST['show_new_reviews']) ? 1 : 0;
        $show_popular_books = isset($_POST['show_popular_books']) ? 1 : 0;
        $show_popular_tags = isset($_POST['show_popular_tags']) ? 1 : 0;
        
        // 設定をファイルに保存（データベーステーブルがない場合の代替案）
        $settings = array(
            'show_latest_activities' => $show_latest_activities,
            'show_new_reviews' => $show_new_reviews,
            'show_popular_books' => $show_popular_books,
            'show_popular_tags' => $show_popular_tags,
            'updated_at' => time()
        );
        
        $settings_file = dirname(__DIR__) . '/config/site_settings.json';
        
        // ディレクトリが存在しない場合は作成
        $config_dir = dirname($settings_file);
        if (!is_dir($config_dir)) {
            mkdir($config_dir, 0755, true);
        }
        
        // 設定を保存
        if (file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT))) {
            $success_message = '設定を保存しました。';
            
            // キャッシュをクリア
            require_once(dirname(__DIR__) . '/library/cache.php');
            $cache = getCache();
            $cache->clear();
        } else {
            $error_message = '設定の保存に失敗しました。';
        }
        
    } catch (Exception $e) {
        $error_message = 'エラーが発生しました: ' . $e->getMessage();
    }
}

// 現在の設定を読み込み
$current_settings = array(
    'show_latest_activities' => 1,
    'show_new_reviews' => 1,
    'show_popular_books' => 1,
    'show_popular_tags' => 1
);

$settings_file = dirname(__DIR__) . '/config/site_settings.json';
if (file_exists($settings_file)) {
    $loaded_settings = json_decode(file_get_contents($settings_file), true);
    if ($loaded_settings) {
        $current_settings = array_merge($current_settings, $loaded_settings);
    }
}

// ページタイトル
$page_title = 'サイト設定';

// ヘッダーを出力
include('layout/header.php');
?>

<div class="space-y-6">
            
    <?php if ($error_message): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
    </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
    </div>
    <?php endif; ?>
            
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">トップページ表示設定</h3>
        </div>
        <div class="p-6">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="save_settings">
                        
                <div class="space-y-4">
                    <label class="flex items-start">
                        <input type="checkbox" name="show_latest_activities" value="1"
                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                               <?php echo $current_settings['show_latest_activities'] ? 'checked' : ''; ?>>
                        <div class="ml-3">
                            <span class="font-medium text-gray-900">最新の活動を表示する</span>
                            <p class="text-sm text-gray-600">ユーザーの読書開始・読了などの最新活動を表示します</p>
                        </div>
                    </label>
                        
                    <label class="flex items-start">
                        <input type="checkbox" name="show_new_reviews" value="1"
                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                               <?php echo $current_settings['show_new_reviews'] ? 'checked' : ''; ?>>
                        <div class="ml-3">
                            <span class="font-medium text-gray-900">新着レビューを表示する</span>
                            <p class="text-sm text-gray-600">最新の書評・レビューを表示します</p>
                        </div>
                    </label>
                        
                    <label class="flex items-start">
                        <input type="checkbox" name="show_popular_books" value="1"
                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                               <?php echo $current_settings['show_popular_books'] ? 'checked' : ''; ?>>
                        <div class="ml-3">
                            <span class="font-medium text-gray-900">人気の本を表示する</span>
                            <p class="text-sm text-gray-600">多くのユーザーが読んでいる本を表示します</p>
                        </div>
                    </label>
                        
                    <label class="flex items-start">
                        <input type="checkbox" name="show_popular_tags" value="1"
                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                               <?php echo $current_settings['show_popular_tags'] ? 'checked' : ''; ?>>
                        <div class="ml-3">
                            <span class="font-medium text-gray-900">人気のタグを表示する</span>
                            <p class="text-sm text-gray-600">よく使われているタグを表示します</p>
                        </div>
                    </label>
                </div>
                        
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                これらの設定を無効にすることで、トップページの読み込み速度が向上する場合があります。
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>
                        設定を保存
                    </button>
                </div>
            </form>
        </div>
    </div>
            
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">パフォーマンス情報</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-700 mb-4">現在の設定による影響:</p>
            <ul class="space-y-2">
                <li class="flex items-center">
                    <span class="w-40 text-gray-600">最新の活動:</span>
                    <?php if ($current_settings['show_latest_activities']): ?>
                        <span class="text-green-600 font-medium">有効</span>
                        <span class="text-sm text-gray-500 ml-2">(データベースクエリ実行)</span>
                    <?php else: ?>
                        <span class="text-gray-400 font-medium">無効</span>
                        <span class="text-sm text-gray-500 ml-2">(クエリスキップ)</span>
                    <?php endif; ?>
                </li>
                <li class="flex items-center">
                    <span class="w-40 text-gray-600">新着レビュー:</span>
                    <?php if ($current_settings['show_new_reviews']): ?>
                        <span class="text-green-600 font-medium">有効</span>
                        <span class="text-sm text-gray-500 ml-2">(データベースクエリ実行)</span>
                    <?php else: ?>
                        <span class="text-gray-400 font-medium">無効</span>
                        <span class="text-sm text-gray-500 ml-2">(クエリスキップ)</span>
                    <?php endif; ?>
                </li>
                <li class="flex items-center">
                    <span class="w-40 text-gray-600">人気の本:</span>
                    <?php if ($current_settings['show_popular_books']): ?>
                        <span class="text-green-600 font-medium">有効</span>
                        <span class="text-sm text-gray-500 ml-2">(データベースクエリ実行)</span>
                    <?php else: ?>
                        <span class="text-gray-400 font-medium">無効</span>
                        <span class="text-sm text-gray-500 ml-2">(クエリスキップ)</span>
                    <?php endif; ?>
                </li>
                <li class="flex items-center">
                    <span class="w-40 text-gray-600">人気のタグ:</span>
                    <?php if ($current_settings['show_popular_tags']): ?>
                        <span class="text-green-600 font-medium">有効</span>
                        <span class="text-sm text-gray-500 ml-2">(データベースクエリ実行)</span>
                    <?php else: ?>
                        <span class="text-gray-400 font-medium">無効</span>
                        <span class="text-sm text-gray-500 ml-2">(クエリスキップ)</span>
                    <?php endif; ?>
                </li>
            </ul>
                    
            <?php if (isset($current_settings['updated_at'])): ?>
            <p class="mt-4 text-sm text-gray-500">
                最終更新: <?php echo date('Y年m月d日 H:i:s', strtotime($current_settings['updated_at'])); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// フッターを出力
include('layout/footer.php');
?>