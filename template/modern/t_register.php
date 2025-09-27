<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// ステップに応じたコンテンツを生成
ob_start();
?>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <?php if (!isset($step) || $step === 'input'): ?>
        <!-- 入力フォーム -->
        <div>
            <div class="text-center">
                <img src="/template/modern/img/readnest_logo.png" alt="ReadNest" class="mx-auto h-12 w-auto">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                    アカウントを作成
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    既にアカウントをお持ちの方は
                    <a href="/" class="font-medium text-readnest-primary hover:text-readnest-accent">
                        ログイン
                    </a>
                </p>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-300">エラーが発生しました</h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-400">
                            <ul class="list-disc list-inside space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <form class="mt-8 space-y-6" action="/register.php" method="POST">
                <div class="space-y-4">
                    <div>
                        <label for="email1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            メールアドレス
                        </label>
                        <input id="email1" 
                               name="email1" 
                               type="email" 
                               autocomplete="email" 
                               required 
                               value="<?php echo html($email1); ?>"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-readnest-primary focus:border-readnest-primary focus:z-10 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="email2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            メールアドレス（確認）
                        </label>
                        <input id="email2" 
                               name="email2" 
                               type="email" 
                               autocomplete="email" 
                               required 
                               value="<?php echo html($email2); ?>"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-readnest-primary focus:border-readnest-primary focus:z-10 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="nickname" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            ニックネーム
                        </label>
                        <input id="nickname" 
                               name="nickname" 
                               type="text" 
                               autocomplete="username" 
                               required 
                               maxlength="50"
                               value="<?php echo html($nickname); ?>"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-readnest-primary focus:border-readnest-primary focus:z-10 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">50文字以内で入力してください</p>
                    </div>
                    
                    <div>
                        <label for="password1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            パスワード
                        </label>
                        <input id="password1" 
                               name="password1" 
                               type="password" 
                               autocomplete="new-password" 
                               required 
                               minlength="6"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-readnest-primary focus:border-readnest-primary focus:z-10 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">6文字以上で入力してください</p>
                    </div>
                    
                    <div>
                        <label for="password2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            パスワード（確認）
                        </label>
                        <input id="password2" 
                               name="password2" 
                               type="password" 
                               autocomplete="new-password" 
                               required 
                               minlength="6"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-readnest-primary focus:border-readnest-primary focus:z-10 sm:text-sm">
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="agree_terms" 
                               name="agree_terms" 
                               type="checkbox" 
                               value="1"
                               <?php echo $agree_terms ? 'checked' : ''; ?>
                               class="h-4 w-4 text-readnest-primary focus:ring-readnest-primary border-gray-300 dark:border-gray-600 rounded"
                               required>
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="agree_terms" class="font-medium text-gray-700 dark:text-gray-300">
                            <a href="/terms.php" target="_blank" class="text-readnest-primary hover:text-readnest-accent">利用規約</a>に同意します
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-readnest-primary hover:bg-readnest-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-user-plus text-white group-hover:text-white"></i>
                        </span>
                        確認画面へ
                    </button>
                </div>
            </form>
        </div>
        
        <?php elseif ($step === 'confirm'): ?>
        <!-- 確認画面 -->
        <div>
            <div class="text-center">
                <img src="/template/modern/img/readnest_logo.png" alt="ReadNest" class="mx-auto h-12 w-auto">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                    登録内容の確認
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    以下の内容で登録します
                </p>
            </div>
            
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">メールアドレス</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100"><?php echo html($email1); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ニックネーム</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100"><?php echo html($nickname); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">パスワード</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            <?php echo str_repeat('●', strlen($password1)); ?>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <div class="mt-6 space-y-3">
                <form action="/register.php" method="POST">
                    <input type="hidden" name="email1" value="<?php echo html($email1); ?>">
                    <input type="hidden" name="email2" value="<?php echo html($email2); ?>">
                    <input type="hidden" name="nickname" value="<?php echo html($nickname); ?>">
                    <input type="hidden" name="password1" value="<?php echo html($password1); ?>">
                    <input type="hidden" name="password2" value="<?php echo html($password2); ?>">
                    <input type="hidden" name="agree_terms" value="1">
                    <input type="hidden" name="confirm" value="yes">
                    
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-readnest-primary hover:bg-readnest-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary">
                        この内容で登録する
                    </button>
                </form>
                
                <form action="/register.php" method="POST">
                    <input type="hidden" name="email1" value="<?php echo html($email1); ?>">
                    <input type="hidden" name="email2" value="<?php echo html($email2); ?>">
                    <input type="hidden" name="nickname" value="<?php echo html($nickname); ?>">
                    <input type="hidden" name="password1" value="<?php echo html($password1); ?>">
                    <input type="hidden" name="password2" value="<?php echo html($password2); ?>">
                    <input type="hidden" name="agree_terms" value="1">
                    
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary">
                        修正する
                    </button>
                </form>
            </div>
        </div>
        
        <?php elseif ($step === 'complete'): ?>
        <!-- 完了画面 -->
        <div>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30">
                    <i class="fas fa-check text-green-600 text-2xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                    仮登録が完了しました
                </h2>
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <p>
                        <strong><?php echo html($email1); ?></strong> 宛に<br>
                        確認メールを送信しました。
                    </p>
                    <p>
                        メールに記載されているURLをクリックして<br>
                        本登録を完了してください。
                    </p>
                </div>
            </div>
            
            <div class="mt-8 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">ご注意</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                            <ul class="list-disc list-inside space-y-1">
                                <li>メールが届かない場合は、迷惑メールフォルダをご確認ください</li>
                                <li>URLの有効期限は1時間です</li>
                                <li>1時間を過ぎた場合は、再度新規登録をお願いします</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <a href="/" 
                   class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-readnest-primary hover:bg-readnest-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-readnest-primary">
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