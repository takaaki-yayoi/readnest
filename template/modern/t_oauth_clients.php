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
                    <span class="text-gray-700 dark:text-gray-300">OAuthクライアント管理</span>
                </li>
            </ol>
        </nav>

        <!-- ヘッダー -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">OAuthクライアント管理</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Claude.aiなどのサービスからReadNestデータにアクセスするためのOAuthクライアントを管理できます。</p>
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

        <!-- 新しく生成されたOAuthクライアント -->
        <?php if (!empty($new_client)): ?>
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-key text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300 mb-2">OAuthクライアントが生成されました</h3>
                        <p class="text-xs text-yellow-700 dark:text-yellow-400 mb-3">
                            これらの情報は一度しか表示されません。安全な場所に保存してください。
                        </p>
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs font-medium text-yellow-800 dark:text-yellow-300">Client ID:</label>
                                <div class="bg-white dark:bg-gray-800 rounded border border-yellow-300 dark:border-yellow-600 p-2 mt-1">
                                    <code class="text-sm text-gray-900 dark:text-gray-100 font-mono break-all"><?php echo html($new_client['client_id']); ?></code>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-yellow-800 dark:text-yellow-300">Client Secret:</label>
                                <div class="bg-white dark:bg-gray-800 rounded border border-yellow-300 dark:border-yellow-600 p-2 mt-1">
                                    <code class="text-sm text-gray-900 dark:text-gray-100 font-mono break-all"><?php echo html($new_client['client_secret']); ?></code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- OAuthクライアント生成フォーム -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fas fa-plus-circle mr-2 text-readnest-primary"></i>
                    新しいOAuthクライアントを生成
                </h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="action" value="generate">
                    <div>
                        <label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            クライアント名
                        </label>
                        <input type="text"
                               id="client_name"
                               name="client_name"
                               required
                               placeholder="例: Claude.ai"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-readnest-primary focus:border-readnest-primary dark:bg-gray-700 dark:text-gray-100">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            接続するサービスの名前を入力してください
                        </p>
                    </div>
                    <div>
                        <label for="redirect_uris" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            リダイレクトURI
                        </label>
                        <textarea
                               id="redirect_uris"
                               name="redirect_uris"
                               required
                               rows="3"
                               placeholder="https://claude.ai/api/mcp/auth_callback"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-readnest-primary focus:border-readnest-primary dark:bg-gray-700 dark:text-gray-100"></textarea>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Claude.aiの場合: https://claude.ai/api/mcp/auth_callback
                        </p>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-readnest-primary hover:bg-readnest-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary">
                        <i class="fas fa-key mr-2"></i>
                        OAuthクライアントを生成
                    </button>
                </form>
            </div>
        </div>

        <!-- OAuthクライアント一覧 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fas fa-list mr-2 text-readnest-primary"></i>
                    登録済みOAuthクライアント
                </h2>

                <?php if (empty($oauth_clients)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-key text-gray-300 dark:text-gray-600 text-5xl mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">OAuthクライアントはまだ登録されていません</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">上のフォームから新しいOAuthクライアントを生成してください</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($oauth_clients as $client): ?>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">
                                            <?php echo html($client['client_name']); ?>
                                        </h3>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                            <div>
                                                <span class="font-medium">Client ID:</span>
                                                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded ml-2">
                                                    <?php echo html($client['client_id']); ?>
                                                </code>
                                            </div>
                                            <div>
                                                <span class="font-medium">Redirect URIs:</span>
                                                <div class="mt-1 ml-2">
                                                    <?php foreach (explode("\n", $client['redirect_uris']) as $uri): ?>
                                                        <div class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded inline-block mr-2 mb-1">
                                                            <?php echo html(trim($uri)); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="text-xs">
                                                <i class="far fa-calendar mr-1"></i>
                                                作成日: <?php echo html(date('Y年m月d日 H:i', strtotime($client['created_at']))); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <form method="post" class="inline" onsubmit="return confirm('このOAuthクライアントを削除してもよろしいですか？関連するトークンもすべて無効になります。')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="client_id" value="<?php echo html($client['client_id']); ?>">
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
                            <li>Client SecretはOAuthクライアント生成時に一度だけ表示されます</li>
                            <li>Client Secretは他人と共有しないでください</li>
                            <li>Client Secretをgitなどのバージョン管理システムにコミットしないでください</li>
                            <li>不要になったクライアントは削除してください</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$d_content = ob_get_clean();
include(__DIR__ . '/t_base.php');
?>
