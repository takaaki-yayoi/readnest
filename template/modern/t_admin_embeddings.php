<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// ヘルパー関数を読み込み
require_once(dirname(dirname(__DIR__)) . '/library/form_helpers.php');

// メインコンテンツを生成
ob_start();
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- ヘッダー -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    <i class="fas fa-vector-square text-purple-600 mr-2"></i>
                    エンベディング管理
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">OpenAI text-embedding-3-smallモデルを使用して書籍の内容をベクトル化し、高精度な類似本検索を実現します</p>
            </div>
        </div>

        <!-- OpenAI API設定状態 -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">
                <i class="fas fa-info-circle mr-2"></i>OpenAI API設定
            </h3>
            <?php if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)): ?>
                <p class="text-sm text-green-700">
                    <i class="fas fa-check-circle mr-1"></i>
                    APIキーが設定されています（text-embedding-3-smallモデルを使用）
                </p>
            <?php else: ?>
                <p class="text-sm text-red-700 mb-2">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    APIキーが設定されていません
                </p>
                <div class="bg-white dark:bg-gray-700 rounded p-3 mt-2">
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-semibold mb-2">設定方法：</p>
                    <ol class="text-sm text-gray-600 dark:text-gray-400 list-decimal list-inside space-y-1">
                        <li><a href="https://platform.openai.com/" target="_blank" class="text-blue-600 hover:underline">OpenAI Platform</a>でアカウントを作成</li>
                        <li>API Keysセクションで新しいAPIキーを生成</li>
                        <li>config.phpに以下を追加：<br>
                            <code class="bg-gray-100 dark:bg-gray-600 dark:text-gray-200 px-2 py-1 rounded">define('OPENAI_API_KEY', 'sk-...');</code>
                        </li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>

        <!-- 処理結果の表示 -->
        <?php if ($d_result !== null): ?>
            <div class="mb-6">
                <?php if (isset($d_result['error'])): ?>
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-times-circle text-red-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    エラー: <?php echo htmlspecialchars($d_result['error']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php elseif (isset($d_result['single'])): ?>
                    <!-- 個別生成の結果 -->
                    <div class="rounded-md <?php echo $d_result['success'] ? 'bg-green-50' : 'bg-red-50'; ?> p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas <?php echo $d_result['success'] ? 'fa-check-circle text-green-400' : 'fa-times-circle text-red-400'; ?> text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium <?php echo $d_result['success'] ? 'text-green-800' : 'text-red-800'; ?>">
                                    ASIN: <?php echo htmlspecialchars($d_result['asin']); ?> のエンベディング生成が
                                    <?php echo $d_result['success'] ? '完了しました' : '失敗しました'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- 一括生成の結果 -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">
                            <i class="fas fa-chart-bar mr-2"></i>一括生成結果
                        </h3>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div class="bg-white rounded p-3">
                                <p class="text-gray-600">処理件数</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $d_result['total']; ?></p>
                            </div>
                            <div class="bg-white rounded p-3">
                                <p class="text-gray-600">成功</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo $d_result['success']; ?></p>
                            </div>
                            <div class="bg-white rounded p-3">
                                <p class="text-gray-600">失敗</p>
                                <p class="text-2xl font-bold text-red-600"><?php echo $d_result['failed']; ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($d_result['processed'])): ?>
                            <div class="mt-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">処理詳細：</h4>
                                <div class="max-h-40 overflow-y-auto bg-white rounded p-2 text-xs">
                                    <?php foreach ($d_result['processed'] as $item): ?>
                                        <div class="flex justify-between py-1 border-b">
                                            <span><?php echo htmlspecialchars($item['title']); ?></span>
                                            <span class="<?php echo $item['status'] === 'success' ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo $item['status']; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 統計情報 -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    <i class="fas fa-chart-pie text-indigo-600 mr-2"></i>
                    エンベディング統計
                </h2>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">総書籍数</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($d_stats['total_books']); ?></p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">エンベディング済み</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($d_stats['books_with_embedding']); ?></p>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">生成可能（説明文あり）</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($d_stats['books_need_embedding']); ?></p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">カバー率</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $d_stats['coverage_percentage']; ?>%</p>
                    </div>
                </div>
                
                <!-- プログレスバー -->
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span>エンベディング生成進捗</span>
                        <span><?php echo $d_stats['coverage_percentage']; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 h-3 rounded-full transition-all duration-500" 
                             style="width: <?php echo $d_stats['coverage_percentage']; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 一括生成フォーム -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        <i class="fas fa-layer-group text-green-600 mr-2"></i>
                        一括エンベディング生成
                    </h2>
                </div>
                <div class="px-6 py-4">
                    <?php if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="generate_batch">
                            
                            <div class="mb-4">
                                <label for="limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    生成件数（API制限に注意）
                                </label>
                                <select name="limit" id="limit" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    <option value="5">5件</option>
                                    <option value="10" selected>10件</option>
                                    <option value="20">20件</option>
                                    <option value="50">50件</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                                <i class="fas fa-rocket mr-2"></i>
                                エンベディング生成開始
                            </button>
                        </form>
                        
                        <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-md">
                            <p class="text-xs text-amber-800">
                                <i class="fas fa-coins mr-1"></i>
                                料金目安: text-embedding-3-small は $0.02 / 1M トークン
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <p class="text-sm text-gray-600 dark:text-gray-400">OpenAI APIキーを設定してください</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 個別生成フォーム -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        <i class="fas fa-cube text-blue-600 mr-2"></i>
                        個別エンベディング生成
                    </h2>
                </div>
                <div class="px-6 py-4">
                    <?php if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="generate_single">
                            
                            <div class="mb-4">
                                <label for="asin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ASIN / ISBN
                                </label>
                                <input type="text"
                                       name="asin"
                                       id="asin"
                                       placeholder="例: B00ABCDEFG"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500"
                                       required>
                            </div>
                            
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="fas fa-magic mr-2"></i>
                                指定した本のエンベディング生成
                            </button>
                        </form>
                        
                        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-xs text-blue-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                説明文がない本は事前に説明文を取得してください
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <p class="text-sm text-gray-600 dark:text-gray-400">OpenAI APIキーを設定してください</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 最近生成された本 -->
        <?php if (!empty($d_recent_books)): ?>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mt-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    <i class="fas fa-history text-indigo-600 mr-2"></i>
                    最近エンベディング生成された本
                </h2>
            </div>
            <div class="px-6 py-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ASIN</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">タイトル</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">著者</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">説明文（抜粋）</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">モデル</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">生成日時</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($d_recent_books as $book): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100 font-mono">
                                <?php echo htmlspecialchars($book['asin']); ?>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                                <?php echo htmlspecialchars(mb_substr($book['title'], 0, 20)); ?>...
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($book['author']); ?>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">
                                <?php echo htmlspecialchars($book['description_preview']); ?>...
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="px-2 py-1 text-xs bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded">
                                    <?php echo htmlspecialchars($book['embedding_model']); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">
                                <?php echo date('m/d H:i', strtotime($book['embedding_generated_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>