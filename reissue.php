<?php
/**
 * パスワード再発行ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// モダン設定を読み込み
require_once('modern_config.php');

// ページタイトル設定
$d_site_title = "パスワード再発行 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestのパスワードを再発行します。メールアドレスとニックネームを入力してください。";
$g_meta_keyword = "パスワード再発行,ログイン,ReadNest";

$valid_flag = true;
$g_error = '';
$g_success = '';

// ログイン済みの場合はトップページにリダイレクト
if(checkLogin()) {
    header('Location: /index.php');
    exit;
}

// POST処理
if(!empty($_POST)) {
    $email = sanitizeInput($_POST['email'] ?? '');
    $nickname = sanitizeInput($_POST['nickname'] ?? '');
    
    // バリデーション
    if(empty($email)) {
        $g_error = 'メールアドレスを入力してください。';
        $valid_flag = false;
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $g_error = '正しいメールアドレス形式で入力してください。';
        $valid_flag = false;
    } elseif(empty($nickname)) {
        $g_error = 'ニックネームを入力してください。';
        $valid_flag = false;
    }
    
    if($valid_flag) {
        try {
            $user_id = getUserForReissue($email, $nickname);
            
            if($user_id != NULL) {
                $tmp_password = passwordReissue($user_id);

                // sendmail
                $mail_title = "ReadNest - パスワード再発行メール";
                $mail_body = "ReadNestをご利用いただき、ありがとうございます。\n\n" .
                           "パスワードの再発行を行いました。\n" .
                           "以下の仮パスワードでログインし、すぐにパスワードを変更してください。\n\n" .
                           "[仮パスワード]\n" . 
                           $tmp_password . "\n\n" .
                           "ログインURL:\n" .
                           "https://" . $_SERVER['HTTP_HOST'] . "/index.php\n\n" .
                           "※このメールは自動送信されています。\n\n" .
                           "ReadNest\n" . 
                           "https://" . $_SERVER['HTTP_HOST'] . "\n";
                
                $result = mb_send_mail($email, $mail_title, $mail_body, "From: ReadNest <" . $mail_address . ">");
                
                if(!$result) {
                    error_log('Failed to send password reissue email to: ' . $email);
                    $g_error = 'メール送信に失敗しました。しばらく時間をおいて再度お試しください。';
                } else {
                    $g_success = 'パスワード再発行メールを送信しました。メールをご確認ください。';
                }
            } else {
                $g_error = 'メールアドレスまたはニックネームが間違っています。';
            }
        } catch (Exception $e) {
            error_log('Password reissue error: ' . $e->getMessage());
            $g_error = 'システムエラーが発生しました。しばらく時間をおいて再度お試しください。';
        }
    }
}

// コンテンツを準備
ob_start();
?>

<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="/" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-700">パスワード再発行</span>
                </li>
            </ol>
        </nav>

        <div class="bg-white rounded-lg shadow-md p-6">
            <!-- ロゴ -->
            <div class="text-center mb-6">
                <img src="/template/modern/img/readnest_logo.png" alt="ReadNest" class="h-16 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">パスワード再発行</h1>
                <p class="text-gray-600 mt-2">メールアドレスとニックネームを入力してください</p>
            </div>

            <?php if (!empty($g_success)): ?>
            <!-- 成功メッセージ -->
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo html($g_success); ?></span>
                </div>
                <p class="text-sm mt-2">
                    メールが届かない場合は、迷惑メールフォルダもご確認ください。
                </p>
            </div>
            
            <div class="text-center">
                <a href="/index.php" class="btn-primary">
                    <i class="fas fa-arrow-left mr-2"></i>ログインページに戻る
                </a>
            </div>
            
            <?php else: ?>
            <!-- フォーム -->
            <?php if (!empty($g_error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo html($g_error); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <form action="/reissue.php" method="post">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-1"></i>メールアドレス
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           required
                           value="<?php echo html($_POST['email'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent"
                           placeholder="email@example.com">
                </div>

                <div class="mb-6">
                    <label for="nickname" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-1"></i>ニックネーム
                    </label>
                    <input type="text" 
                           name="nickname" 
                           id="nickname" 
                           required
                           value="<?php echo html($_POST['nickname'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:border-transparent"
                           placeholder="登録時のニックネーム">
                </div>

                <button type="submit" 
                        class="w-full bg-readnest-primary text-white py-2 px-4 rounded-md hover:bg-readnest-accent transition-colors focus:outline-none focus:ring-2 focus:ring-readnest-primary focus:ring-offset-2">
                    <i class="fas fa-paper-plane mr-2"></i>パスワード再発行メールを送信
                </button>
            </form>

            <!-- セキュリティ情報 -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-shield-alt mr-1"></i>セキュリティについて
                </h3>
                <ul class="text-xs text-blue-700 space-y-1">
                    <li>• 仮パスワードは一時的なものです。ログイン後すぐに変更してください</li>
                    <li>• メールが届かない場合は迷惑メールフォルダもご確認ください</li>
                    <li>• ご不明な点は <a href="/help.php" class="underline">ヘルプページ</a> をご覧ください</li>
                </ul>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    ログインページに戻る →
                    <a href="/index.php" class="text-readnest-primary hover:text-readnest-accent font-medium">
                        ログイン
                    </a>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$d_content = ob_get_clean();

// テンプレートを読み込み
require_once('template/modern/t_base.php');
?>