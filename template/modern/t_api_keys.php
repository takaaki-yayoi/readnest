<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 mx-2"></i>
                    <span class="text-gray-700 dark:text-gray-300">API Key管理</span>
                </li>
            </ol>
        </nav>

        <!-- ヘッダー -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">API Key管理</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">MCP ServerなどからReadNestデータにアクセスするためのAPI Keyを管理できます。</p>
        </div>

        <!-- エラーメッセージ -->
        <?php if (!empty($error)): ?>
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-300"><?php echo html($error); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 成功メッセージ -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-300"><?php echo html($message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 新しく生成されたAPI Key -->
        <?php if (!empty($new_api_key)): ?>
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-key text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300 mb-2">API Keyが生成されました</h3>
                        <p class="text-xs text-yellow-700 dark:text-yellow-400 mb-3">
                            このAPI Keyは一度しか表示されません。安全な場所に保存してください。
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded border border-yellow-300 dark:border-yellow-600 p-3">
                            <code class="text-sm text-gray-900 dark:text-gray-100 font-mono break-all" id="new-api-key"><?php echo html($new_api_key); ?></code>
                        </div>
                        <button onclick="copyApiKey()" class="mt-3 inline-flex items-center px-3 py-2 border border-yellow-300 dark:border-yellow-600 shadow-sm text-sm font-medium rounded-md text-yellow-700 dark:text-yellow-300 bg-white dark:bg-gray-800 hover:bg-yellow-50 dark:hover:bg-gray-700">
                            <i class="fas fa-copy mr-2"></i>
                            コピー
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- API Key生成フォーム -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fas fa-plus-circle mr-2 text-readnest-primary"></i>
                    新しいAPI Keyを生成
                </h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="action" value="generate">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            API Keyの名前
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               required
                               placeholder="例: MCP Server"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-readnest-primary focus:border-readnest-primary dark:bg-gray-700 dark:text-gray-100">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            このAPI Keyの用途を識別できる名前をつけてください
                        </p>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-readnest-primary hover:bg-readnest-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary">
                        <i class="fas fa-key mr-2"></i>
                        API Keyを生成
                    </button>
                </form>
            </div>
        </div>

        <!-- API Key一覧 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fas fa-list mr-2 text-readnest-primary"></i>
                    登録済みAPI Key
                </h2>

                <?php if (empty($api_keys)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-key text-gray-300 dark:text-gray-600 text-5xl mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">API Keyはまだ登録されていません</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">上のフォームから新しいAPI Keyを生成してください</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($api_keys as $key): ?>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 <?php echo $key['is_active'] ? '' : 'bg-gray-50 dark:bg-gray-900/50 opacity-60'; ?>">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100">
                                                <?php echo html($key['name']); ?>
                                            </h3>
                                            <?php if ($key['is_active']): ?>
                                                <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    有効
                                                </span>
                                            <?php else: ?>
                                                <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                                    <i class="fas fa-times-circle mr-1"></i>
                                                    無効
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                            <div>
                                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                    <?php echo html(substr($key['api_key'], 0, 16)); ?>...<?php echo html(substr($key['api_key'], -8)); ?>
                                                </span>
                                            </div>
                                            <div class="text-xs">
                                                <i class="far fa-calendar mr-1"></i>
                                                作成日: <?php echo html(date('Y年m月d日 H:i', strtotime($key['created_at']))); ?>
                                            </div>
                                            <?php if ($key['last_used_at']): ?>
                                                <div class="text-xs">
                                                    <i class="far fa-clock mr-1"></i>
                                                    最終使用: <?php echo html(date('Y年m月d日 H:i', strtotime($key['last_used_at']))); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($key['expires_at']): ?>
                                                <div class="text-xs">
                                                    <i class="far fa-hourglass-end mr-1"></i>
                                                    有効期限: <?php echo html(date('Y年m月d日 H:i', strtotime($key['expires_at']))); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex flex-col space-y-2">
                                        <!-- 有効/無効切り替え -->
                                        <form method="post" class="inline">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="api_key_id" value="<?php echo $key['api_key_id']; ?>">
                                            <button type="submit" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                                <i class="fas fa-toggle-<?php echo $key['is_active'] ? 'on' : 'off'; ?> mr-1"></i>
                                                <?php echo $key['is_active'] ? '無効化' : '有効化'; ?>
                                            </button>
                                        </form>
                                        <!-- 削除 -->
                                        <form method="post" class="inline" onsubmit="return confirm('このAPI Keyを削除してもよろしいですか？')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="api_key_id" value="<?php echo $key['api_key_id']; ?>">
                                            <button type="submit" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                                <i class="fas fa-trash-alt mr-1"></i>
                                                削除
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- セキュリティに関する注意事項 -->
        <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">セキュリティに関する注意</h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                        <ul class="list-disc list-inside space-y-1">
                            <li>API Keyは他人と共有しないでください</li>
                            <li>API Keyをgitなどのバージョン管理システムにコミットしないでください</li>
                            <li>API Keyが漏洩した場合は、すぐに無効化または削除してください</li>
                            <li>API Keyは読み取り専用アクセスのみ許可されています</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- MCP Serverセットアップガイドへのリンク -->
        <div class="mt-6">
            <a href="https://github.com/your-repo/readnest-mcp"
               target="_blank"
               class="inline-flex items-center text-sm text-readnest-primary hover:text-readnest-secondary">
                <i class="fas fa-external-link-alt mr-2"></i>
                MCP Serverのセットアップ方法を見る
            </a>
        </div>
    </div>
</div>

<script>
function copyApiKey() {
    const apiKey = document.getElementById('new-api-key').textContent;
    navigator.clipboard.writeText(apiKey).then(() => {
        alert('API Keyをコピーしました');
    }).catch(err => {
        console.error('コピーに失敗しました:', err);
    });
}
</script>

<?php
$d_content = ob_get_clean();
include(__DIR__ . '/t_base.php');
?>
