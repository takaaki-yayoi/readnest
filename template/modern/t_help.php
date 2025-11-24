<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-800 dark:bg-gray-900 min-h-screen">
    <!-- ヘッダーセクション -->
    <div class="bg-gradient-to-r from-readnest-primary to-readnest-accent dark:from-gray-800 dark:to-gray-700 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-6">
                <i class="fas fa-question-circle text-6xl opacity-80"></i>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold mb-4">ヘルプ・使い方</h1>
            <p class="text-xl text-white opacity-90">
                ReadNestの使い方やよくある質問をご案内します
            </p>
            <div class="mt-6">
                <a href="/tutorial.php" class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-800 text-readnest-primary dark:text-readnest-accent rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors font-medium">
                    <i class="fas fa-graduation-cap mr-2"></i>
                    インタラクティブチュートリアルを開始
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- 検索ボックス -->
        <div class="mb-12">
            <div class="max-w-2xl mx-auto">
                <div class="relative">
                    <input type="text" 
                           id="help-search"
                           placeholder="ヘルプ内容を検索..."
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 dark:text-gray-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 目次 -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 text-center">目次</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="#getting-started" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-play-circle text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">はじめに</span>
                    </div>
                </a>
                <a href="#account-creation" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-user-plus text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">アカウント作成とログイン</span>
                    </div>
                </a>
                <a href="#first-steps" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-rocket text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">初めにやること</span>
                    </div>
                </a>
                <a href="#add-books" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-plus-circle text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">本の追加</span>
                    </div>
                </a>
                <a href="#reading-management" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-book-open text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">読書管理</span>
                    </div>
                </a>
                <a href="#favorites" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-star text-yellow-500 text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">お気に入り機能</span>
                    </div>
                </a>
                <a href="/my_reviews.php" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-pen-to-square text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">マイレビュー</span>
                    </div>
                </a>
                <a href="#likes" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-heart text-red-500 text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">いいね機能</span>
                    </div>
                </a>
                <a href="#bookshelf" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-bookmark text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">本棚の使い方</span>
                    </div>
                </a>
                <a href="#global-search" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-globe text-purple-500 text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">グローバル検索</span>
                    </div>
                </a>
                <a href="#profile" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-user text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">プロフィール設定</span>
                    </div>
                </a>
                <a href="#tags-social" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-tags text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">タグ・共有機能</span>
                    </div>
                </a>
                <a href="#reading-calendar" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-check text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">読書カレンダー</span>
                    </div>
                </a>
                <a href="#reading-insights" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-brain text-purple-500 text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">読書インサイト</span>
                        <span class="ml-2 text-xs bg-gradient-to-r from-purple-600 to-pink-600 text-white px-2 py-0.5 rounded-full">New</span>
                    </div>
                </a>
                <a href="#new-features" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-sparkles text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">新機能</span>
                        <span class="ml-2 text-xs bg-gradient-to-r from-purple-600 to-blue-600 text-white px-2 py-0.5 rounded-full">ダークモード対応</span>
                    </div>
                </a>
                <a href="#ai-features" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-robot text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">AI機能</span>
                        <span class="ml-2 text-xs bg-gradient-to-r from-green-600 to-blue-600 text-white px-2 py-0.5 rounded-full">拡充</span>
                    </div>
                </a>
                <a href="#google-login" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fab fa-google text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">Googleログイン</span>
                    </div>
                </a>
                <a href="#x-integration" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-black mr-3" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        <span class="font-medium text-gray-900 dark:text-gray-100">X（Twitter）連携</span>
                    </div>
                </a>
                <a href="#api-integration" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-plug text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">API連携（Claude.ai）</span>
                    </div>
                </a>
                <a href="/leveling_guide.php" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-trophy text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">レベリングシステム</span>
                    </div>
                </a>
                <a href="#faq" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-question text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">よくある質問</span>
                    </div>
                </a>
                <a href="#contact-form" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-readnest-primary text-xl mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">お問い合わせ</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- ヘルプコンテンツ -->
        <div class="space-y-12" x-data="{ activeSection: '' }">
            
            <!-- はじめに -->
            <section id="getting-started" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-play-circle text-readnest-primary mr-3"></i>
                    はじめに
                </h2>
                
                <div class="prose prose-lg max-w-none">
                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-6">
                        ReadNestは、あなたの読書生活をより豊かにするための読書管理ツールです。
                        本の記録から読書仲間との交流まで、様々な機能を提供しています。
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-readnest-beige dark:bg-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">📚 読書記録</h3>
                            <p class="text-gray-600 dark:text-gray-400">読んだ本、読書中の本、これから読みたい本を管理できます。</p>
                        </div>
                        <div class="bg-readnest-beige dark:bg-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">📊 進捗管理</h3>
                            <p class="text-gray-600 dark:text-gray-400">読書の進捗をページ数で記録し、目標達成をサポートします。</p>
                        </div>
                        <div class="bg-readnest-beige dark:bg-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">⭐ レビュー機能</h3>
                            <p class="text-gray-600 dark:text-gray-400">読んだ本の感想や評価を記録・共有できます。</p>
                        </div>
                        <div class="bg-readnest-beige dark:bg-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">👥 コミュニティ</h3>
                            <p class="text-gray-600 dark:text-gray-400">他の読書家とつながり、おすすめの本を発見できます。</p>
                        </div>
                        <div class="bg-readnest-beige dark:bg-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">🏆 レベリングシステム</h3>
                            <p class="text-gray-600 dark:text-gray-400">読書の成果を可視化し、レベルアップで称号を獲得できます。</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- アカウント作成とログイン -->
            <section id="account-creation" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-user-plus text-readnest-primary mr-3"></i>
                    アカウント作成とログイン
                </h2>
                
                <div class="prose prose-lg max-w-none">
                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-6">
                        ReadNestには2つのログイン方法があります。Googleアカウントでのログインが最も簡単で、おすすめです。
                    </p>
                    
                    <div class="space-y-8">
                        <!-- Googleログイン -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <svg class="w-6 h-6 mr-2" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                Googleアカウントでログイン（推奨）
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                最も簡単で安全な方法です。すでにGoogleアカウントをお持ちの方は、ワンクリックでログインできます。
                            </p>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="dark:text-gray-200">簡単ログイン</strong> - パスワードを覚える必要がありません
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="dark:text-gray-200">高セキュリティ</strong> - Googleの強固なセキュリティで保護されます
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="dark:text-gray-200">自動アカウント作成</strong> - 初めての方でも自動的にアカウントが作成されます
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="/" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                                    トップページからGoogleでログイン
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- 通常のメール登録 -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-envelope text-gray-600 dark:text-gray-400 mr-2"></i>
                                メールアドレスで登録
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                従来の方法でアカウントを作成することもできます。
                            </p>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-start">
                                    <span class="text-gray-600 dark:text-gray-400 mr-2">1.</span>
                                    <div class="dark:text-gray-300">
                                        トップページの「新規登録」ボタンをクリック
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-gray-600 dark:text-gray-400 mr-2">2.</span>
                                    <div class="dark:text-gray-300">
                                        メールアドレスとパスワードを入力
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-gray-600 dark:text-gray-400 mr-2">3.</span>
                                    <div class="dark:text-gray-300">
                                        確認メールのリンクをクリックして登録完了
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- アカウント連携 -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                                <i class="fas fa-link text-yellow-600 mr-2"></i>
                                既存アカウントとGoogleアカウントの連携
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                すでにメールアドレスで登録済みの方も、Googleアカウントと連携できます。
                            </p>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        同じメールアドレスでGoogleログインすると、自動的に連携確認画面が表示されます
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-shield-alt text-green-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        連携後は、どちらの方法でもログインできるようになります
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ログイントラブル -->
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-6">
                            <h4 class="font-semibold text-red-900 dark:text-red-100 mb-2">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                ログインできない場合
                            </h4>
                            <ul class="space-y-2 text-red-800 dark:text-red-200 text-sm">
                                <li>• パスワードを忘れた場合：ログイン画面の「パスワードを忘れた方」から再設定</li>
                                <li>• Googleログインエラー：ブラウザのキャッシュをクリアして再試行</li>
                                <li>• それでも解決しない場合：<a href="#contact-form" class="text-readnest-primary hover:underline">お問い合わせ</a>ください</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 初めにやること -->
            <section id="first-steps" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-rocket text-readnest-primary mr-3"></i>
                    初めにやること
                </h2>
                
                <div class="prose prose-lg max-w-none">
                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-6">
                        ReadNestに登録したら、まず以下の設定を行うことをおすすめします。
                        快適に読書管理をスタートできます。
                    </p>
                    
                    <div class="space-y-6">
                        <!-- プロフィール設定 -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                                <span class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">1</span>
                                プロフィールを設定する
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                あなたの読書プロフィールを充実させましょう。
                            </p>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="dark:text-gray-200">プロフィール画像をアップロード</strong> - 
                                        <a href="/account.php" class="text-readnest-primary hover:underline">アカウント設定</a>から画像を設定
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="dark:text-gray-200">自己紹介を書く</strong> - 
                                        好きなジャンルや読書の目標などを記入
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="dark:text-gray-200">年間目標読書数を設定</strong> - 
                                        目標を設定して進捗を可視化
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 公開設定 -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                                <span class="bg-green-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">2</span>
                                公開設定を確認する
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                プライバシー設定を確認して、安心して使えるようにしましょう。
                            </p>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-start">
                                    <i class="fas fa-shield-alt text-blue-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="dark:text-gray-200">読書記録の公開設定</strong> - 
                                        公開/非公開を選択できます
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-gray-600 dark:text-gray-400 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        公開設定は<a href="/account.php" class="text-readnest-primary hover:underline">アカウント設定</a>からいつでも変更可能です
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-file-contract text-gray-600 dark:text-gray-400 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        詳しくは<a href="/terms.php#privacy" class="text-readnest-primary hover:underline">プライバシーポリシー</a>をご確認ください
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 最初の本を追加 -->
                        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                                <span class="bg-yellow-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">3</span>
                                最初の本を追加する
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                今読んでいる本や、最近読んだ本を追加してみましょう。
                            </p>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-start">
                                    <i class="fas fa-book text-amber-600 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        <a href="/add_book.php" class="text-readnest-primary hover:underline font-medium">本を追加</a>ページから検索
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-star text-yellow-500 mt-1 mr-2"></i>
                                    <div class="dark:text-gray-300">
                                        読み終わった本にはレビューと評価を付けられます
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ヒント -->
                        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-6">
                            <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-2 flex items-center">
                                <i class="fas fa-lightbulb text-purple-600 mr-2"></i>
                                ヒント
                            </h4>
                            <ul class="space-y-2 text-purple-800 text-sm">
                                <li>• タグ機能を使って本を整理すると、後で探しやすくなります</li>
                                <li>• 読書進捗を記録すると、グラフで可視化されます</li>
                                <li>• 他のユーザーのレビューを参考に、次に読む本を見つけられます</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 本の追加 -->
            <section id="add-books" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-plus-circle text-readnest-primary mr-3"></i>
                    本の追加
                </h2>
                
                <div class="space-y-8">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">1. キーワード検索で追加</h3>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                            <div class="flex items-start space-x-4">
                                <div class="bg-readnest-primary text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">1</div>
                                <div class="flex-1">
                                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ヘッダーの検索ボックスまたは「本を追加」ページで書籍名や著者名を入力します。</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 mt-4">
                            <div class="flex items-start space-x-4">
                                <div class="bg-readnest-primary text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">2</div>
                                <div class="flex-1">
                                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">検索結果から追加したい本を選択し、読書状況を設定します。</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AI検索機能の紹介 -->
                        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg p-6 mt-4">
                            <h4 class="font-semibold text-orange-900 dark:text-orange-100 mb-3 flex items-center">
                                <i class="fas fa-magic mr-2"></i>
                                AI検索を活用しよう
                            </h4>
                            <p class="text-orange-800 dark:text-orange-200 mb-3">
                                「AI検索」トグルをONにすると、自然な言葉で本を検索できます：
                            </p>
                            <ul class="space-y-2 text-sm text-orange-700 dark:text-orange-300">
                                <li><i class="fas fa-check-circle mr-2"></i>「元気が出るビジネス書」</li>
                                <li><i class="fas fa-check-circle mr-2"></i>「夏に読みたい爽やかな小説」</li>
                                <li><i class="fas fa-check-circle mr-2"></i>「初心者向けの料理本」</li>
                            </ul>
                            <p class="text-sm text-orange-600 dark:text-orange-400 mt-3">
                                気分やテーマで本を探したい時に便利です。
                            </p>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            2. バーコード読み取りで追加
                        </h3>
                        <div class="bg-gradient-to-r from-blue-50 to-green-50 dark:from-blue-900/20 dark:to-green-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center">
                                        <i class="fas fa-barcode mr-2"></i>
                                        カメラでISBNバーコードを読み取り
                                    </h4>
                                    <div class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                                        <div class="flex items-center space-x-2">
                                            <span class="w-6 h-6 bg-blue-600 dark:bg-blue-700 text-white rounded-full flex items-center justify-center text-xs">1</span>
                                            <span>本を追加ページで「📱 バーコード」ボタンをタップ</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="w-6 h-6 bg-blue-600 dark:bg-blue-700 text-white rounded-full flex items-center justify-center text-xs">2</span>
                                            <span>カメラアクセスを許可</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="w-6 h-6 bg-blue-600 dark:bg-blue-700 text-white rounded-full flex items-center justify-center text-xs">3</span>
                                            <span>本の裏面のISBNバーコードをカメラに向ける</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="w-6 h-6 bg-blue-600 dark:bg-blue-700 text-white rounded-full flex items-center justify-center text-xs">4</span>
                                            <span>自動的に本が検索・表示される</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-3 flex items-center">
                                        <i class="fas fa-lightbulb mr-2"></i>
                                        読み取りのコツ
                                    </h4>
                                    <div class="space-y-2 text-sm text-green-800">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-ruler text-yellow-500"></i>
                                            <span>バーコードから10-15cm離す</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-sun text-yellow-500"></i>
                                            <span>明るい場所で読み取る</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-hand text-blue-500"></i>
                                            <span>手振れに注意してゆっくり動かす</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-mouse-pointer text-purple-500"></i>
                                            <span>ピンボケ時は画面をタップしてフォーカス調整</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">3. 手動で追加</h3>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                            <p class="text-blue-800">
                                検索で見つからない本は、「手動で本を追加」から書籍情報を直接入力して追加できます。
                            </p>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">読書状況の種類</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <div class="w-3 h-3 bg-yellow-400 rounded-full mr-2"></div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">いつか買う</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">興味があって将来読みたい本</p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <div class="w-3 h-3 bg-red-400 rounded-full mr-2"></div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">買ったけどまだ読んでない</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">購入済みだが未読の本</p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <div class="w-3 h-3 bg-blue-400 rounded-full mr-2"></div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">読んでいるところ</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">現在読書中の本</p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <div class="w-3 h-3 bg-green-400 rounded-full mr-2"></div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">読み終わった！</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">最近読み終わった本</p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 md:col-span-2">
                                <div class="flex items-center mb-2">
                                    <div class="w-3 h-3 bg-gray-400 rounded-full mr-2"></div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">昔読んだ</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">過去に読んだことがある本（読了日を設定可能）</p>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-amber-100 dark:bg-amber-900/30 rounded text-sm">
                            <p class="text-amber-800 dark:text-amber-300">
                                <i class="fas fa-lightbulb mr-1"></i>
                                <strong>ヒント：</strong>「昔読んだ」を選択すると、読了日を過去の日付に設定できます。これにより、過去の読書履歴を正確に記録できます。
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 読書管理 -->
            <section id="reading-management" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-book-open text-readnest-primary mr-3"></i>
                    読書管理
                </h2>
                
                <div class="space-y-8">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">進捗の記録</h3>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">本の詳細ページで「読書状況を編集」をクリックすると、以下の情報を更新できます：</p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                <li>読書状況（上記の5つの状態）</li>
                                <li>現在のページ数</li>
                                <li>5段階評価</li>
                                <li>感想・レビュー（URLは自動的にリンクに変換されます）</li>
                                <li>タグの追加・編集</li>
                                <li>読了日の手動設定</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-history text-blue-600 mr-2"></i>
                            読書履歴の編集
                        </h3>
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                過去の読書記録を後から追加・編集・削除できます。読書カレンダーや連続記録も自動的に更新されます。
                            </p>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-clock-rotate-left text-blue-600 mr-3 mt-1"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">過去の進捗を追加</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            本の詳細ページから「読書履歴を編集」をクリックし、過去の日付と時刻を指定して読書進捗を追加できます。
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-edit text-indigo-600 mr-3 mt-1"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">時刻まで細かく記録</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            日付だけでなく時刻も指定できるため、1日に複数回読書した記録も正確に管理できます。
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-calendar-check text-green-600 mr-3 mt-1"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">読書カレンダーへの反映</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            追加・編集した履歴は読書カレンダーに自動反映され、連続記録もバックフィルされます。
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-flag-checkered text-purple-600 mr-3 mt-1"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">ステータスの自動更新</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            最終ページの進捗を追加すると自動的に「読了」ステータスに変更されます。
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
                                <p class="text-sm text-amber-800">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>注意：</strong>読書履歴を編集すると、本の更新日が最新の読書記録の日時に合わせて変更されます。これにより本棚の並び順が自然になります。
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-camera text-readnest-primary mr-2"></i>
                            表紙画像の変更
                        </h3>
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-6">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                本の表紙画像を自由に変更できます。
                            </p>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-purple-600 mr-3 mt-1"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">本の詳細ページで表紙画像の右上にある<i class="fas fa-camera mx-1"></i>ボタンをクリック</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-purple-600 mr-3 mt-1"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">Google Books、OpenLibrary、国立国会図書館から候補画像を自動取得</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-purple-600 mr-3 mt-1"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">お好きな画像ファイルをアップロード（JPEG、PNG、GIF、WebP対応）</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-lightbulb text-yellow-500 mr-3 mt-1"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300"><strong>ヒント：</strong>本棚ページで「<i class="fas fa-image-slash mx-1"></i>表紙なし」フィルタを使うと、表紙が未設定の本だけを表示できます</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            読了日の編集
                        </h3>
                        <div class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border border-green-200 dark:border-green-700 rounded-lg p-6">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                過去に読んだ本を登録する際や、実際に読み終えた日を記録したい場合に、読了日を手動で設定できます。
                            </p>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-calendar-check text-green-600 dark:text-green-400 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">読了日の設定方法</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            本の詳細ページで「読書状況を編集」をクリックし、読了日フィールドで日付を選択します。
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-edit text-blue-600 dark:text-blue-400 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">インライン編集</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            本棚の読了日表示部分をクリックすると、その場で読了日を編集できます。
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-history text-purple-600 dark:text-purple-400 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">過去の本の登録</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            「昔読んだ本」として登録し、読了日を過去の日付に設定することで、読書履歴を正確に記録できます。
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 読了日設定時の動作 -->
                            <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
                                <h4 class="font-semibold text-amber-900 mb-3">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    読了日を設定したときの動作
                                </h4>
                                
                                <div class="space-y-4 text-sm">
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-amber-900 dark:text-amber-100 mb-2">📊 統計への反映</p>
                                        <p class="text-amber-800">
                                            読了日を設定すると、その月の読書冊数として計上されます。
                                            例：6月6日に読了日を設定 → 6月の読書冊数に含まれます
                                        </p>
                                    </div>
                                    
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-amber-900 dark:text-amber-100 mb-2">📅 読書カレンダーへの表示</p>
                                        <div class="pl-4 border-l-2 border-amber-300">
                                            <p class="text-amber-800 mb-2">
                                                <strong class="dark:text-gray-200">読書進捗がない本の場合：</strong><br>
                                                読了日に自動的に読了記録が作成され、カレンダーのその日付に表示されます。
                                            </p>
                                            <p class="text-amber-800">
                                                <strong class="dark:text-gray-200">読書進捗がある本の場合：</strong><br>
                                                読了日の設定のみ更新され、カレンダーは実際の読書記録の日付で表示されます。
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-amber-900 dark:text-amber-100 mb-2">🔔 X（Twitter）への投稿</p>
                                        <p class="text-amber-800">
                                            読了日を手動で設定した場合、Xへの自動投稿は行われません。
                                            過去の読書記録を静かに整理できます。
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-blue-100 dark:bg-blue-900/30 rounded text-sm">
                                <p class="text-blue-800 dark:text-blue-300">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    <strong>ヒント：</strong>過去に読んだ本を整理したい場合は、「昔読んだ」ステータスで追加し、読了日を設定しましょう。
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            レビューの管理
                        </h3>
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-6">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                書いたレビューを一元管理できる「マイレビュー」機能が追加されました。
                            </p>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-list-alt text-purple-600 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">レビュー一覧の確認</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            マイレビューページで、これまでに書いたすべてのレビューを確認できます。
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-chart-bar text-indigo-600 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">統計情報</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            レビュー総数、平均評価、評価分布などの統計が表示されます。
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-link text-blue-600 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-100">URL自動リンク</strong>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                            レビュー内のURLは自動的にクリック可能なリンクに変換されます。
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 p-3 bg-purple-100 dark:bg-purple-900/30 rounded text-sm">
                                <p class="text-purple-800 dark:text-purple-300">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    <strong>アクセス方法：</strong>トップページ、本棚、プロフィールページからアクセスできます。
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">読書目標の設定</h3>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                            <p class="text-blue-800 dark:text-blue-200 mb-3">
                                年間目標読書数を設定して、読書のモチベーションを維持しましょう。
                            </p>
                            <div class="bg-white dark:bg-gray-700 rounded p-4">
                                <p class="font-medium text-gray-900 dark:text-gray-100 mb-2">設定方法：</p>
                                <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                    <li>ヘッダーのユーザーメニューから「設定」を選択</li>
                                    <li>「プロフィール設定」タブで「年間目標読書数」を入力</li>
                                    <li>トップページで進捗状況を確認できます</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">レベリングシステム</h3>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                            <p class="text-yellow-800 dark:text-yellow-300 mb-3">
                                読了した本のページ数に応じてレベルアップし、特別な称号を獲得できます。
                            </p>
                            <div class="bg-white dark:bg-gray-700 rounded p-4">
                                <p class="font-medium text-gray-900 dark:text-gray-100 mb-2">レベルアップの仕組み：</p>
                                <ul class="list-disc list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                    <li>読了・既読の本のページ数が累積されます</li>
                                    <li>100ページから開始し、レベルが上がるごとに必要ページ数が増加</li>
                                    <li>特定レベルで「本の虫」「読書家」「博識者」などの称号を獲得</li>
                                    <li>レベルバッジは活動履歴やランキングなどで表示されます</li>
                                </ul>
                                <div class="mt-3">
                                    <a href="/leveling_guide.php" class="inline-flex items-center text-yellow-700 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300 font-medium">
                                        <i class="fas fa-trophy mr-2"></i>
                                        レベリングシステムの詳細を見る
                                        <i class="fas fa-arrow-right ml-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- お気に入り機能 -->
            <section id="favorites" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-star text-yellow-500 mr-3"></i>
                    お気に入り機能
                </h2>
                
                <div class="space-y-8">
                    <!-- お気に入りの基本説明 -->
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-heart text-red-500 mr-2"></i>
                            お気に入りとは？
                        </h3>
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            本棚の中から特に大切な本、思い入れのある本、おすすめしたい本などを「お気に入り」として登録できる機能です。
                            お気に入りに登録した本は専用ページで管理でき、プロフィールページでも公開できます。
                        </p>
                    </div>
                    
                    <!-- お気に入りの登録方法 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-plus-circle text-blue-500 mr-2"></i>
                            お気に入りの登録方法
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">📚 本棚から登録</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    本棚の一覧で、各本の右下にある星アイコンをクリックすると、お気に入りに登録されます。
                                </p>
                                <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded text-xs">
                                    <span class="text-gray-400">☆</span> → クリック → <span class="text-yellow-500">★</span>
                                </div>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">📖 本の詳細ページから登録</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    本の詳細ページの表紙画像にある星アイコンをクリックしても登録できます。
                                </p>
                                <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded text-xs">
                                    ツールチップで状態を確認できます
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 公開設定機能 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-eye text-green-500 mr-2"></i>
                            プロフィールへの公開設定
                        </h3>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 rounded-lg p-4">
                            <p class="text-blue-900 dark:text-blue-100 mb-4">
                                <strong>※ この機能は公開設定をしているユーザーのみ利用可能です</strong>
                            </p>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-eye text-green-500 mt-1 mr-3 w-4"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-200">公開（緑の目アイコン）</strong>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">プロフィールページで他のユーザーに表示されます</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-eye-slash text-gray-400 mt-1 mr-3 w-4"></i>
                                    <div class="dark:text-gray-300">
                                        <strong class="text-gray-900 dark:text-gray-200">非公開（グレーの目アイコン）</strong>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">自分だけが見ることができます</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">個別設定</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    お気に入りページで、各本の左上にある目のアイコンをクリックして、個別に公開/非公開を切り替えられます。
                                </p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">一括設定</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    「公開設定」ボタンから、すべてのお気に入りを一括で公開または非公開に設定できます。
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- お気に入りページの機能 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-list text-purple-500 mr-2"></i>
                            お気に入りページの機能
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="bg-gradient-to-br from-yellow-50 to-white dark:from-yellow-900/20 dark:to-gray-800 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">📚 一覧表示</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    お気に入りに登録した本を一覧で確認できます
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-yellow-50 to-white dark:from-yellow-900/20 dark:to-gray-800 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">⭐ 解除機能</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    右上の星アイコンでお気に入りから削除できます
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-yellow-50 to-white dark:from-yellow-900/20 dark:to-gray-800 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">👁️ 公開管理</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    公開設定ユーザーは表示の管理ができます
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/20 dark:to-gray-800 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">🔀 並び替え機能</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    ドラッグ&ドロップで自由に順番を変更できます
                                </p>
                            </div>
                        </div>
                        
                        <!-- ドラッグ&ドロップ機能の詳細 -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3">
                                <i class="fas fa-arrows-alt mr-2"></i>
                                ドラッグ&ドロップでの並び替え
                            </h4>
                            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                <li>「並び替えモード」ボタンをクリックして、編集モードに切り替え</li>
                                <li>本の左上に表示される青いハンドル（⋮）をドラッグ</li>
                                <li>好きな位置にドロップして順番を変更</li>
                                <li>「完了」ボタンをクリックして並び順を保存</li>
                            </ol>
                            <div class="mt-3 p-2 bg-white dark:bg-gray-700 rounded">
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    並び順は自動的に保存され、次回アクセス時も維持されます
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ヒント -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                        <h4 class="font-semibold text-amber-900 mb-2">
                            <i class="fas fa-lightbulb mr-2"></i>
                            活用のヒント
                        </h4>
                        <ul class="space-y-2 text-sm text-amber-800">
                            <li>• 人生のベスト本をお気に入りに登録して、プロフィールで紹介しましょう</li>
                            <li>• 繰り返し読みたい本をお気に入りにして、すぐアクセスできるようにしましょう</li>
                            <li>• おすすめしたい本を公開設定にして、他のユーザーと共有しましょう</li>
                            <li>• プライベートな本は非公開設定で自分だけの記録として保管できます</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- いいね機能 -->
            <section id="likes" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-heart text-red-500 mr-3"></i>
                    いいね機能
                </h2>

                <div class="space-y-8">
                    <!-- いいねの基本説明 -->
                    <div class="bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-thumbs-up text-red-500 mr-2"></i>
                            いいね機能とは？
                        </h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            他のユーザーの読書活動やレビューに「いいね」を送ることで、共感や応援の気持ちを伝えられる機能です。
                            いいねを受け取ると通知が届き、読書コミュニティとのつながりを感じることができます。
                        </p>
                    </div>

                    <!-- いいねできる対象 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            いいねできる投稿
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">📚 読書活動</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    「読書活動」ページで表示される、みんなの読書状況の更新にいいねできます。
                                </p>
                                <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                    <li>• 読み始めた報告</li>
                                    <li>• 読了報告</li>
                                    <li>• 読書進捗の更新</li>
                                </ul>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">✍️ レビュー</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    本の詳細ページで表示される、他のユーザーのレビューにいいねできます。
                                </p>
                                <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                    <li>• 本の感想・書評</li>
                                    <li>• 評価とコメント</li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>注意：</strong>自分の投稿にはいいねできません
                            </p>
                        </div>
                    </div>

                    <!-- いいねの方法 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-hand-pointer text-blue-500 mr-2"></i>
                            いいねの方法
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="bg-red-100 dark:bg-red-900/30 rounded-full p-2 mr-4">
                                    <i class="far fa-heart text-red-500"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">1. ハートアイコンをクリック</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        投稿の下にあるハートアイコンをクリックするだけでいいねが送られます
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="bg-red-500 rounded-full p-2 mr-4">
                                    <i class="fas fa-heart text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">2. いいねの取り消し</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        もう一度クリックすると、いいねを取り消すことができます
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- いいねページの使い方 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-list text-purple-500 mr-2"></i>
                            いいねページの使い方
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            ユーザーメニューから「いいね」を選択すると、いいねページにアクセスできます。
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    <i class="fas fa-heart text-red-500 mr-2"></i>いいねした投稿
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    あなたがいいねした読書活動やレビューの一覧が表示されます。後で見返したい投稿の記録として活用できます。
                                </p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    <i class="fas fa-bell text-blue-500 mr-2"></i>いいねされた投稿
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    あなたの投稿が受け取ったいいねの履歴が表示されます。誰がいいねしてくれたかも確認できます。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 通知機能 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-bell text-orange-500 mr-2"></i>
                            通知機能
                        </h3>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <div class="relative mr-4 mt-1">
                                        <i class="fas fa-heart text-gray-400 text-xl"></i>
                                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">3</span>
                                    </div>
                                    <div>
                                        <strong class="text-gray-900 dark:text-gray-100">ホームページの通知バッジ</strong>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            新しいいいねがあると、ホームページ上部のハートボタンに赤いバッジが表示されます。
                                            数字は未確認のいいね数を示します。
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 text-xl mr-4 mt-1"></i>
                                    <div>
                                        <strong class="text-gray-900 dark:text-gray-100">バッジのクリア</strong>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            ハートボタンをクリックして「いいねされた投稿」タブを開くと、通知バッジがクリアされます。
                                            次回新しいいいねがあるまで、バッジは表示されません。
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ヒント -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                        <h4 class="font-semibold text-amber-900 dark:text-amber-100 mb-2">
                            <i class="fas fa-lightbulb mr-2"></i>
                            活用のヒント
                        </h4>
                        <ul class="space-y-2 text-sm text-amber-800 dark:text-amber-200">
                            <li>• 共感した読書活動にいいねして、読書仲間とつながりましょう</li>
                            <li>• 参考になったレビューにいいねすることで、感謝の気持ちを伝えられます</li>
                            <li>• いいねした投稿を後で見返して、気になる本を見つけることができます</li>
                            <li>• 受け取ったいいねは、あなたの投稿が役立った証。モチベーションアップにつながります</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 本棚の使い方 -->
            <section id="bookshelf" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-bookmark text-readnest-primary mr-3"></i>
                    本棚の使い方
                </h2>
                
                <div class="space-y-8">
                    <!-- 基本機能 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-readnest-beige to-white dark:from-gray-700 dark:to-gray-800 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">📖 絞り込み表示</h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">読書状況別に本を絞り込んで表示できます。現在読んでいる本だけを見たい時などに便利です。</p>
                        </div>
                        <div class="bg-gradient-to-br from-readnest-beige to-white dark:from-gray-700 dark:to-gray-800 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">🔍 検索機能</h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">本棚内で特定の本を検索できます。著者名、タイトル、タグで検索可能です。</p>
                        </div>
                        <div class="bg-gradient-to-br from-readnest-beige to-white dark:from-gray-700 dark:to-gray-800 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">📊 統計表示</h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">読書統計や月別の読書数などを確認できます。読書インサイトで多面的な分析も可能。</p>
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded p-3 text-sm">
                                <p class="text-blue-800 dark:text-blue-200">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    <strong>新機能：</strong>読書履歴ページのグラフをクリックすると、その期間・評価の本の一覧を表示できます。
                                </p>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-readnest-beige to-white dark:from-gray-700 dark:to-gray-800 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">🧠 読書インサイト連携</h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">読書インサイトとシームレスに連携。ワンクリックでAI分析や視覚的な読書分析へ移動できます。</p>
                        </div>
                        <div class="bg-gradient-to-br from-readnest-beige to-white dark:from-gray-700 dark:to-gray-800 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">📝 マイレビュー機能</h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">本棚ページ上部の「マイレビュー」ボタンから、書いたレビューの一覧を確認できます。</p>
                        </div>
                    </div>
                    
                    <!-- 検索機能の詳細 -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-search text-blue-600 mr-3"></i>
                            高度な検索機能
                        </h3>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-4">
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">検索タイプ：</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                    <h5 class="font-medium text-gray-900 mb-2">📖 タイトル検索</h5>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">本のタイトルで検索</p>
                                </div>
                                <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                    <h5 class="font-medium text-gray-900 mb-2">✍️ 著者検索</h5>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">著者名で検索</p>
                                </div>
                                <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                    <h5 class="font-medium text-gray-900 mb-2">🏷️ タグ検索</h5>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">付けたタグで検索</p>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-blue-100 dark:bg-blue-900/30 rounded text-sm">
                                <p class="text-blue-800 dark:text-blue-300">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    <strong>連携機能：</strong>著者検索やタグ検索の結果から「マップで確認」をクリックすると、読書インサイトのマップモードで該当項目をすぐに確認できます。
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 読書インサイトとの連携 -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-link text-green-600 mr-3"></i>
                            読書インサイトとの連携
                        </h3>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-4">
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">連携ポイント：</h4>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-arrow-right text-green-600 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">本棚 → 読書インサイト</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">ヘッダーの「読書インサイト」ボタンから各種分析ページへ</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-arrow-left text-green-600 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">読書インサイト → 本棚</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">マップのバブルやクラスタから該当する本の検索結果へ</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-sync text-green-600 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">多面的な分析</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">概要、AI分類、マップ、ペース分析の4つの視点で読書を分析</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-green-100 dark:bg-green-900/30 rounded text-sm">
                                <p class="text-green-800 dark:text-green-300">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <strong>効率的な使い方：</strong>読書インサイトで全体的な傾向を把握し、本棚で詳細な管理を行う、というように使い分けることで効率的な読書管理が可能です。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- データの品質向上 -->
            <section id="data-quality" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-database text-blue-600 mr-3"></i>
                    データの品質向上
                </h2>
                
                <div class="prose prose-lg max-w-none">
                    <p class="text-xl text-gray-700 dark:text-gray-300 mb-8">
                        ReadNestでは、書籍データの品質を継続的に改善しています。
                    </p>
                    
                    <div class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                                <i class="fas fa-user-edit text-purple-500 mr-3"></i>
                                著者名の自動補完
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                古い本で著者名が登録されていない場合も、書籍データベースから自動的に著者情報を取得・表示します。
                            </p>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    本棚、お気に入り、プロフィール、活動履歴など、すべてのページで著者名が正しく表示されます
                                </p>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                                <i class="fas fa-image text-green-500 mr-3"></i>
                                表紙画像の管理
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                表紙画像の変更・アップロード機能により、見た目の統一感を保ちながら本棚を管理できます。
                            </p>
                            <ul class="list-disc list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                <li>画像URLの直接指定</li>
                                <li>ローカル画像のアップロード</li>
                                <li>表紙なしフィルタで未設定の本を簡単に発見</li>
                            </ul>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                                <i class="fas fa-calendar-alt text-orange-500 mr-3"></i>
                                更新日の正確な管理
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                読書状態の変更時のみ更新日を記録し、管理者による修正では更新日を変更しません。
                            </p>
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded p-3">
                                <p class="text-sm text-amber-800">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    お気に入りページでは、本の更新日（読書活動の日付）が表示されます
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 rounded-lg">
                        <p class="text-blue-900 dark:text-blue-100">
                            <i class="fas fa-lightbulb mr-2"></i>
                            <strong>ヒント：</strong>データに不整合がある場合は、本の詳細ページから手動で修正することもできます。
                        </p>
                    </div>
                </div>
            </section>
            
            <!-- グローバル検索 -->
            <section id="global-search" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-globe text-purple-500 mr-3"></i>
                    グローバル検索
                </h2>
                
                <div class="space-y-8">
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg p-6">
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            グローバル検索は、ReadNest全体から書籍やレビューを横断的に検索できる強力な機能です。
                            他のユーザーが登録した本やレビューも含めて検索対象となるため、新しい本との出会いが期待できます。
                        </p>
                    </div>
                    
                    <!-- アクセス方法 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">アクセス方法</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-2">
                                    <i class="fas fa-desktop text-blue-500 mr-2"></i>
                                    デスクトップ
                                </h4>
                                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                    <li>• ヘッダー右上の検索ボックス</li>
                                    <li>• ナビゲーション「探す」→「グローバル検索」</li>
                                    <li>• ショートカット: / キー</li>
                                </ul>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-2">
                                    <i class="fas fa-mobile-alt text-green-500 mr-2"></i>
                                    モバイル
                                </h4>
                                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                    <li>• ヘッダー右上の地球儀アイコン</li>
                                    <li>• ハンバーガーメニュー→「グローバル検索」</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 検索機能 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">検索機能の詳細</h3>
                        <div class="space-y-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">検索タイプ</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="flex items-start">
                                        <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-1 rounded text-xs mr-2">すべて</span>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">書籍とレビューを横断検索</p>
                                    </div>
                                    <div class="flex items-start">
                                        <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-1 rounded text-xs mr-2">タイトル</span>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">書籍タイトルのみを検索</p>
                                    </div>
                                    <div class="flex items-start">
                                        <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-1 rounded text-xs mr-2">著者</span>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">著者名のみを検索</p>
                                    </div>
                                    <div class="flex items-start">
                                        <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-1 rounded text-xs mr-2">ISBN</span>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">ISBNコードで正確に検索</p>
                                    </div>
                                    <div class="flex items-start">
                                        <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded text-xs mr-2">レビュー</span>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">レビュー内容から検索</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 mb-3">ソート機能</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="flex items-start">
                                        <i class="fas fa-sort-amount-down text-gray-400 mr-2"></i>
                                        <div class="dark:text-gray-300">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">関連度順</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">検索キーワードとの関連性が高い順</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-users text-gray-400 mr-2"></i>
                                        <div class="dark:text-gray-300">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">読者数順</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">登録ユーザー数が多い/少ない順</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-star text-gray-400 mr-2"></i>
                                        <div class="dark:text-gray-300">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">評価順</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">平均評価が高い/低い順</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-font text-gray-400 mr-2"></i>
                                        <div class="dark:text-gray-300">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">タイトル・著者順</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">五十音順（あ→ん / ん→あ）</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 活用のヒント -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">活用のヒント</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/20 dark:to-gray-800 rounded-lg p-4">
                                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                                    <i class="fas fa-book-reader mr-2"></i>
                                    新しい本を発見
                                </h4>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    他のユーザーが読んでいる本から、自分の興味に合う新しい本を見つけられます
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-green-50 to-white dark:from-green-900/20 dark:to-gray-800 rounded-lg p-4">
                                <h4 class="font-medium text-green-900 dark:text-green-100 mb-2">
                                    <i class="fas fa-comments mr-2"></i>
                                    レビューを参考に
                                </h4>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    他の読者のレビューを読んで、本選びの参考にできます
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-white dark:from-purple-900/20 dark:to-gray-800 rounded-lg p-4">
                                <h4 class="font-medium text-purple-900 dark:text-purple-100 mb-2">
                                    <i class="fas fa-chart-line mr-2"></i>
                                    人気の本をチェック
                                </h4>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    読者数順でソートして、今人気の本を確認できます
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-orange-50 to-white dark:from-orange-900/20 dark:to-gray-800 rounded-lg p-4">
                                <h4 class="font-medium text-orange-900 dark:text-orange-100 mb-2">
                                    <i class="fas fa-star mr-2"></i>
                                    高評価本を探す
                                </h4>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    評価順でソートして、評価の高い本を見つけられます
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 注意事項 -->
                    <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h4 class="font-semibold text-gray-900 mb-3">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            ご注意
                        </h4>
                        <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                            <li>• グローバル検索では、プライバシー設定で公開を許可しているユーザーの情報のみが表示されます</li>
                            <li>• 検索結果はキャッシュされるため、最新の情報が反映されるまで時間がかかる場合があります</li>
                            <li>• レビュー検索では、50文字以上のレビューのみが対象となります</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- プロフィール設定 -->
            <section id="profile" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-user text-readnest-primary mr-3"></i>
                    プロフィール設定
                </h2>
                
                <div class="space-y-6">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">アカウント設定</h3>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">ヘッダーのユーザーメニューから「設定」を選択すると、以下の設定ができます：</p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                <li>ニックネームの変更</li>
                                <li>メールアドレスの変更</li>
                                <li>パスワードの変更</li>
                                <li>読書記録の公開設定</li>
                                <li>年間目標読書数の設定</li>
                                <li>自己紹介の編集</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">プライバシー設定</h3>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                            <p class="text-yellow-800 dark:text-yellow-300">
                                <strong>公開設定について：</strong><br>
                                「読書記録を公開する」に設定すると、他のユーザーがあなたの読書記録を見ることができます。
                                プライベートで利用したい場合は「公開しない」を選択してください。
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 読書カレンダー -->
            <section id="reading-calendar" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-calendar-check text-emerald-600 mr-3"></i>
                    読書カレンダー
                </h2>
                
                <div class="space-y-8">
                    <!-- 概要 -->
                    <div class="bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">📅 読書習慣を可視化</h3>
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            読書カレンダーは、あなたの読書習慣を視覚的に表示し、継続的な読書をサポートする機能です。
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>日々の読書記録を色分け表示</li>
                                    <li>連続読書日数の追跡</li>
                                    <li>月間・年間の読書統計</li>
                                    <li>読んだ本のサムネイル表示</li>
                                    <li>今日読む本のリマインダー</li>
                                </ul>
                            </div>
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">こんな方におすすめ：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>読書習慣を身につけたい</li>
                                    <li>毎日の読書を継続したい</li>
                                    <li>読書の記録を振り返りたい</li>
                                    <li>モチベーションを維持したい</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 使い方 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">🚀 読書カレンダーの使い方</h3>
                        <div class="grid gap-4">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">1</div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">アクセス方法</p>
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">本棚ページのヘッダーから「読書カレンダー」ボタンをクリック、またはナビゲーションメニューから選択</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">2</div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">読書を記録</p>
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">本の読書進捗を更新すると、その日のカレンダーに自動的に記録されます</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">3</div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">振り返りと分析</p>
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">月間の読書日数、連続記録、読書パターンを確認して、習慣形成に活用</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 機能詳細 -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">💡 便利な機能</h3>
                        <div class="space-y-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    <i class="fas fa-fire text-orange-500 mr-2"></i>
                                    連続読書記録
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 mb-3">
                                    毎日読書を続けると連続記録が更新されます。継続日数に応じて特別なメッセージやマイルストーンが表示され、モチベーション維持をサポートします。
                                </p>
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 mt-3">
                                    <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-3 text-sm">📊 達成マイルストーン</h5>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-seedling text-green-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">3日 - 読書習慣スタート</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-fire text-orange-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">7日 - 1週間達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-medal text-yellow-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">30日 - 1ヶ月達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-crown text-yellow-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">100日 - 100日達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-trophy text-orange-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">200日 - 200日達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-crown text-indigo-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">365日 - 1年達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-gem text-yellow-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">500日 - 500日達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-crown text-red-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">730日 - 2年達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-gem text-purple-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">1000日 - 1000日達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-crown text-purple-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">1095日 - 3年達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-gem text-pink-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">1825日 - 5年達成</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-crown text-yellow-500"></i>
                                            <span class="text-gray-700 dark:text-gray-300">3650日 - 10年達成</span>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-3 italic">
                                        ※ その他にも14日、21日、50日、150日、300日、4年、6年、7年、8年、9年のマイルストーンもあります
                                    </p>
                                </div>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 mb-2">
                                    <i class="fas fa-book text-emerald-500 mr-2"></i>
                                    今日の読書セクション
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                    今日読んだ本と、まだ読んでいない読書中の本が表示されます。本をクリックして直接進捗を更新できます。
                                </p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 mb-2">
                                    <i class="fas fa-images text-purple-500 mr-2"></i>
                                    本のサムネイル表示
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                    カレンダーの各日付に読んだ本の表紙画像が小さく表示されます。ホバーすると本のタイトルが表示され、クリックで詳細ページへ移動できます。
                                </p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 mb-2">
                                    <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                                    統計とトップページ連携
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                    読書カレンダーのデータはトップページの統計セクションにも反映されます。「今月の読書カレンダー」から詳細な記録を確認できます。
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ヒント -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                        <h4 class="font-semibold text-yellow-900 mb-2">
                            <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
                            習慣化のコツ
                        </h4>
                        <ul class="space-y-2 text-yellow-800 dark:text-yellow-300 text-sm">
                            <li>• 毎日同じ時間に読書する習慣をつけましょう（朝起きてすぐ、寝る前など）</li>
                            <li>• 最初は10分から始めて、徐々に時間を増やしていきましょう</li>
                            <li>• 読書カレンダーの緑色の丸が途切れないように意識すると継続しやすいです</li>
                            <li>• 連続記録が途切れても気にせず、また今日から始めましょう</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 新機能 -->
            <section id="new-features" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-sparkles text-yellow-500 mr-3"></i>
                    新機能
                </h2>
                
                <div class="space-y-8">
                    <!-- ダークモード対応 -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-800 dark:to-slate-800 rounded-lg p-6 border-2 border-gray-200 dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">🌙 ダークモード対応</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            目に優しいダークモードが全ページに対応しました。夜間の読書記録や長時間の利用でも目の疲れを軽減できます。
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な特徴：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>システム設定に連動した自動切り替え</li>
                                    <li>ヘッダーの月アイコンで手動切り替え可能</li>
                                    <li>すべてのページで統一されたデザイン</li>
                                    <li>選択状態が記憶され、次回も同じモードで表示</li>
                                    <li>グラフやチャートも最適化済み</li>
                                </ul>
                            </div>
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">切り替え方法：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li><strong>自動切り替え：</strong>OSの設定に従って自動で切り替わります</li>
                                    <li><strong>手動切り替え：</strong>ヘッダー右上の月アイコンをクリック</li>
                                    <li><strong>設定の保存：</strong>選択したモードはブラウザに記憶されます</li>
                                </ul>
                                <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                    <p class="text-sm text-blue-800 dark:text-blue-200">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <strong>ヒント：</strong>夜間は自動的にダークモードに切り替えることで、目の負担を大幅に軽減できます。
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 読書ペース分析 -->
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">📊 読書ペース分析</h3>
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            トップページに読書ペース分析機能が追加されました。月間目標との関係が一目で分かるようになりました。
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>今月の平均読書ペース表示</li>
                                    <li>月間目標達成に必要なペース計算</li>
                                    <li>目標達成状況の可視化（順調/要加速）</li>
                                    <li>時間帯別読書パターンのヒートマップ</li>
                                </ul>
                            </div>
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">活用方法：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>日々の読書ペースを確認</li>
                                    <li>目標達成に向けた調整</li>
                                    <li>最適な読書時間帯の発見</li>
                                    <li>読書習慣の改善</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- 読書カレンダーの改善 -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">📅 読書カレンダーの改善</h3>
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            読書カレンダーのレイアウトが改善され、より見やすくなりました。
                        </p>
                        <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li><strong>PC表示の最適化</strong>：1年分のヒートマップが画面内に収まるように調整</li>
                            <li><strong>月ラベルの改善</strong>：縦書きから横書きに変更し、視認性が向上</li>
                            <li><strong>スマートフォン対応</strong>：画面サイズに応じた適切なセルサイズ</li>
                            <li><strong>ヒートマップと統計情報の分離</strong>：情報が整理され見やすく</li>
                        </ul>
                    </div>

                    <!-- 作家クラウド機能 -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">✍️ 作家クラウド機能</h3>
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            あなたが読んだ本の作家を可視化する機能が追加されました。読書傾向が一目で分かります。
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>作家名のクラウド表示</li>
                                    <li>読んだ冊数による文字サイズ変化</li>
                                    <li>読書状況による色分け</li>
                                    <li>高評価作家のハイライト（⭐）</li>
                                    <li>作家ポータルへのリンク</li>
                                </ul>
                            </div>
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">アクセス方法：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>本棚ページの「作家クラウド」タブ</li>
                                    <li>トップページの作家クラウドボックス</li>
                                    <li>「すべての作家を見る」から一覧表示</li>
                                    <li>「作家ポータル」から詳細分析</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 読書履歴・統計ページ -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6 mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">📈 読書履歴・統計ページ</h3>
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            詳細な読書統計と履歴を確認できる専用ページです。
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>年別・月別・日別の読書冊数グラフ</li>
                                    <li>読書ページ数の推移グラフ</li>
                                    <li>累積値のオーバーレイ表示</li>
                                    <li>評価分布の円グラフ</li>
                                    <li>最近読了した本の一覧</li>
                                </ul>
                            </div>
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">インタラクティブ機能：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li><strong>グラフクリック：</strong>棒グラフをクリックでその期間の本を表示</li>
                                    <li><strong>評価フィルタ：</strong>円グラフをクリックでその評価の本を表示</li>
                                    <li><strong>モーダル表示：</strong>本の一覧をポップアップで確認</li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-emerald-100 rounded text-sm">
                            <p class="text-emerald-800">
                                <i class="fas fa-hand-pointer mr-1"></i>
                                グラフの各要素はクリック可能です。カーソルを合わせるとポインターに変わります。
                            </p>
                        </div>
                    </div>
                    
                    <!-- 統合タブUI -->
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">🗂️ 統合タブUI</h3>
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            本棚ページの読書統計、AIアドバイザー、作家クラウド、タグクラウドが1つのタブUIに統合されました。
                        </p>
                        <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li><strong>読書統計タブ</strong>：読書状況の概要と読書カレンダー・詳細統計へのリンク</li>
                            <li><strong>AIアドバイザータブ</strong>：AI による読書推薦、傾向分析、チャレンジ提案</li>
                            <li><strong>作家クラウドタブ</strong>：読んだ本の作家を可視化、作家ポータルへのリンク</li>
                            <li><strong>タグクラウドタブ</strong>：人気タグと最近のタグの切り替え表示</li>
                        </ul>
                    </div>
                    
                    <!-- マイレビュー機能 -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">📝 マイレビュー機能</h3>
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            自分が書いたレビューを一覧で確認できる「マイレビュー」ページが追加されました。
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>レビュー総数の確認</li>
                                    <li>平均評価と評価分布の表示</li>
                                    <li>新着順・評価順での並び替え</li>
                                    <li>レビュー内容の検索機能</li>
                                    <li>URL自動リンク機能</li>
                                </ul>
                            </div>
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">アクセス方法：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>トップページの読書記録セクション</li>
                                    <li>本棚ページの上部ボタン</li>
                                    <li>プロフィールページの統計欄</li>
                                    <li>各レビューから詳細ページへ移動可能</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- セキュリティ強化 -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">🔒 セキュリティ強化</h3>
                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                            本の追加機能のセキュリティが強化されました。
                        </p>
                        <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <li><strong>レート制限の緩和</strong>：1分間に20冊まで本を追加可能に</li>
                            <li><strong>CSRF保護の強化</strong>：より安全な本の追加・編集</li>
                            <li><strong>エラーロギング</strong>：問題発生時の迅速な対応</li>
                            <li><strong>入力検証の改善</strong>：不正なデータの防止</li>
                        </ul>
                    </div>

                    <!-- 利用上の注意 -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600 p-4">
                        <h4 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>
                            ご利用にあたって
                        </h4>
                        <ul class="space-y-1 text-yellow-700 dark:text-yellow-400 text-sm">
                            <li>• 新機能は順次追加・改善されていきます</li>
                            <li>• 不具合を発見された場合は、お問い合わせフォームからご連絡ください</li>
                            <li>• ご意見・ご要望もお待ちしております</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- X連携機能 -->
            <section id="x-integration" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <svg class="w-8 h-8 text-black mr-3" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    X（Twitter）連携
                </h2>
                
                <div class="space-y-8">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">📱 自動投稿機能</h3>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">読書記録を自動的にX（旧Twitter）に投稿できます。</p>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">投稿されるタイミング</p>
                                        <ul class="text-sm text-gray-600 dark:text-gray-400 mt-1 list-disc list-inside ml-4">
                                            <li>本を「読みたい」リストに追加したとき</li>
                                            <li>本を読み始めたとき</li>
                                            <li>読書進捗を更新したとき（読書メモも含まれます）</li>
                                            <li>本を読み終わったとき</li>
                                            <li>レビューを投稿したとき</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="flex items-start mt-4">
                                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-gray-800 dark:text-gray-200">@dokusho アカウントについて</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            日記を「公開」に設定しているユーザーの読書活動は、ReadNest公式アカウント 
                                            <a href="https://x.com/dokusho" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium">@dokusho</a> 
                                            からも自動的に投稿されます。これにより、ReadNestコミュニティ全体で読書の輪が広がります。
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                            ※ プライバシー設定で日記を「非公開」にしている場合、@dokushoからの投稿は行われません。
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start mt-4">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-3"></i>
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-gray-800 dark:text-gray-200">文字数制限について</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Xの文字数制限（280文字）を考慮して、日本語は2文字、英数字は1文字として計算されます。
                                            長いレビューは自動的に省略され、ReadNestへのリンクが付加されます。
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">🔗 連携方法</h3>
                        <div class="grid gap-4">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">1</div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 mb-1">アカウント設定にアクセス</p>
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">ユーザーメニューから「設定」→「X連携設定」タブを選択</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">2</div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">Xと連携</p>
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">「Xと連携する」ボタンをクリックし、Xの認証画面で承認</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">3</div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">投稿設定をカスタマイズ</p>
                                        <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">投稿するイベントを個別に選択できます</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">📝 投稿例</h3>
                        <div class="bg-gray-900 text-white rounded-lg p-4 font-mono text-sm">
                            <p class="mb-2">@あなたのユーザー名 さんが「ノルウェイの森」を読み始めました！ #読書記録 #ReadNest</p>
                            <p class="text-blue-400">https://readnest.jp/book/12345</p>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                        <p class="text-yellow-800 dark:text-yellow-300">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>注意：</strong>読書記録が「公開」設定の場合のみ投稿されます。
                        </p>
                    </div>
                </div>
            </section>

            <!-- API連携（Claude.ai） -->
            <section id="api-integration" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-plug text-readnest-primary mr-3"></i>
                    API連携（Claude.ai）
                </h2>

                <div class="space-y-8">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">🤖 Claude.aiと連携</h3>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                            <p class="text-gray-700 dark:text-gray-300 mb-4">
                                Claude.aiからあなたの本棚データに直接アクセスできるようになります。
                                読書統計の確認や本の検索などを、会話形式で簡単に行えます。
                            </p>
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                <p class="text-sm text-blue-800 dark:text-blue-300">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>できること：</strong>本棚の閲覧、読書統計の取得、本の検索など
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">🔗 連携手順</h3>
                        <div class="grid gap-4">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold flex-shrink-0">1</div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-2">OAuthクライアントを作成</p>
                                        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc list-inside ml-2">
                                            <li>
                                                <a href="/account.php" class="text-readnest-primary hover:underline">アカウント設定</a> →
                                                「API連携設定」タブ →
                                                「OAuthクライアントを管理」をクリック
                                            </li>
                                            <li>クライアント名: <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">Claude.ai</code></li>
                                            <li>リダイレクトURI: <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">https://claude.ai/api/mcp/auth_callback</code></li>
                                            <li><strong>Client IDとClient Secretをコピー</strong>（一度しか表示されません）</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold flex-shrink-0">2</div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-2">Claude.aiで設定</p>
                                        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc list-inside ml-2">
                                            <li>Claude.aiで「Custom Connectors」を開く</li>
                                            <li>「Add custom connector」をクリック</li>
                                            <li>Name: <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">ReadNest</code></li>
                                            <li>URL: <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">https://readnest.jp/mcp/messages.php</code></li>
                                            <li>Advanced settingsを開いて、Client IDとSecretを入力</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold flex-shrink-0">3</div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-2">認可して完了</p>
                                        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc list-inside ml-2">
                                            <li>「Add」→「Connect」をクリック</li>
                                            <li>ReadNestの認可画面で「許可する」をクリック</li>
                                            <li>接続完了！</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">💬 使い方の例</h3>
                        <div class="space-y-3">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-sm font-mono text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-comment text-readnest-primary mr-2"></i>
                                    「本棚の本を見せて」
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-sm font-mono text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-comment text-readnest-primary mr-2"></i>
                                    「読書統計を教えて」
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-sm font-mono text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-comment text-readnest-primary mr-2"></i>
                                    「積読の本をリストアップして」
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-sm font-mono text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-comment text-readnest-primary mr-2"></i>
                                    「読書中の本の進捗は？」
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                        <p class="text-yellow-800 dark:text-yellow-300">
                            <i class="fas fa-shield-alt mr-2"></i>
                            <strong>セキュリティ：</strong>OAuth 2.0を使用した安全な認証です。ReadNestのパスワードをClaude.aiに入力する必要はありません。
                        </p>
                    </div>
                </div>
            </section>

            <!-- タグ・共有機能 -->
            <section id="tags-social" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-tags text-readnest-primary mr-3"></i>
                    タグ・共有機能
                </h2>
                
                <div class="space-y-8">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">タグ機能</h3>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">本にタグを付けて整理・検索できます。</p>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <div class="bg-blue-600 dark:bg-blue-700 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-3 mt-0.5">1</div>
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">タグの追加</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">本の詳細ページで「タグを編集」をクリックし、カンマ区切りでタグを入力します（例：SF, ミステリー, 感動）。</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="bg-blue-600 dark:bg-blue-700 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-3 mt-0.5">2</div>
                                    <div class="dark:text-gray-300">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">タグで検索</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">トップページの「人気のタグ」から、同じタグが付いた本を一覧表示できます。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">ソーシャル共有</h3>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                            <p class="text-blue-800 mb-4">
                                お気に入りの本をSNSで共有できます。本の詳細ページにある共有ボタンから：
                            </p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="bg-white rounded p-3 text-center">
                                    <div class="w-10 h-10 mx-auto mb-2 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center font-bold">X</div>
                                    <p class="text-sm">X (Twitter)</p>
                                </div>
                                <div class="bg-white rounded p-3 text-center">
                                    <div class="w-10 h-10 mx-auto mb-2 bg-blue-600 rounded-full flex items-center justify-center text-white">
                                        <i class="fab fa-facebook-f"></i>
                                    </div>
                                    <p class="text-sm">Facebook</p>
                                </div>
                                <div class="bg-white rounded p-3 text-center">
                                    <div class="w-10 h-10 mx-auto mb-2 bg-green-500 rounded-full flex items-center justify-center text-white">
                                        <i class="fab fa-line"></i>
                                    </div>
                                    <p class="text-sm">LINE</p>
                                </div>
                                <div class="bg-white rounded p-3 text-center">
                                    <div class="w-10 h-10 mx-auto mb-2 bg-gray-50 dark:bg-gray-8000 rounded-full flex items-center justify-center text-white">
                                        <i class="fas fa-link"></i>
                                    </div>
                                    <p class="text-sm">リンクコピー</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- AI機能 -->
            <section id="ai-features" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-robot text-readnest-primary mr-3"></i>
                    AI機能
                </h2>
                
                <div class="prose prose-lg max-w-none">
                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-6">
                        ReadNestは最新のAI技術を活用して、あなたの読書体験をより豊かにする様々な機能を提供しています。
                        これらの機能は、読書の楽しみを広げ、新しい本との出会いをサポートします。
                    </p>
                    
                    <div class="space-y-8">
                        <!-- AI検索機能 -->
                        <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-search text-orange-600 mr-3"></i>
                                AI検索
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                自然な言葉で本を検索できます。「泣ける恋愛小説」「ビジネスで役立つ本」など、気分やテーマで探したい時に便利です。
                            </p>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能:</h4>
                                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>自然な言葉で検索可能</li>
                                    <li>気分やテーマから本を探せる</li>
                                    <li>ジャンルや雰囲気で絞り込み</li>
                                </ul>
                                
                                <h4 class="font-semibold text-gray-900 mt-4">検索例:</h4>
                                <div class="space-y-3">
                                    <div class="bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg">
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                                            <i class="fas fa-quote-left text-orange-400 mr-1"></i>
                                            <strong class="dark:text-gray-200">泣ける恋愛小説</strong>
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">→ 感動的な恋愛小説を幅広く検索</p>
                                    </div>
                                    <div class="bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg">
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                                            <i class="fas fa-quote-left text-orange-400 mr-1"></i>
                                            <strong class="dark:text-gray-200">心が温まる家族の物語</strong>
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">→ 感情やテーマから関連する本を検索</p>
                                    </div>
                                    <div class="bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg">
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                                            <i class="fas fa-quote-left text-orange-400 mr-1"></i>
                                            <strong class="dark:text-gray-200">人工知能について学べる入門書</strong>
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">→ テーマやレベルから適切な本を提案</p>
                                    </div>
                                </div>
                                
                                <h4 class="font-semibold text-gray-900 mt-4">使い方:</h4>
                                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>検索ボックスに自然な言葉で検索したい内容を入力</li>
                                    <li>「AI検索」ボタンをクリック（または検索ボックス下のAI検索トグルをON）</li>
                                    <li>AIが内容を解析し、関連する本を表示</li>
                                    <li>通常の検索に戻したい場合は、AI検索トグルをOFFに</li>
                                </ol>
                                
                                <div class="mt-4 p-3 bg-orange-100 rounded text-sm">
                                    <p class="text-orange-800">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <strong class="dark:text-gray-200">ヒント：</strong>覚えている情報をできるだけ詳しく入力すると、より正確な検索結果が得られます。
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AI書評アシスタント -->
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-900/30 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-pen-fancy text-purple-600 mr-3"></i>
                                AI書評アシスタント
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                簡単な感想を入力するだけで、AIが詳細で魅力的な書評を自動生成。書評作成の負担を軽減します。
                            </p>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能:</h4>
                                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>感想から詳細な書評を生成</li>
                                    <li>書評の長さを自由に調整</li>
                                    <li>適切なタグを自動提案</li>
                                </ul>
                                
                                <h4 class="font-semibold text-gray-900 mt-4">使い方:</h4>
                                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>読み終わった本の詳細ページで「読書状況を編集」をクリック</li>
                                    <li>感想欄に簡単な感想やメモを入力</li>
                                    <li>「AI書評アシスタント」ボタンをクリック</li>
                                    <li>生成された書評を確認し、必要に応じて編集</li>
                                </ol>
                                
                                <div class="mt-4 p-3 bg-purple-100 dark:bg-purple-900/30 rounded text-sm">
                                    <p class="text-purple-800 dark:text-purple-300">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <strong class="dark:text-gray-200">ヒント：</strong>具体的な感想（好きだった場面、印象的なセリフなど）を書くと、より個性的な書評が生成されます。
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AI推薦機能 NEW! -->
                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg p-6 relative">
                            <span class="absolute top-4 right-4 bg-red-500 text-white text-xs px-2 py-1 rounded-full font-bold">NEW!</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-robot text-indigo-600 mr-3"></i>
                                AI推薦機能
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                あなたの読書傾向をAIが分析し、好みに合った本を提案する新機能です。文章スタイル、テーマ、内容の類似性を総合的に判断します。
                            </p>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能:</h4>
                                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>高評価本を基準にした推薦</li>
                                    <li>個別の本に基づく類似本検索</li>
                                    <li>お気に入り本からの推薦</li>
                                    <li>リアルタイムでembedding生成</li>
                                </ul>
                                
                                <h4 class="font-semibold text-gray-900 mt-4">利用場所:</h4>
                                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li><strong>メインページ：</strong>ナビゲーションの「AI推薦」から</li>
                                    <li><strong>本の詳細ページ：</strong>ページ下部の「AIが見つけた似た本」セクション</li>
                                    <li><strong>お気に入りページ：</strong>お気に入り本に基づく推薦</li>
                                    <li><strong>本棚ページ：</strong>AI読書アドバイザー内のリンク</li>
                                </ul>
                                
                                <h4 class="font-semibold text-gray-900 mt-4">使い方:</h4>
                                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>ナビゲーションの「AI推薦」をクリック</li>
                                    <li>推薦タイプを選択（おすすめ、特定の本ベース、人気）</li>
                                    <li>類似度の高い本が自動的に表示されます</li>
                                    <li>気になる本をクリックして詳細を確認</li>
                                </ol>
                                
                                <div class="mt-4 p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded text-sm">
                                    <p class="text-indigo-800 dark:text-indigo-300">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <strong class="dark:text-gray-200">ヒント：</strong>より多くの本を読んで評価をつけると、推薦の精度が向上します。
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 読書アシスタント NEW! -->
                        <div class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 rounded-lg p-6 relative">
                            <span class="absolute top-4 right-4 bg-gradient-to-r from-green-600 to-blue-600 text-white text-xs px-2 py-1 rounded-full font-bold animate-pulse">AI</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-robot text-green-600 mr-3"></i>
                                読書アシスタント
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                AIアシスタントがあなたの読書に関する質問に答えます。本の情報、読書進捗、データベース検索など、幅広い機能を提供します。
                            </p>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能:</h4>
                                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li><strong>読書データアクセス：</strong>7つの専用ツールで正確にデータを取得
                                        <ul class="list-circle list-inside ml-6 mt-1 text-sm">
                                            <li>本棚・読書統計・検索</li>
                                            <li>読書履歴・よく読むジャンル</li>
                                            <li>レビュー・本の詳細情報</li>
                                        </ul>
                                    </li>
                                    <li><strong>自然言語での質問：</strong>「今年読了した本は？」「評価が4以上の本」「積読の中からおすすめは？」など</li>
                                    <li><strong>本の推薦：</strong>読書傾向に基づいた本の提案</li>
                                    <li><strong>会話履歴：</strong>過去の会話を保存・復元・削除</li>
                                    <li><strong>動的リンク：</strong>本の状況に応じて適切なページへ誘導</li>
                                </ul>
                                
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mt-4">アクセス方法:</h4>
                                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>ナビゲーションバーの「アシスタント」リンクから</li>
                                    <li>各ページ右下のアシスタントアイコンから（reading_assistant.phpページ以外）</li>
                                </ul>
                                
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mt-4">使い方:</h4>
                                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>アシスタントページまたはオーバーレイを開く</li>
                                    <li>質問をチャット欄に入力</li>
                                    <li>サンプル質問ボタンから選択も可能</li>
                                    <li>AIが適切なツールを自動選択してデータを取得</li>
                                    <li>会話履歴から過去の会話を復元可能</li>
                                </ol>

                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mt-4">質問例:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                    <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                        <strong class="text-gray-900 dark:text-gray-100">📊 統計:</strong> 「読了した本は何冊？」「今年読んだ本は？」
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                        <strong class="text-gray-900 dark:text-gray-100">🔍 検索:</strong> 「村上春樹の本を検索して」「ミステリー小説を探して」
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                        <strong class="text-gray-900 dark:text-gray-100">📅 履歴:</strong> 「今月読んだ本の一覧」「2024年の読書履歴」
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                        <strong class="text-gray-900 dark:text-gray-100">📝 レビュー:</strong> 「レビューを書いた本を見せて」「高評価の本は？」
                                    </div>
                                </div>

                                <div class="mt-4 p-3 bg-green-100 dark:bg-green-900/30 rounded text-sm">
                                    <p class="text-green-800 dark:text-green-300">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <strong class="dark:text-gray-200">ヒント：</strong>複数のツールを組み合わせた複雑な質問にも対応できます。例：「積読の中から次に読むべき本を推薦して」
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AI読書アドバイザー -->
                        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-robot text-purple-600 mr-3"></i>
                                AI読書アドバイザー
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                あなたの読書履歴を分析して、次に読むべき本を提案します。3つの機能で読書体験をサポートします。
                            </p>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-6">
                                <!-- おすすめの本 -->
                                <div class="border-l-4 border-purple-500 pl-4">
                                    <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-2">
                                        <i class="fas fa-book mr-2"></i>おすすめの本
                                    </h4>
                                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">読書履歴に基づく推薦</p>
                                    <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                        <li>本棚ページのAI読書アドバイザーセクションを開く</li>
                                        <li>「AIに推薦してもらう」ボタンをクリック</li>
                                        <li>AIが読書履歴を分析して本を推薦</li>
                                        <li>「検索して追加」または「手動で追加」ボタンで本棚に追加</li>
                                    </ol>
                                </div>
                                
                                <!-- 読書傾向分析 -->
                                <div class="border-l-4 border-indigo-500 pl-4">
                                    <h4 class="font-semibold text-indigo-900 mb-2">
                                        <i class="fas fa-chart-line mr-2"></i>読書傾向分析
                                    </h4>
                                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">あなたの読書パターンを分析</p>
                                    <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                        <li>「傾向を分析する」ボタンをクリック</li>
                                        <li>AIがあなたの読書傾向を詳しく分析</li>
                                        <li>好きなジャンル、著者の傾向、読書ペースなどを表示</li>
                                        <li>分析結果を参考に次の読書計画を立てる</li>
                                    </ol>
                                </div>
                                
                                <!-- 読書チャレンジ -->
                                <div class="border-l-4 border-pink-500 pl-4">
                                    <h4 class="font-semibold text-pink-900 mb-2">
                                        <i class="fas fa-trophy mr-2"></i>読書チャレンジ
                                    </h4>
                                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">新しいジャンルに挑戦</p>
                                    <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                        <li>「チャレンジを見る」ボタンをクリック</li>
                                        <li>AIがあなたの読書の幅を広げる提案を作成</li>
                                        <li>未読のジャンルや新しい著者への挑戦を促す</li>
                                        <li>チャレンジに取り組んで読書の世界を広げる</li>
                                    </ol>
                                </div>
                                
                                <div class="mt-4 p-3 bg-purple-100 dark:bg-purple-900/30 rounded text-sm">
                                    <p class="text-purple-800 dark:text-purple-300">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <strong class="dark:text-gray-200">ヒント：</strong>読了済みの本が多いほど、AIはより精度の高い提案ができます。本に評価やタグを付けることで、さらに良い推薦が受けられます。
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AIチャットアシスタント -->
                        <div class="bg-gradient-to-r from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-900/30 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-robot text-pink-600 mr-3"></i>
                                AIチャットアシスタント
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                画面右下のチャットで、本に関する質問や相談が可能。読書の疑問を即座に解決します。
                            </p>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">主な機能:</h4>
                                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>本に関する質問応答</li>
                                    <li>読書相談・アドバイス</li>
                                    <li>ページ内容に応じた提案</li>
                                </ul>
                                
                                <h4 class="font-semibold text-gray-900 mt-4">できること:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                        <h5 class="font-medium text-gray-900 mb-2">📚 読書相談</h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">"SF小説のおすすめを教えて"</p>
                                    </div>
                                    <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                        <h5 class="font-medium text-gray-900 mb-2">🔍 書籍情報</h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">"この本のあらすじを知りたい"</p>
                                    </div>
                                    <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                        <h5 class="font-medium text-gray-900 mb-2">📝 書評作成</h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">"感想を書評にまとめて"</p>
                                    </div>
                                    <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                        <h5 class="font-medium text-gray-900 mb-2">💡 読書分析</h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">"私の読書傾向を分析して"</p>
                                    </div>
                                    <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                        <h5 class="font-medium text-gray-900 mb-2">🏆 読書チャレンジ</h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">"新しいジャンルの本を提案して"</p>
                                    </div>
                                    <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                        <h5 class="font-medium text-gray-900 mb-2">❓ 使い方案内</h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">"ReadNestの機能を教えて"</p>
                                    </div>
                                </div>
                                
                                <h4 class="font-semibold text-gray-900 mt-4">使い方：</h4>
                                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>画面右下の <img src="/favicon.png" alt="AI" class="inline w-6 h-6"> アイコンをクリック</li>
                                    <li>チャットウィンドウが開きます</li>
                                    <li>質問や相談を入力して送信</li>
                                    <li>AIアシスタントが即座に回答</li>
                                    <li>会話は継続できるので、追加の質問も可能</li>
                                </ol>
                                
                                <h4 class="font-semibold text-gray-900 mt-4">特徴：</h4>
                                <div class="space-y-2 text-gray-700 dark:text-gray-300">
                                    <div class="flex items-start">
                                        <i class="fas fa-map-marker-alt text-red-500 mt-1 mr-2"></i>
                                        <span><strong>コンテキスト認識：</strong>現在のページに応じた適切な提案</span>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-history text-red-500 mt-1 mr-2"></i>
                                        <span><strong>会話履歴：</strong>セッション中の会話を記憶</span>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-window-minimize text-red-500 mt-1 mr-2"></i>
                                        <span><strong>最小化機能：</strong>邪魔にならない設計</span>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-robot text-red-500 mt-1 mr-2"></i>
                                        <span><strong>GPT-4o-mini：</strong>最新のAI技術を活用</span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 p-3 bg-red-100 dark:bg-red-900/20 rounded text-sm">
                                    <p class="text-red-800 dark:text-red-200">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        <strong class="dark:text-gray-200">ヒント：</strong>本の詳細ページでAIアシスタントを使うと、その本に関する具体的な質問ができます。
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AI機能の注意事項 -->
                        <div class="mt-8 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-info-circle text-gray-600 dark:text-gray-400 mr-2"></i>
                                AI機能について
                            </h4>
                            <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                    <span>すべてのAI機能は無料でご利用いただけます</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                    <span>AIの提案は参考情報です。最終的な判断はユーザー様にお任せします</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                    <span>プライバシーは保護されており、あなたの読書データは安全に管理されます</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                    <span>AI機能は継続的に改善されており、より精度の高い提案を目指しています</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 読書インサイト -->
            <section id="reading-insights" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-brain text-readnest-primary mr-3"></i>
                    読書インサイト
                    <span class="ml-3 text-sm bg-gradient-to-r from-purple-600 to-pink-600 text-white px-3 py-1 rounded-full">New</span>
                </h2>
                
                <div class="prose prose-lg max-w-none">
                    <p class="text-xl text-gray-700 dark:text-gray-300 mb-8">
                        AI分析と多彩な可視化で、あなたの読書体験を深く理解し、新たな発見をサポートする統合分析ツールです。
                    </p>
                    
                    <!-- 4つのモード紹介 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center">
                                <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                                概要（Overview）
                            </h3>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                読書統計と最近の活動を一覧表示。総読書数、月別推移、最近読んだ本などを確認できます。
                            </p>
                            <a href="/reading_insights.php?mode=overview" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <span>概要を見る</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-6 border border-purple-200 dark:border-purple-700">
                            <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-3 flex items-center">
                                <i class="fas fa-network-wired text-purple-600 mr-2"></i>
                                AI分類（Clusters）
                            </h3>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                AIが本の内容を分析し、テーマ別にクラスタリング。読書傾向や隠れた関連性を発見できます。
                            </p>
                            <a href="/reading_insights.php?mode=clusters" class="inline-flex items-center text-purple-600 hover:text-purple-800 text-sm font-medium">
                                <span>AI分類を見る</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6 border border-green-200 dark:border-green-700">
                            <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-3 flex items-center">
                                <i class="fas fa-map-marked-alt text-green-600 mr-2"></i>
                                読書マップ（Map）
                            </h3>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                著者別・タグ別の読書状況をバブルチャートで可視化。お気に入り著者や読書の偏りが一目瞭然。
                            </p>
                            <a href="/reading_insights.php?mode=map" class="inline-flex items-center text-green-600 hover:text-green-800 text-sm font-medium">
                                <span>マップを見る</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                        
                        <div class="bg-gradient-to-br from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 rounded-lg p-6 border border-orange-200 dark:border-orange-700">
                            <h3 class="text-lg font-semibold text-orange-900 dark:text-orange-100 mb-3 flex items-center">
                                <i class="fas fa-tachometer-alt text-orange-600 mr-2"></i>
                                読書ペース（Pace）
                            </h3>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                読書速度と完読率を詳細分析。ジャンル別の読書パターンや時間管理の改善点を把握できます。
                            </p>
                            <a href="/reading_insights.php?mode=pace" class="inline-flex items-center text-orange-600 hover:text-orange-800 text-sm font-medium">
                                <span>ペース分析を見る</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- 使い方のヒント -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg p-6 border border-indigo-200 dark:border-indigo-700">
                        <h3 class="text-xl font-semibold text-indigo-900 mb-4 flex items-center">
                            <i class="fas fa-lightbulb text-yellow-500 mr-3"></i>
                            活用のヒント
                        </h3>
                        <div class="space-y-3 text-gray-700 dark:text-gray-300">
                            <div class="flex items-start">
                                <span class="text-purple-600 mr-2">📊</span>
                                <p class="text-sm"><strong>定期的にチェック：</strong>月初めに概要モードで先月の読書を振り返り、今月の目標を立てましょう</p>
                            </div>
                            <div class="flex items-start">
                                <span class="text-purple-600 mr-2">🤖</span>
                                <p class="text-sm"><strong>AIクラスタを活用：</strong>意外な本の関連性を発見し、新しいジャンルへの扉を開きましょう</p>
                            </div>
                            <div class="flex items-start">
                                <span class="text-purple-600 mr-2">🗺️</span>
                                <p class="text-sm"><strong>読書マップで偏りチェック：</strong>特定の著者やジャンルに偏っていないか確認し、読書の幅を広げましょう</p>
                            </div>
                            <div class="flex items-start">
                                <span class="text-purple-600 mr-2">⚡</span>
                                <p class="text-sm"><strong>ペース分析で効率化：</strong>ジャンル別の読書速度を把握し、読書計画を最適化しましょう</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- アクセス方法 -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-route text-green-600 mr-3"></i>
                            アクセス方法
                        </h3>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-3">
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">複数の方法でアクセスできます：</h4>
                            <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                <li><strong>本棚ページから</strong>：ヘッダーの「読書インサイト」ボタンをクリック</li>
                                <li><strong>直接アクセス</strong>：メニューから「読書インサイト」を選択</li>
                                <li><strong>URLパラメータ</strong>：?mode=でoverviewp/clusters/map/paceを指定して各モードへ直接移動</li>
                            </ol>
                            
                            <div class="mt-4 p-3 bg-green-100 rounded text-sm">
                                <p class="text-green-800">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    <strong>ヒント：</strong>各モードは画面上部のタブで簡単に切り替えられます。
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 詳細機能説明 -->
                    <div class="space-y-8">
                        <!-- 読書マップモード -->
                        <div class="bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-map text-green-600 mr-3"></i>
                                読書マップの詳細機能
                            </h3>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-4">
                                <h4 class="font-semibold text-gray-900 mb-3">📊 2つのマップモード</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="border border-green-200 rounded-lg p-3">
                                        <h5 class="font-medium text-green-900 dark:text-green-100 mb-2">
                                            <i class="fas fa-sitemap text-green-600 mr-1"></i>
                                            クラシックマップ
                                        </h5>
                                        <ul class="space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                            <li>• 著者別・タグ別の読書分布を表示</li>
                                            <li>• 各カテゴリは読書冊数に応じた大きさ</li>
                                            <li>• シンプルで分かりやすい階層構造</li>
                                            <li>• 高速表示で軽快な動作</li>
                                        </ul>
                                    </div>
                                    <div class="border border-purple-200 rounded-lg p-3">
                                        <h5 class="font-medium text-purple-900 dark:text-purple-100 mb-2">
                                            <i class="fas fa-brain text-purple-600 mr-1"></i>
                                            AI分析マップ
                                        </h5>
                                        <ul class="space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                            <li>• AIが本の内容を分析してクラスタリング</li>
                                            <li>• テーマ別の意味的グループを自動生成</li>
                                            <li>• 各クラスタに表紙画像を表示</li>
                                            <li>• LLMによる詳細な分析と読書提案</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="dark:text-gray-300">
                                        <h4 class="font-semibold text-gray-900 mb-2">🔍 操作方法</h4>
                                        <ul class="space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                            <li>• <strong>クリック</strong>：ボックスをクリックで詳細ポップアップ表示</li>
                                            <li>• <strong>ホバー</strong>：マウスを合わせて基本情報を表示</li>
                                            <li>• <strong>切り替え</strong>：画面上部のボタンでマップモード切替</li>
                                            <li>• <strong>更新</strong>：「マップを更新」で最新データを反映</li>
                                        </ul>
                                    </div>
                                    <div class="dark:text-gray-300">
                                        <h4 class="font-semibold text-gray-900 mb-2">🎨 視覚的特徴</h4>
                                        <ul class="space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                            <li>• <strong>ボックスサイズ</strong>：含まれる本の冊数を反映</li>
                                            <li>• <strong>色分け</strong>：カテゴリやテーマごとに自動配色</li>
                                            <li>• <strong>表紙画像</strong>：AI分析マップでは本の表紙を表示</li>
                                            <li>• <strong>評価表示</strong>：平均評価を★マークで表示</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mt-4 p-3 bg-green-100 dark:bg-green-900/30 rounded text-sm">
                                    <p class="text-green-800 dark:text-green-300">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <strong class="dark:text-gray-200">便利な機能：</strong>AI分析マップは初回表示時に分析を行うため少し時間がかかりますが、結果は1時間キャッシュされるため2回目以降は高速表示されます。
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AI機能の詳細 -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-robot text-purple-600 mr-3"></i>
                                AI分類（クラスタリング）の仕組み
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                OpenAIのEmbedding APIを使用して、レビューや感想の内容を768次元のベクトルに変換し、意味的な類似性に基づいて自動的にグループ化します。
                            </p>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">分析の流れ：</h4>
                                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>あなたが書いたレビューや感想をAIが解析</li>
                                    <li>text-embedding-3-smallモデルで768次元のベクトルに変換</li>
                                    <li>K-means++アルゴリズムで6〜8個のクラスタに分類</li>
                                    <li>GPT-4o-miniが各クラスタの特徴を分析して命名</li>
                                    <li>クラスタごとに表紙画像付きで視覚的に表示</li>
                                </ol>
                                
                                <div class="mt-4 p-3 bg-purple-100 dark:bg-purple-900/30 rounded text-sm">
                                    <p class="text-purple-800 dark:text-purple-300">
                                        <i class="fas fa-sparkles mr-1"></i>
                                        <strong class="dark:text-gray-200">発見の例：</strong>「感動系作品」「エンターテイメント系」「学習・教養系」など、あなたの読書傾向から意外な共通点が見つかります。
                                    </p>
                                </div>
                                
                                <h4 class="font-semibold text-gray-900 mt-4">LLM分析で得られる情報：</h4>
                                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                    <li>クラスタ名（創造的で魅力的な名前）</li>
                                    <li>グループの特徴説明（50文字以内）</li>
                                    <li>共通テーマの抽出（3つ）</li>
                                    <li>読書提案（似た傾向の本の推薦）</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- パフォーマンス情報 -->
                        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-tachometer-alt text-gray-600 dark:text-gray-400 mr-2"></i>
                                パフォーマンス最適化について
                            </h4>
                            <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                    <span>大量のデータ（3000冊以上）でも高速表示</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                    <span>キャッシュ機能により2回目以降の表示が超高速</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                    <span>画像が多くても軽快な操作性を実現</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-database text-blue-500 mt-1 mr-2"></i>
                                    <span>Embeddingデータは24時間キャッシュされ、再計算を最小化</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Googleログイン -->
            <section id="google-login" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fab fa-google text-readnest-primary mr-3"></i>
                    Googleログイン
                </h2>
                
                <div class="prose prose-lg max-w-none">
                    <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-6">
                        ReadNestでは、Googleアカウントを使った簡単で安全なログインが可能です。
                        パスワードを覚える必要がなく、ワンクリックでアクセスできます。
                    </p>
                    
                    <div class="space-y-8">
                        <!-- メリット -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-star text-blue-600 mr-3"></i>
                                Googleログインのメリット
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 mb-2">🔐 セキュリティ</h4>
                                    <ul class="space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                        <li>• Googleの高度なセキュリティで保護</li>
                                        <li>• 2段階認証の利用が可能</li>
                                        <li>• パスワード漏洩の心配なし</li>
                                    </ul>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 mb-2">⚡ 利便性</h4>
                                    <ul class="space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                        <li>• ワンクリックでログイン</li>
                                        <li>• パスワードを覚える必要なし</li>
                                        <li>• 新規登録も自動で完了</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- アカウント管理 -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-user-cog text-green-600 mr-3"></i>
                                アカウント管理
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-2">Googleのみでログインしているユーザー</h4>
                                    <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                        <li>パスワード変更機能は表示されません（不要のため）</li>
                                        <li>アカウント削除時にパスワード入力は不要です</li>
                                        <li>Google連携解除オプションは表示されません</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-2">メールアドレスで登録後、Googleを連携したユーザー</h4>
                                    <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                        <li>両方の方法でログイン可能</li>
                                        <li>パスワード変更が可能</li>
                                        <li>Google連携を解除できます</li>
                                        <li>アカウント削除時はパスワード入力が必要</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 連携方法 -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <i class="fas fa-link text-purple-600 mr-3"></i>
                                既存アカウントとの連携
                            </h3>
                            
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                                すでにメールアドレスで登録済みの方も、簡単にGoogleアカウントと連携できます。
                            </p>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">連携手順：</h4>
                                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>同じメールアドレスのGoogleアカウントでログイン</li>
                                    <li>自動的に表示される連携確認画面で承認</li>
                                    <li>以降は両方の方法でログイン可能に</li>
                                </ol>
                                
                                <div class="mt-4 p-3 bg-purple-100 dark:bg-purple-900/30 rounded text-sm">
                                    <p class="text-purple-800 dark:text-purple-300">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <strong class="dark:text-gray-200">注意：</strong>連携するメールアドレスは、ReadNestに登録済みのものと同じである必要があります。
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- トラブルシューティング -->
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-red-900 dark:text-red-100 mb-3 flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                Googleログインで問題が発生した場合
                            </h3>
                            <ul class="space-y-2 text-red-800 dark:text-red-200 text-sm">
                                <li>• <strong>ポップアップがブロックされる</strong>：ブラウザの設定でreadnest.jpのポップアップを許可してください</li>
                                <li>• <strong>エラーが表示される</strong>：ブラウザのキャッシュとCookieをクリアして再試行</li>
                                <li>• <strong>連携できない</strong>：Googleアカウントのメールアドレスを確認してください</li>
                                <li>• <strong>それでも解決しない場合</strong>：<a href="#contact-form" class="text-readnest-primary hover:underline">お問い合わせ</a>ください</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- よくある質問 -->
            <section id="faq" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <i class="fas fa-question text-readnest-primary mr-3"></i>
                    よくある質問
                </h2>
                
                <div class="space-y-6" x-data="{ openFaq: null }">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <button @click="openFaq = openFaq === 1 ? null : 1" 
                                class="w-full text-left px-6 py-4 focus:outline-none hover:bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">本が検索で見つからない場合はどうすればいいですか？</h3>
                                <i class="fas fa-chevron-down transform transition-transform" 
                                   :class="openFaq === 1 ? 'rotate-180' : ''"></i>
                            </div>
                        </button>
                        <div x-show="openFaq === 1" x-transition class="px-6 pb-4">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                検索で見つからない本は「手動で本を追加」機能を使用してください。
                                タイトル、著者名、ページ数などの基本情報を入力することで本棚に追加できます。
                            </p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <button @click="openFaq = openFaq === 2 ? null : 2" 
                                class="w-full text-left px-6 py-4 focus:outline-none hover:bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">読書状況を間違えて登録してしまいました</h3>
                                <i class="fas fa-chevron-down transform transition-transform" 
                                   :class="openFaq === 2 ? 'rotate-180' : ''"></i>
                            </div>
                        </button>
                        <div x-show="openFaq === 2" x-transition class="px-6 pb-4">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                本の詳細ページで「読書状況を編集」をクリックすると、いつでも読書状況、ページ数、評価、感想を変更できます。
                            </p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <button @click="openFaq = openFaq === 6 ? null : 6" 
                                class="w-full text-left px-6 py-4 focus:outline-none hover:bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">本を本棚から削除したい</h3>
                                <i class="fas fa-chevron-down transform transition-transform" 
                                   :class="openFaq === 6 ? 'rotate-180' : ''"></i>
                            </div>
                        </button>
                        <div x-show="openFaq === 6" x-transition class="px-6 pb-4">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                本の詳細ページで、その本に対するあなたの記録（ステータス、レビュー、タグなど）がある場合、「本棚から削除」ボタンが表示されます。
                                このボタンをクリックすると、本棚から削除できます。
                            </p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <button @click="openFaq = openFaq === 3 ? null : 3" 
                                class="w-full text-left px-6 py-4 focus:outline-none hover:bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">他の人に読書記録を見られたくありません</h3>
                                <i class="fas fa-chevron-down transform transition-transform" 
                                   :class="openFaq === 3 ? 'rotate-180' : ''"></i>
                            </div>
                        </button>
                        <div x-show="openFaq === 3" x-transition class="px-6 pb-4">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                アカウント設定の「プライバシー設定」で「読書記録を公開しない」を選択してください。
                                これにより、あなたの読書記録は他のユーザーから見えなくなります。
                            </p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <button @click="openFaq = openFaq === 4 ? null : 4" 
                                class="w-full text-left px-6 py-4 focus:outline-none hover:bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">パスワードを忘れてしまいました</h3>
                                <i class="fas fa-chevron-down transform transition-transform" 
                                   :class="openFaq === 4 ? 'rotate-180' : ''"></i>
                            </div>
                        </button>
                        <div x-show="openFaq === 4" x-transition class="px-6 pb-4">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                ログインページの「パスワードを忘れた方はこちら」リンクから、
                                登録済みのメールアドレスを入力してパスワードリセットの手続きを行ってください。
                            </p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <button @click="openFaq = openFaq === 5 ? null : 5" 
                                class="w-full text-left px-6 py-4 focus:outline-none hover:bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">アカウントを削除したいのですが</h3>
                                <i class="fas fa-chevron-down transform transition-transform" 
                                   :class="openFaq === 5 ? 'rotate-180' : ''"></i>
                            </div>
                        </button>
                        <div x-show="openFaq === 5" x-transition class="px-6 pb-4">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                                アカウントの削除は、アカウント設定ページから行えます。
                            </p>
                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded p-3">
                                <p class="text-sm text-red-800 dark:text-red-200">
                                    <strong>注意：</strong>アカウントを削除すると、すべての読書記録、レビュー、タグが完全に削除され、復元できません。
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <button @click="openFaq = openFaq === 7 ? null : 7" 
                                class="w-full text-left px-6 py-4 focus:outline-none hover:bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">ReadNestの開発について知りたい</h3>
                                <i class="fas fa-chevron-down transform transition-transform" 
                                   :class="openFaq === 7 ? 'rotate-180' : ''"></i>
                            </div>
                        </button>
                        <div x-show="openFaq === 7" x-transition class="px-6 pb-4">
                            <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                ReadNestの開発状況や新機能の情報は、フッターにある
                                <a href="https://yayoi-taka.hatenablog.com/" target="_blank" rel="noopener noreferrer" class="text-readnest-primary hover:underline">開発者ブログ</a>
                                でご確認いただけます。技術的な詳細や今後の予定なども掲載しています。
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- お問い合わせセクション -->
        <div class="mt-16 bg-gradient-to-r from-readnest-primary to-readnest-accent rounded-lg text-white p-8 text-center">
            <h2 class="text-2xl font-bold mb-4">その他のご質問</h2>
            <p class="text-lg mb-6 opacity-90">
                ヘルプで解決しない問題がございましたら、お気軽にお問い合わせください。
            </p>
            <?php if ($login_flag): ?>
                <div class="bg-white bg-opacity-20 rounded-lg p-6 text-left max-w-md mx-auto">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">お問い合わせ情報</h3>
                    <p class="text-sm opacity-90 mb-2">ログイン中のアカウント:</p>
                    <p class="font-medium text-gray-900 dark:text-gray-100"><?php echo html($d_nickname); ?></p>
                    <p class="text-sm opacity-90"><?php echo html($user_email); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- お問い合わせフォーム -->
        <?php
        // CSRF トークンを生成
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        ?>
        <section id="contact-form" class="bg-white rounded-lg shadow-lg p-8 mt-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-envelope text-readnest-primary mr-3"></i>
                お問い合わせ
            </h2>
            
            <div class="prose prose-lg max-w-none">
                <p class="text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-6">
                    ReadNestに関するご質問、ご要望、不具合報告などがございましたら、以下のフォームからお気軽にお問い合わせください。
                </p>
                
                <?php if (isset($_SESSION['contact_success'])): ?>
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                お問い合わせを受け付けました。内容を確認の上、ご返信させていただきます。
                            </p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['contact_success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['contact_error'])): ?>
                <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                <?php echo html($_SESSION['contact_error']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['contact_error']); ?>
                <?php endif; ?>
                
                <form action="/contact_submit.php" method="post" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="contact_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                お名前 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="contact_name" 
                                   name="name" 
                                   required
                                   value="<?php echo html(isset($d_nickname) ? $d_nickname : ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                メールアドレス <span class="text-red-500">*</span>
                            </label>
                            <input type="email" 
                                   id="contact_email" 
                                   name="email" 
                                   required
                                   value="<?php echo html(isset($user_email) ? $user_email : ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="contact_category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            お問い合わせ種別 <span class="text-red-500">*</span>
                        </label>
                        <select id="contact_category" 
                                name="category" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                            <option value="">選択してください</option>
                            <option value="question">使い方に関する質問</option>
                            <option value="request">機能改善のご要望</option>
                            <option value="bug">不具合の報告</option>
                            <option value="other">その他</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="contact_subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            件名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="contact_subject" 
                               name="subject" 
                               required
                               maxlength="100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="contact_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            お問い合わせ内容 <span class="text-red-500">*</span>
                        </label>
                        <textarea id="contact_message" 
                                  name="message" 
                                  required
                                  rows="6"
                                  maxlength="2000"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent"
                                  placeholder="お問い合わせ内容を具体的にご記入ください"></textarea>
                        <p class="mt-1 text-sm text-gray-500">最大2000文字まで</p>
                    </div>
                    
                    <?php if ($login_flag): ?>
                    <input type="hidden" name="user_id" value="<?php echo html($_SESSION['AUTH_USER']); ?>">
                    <?php endif; ?>
                    
                    <!-- CSRF対策用トークン -->
                    <input type="hidden" name="csrf_token" value="<?php echo html(isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''); ?>">
                    
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="text-red-500">*</span> は必須項目です
                        </p>
                        <button type="submit" 
                                class="btn-primary">
                            <i class="fas fa-paper-plane mr-2"></i>送信する
                        </button>
                    </div>
                </form>
                
                <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">ご注意</h4>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• お問い合わせへの返信には数日お時間をいただく場合があります</li>
                        <li>• 内容によっては返信できない場合もございます</li>
                        <li>• 個人情報は適切に管理し、お問い合わせ対応以外の目的では使用しません</li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- 検索機能のJavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('help-search');
    const sections = document.querySelectorAll('section');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        sections.forEach(section => {
            const content = section.textContent.toLowerCase();
            if (content.includes(searchTerm) || searchTerm === '') {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
    });
    
    // 各セクションにコピーリンクボタンを追加
    const sectionsWithId = document.querySelectorAll('section[id]');
    sectionsWithId.forEach(section => {
        const sectionId = section.getAttribute('id');
        const header = section.querySelector('h2');
        
        if (header) {
            // コピーボタンを作成
            const copyButton = document.createElement('button');
            copyButton.innerHTML = '<i class="fas fa-link"></i>';
            copyButton.className = 'ml-3 text-gray-400 hover:text-readnest-primary transition-colors text-base';
            copyButton.setAttribute('title', 'セクションリンクをコピー');
            copyButton.setAttribute('data-section-id', sectionId);
            
            // ボタンのスタイルを設定
            copyButton.style.cssText = 'background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 4px; vertical-align: middle;';
            
            // ホバー時の背景色
            copyButton.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(0, 0, 0, 0.05)';
            });
            copyButton.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'transparent';
            });
            
            // クリック時の処理
            copyButton.addEventListener('click', async function(e) {
                e.stopPropagation();
                const url = window.location.origin + window.location.pathname + '#' + sectionId;
                
                try {
                    await navigator.clipboard.writeText(url);
                    
                    // 成功時のフィードバック
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check text-green-600"></i>';
                    this.setAttribute('title', 'コピーしました！');
                    
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.setAttribute('title', 'セクションリンクをコピー');
                    }, 2000);
                } catch (err) {
                    // エラー時のフィードバック
                    console.error('コピーに失敗しました:', err);
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-times text-red-600"></i>';
                    
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                    }, 2000);
                }
            });
            
            // ヘッダーにボタンを追加
            header.appendChild(copyButton);
        }
    });
    
    // スムーススクロール
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>