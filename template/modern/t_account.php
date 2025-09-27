<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// コンテンツ部分を生成
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
                    <span class="text-gray-700 dark:text-gray-300">アカウント設定</span>
                </li>
            </ol>
        </nav>

        <!-- ヘッダー -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">アカウント設定</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">プロフィール情報やアカウントの設定を管理できます。</p>
        </div>

        <!-- エラーメッセージ -->
        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md p-4">
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

        <!-- 成功メッセージ -->
        <?php if (!empty($success_message)): ?>
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-300"><?php echo html($success_message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-data="{ activeTab: 'profile' }">
            <!-- サイドバー -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative mb-4">
                            <?php if ($has_profile_photo): ?>
                                <img class="w-24 h-24 rounded-full object-cover" 
                                     src="<?php echo html($profile_photo_url); ?>" 
                                     alt="<?php echo html($form_data['nickname']); ?>">
                            <?php else: ?>
                                <div class="w-24 h-24 rounded-full bg-readnest-primary text-white flex items-center justify-center text-2xl font-bold">
                                    <?php echo html(mb_substr($form_data['nickname'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-center">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100"><?php echo html($form_data['nickname']); ?></h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo html($form_data['email']); ?></p>
                        </div>
                        
                        <!-- プロフィール画像アップロード -->
                        <div class="mt-4 w-full" x-data="{ uploading: false }">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 mb-2">
                                <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                                    <i class="fas fa-camera mr-1"></i>
                                    プロフィール写真を変更
                                </p>
                            </div>
                            <form method="post" enctype="multipart/form-data" class="space-y-2">
                                <input type="hidden" name="action" value="upload_photo">
                                <label class="block cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        <span class="bg-readnest-primary text-white px-4 py-2 rounded-full text-sm font-semibold hover:bg-readnest-accent transition-colors inline-flex items-center">
                                            <i class="fas fa-upload mr-2"></i>
                                            写真を選択
                                        </span>
                                        <input type="file" 
                                               name="profile_photo" 
                                               accept="image/jpeg,image/png,image/gif"
                                               @change="uploading = true; $el.form.submit()"
                                               class="hidden">
                                    </div>
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                    対応形式: JPEG, PNG, GIF (最大2MB)
                                </p>
                                <div x-show="uploading" class="text-center">
                                    <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-readnest-primary"></div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">アップロード中...</span>
                                </div>
                            </form>
                            <?php if ($has_profile_photo): ?>
                            <form method="post" class="mt-2">
                                <?php csrfFieldTag(); ?>
                                <input type="hidden" name="action" value="delete_photo">
                                <button type="submit"
                                        onclick="return confirm('プロフィール画像を削除しますか？')"
                                        class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">
                                    <i class="fas fa-trash-alt mr-1"></i>画像を削除
                                </button>
                            </form>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">JPEG、PNG、GIF（最大1MB）</p>
                        </div>
                    </div>
                    
                    <nav class="space-y-2">
                        <button @click="activeTab = 'profile'"
                                :class="activeTab === 'profile' ? 'bg-readnest-primary text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-user mr-2"></i>プロフィール設定
                        </button>
                        <button @click="activeTab = 'password'"
                                :class="activeTab === 'password' ? 'bg-readnest-primary text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-lock mr-2"></i>パスワード変更
                        </button>
                        <button @click="activeTab = 'x_settings'"
                                :class="activeTab === 'x_settings' ? 'bg-readnest-primary text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fab fa-x-twitter mr-2"></i>X連携設定
                        </button>
                        <button @click="activeTab = 'privacy'"
                                :class="activeTab === 'privacy' ? 'bg-readnest-primary text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-shield-alt mr-2"></i>プライバシー設定
                        </button>
                    </nav>
                </div>
            </div>

            <!-- メインコンテンツ -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    
                    <!-- プロフィール設定 -->
                    <div x-show="activeTab === 'profile'" x-transition class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">プロフィール設定</h3>
                        
                        <?php if ($step === 'confirm'): ?>
                            <!-- 確認画面 -->
                            <div class="space-y-6">
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md p-4">
                                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-3">以下の内容で更新します</h4>
                                    <dl class="space-y-3">
                                        <!-- メールアドレスは変更不可なので確認画面では表示しない -->
                                        <div>
                                            <dt class="text-sm font-medium text-blue-700 dark:text-blue-300">ニックネーム</dt>
                                            <dd class="text-sm text-blue-600 dark:text-blue-400"><?php echo html($form_data['nickname']); ?></dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-blue-700 dark:text-blue-300">読書記録の公開設定</dt>
                                            <dd class="text-sm text-blue-600 dark:text-blue-400"><?php echo $form_data['diary_policy'] ? '公開する' : '公開しない'; ?></dd>
                                        </div>
                                        <?php if (!empty($form_data['books_per_year'])): ?>
                                        <div>
                                            <dt class="text-sm font-medium text-blue-700 dark:text-blue-300">年間目標読書数</dt>
                                            <dd class="text-sm text-blue-600 dark:text-blue-400"><?php echo html($form_data['books_per_year']); ?>冊</dd>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($form_data['introduction'])): ?>
                                        <div>
                                            <dt class="text-sm font-medium text-blue-700 dark:text-blue-300">自己紹介</dt>
                                            <dd class="text-sm text-blue-600 dark:text-blue-400"><?php echo nl2br(html($form_data['introduction'])); ?></dd>
                                        </div>
                                        <?php endif; ?>
                                    </dl>
                                </div>
                                
                                <div class="flex space-x-4">
                                    <form method="post" class="inline">
                                        <?php csrfFieldTag(); ?>
                                        <input type="hidden" name="action" value="update_profile">
                                        <!-- メールアドレスは変更不可なのでhidden不要 -->
                                        <input type="hidden" name="nickname" value="<?php echo html($form_data['nickname']); ?>">
                                        <input type="hidden" name="diary_policy" value="<?php echo $form_data['diary_policy']; ?>">
                                        <input type="hidden" name="books_per_year" value="<?php echo html($form_data['books_per_year']); ?>">
                                        <input type="hidden" name="introduction" value="<?php echo html($form_data['introduction']); ?>">
                                        <input type="hidden" name="confirm" value="yes">
                                        <button type="submit" class="btn-primary">
                                            <i class="fas fa-check mr-2"></i>更新する
                                        </button>
                                    </form>
                                    <button onclick="history.back()" class="btn-outline">
                                        <i class="fas fa-arrow-left mr-2"></i>戻る
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- 入力フォーム -->
                            <form method="post" class="space-y-6">
                                <?php csrfFieldTag(); ?>
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">メールアドレス</label>
                                    <input type="email" 
                                           name="email" 
                                           id="email" 
                                           value="<?php echo html($form_data['email']); ?>"
                                           readonly
                                           class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed">
                                    <p class="mt-1 text-sm text-gray-500">メールアドレスは変更できません</p>
                                </div>
                                
                                <div>
                                    <label for="nickname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ニックネーム</label>
                                    <input type="text" 
                                           name="nickname" 
                                           id="nickname" 
                                           value="<?php echo html($form_data['nickname']); ?>"
                                           required
                                           maxlength="50"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">読書記録の公開設定</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="radio" 
                                                   name="diary_policy" 
                                                   value="1" 
                                                   <?php echo $form_data['diary_policy'] == 1 ? 'checked' : ''; ?>
                                                   class="mr-2 text-readnest-primary focus:ring-readnest-primary">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">公開する（他のユーザーに読書記録を公開します）</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" 
                                                   name="diary_policy" 
                                                   value="0" 
                                                   <?php echo $form_data['diary_policy'] == 0 ? 'checked' : ''; ?>
                                                   class="mr-2 text-readnest-primary focus:ring-readnest-primary">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">公開しない（プライベートで利用します）</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="books_per_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">年間目標読書数（任意）</label>
                                    <div class="relative">
                                        <input type="number" 
                                               name="books_per_year" 
                                               id="books_per_year" 
                                               value="<?php echo html($form_data['books_per_year']); ?>"
                                               min="0" 
                                               max="1000"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent pr-12">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">冊</span>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">年間で読みたい本の目標冊数を設定できます</p>
                                    <div class="mt-2">
                                        <a href="/monthly_goals.php" class="inline-flex items-center text-sm text-readnest-primary dark:text-readnest-accent hover:text-readnest-accent dark:hover:text-readnest-primary">
                                            <i class="fas fa-calendar-check mr-1"></i>
                                            月間目標を設定する
                                        </a>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="introduction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">自己紹介（任意）</label>
                                    <textarea name="introduction" 
                                              id="introduction" 
                                              rows="4" 
                                              maxlength="500"
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent"
                                              placeholder="読書の好みや興味のあるジャンルなど、自由に書いてください"><?php echo html($form_data['introduction']); ?></textarea>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">500文字以内で入力してください</p>
                                </div>
                                
                                <div class="pt-4">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-save mr-2"></i>更新内容を確認
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- パスワード変更 -->
                    <div x-show="activeTab === 'password'" x-transition class="p-6">
                        <?php
                        // Googleログインのみのユーザーか確認
                        $is_google_only = false;
                        if ($is_google_linked && !empty($user_info['google_id'])) {
                            // create_dateとregist_dateが同じか確認（無効な日付を除外）
                            if ($user_info['create_date'] === $user_info['regist_date'] && 
                                $user_info['regist_date'] !== '0000-00-00 00:00:00' &&
                                $user_info['regist_date'] !== null) {
                                $is_google_only = true;
                            }
                        }
                        ?>
                        
                        <?php if ($is_google_only): ?>
                        <!-- Googleユーザー向けメッセージ -->
                        <div class="text-center py-8">
                            <i class="fab fa-google text-4xl text-gray-400 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Googleアカウントでログインしています</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                パスワードの設定・変更は不要です。<br>
                                引き続きGoogleアカウントでログインしてください。
                            </p>
                        </div>
                        <?php else: ?>
                        <!-- 通常のパスワード変更フォーム -->
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">パスワード変更</h3>
                        
                        <form method="post" class="space-y-6">
                            <?php csrfFieldTag(); ?>
                            <input type="hidden" name="action" value="change_password">
                            
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">現在のパスワード</label>
                                <input type="password" 
                                       name="current_password" 
                                       id="current_password" 
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">新しいパスワード</label>
                                <input type="password" 
                                       name="new_password" 
                                       id="new_password" 
                                       required
                                       minlength="6"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                                <p class="mt-1 text-sm text-gray-500">6文字以上で入力してください</p>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">新しいパスワード（確認）</label>
                                <input type="password" 
                                       name="confirm_password" 
                                       id="confirm_password" 
                                       required
                                       minlength="6"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent">
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-key mr-2"></i>パスワードを変更
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>

                    <!-- X連携設定 -->
                    <div x-show="activeTab === 'x_settings'" x-transition class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">
                            X（Twitter）連携設定
                            <a href="/help.php#x-integration" target="_blank" 
                               class="ml-2 text-sm text-blue-500 hover:text-blue-600"
                               title="X連携の詳細">
                                <i class="fas fa-question-circle"></i>
                            </a>
                        </h3>
                        
                        <?php if (!$x_connected): ?>
                            <!-- X未連携の場合 -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-8 text-center">
                                <div class="max-w-md mx-auto">
                                    <div class="bg-black rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center shadow-lg">
                                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                        </svg>
                                    </div>
                                    <h4 class="text-xl font-bold text-gray-900 mb-3">Xアカウントと連携</h4>
                                    <p class="text-gray-600 mb-8 leading-relaxed">
                                        読書記録を自動でXに投稿できます。<br>
                                        フォロワーと読書体験を共有しましょう。
                                    </p>
                                    <a href="/x_connect.php" 
                                       class="inline-flex items-center justify-center px-8 py-4 bg-black text-white font-medium rounded-full hover:bg-gray-800 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                        <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                        </svg>
                                        <span class="text-lg">Xと連携する</span>
                                    </a>
                                    <p class="text-xs text-gray-500 mt-6">
                                        <i class="fas fa-shield-alt mr-1"></i>
                                        安全なOAuth認証でアカウントを保護します
                                    </p>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- X連携済みの場合 -->
                            <div class="space-y-6">
                                <!-- 連携状態表示 -->
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6">
                                    <div class="flex items-center">
                                        <div class="bg-black rounded-full w-12 h-12 flex items-center justify-center mr-4">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-base font-semibold text-gray-900">
                                                @<?php echo html($x_screen_name); ?>
                                            </p>
                                            <p class="text-sm text-green-600 mt-0.5">
                                                <i class="fas fa-check-circle mr-1"></i>連携済み
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 投稿設定フォーム -->
                                <form method="post" class="space-y-6">
                                    <?php csrfFieldTag(); ?>
                                    <input type="hidden" name="action" value="update_x_settings">
                                    
                                    <!-- 自動投稿の有効/無効 -->
                                    <div>
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   name="x_post_enabled" 
                                                   value="1" 
                                                   <?php echo $x_post_enabled ? 'checked' : ''; ?>
                                                   class="rounded border-gray-300 text-readnest-primary focus:ring-readnest-primary mr-3">
                                            <span class="text-sm font-medium text-gray-700">
                                                読書記録の自動投稿を有効にする
                                            </span>
                                        </label>
                                        <p class="text-xs text-gray-500 mt-1 ml-7">
                                            有効にすると、選択したイベント時に自動的にXに投稿されます
                                        </p>
                                    </div>
                                    
                                    <!-- 投稿するイベントの選択 -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">投稿するイベント</h4>
                                        <div class="space-y-2 ml-3">
                                            <label class="flex items-center">
                                                <input type="checkbox" 
                                                       name="x_post_events[]" 
                                                       value="<?php echo X_EVENT_WANT_TO_READ; ?>"
                                                       <?php echo ($x_post_events & X_EVENT_WANT_TO_READ) ? 'checked' : ''; ?>
                                                       class="rounded border-gray-300 text-readnest-primary focus:ring-readnest-primary mr-2">
                                                <span class="text-sm text-gray-600">本を「読みたい」リストに追加したとき</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" 
                                                       name="x_post_events[]" 
                                                       value="<?php echo X_EVENT_READING_NOW; ?>"
                                                       <?php echo ($x_post_events & X_EVENT_READING_NOW) ? 'checked' : ''; ?>
                                                       class="rounded border-gray-300 text-readnest-primary focus:ring-readnest-primary mr-2">
                                                <span class="text-sm text-gray-600">本を読み始めたとき</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" 
                                                       name="x_post_events[]" 
                                                       value="<?php echo X_EVENT_READING_PROGRESS; ?>"
                                                       <?php echo ($x_post_events & X_EVENT_READING_PROGRESS) ? 'checked' : ''; ?>
                                                       class="rounded border-gray-300 text-readnest-primary focus:ring-readnest-primary mr-2">
                                                <span class="text-sm text-gray-600">読書進捗を更新したとき</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" 
                                                       name="x_post_events[]" 
                                                       value="<?php echo X_EVENT_READING_FINISH; ?>"
                                                       <?php echo ($x_post_events & X_EVENT_READING_FINISH) ? 'checked' : ''; ?>
                                                       class="rounded border-gray-300 text-readnest-primary focus:ring-readnest-primary mr-2">
                                                <span class="text-sm text-gray-600">本を読み終わったとき</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" 
                                                       name="x_post_events[]" 
                                                       value="<?php echo X_EVENT_REVIEW; ?>"
                                                       <?php echo ($x_post_events & X_EVENT_REVIEW) ? 'checked' : ''; ?>
                                                       class="rounded border-gray-300 text-readnest-primary focus:ring-readnest-primary mr-2">
                                                <span class="text-sm text-gray-600">レビューを投稿したとき</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="pt-4">
                                        <button type="submit" class="btn-primary">
                                            <i class="fas fa-save mr-2"></i>設定を保存
                                        </button>
                                    </div>
                                </form>
                                
                                <!-- 連携解除 -->
                                <div class="border-t pt-6">
                                    <div class="bg-gray-50 rounded-lg p-6">
                                        <h4 class="text-base font-medium text-gray-900 mb-3">X連携の解除</h4>
                                        <p class="text-sm text-gray-600 mb-4">
                                            連携を解除すると、自動投稿機能が無効になります。<br>
                                            いつでも再度連携することができます。
                                        </p>
                                        <a href="/x_disconnect.php" 
                                           onclick="return confirm('Xとの連携を解除してもよろしいですか？')"
                                           class="inline-flex items-center text-sm font-medium text-red-600 hover:text-red-700 transition-colors">
                                            <i class="fas fa-unlink mr-2"></i>
                                            連携を解除する
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- プライバシー設定 -->
                    <div x-show="activeTab === 'privacy'" x-transition class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">プライバシー設定</h3>
                        
                        <div class="space-y-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-2">読書記録の公開設定</h4>
                                <p class="text-sm text-gray-600 mb-4">
                                    現在の設定: <span class="font-medium"><?php echo $form_data['diary_policy'] ? '公開する' : '公開しない'; ?></span>
                                </p>
                                <p class="text-sm text-gray-500">
                                    この設定は「プロフィール設定」タブから変更できます。
                                </p>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-blue-800">プライバシーについて</h4>
                                        <div class="mt-2 text-sm text-blue-700">
                                            <p>ReadNestではお客様のプライバシーを尊重しています。</p>
                                            <ul class="mt-2 list-disc list-inside space-y-1">
                                                <li>個人情報は適切に管理され、第三者に提供されることはありません</li>
                                                <li>読書記録の公開設定はいつでも変更できます</li>
                                                <li>アカウントの削除は下記の「アカウント削除」セクションから行えます</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- Google連携セクション -->
                        <div class="mt-8 bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center">
                                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" class="w-5 h-5 mr-2">
                                    <h4 class="text-base font-medium text-gray-900">Google連携設定</h4>
                                </div>
                            </div>
                            <div class="px-6 py-4">
                                <?php if ($google_auth_info): ?>
                                    <!-- Google連携済み -->
                                    <div class="space-y-4">
                                        <div class="flex items-center">
                                            <?php if (!empty($google_auth_info['google_picture'])): ?>
                                            <img src="<?php echo html($google_auth_info['google_picture']); ?>" 
                                                 alt="Google Profile" 
                                                 class="w-12 h-12 rounded-full mr-4">
                                            <?php endif; ?>
                                            <div>
                                                <p class="font-medium text-gray-900"><?php echo html($google_auth_info['google_name']); ?></p>
                                                <p class="text-sm text-gray-600"><?php echo html($google_auth_info['google_email']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <?php 
                                        // Googleログインのみのユーザーか確認
                                        // create_dateとregist_dateが同じで、google_idが存在する場合はGoogleで登録したユーザー
                                        $is_google_only = false;
                                        if ($is_google_linked && !empty($user_info['google_id'])) {
                                            // create_dateとregist_dateが同じか確認（無効な日付を除外）
                                            if ($user_info['create_date'] === $user_info['regist_date'] && 
                                                $user_info['regist_date'] !== '0000-00-00 00:00:00' &&
                                                $user_info['regist_date'] !== null) {
                                                $is_google_only = true;
                                            }
                                        }
                                        ?>
                                        
                                        <?php if (!$is_google_only): ?>
                                        <!-- 通常ユーザーにはGoogle連携解除を表示 -->
                                        <div class="border-t pt-4">
                                            <div class="bg-gray-50 rounded-lg p-4">
                                                <h5 class="text-sm font-medium text-gray-900 mb-2">Google連携の解除</h5>
                                                <p class="text-sm text-gray-600 mb-3">
                                                    連携を解除すると、Googleアカウントでのログインができなくなります。<br>
                                                    通常のメールアドレスとパスワードでのログインは引き続き利用できます。
                                                </p>
                                                <form method="POST" onsubmit="return confirm('Googleアカウントの連携を解除してもよろしいですか？')">
                                                    <input type="hidden" name="action" value="unlink_google">
                                                    <button type="submit" 
                                                            class="inline-flex items-center text-sm font-medium text-red-600 hover:text-red-700 transition-colors">
                                                        <i class="fas fa-unlink mr-2"></i>
                                                        連携を解除する
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <!-- Googleログインのみのユーザーにはメッセージを表示 -->
                                        <div class="border-t pt-4">
                                            <div class="bg-blue-50 rounded-lg p-4">
                                                <p class="text-sm text-blue-800">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    Googleアカウントでのログインをご利用いただいています。<br>
                                                    アカウントを削除したい場合は、下記の「アカウント削除」セクションから手続きを行ってください。
                                                </p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Google未連携 -->
                                    <div class="text-center py-8">
                                        <i class="fab fa-google text-4xl text-gray-400 mb-4"></i>
                                        <p class="text-gray-600 mb-6">
                                            Googleアカウントと連携すると、<br>
                                            Googleアカウントでログインできるようになります。
                                        </p>
                                        <?php if (file_exists(BASEDIR . '/config/google_oauth.php')): ?>
                                        <a href="/auth/google_login.php" 
                                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" 
                                                 class="w-5 h-5 mr-2">
                                            Googleアカウントと連携する
                                        </a>
                                        <?php else: ?>
                                        <p class="text-sm text-gray-500">Google連携機能は現在準備中です</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                            
                            <!-- アカウント削除セクション -->
                            <div class="bg-gray-50 rounded-lg p-4 mt-6">
                                <h4 class="font-medium text-gray-900 mb-2">アカウント削除</h4>
                                <p class="text-sm text-gray-600 mb-4">
                                    アカウントを完全に削除したい場合は、以下のボタンから手続きを開始できます。
                                </p>
                                
                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-amber-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <h5 class="text-sm font-medium text-amber-800">ご注意ください</h5>
                                            <div class="mt-2 text-sm text-amber-700">
                                                <p>アカウントを削除すると、以下のデータがすべて削除されます：</p>
                                                <ul class="mt-2 list-disc list-inside space-y-1">
                                                    <li>プロフィール情報</li>
                                                    <li>読書記録・本棚のデータ</li>
                                                    <li>レビュー・コメント</li>
                                                    <li>読書進捗データ</li>
                                                    <li>タグ情報</li>
                                                </ul>
                                                <p class="mt-2 font-medium">この操作は取り消すことができません。</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div x-data="{ showDeleteForm: false }">
                                    <button @click="showDeleteForm = !showDeleteForm" 
                                            class="text-sm text-red-600 hover:text-red-700 font-medium">
                                        <i class="fas fa-trash-alt mr-1"></i>アカウントを削除する
                                    </button>
                                    
                                    <form x-show="showDeleteForm" 
                                          x-transition
                                          action="" 
                                          method="post" 
                                          class="mt-4 bg-white rounded-lg p-4 border border-gray-200"
                                          @submit="return confirm('本当にアカウントを削除しますか？この操作は取り消せません。')">
                                        <?php csrfFieldTag(); ?>
                                        <input type="hidden" name="action" value="delete_account">
                                        <input type="hidden" name="delete_confirm" value="yes">
                                        
                                        <?php 
                                        // Googleログインのみのユーザーか確認
                                        $is_google_only = false;
                                        if ($is_google_linked && !empty($user_info['google_id'])) {
                                            // create_dateとregist_dateが同じか確認（無効な日付を除外）
                                            if ($user_info['create_date'] === $user_info['regist_date'] && 
                                                $user_info['regist_date'] !== '0000-00-00 00:00:00' &&
                                                $user_info['regist_date'] !== null) {
                                                $is_google_only = true;
                                            }
                                        }
                                        ?>
                                        
                                        <?php if (!$is_google_only): ?>
                                        <div class="mb-4">
                                            <label for="delete_password" class="block text-sm font-medium text-gray-700 mb-2">
                                                確認のため、パスワードを入力してください
                                            </label>
                                            <input type="password" 
                                                   name="delete_password" 
                                                   id="delete_password"
                                                   required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                        </div>
                                        <?php else: ?>
                                        <div class="mb-4">
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                <div class="flex items-start">
                                                    <i class="fab fa-google text-blue-600 mt-0.5 mr-2"></i>
                                                    <div class="text-sm text-blue-800">
                                                        <p class="font-medium mb-1">Googleアカウントでログインしています</p>
                                                        <p>パスワードの入力は不要です。「削除を実行」ボタンをクリックすると、アカウントが削除されます。</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex gap-4">
                                            <button type="submit" 
                                                    class="btn btn-sm bg-red-600 text-white hover:bg-red-700 px-4 py-2 text-sm">
                                                <i class="fas fa-check mr-1"></i>削除を実行
                                            </button>
                                            <button type="button" 
                                                    @click="showDeleteForm = false" 
                                                    class="btn btn-sm bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 text-sm">
                                                <i class="fas fa-times mr-1"></i>キャンセル
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                    </div>
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