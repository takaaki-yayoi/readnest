<?php
/**
 * メール設定確認ツール
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

require_once('admin_auth.php');
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(__DIR__ . '/admin_helpers.php');

// 管理者権限チェック
requireAdmin();

// テストメール送信
$test_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $test_email = $_POST['test_email'];
    
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");
    
    $mail_title = "ReadNest - テストメール";
    $mail_body = "これはReadNestからのテストメールです。\n\n" .
                 "メール送信機能が正常に動作しています。\n\n" .
                 "送信日時: " . date('Y-m-d H:i:s') . "\n\n" .
                 "--------------------------\n" . 
                 "ReadNest - あなたの読書の巣\n" . 
                 "https://readnest.jp\n";
    
    $mail_from = mb_encode_mimeheader("ReadNest") . " <noreply@readnest.jp>";
    $mail_headers = "From: " . $mail_from . "\r\n";
    $mail_headers .= "Reply-To: admin@readnest.jp\r\n";
    $mail_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mail_headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $mail_headers .= "MIME-Version: 1.0\r\n";
    
    $result = mb_send_mail($test_email, $mail_title, $mail_body, $mail_headers);
    
    if ($result) {
        $test_result = "success";
    } else {
        $test_result = "failed";
    }
}

// PHP設定情報取得
$php_version = phpversion();
$sendmail_path = ini_get('sendmail_path');
$smtp = ini_get('SMTP');
$smtp_port = ini_get('smtp_port');
$mail_add_x_header = ini_get('mail.add_x_header');

// mb_send_mail設定
$mb_language = mb_language();
$mb_internal_encoding = mb_internal_encoding();

include('layout/header.php');
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">
            <i class="fas fa-envelope mr-3"></i>メール設定確認
        </h1>

        <?php if ($test_result === 'success'): ?>
            <div class="mb-6 p-4 bg-green-100 border-green-400 text-green-700 border rounded-lg">
                テストメールの送信に成功しました。
            </div>
        <?php elseif ($test_result === 'failed'): ?>
            <div class="mb-6 p-4 bg-red-100 border-red-400 text-red-700 border rounded-lg">
                テストメールの送信に失敗しました。
            </div>
        <?php endif; ?>

        <!-- PHP設定情報 -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-cog mr-2"></i>PHP メール設定
                </h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($php_version); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">sendmail_path</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($sendmail_path ?: '(not set)'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">SMTP</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($smtp ?: '(not set)'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">smtp_port</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($smtp_port ?: '(not set)'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">mail.add_x_header</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $mail_add_x_header ? 'On' : 'Off'; ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- mb_send_mail設定 -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-language mr-2"></i>マルチバイト設定
                </h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">mb_language</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($mb_language); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">mb_internal_encoding</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($mb_internal_encoding); ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- テストメール送信 -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-paper-plane mr-2"></i>テストメール送信
                </h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="test_email" class="block text-sm font-medium text-gray-700">送信先メールアドレス</label>
                        <input type="email" 
                               name="test_email" 
                               id="test_email" 
                               required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="test@example.com">
                    </div>
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-send mr-2"></i>
                        テストメール送信
                    </button>
                </form>
            </div>
        </div>

        <!-- エラーログ確認 -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-exclamation-triangle mr-2"></i>トラブルシューティング
                </h2>
            </div>
            <div class="p-6">
                <h3 class="font-semibold mb-2">メールが送信されない場合の確認事項：</h3>
                <ul class="list-disc list-inside space-y-2 text-sm text-gray-600">
                    <li>サーバーのメール送信機能が有効になっているか</li>
                    <li>sendmailまたはpostfixがインストールされているか</li>
                    <li>SPFレコードが正しく設定されているか</li>
                    <li>送信元メールアドレス（noreply@readnest.jp）が適切か</li>
                    <li>ファイアウォールがメール送信をブロックしていないか</li>
                </ul>
                
                <h3 class="font-semibold mt-4 mb-2">PHPエラーログの確認：</h3>
                <pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">
tail -f /var/log/php_errors.log | grep REGISTER
                </pre>
            </div>
        </div>
    </div>
</div>

<?php include('layout/footer.php'); ?>