<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// アクティベーション成功時にbody要素にフラグを設定
if ($activation_status === 'success') {
    $GLOBALS['activation_success'] = true;
}

// コンテンツ部分を生成
ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <?php if ($activation_status === 'success'): ?>
        <!-- 成功画面 -->
        <div>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 dark:bg-green-900/30 mb-5">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-4xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                    本登録が完了しました！
                </h2>
                <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                    ようこそ、<?php echo html(isset($nickname) ? $nickname : ''); ?>さん
                </p>
                <div class="mt-6 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <p>ReadNestへの本登録が完了しました。</p>
                    <p>確認メールを送信しましたのでご確認ください。</p>
                </div>
            </div>
            
            <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-md p-6">
                <h3 class="text-lg font-medium text-blue-900 dark:text-blue-300 mb-4 flex items-center">
                    <i class="fas fa-rocket text-blue-600 dark:text-blue-400 mr-2"></i>
                    初めにやること
                </h3>
                <div class="space-y-4">
                    <!-- ステップ1: プロフィール設定 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <div class="flex items-start">
                            <span class="bg-blue-600 dark:bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 flex-shrink-0">1</span>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">プロフィールを設定する</h4>
                                <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                    <p>• プロフィール画像をアップロード</p>
                                    <p>• 自己紹介や好きなジャンルを記入</p>
                                    <p>• 年間目標読書数を設定</p>
                                </div>
                                <a href="/account.php" class="inline-flex items-center text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 mt-2">
                                    <i class="fas fa-arrow-right mr-1"></i>アカウント設定へ
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ステップ2: 公開設定 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <div class="flex items-start">
                            <span class="bg-green-600 dark:bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 flex-shrink-0">2</span>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">公開設定を確認する</h4>
                                <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                    <p>• 読書記録の公開/非公開を選択</p>
                                    <p>• プライバシー設定をカスタマイズ</p>
                                </div>
                                <a href="/account.php#privacy" class="inline-flex items-center text-xs text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 mt-2">
                                    <i class="fas fa-shield-alt mr-1"></i>プライバシー設定へ
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ステップ3: 本を追加 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <div class="flex items-start">
                            <span class="bg-amber-600 dark:bg-amber-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 flex-shrink-0">3</span>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">最初の本を追加する</h4>
                                <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                    <p>• 検索またはISBNから簡単に追加</p>
                                    <p>• 読書状況を設定（読みたい/読書中/読了）</p>
                                    <p>• タグを付けて整理</p>
                                </div>
                                <a href="/add_book.php" class="inline-flex items-center text-xs text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 mt-2">
                                    <i class="fas fa-plus-circle mr-1"></i>本を追加する
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ヒント -->
                <div class="mt-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                    <p class="text-xs text-purple-800 dark:text-purple-300">
                        <i class="fas fa-lightbulb text-purple-600 dark:text-purple-400 mr-1"></i>
                        <strong>ヒント:</strong> すべての設定は後からでも変更できます。まずは気軽に始めてみましょう！
                    </p>
                </div>
            </div>
            
            <div class="mt-8 space-y-3">
                <!-- チュートリアルボタン（推奨） -->
                <a href="/tutorial.php" 
                   class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all shadow-lg">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-graduation-cap text-white"></i>
                    </span>
                    <span class="flex items-center">
                        インタラクティブチュートリアルを開始
                        <span class="ml-2 px-2 py-0.5 text-xs bg-white/20 rounded-full">おすすめ</span>
                    </span>
                </a>
                
                <a href="/account.php" 
                   class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-readnest-primary hover:bg-readnest-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary transition-colors">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-cog text-white"></i>
                    </span>
                    プロフィールを設定する
                </a>
                
                <a href="/" 
                   class="w-full flex justify-center py-3 px-4 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary transition-colors">
                    <i class="fas fa-home mr-2"></i>
                    ホームへ進む
                </a>
            </div>
        </div>
        
        <?php elseif ($activation_status === 'already_activated'): ?>
        <!-- 既にアクティベート済み -->
        <div>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-yellow-100 dark:bg-yellow-900/30 mb-5">
                    <i class="fas fa-info-circle text-yellow-600 dark:text-yellow-400 text-4xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                    既に本登録済みです
                </h2>
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <p>このアカウントは既に本登録が完了しています。</p>
                    <p>ログインページからログインしてください。</p>
                </div>
            </div>
            
            <div class="mt-8">
                <a href="/" 
                   class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-readnest-primary hover:bg-readnest-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary">
                    ログインページへ
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- エラー画面 -->
        <div>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 dark:bg-red-900/30 mb-5">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-4xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                    エラーが発生しました
                </h2>
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <p class="text-red-600 dark:text-red-400 font-medium"><?php echo html($error_message); ?></p>
                </div>
            </div>
            
            <div class="mt-8 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400 dark:text-yellow-500"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">お困りの場合</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                            <ul class="list-disc list-inside space-y-1">
                                <li>URLの有効期限は24時間です</li>
                                <li>既に本登録済みの場合はログインしてください</li>
                                <li>問題が解決しない場合は新規登録をやり直してください</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 space-y-3">
                <a href="/register.php" 
                   class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-readnest-primary hover:bg-readnest-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary">
                    新規登録へ
                </a>
                
                <a href="/" 
                   class="w-full flex justify-center py-3 px-4 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary">
                    トップページへ
                </a>
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