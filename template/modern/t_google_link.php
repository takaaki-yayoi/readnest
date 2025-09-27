<?php
/**
 * Google連携確認ページのモダンテンプレート
 */

// 直接アクセス防止
if (!defined('CONFIG')) {
    exit('Access Denied');
}

$existing_user = $google_link_data['existing_user'];
$google_user_info = $google_link_data['google_user_info'];
$error_message = $google_link_data['error_message'];

// コンテンツ部分を生成
ob_start();
?>

<!-- Google連携確認セクション -->
<section class="min-h-[calc(100vh-400px)] flex items-center py-12">
    <div class="max-w-lg w-full mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- タイトルセクション -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6">
                <div class="flex items-center space-x-3">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" 
                         class="w-8 h-8">
                    <h1 class="text-2xl font-bold">Googleアカウント連携</h1>
                </div>
            </div>
            
            <!-- コンテンツ -->
            <div class="p-6">
                <!-- Googleアカウント情報 -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h2 class="text-sm font-medium text-gray-700 mb-3">連携するGoogleアカウント</h2>
                    <div class="flex items-center space-x-3">
                        <?php if (!empty($google_user_info['picture'])): ?>
                        <img src="<?php echo html($google_user_info['picture']); ?>" 
                             alt="Google Profile" 
                             class="w-12 h-12 rounded-full">
                        <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                            <i class="fab fa-google text-gray-600"></i>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-medium text-gray-900"><?php echo html($google_user_info['name']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo html($google_user_info['email']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- 説明文 -->
                <div class="mb-6">
                    <p class="text-gray-700 mb-2">
                        このGoogleアカウントは、既存のReadNestアカウント
                        「<span class="font-medium text-readnest-primary"><?php echo html($existing_user['nickname']); ?></span>」
                        と同じメールアドレスです。
                    </p>
                    <p class="text-sm text-gray-600">
                        アカウントを連携すると、今後Googleアカウントでログインできるようになります。
                    </p>
                </div>
                
                <?php if ($error_message): ?>
                <!-- エラーメッセージ -->
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo html($error_message); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- フォーム -->
                <form method="POST">
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            ReadNestアカウントのパスワード
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="現在のパスワードを入力"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="space-y-3">
                        <button type="submit" 
                                name="link_account" 
                                value="yes" 
                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center">
                            <i class="fas fa-link mr-2"></i>
                            アカウントを連携する
                        </button>
                        
                        <button type="submit" 
                                name="link_account" 
                                value="no" 
                                class="w-full bg-gray-200 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                            キャンセル
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 補足情報 -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                <i class="fas fa-shield-alt text-green-600 mr-1"></i>
                あなたの情報は安全に保護されます
            </p>
        </div>
    </div>
</section>

<?php
$d_content = ob_get_clean();

// ベーステンプレートを使用
include(__DIR__ . '/t_base.php');
?>