<?php
if(!defined('CONFIG')) {
    error_log('direct access detected...');
    die('reference for this file is not allowed.');
}

// メインコンテンツを生成
ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- ヘッダー -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-8">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fas fa-graduation-cap text-readnest-primary mr-2"></i>
                    ReadNest チュートリアル
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    ReadNestの主要な機能をインタラクティブに学習できます。
                    各ステップをクリックして、詳しい説明をご覧ください。
                </p>
            </div>
        </div>

        <!-- チュートリアルオプション -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- インタラクティブチュートリアル -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <i class="fas fa-play text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 ml-3">インタラクティブチュートリアル</h2>
                    </div>
                    <p class="text-gray-600 mb-4">
                        画面上でステップごとに機能を説明します。初めての方におすすめです。
                    </p>
                    <button onclick="readNestOnboarding.start()" 
                            class="w-full px-4 py-2 bg-readnest-primary text-white rounded hover:bg-readnest-primary-dark transition-colors">
                        <i class="fas fa-play-circle mr-2"></i>
                        チュートリアルを開始
                    </button>
                </div>
            </div>

            <!-- クイックガイド -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <i class="fas fa-book-open text-green-600 dark:text-green-400 text-xl"></i>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 ml-3">クイックガイド</h2>
                    </div>
                    <p class="text-gray-600 mb-4">
                        主要機能の使い方を素早く確認できます。経験者の方向けです。
                    </p>
                    <a href="#quick-guide" 
                       class="block w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-center">
                        <i class="fas fa-arrow-down mr-2"></i>
                        ガイドを見る
                    </a>
                </div>
            </div>
        </div>

        <!-- クイックガイドセクション -->
        <div id="quick-guide" class="space-y-6">
            <!-- 基本機能 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        基本機能
                    </h2>
                    
                    <div class="space-y-6">
                        <!-- 本の追加 -->
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">1</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">本を追加する</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-3">
                                    検索ボックスにタイトル、著者名、ISBNを入力して本を検索できます。
                                </p>
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                                    <code class="text-sm text-gray-700 dark:text-gray-300">ヒント: ISBNコードでの検索が最も正確です</code>
                                </div>
                            </div>
                        </div>

                        <!-- 読書ステータス -->
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">2</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">読書ステータスを設定</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-3">
                                    本を追加したら、以下のステータスを設定できます：
                                </p>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="bg-blue-50 dark:bg-blue-900/30 p-2 rounded text-center">
                                        <span class="text-sm font-medium text-blue-800 dark:text-blue-300">読みたい</span>
                                    </div>
                                    <div class="bg-yellow-50 dark:bg-yellow-900/30 p-2 rounded text-center">
                                        <span class="text-sm font-medium text-yellow-800 dark:text-yellow-300">読書中</span>
                                    </div>
                                    <div class="bg-green-50 dark:bg-green-900/30 p-2 rounded text-center">
                                        <span class="text-sm font-medium text-green-800 dark:text-green-300">読了</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 進捗記録 -->
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">3</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">読書進捗を記録</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-3">
                                    現在のページ数を入力して、読書の進捗を可視化できます。
                                </p>
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">進捗</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">150 / 300 ページ</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: 50%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 応用機能 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                        <i class="fas fa-rocket text-purple-500 mr-2"></i>
                        応用機能
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- お気に入り -->
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <i class="fas fa-star text-yellow-500 mr-1"></i>
                                お気に入り機能
                            </h3>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• 特別な本をお気に入りに登録</li>
                                <li>• ドラッグ&ドロップで並び替え</li>
                                <li>• 公開/非公開の設定可能</li>
                            </ul>
                        </div>

                        <!-- レビュー -->
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <i class="fas fa-pen text-blue-500 mr-1"></i>
                                レビュー機能
                            </h3>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• 5段階評価で本を評価</li>
                                <li>• 詳細なレビューを投稿</li>
                                <li>• 他の読者と感想を共有</li>
                            </ul>
                        </div>

                        <!-- 統計 -->
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <i class="fas fa-chart-bar text-green-500 mr-1"></i>
                                読書統計
                            </h3>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• 月別・年別の読書グラフ</li>
                                <li>• ジャンル別の分析</li>
                                <li>• 読書ペースの可視化</li>
                            </ul>
                        </div>

                        <!-- カレンダー -->
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <i class="fas fa-calendar text-purple-500 mr-1"></i>
                                読書カレンダー
                            </h3>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• 日々の読書記録を可視化</li>
                                <li>• 読書習慣の継続状況</li>
                                <li>• 月間の読書パターン</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ヒントとコツ -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                        ヒントとコツ
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">毎日の読書習慣</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">少しずつでも毎日進捗を記録することで、読書習慣が身につきます。</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">レビューの活用</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">読了後すぐにレビューを書くことで、本の内容を長く記憶に留められます。</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">目標設定</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">月間や年間の読書目標を設定して、モチベーションを維持しましょう。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 追加のヘルプ -->
        <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
            <div class="flex items-center">
                <i class="fas fa-question-circle text-blue-600 dark:text-blue-400 text-2xl mr-3"></i>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">さらにヘルプが必要ですか？</h3>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        <a href="/help.php" class="text-blue-600 dark:text-blue-400 hover:underline">ヘルプページ</a>で詳細な情報をご覧いただけます。
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>