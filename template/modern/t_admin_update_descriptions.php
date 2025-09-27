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
                    <i class="fas fa-book-medical text-blue-600 mr-2"></i>
                    書籍説明文の更新（管理画面）
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Google Books APIから書籍の説明文を取得してデータベースに保存します</p>
            </div>
        </div>

        <!-- 処理結果の表示 -->
        <?php if ($d_result !== null): ?>
            <div class="mb-6">
                <?php if (isset($d_result['single'])): ?>
                    <!-- 個別更新の結果 -->
                    <div class="rounded-md <?php echo $d_result['success'] ? 'bg-green-50' : 'bg-red-50'; ?> p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas <?php echo $d_result['success'] ? 'fa-check-circle text-green-400' : 'fa-times-circle text-red-400'; ?> text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium <?php echo $d_result['success'] ? 'text-green-800' : 'text-red-800'; ?>">
                                    ASIN: <?php echo htmlspecialchars($d_result['asin']); ?> の更新が
                                    <?php echo $d_result['success'] ? '完了しました' : '失敗しました'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- 一括更新の結果 -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>一括更新結果
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
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- API設定状態 -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">
                <i class="fas fa-key mr-2"></i>Google Books API設定
            </h3>
            <?php if (defined('GOOGLE_BOOKS_API_KEY') && !empty(GOOGLE_BOOKS_API_KEY)): ?>
                <p class="text-sm text-green-700">
                    <i class="fas fa-check-circle mr-1"></i>
                    APIキーが設定されています
                </p>
                <p class="text-xs text-gray-600 mt-1">
                    制限: 1,000回/日（割り当て増加申請で最大100,000回/日まで無料）
                </p>
            <?php else: ?>
                <p class="text-sm text-amber-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    APIキーなしで動作中（1,000回/日制限、IPアドレスごと）
                </p>
                <div class="bg-white dark:bg-gray-700 rounded p-3 mt-2">
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-semibold mb-2">APIキー設定のメリット：</p>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 list-disc list-inside space-y-1">
                        <li>使用状況のモニタリング可能</li>
                        <li>割り当て増加申請可能（最大10万回/日）</li>
                        <li>複数IPからのアクセスでも同じ割り当て</li>
                        <li>完全無料（クレジットカード不要）</li>
                    </ul>
                    <p class="text-xs text-blue-600 mt-2">
                        設定方法は<a href="/docs/google_books_api_setup.md" target="_blank" class="underline">こちら</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 統計情報 -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
                    データベース統計
                </h2>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">総書籍数</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($d_stats['total_books']); ?></p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">説明文あり</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($d_stats['books_with_description']); ?></p>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">説明文なし</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($d_stats['books_without_description']); ?></p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">カバー率</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $d_stats['coverage_percentage']; ?>%</p>
                    </div>
                </div>
                
                <!-- プログレスバー -->
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span>説明文取得進捗</span>
                        <span><?php echo $d_stats['coverage_percentage']; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-500" 
                             style="width: <?php echo $d_stats['coverage_percentage']; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 一括更新フォーム -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        <i class="fas fa-sync-alt text-green-600 mr-2"></i>
                        一括更新
                    </h2>
                </div>
                <div class="px-6 py-4">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_batch">
                        
                        <div class="mb-4">
                            <label for="limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                更新件数（API制限があるため少なめに設定してください）
                            </label>
                            <select name="limit" id="limit" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="5">5件</option>
                                <option value="10" selected>10件</option>
                                <option value="20">20件</option>
                                <option value="50">50件</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            <i class="fas fa-play mr-2"></i>
                            説明文がない本を更新
                        </button>
                    </form>
                    
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p class="text-xs text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Google Books APIには使用制限があります。大量更新は避けてください。
                        </p>
                    </div>
                </div>
            </div>

            <!-- 個別更新フォーム -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        <i class="fas fa-book text-blue-600 mr-2"></i>
                        個別更新
                    </h2>
                </div>
                <div class="px-6 py-4">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_single">
                        
                        <div class="mb-4">
                            <label for="asin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ASIN / ISBN
                            </label>
                            <input type="text"
                                   name="asin"
                                   id="asin"
                                   placeholder="例: B00ABCDEFG"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <i class="fas fa-search mr-2"></i>
                            指定した本の説明文を更新
                        </button>
                    </form>
                    
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-xs text-blue-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            特定の本の説明文を更新したい場合はASINを入力してください。
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 最近更新された本 -->
        <?php if (!empty($d_recent_books)): ?>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mt-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    <i class="fas fa-clock text-indigo-600 mr-2"></i>
                    最近更新された本
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
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">カテゴリ</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">更新日時</th>
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
                                <?php 
                                if (!empty($book['google_categories'])) {
                                    $categories = json_decode($book['google_categories'], true);
                                    echo htmlspecialchars(implode(', ', array_slice($categories ?: [], 0, 2)));
                                }
                                ?>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">
                                <?php echo date('m/d H:i', strtotime($book['google_data_updated_at'])); ?>
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