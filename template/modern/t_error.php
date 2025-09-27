<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// エラー情報
$error_title = isset($error_title) ? $error_title : 'エラーが発生しました';
$error_message = isset($error_message) ? $error_message : '申し訳ございませんが、システムエラーが発生しました。';
$error_code = isset($error_code) ? $error_code : '500';

// コンテンツ部分を生成
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <!-- エラーアイコン -->
            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-3xl text-red-600"></i>
                </div>
            </div>
            
            <!-- エラーコード -->
            <div class="text-center mb-4">
                <h1 class="text-6xl font-bold text-gray-400"><?php echo html($error_code); ?></h1>
            </div>
            
            <!-- エラータイトル -->
            <div class="text-center mb-4">
                <h2 class="text-2xl font-bold text-gray-900"><?php echo html($error_title); ?></h2>
            </div>
            
            <!-- エラーメッセージ -->
            <div class="text-center mb-8">
                <p class="text-gray-600"><?php echo html($error_message); ?></p>
            </div>
            
            <!-- アクションボタン -->
            <div class="space-y-3">
                <button onclick="history.back()" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-arrow-left mr-2"></i>前のページに戻る
                </button>
                
                <a href="/" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-home mr-2"></i>トップページへ
                </a>
                
                <button onclick="location.reload()" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-redo mr-2"></i>ページを再読み込み
                </button>
            </div>
            
            <!-- 技術的な詳細（開発環境のみ） -->
            <?php if (defined('DEBUG') && DEBUG): ?>
            <div class="mt-8 p-4 bg-gray-100 rounded-lg">
                <h3 class="text-sm font-medium text-gray-800 mb-2">技術的な詳細</h3>
                <div class="text-xs text-gray-600 font-mono">
                    <?php if (isset($error_file)): ?>
                    <p><strong>ファイル:</strong> <?php echo html($error_file); ?></p>
                    <?php endif; ?>
                    <?php if (isset($error_line)): ?>
                    <p><strong>行:</strong> <?php echo html($error_line); ?></p>
                    <?php endif; ?>
                    <?php if (isset($error_trace)): ?>
                    <p><strong>スタックトレース:</strong></p>
                    <pre class="mt-2 whitespace-pre-wrap"><?php echo html($error_trace); ?></pre>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- サポート情報 -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    問題が解決しない場合は、
                    <a href="/help.php" class="text-green-600 hover:text-green-700 underline">
                        ヘルプページ
                    </a>
                    をご確認ください。
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// 追加のスクリプト
ob_start();
?>
<script>
// エラー報告の自動送信（オプション）
if ('serviceWorker' in navigator) {
    // Service Worker を使用してオフライン対応
    navigator.serviceWorker.register('/sw.js').catch(function(error) {
        console.log('Service Worker registration failed:', error);
    });
}

// ユーザーの操作を記録（分析用）
document.addEventListener('click', function(e) {
    if (e.target.matches('button, a')) {
        // 分析データを送信（必要に応じて）
        console.log('User action:', e.target.textContent.trim());
    }
});
</script>
<?php
$d_additional_scripts = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>