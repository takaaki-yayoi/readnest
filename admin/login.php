<?php
/**
 * 管理者ログインページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 設定を読み込み
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/library/session.php');
require_once(dirname(__DIR__) . '/library/database.php');
require_once(__DIR__ . '/admin_helpers.php');
require_once('admin_auth.php');

// 既に管理者としてログインしている場合はダッシュボードへ
if (isAdmin()) {
    header('Location: /admin/');
    exit;
}

$error_message = '';

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF トークンの検証
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $error_message = '不正なリクエストです。もう一度お試しください。';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error_message = 'メールアドレスとパスワードを入力してください。';
        } else {
            // 認証
            $user_id = authUser($email, $password);
            
            if ($user_id && in_array($email, ADMIN_EMAILS, true)) {
                // セッションにユーザーIDを保存
                $_SESSION['AUTH_USER'] = $user_id;
                
                // CSRFトークンを再生成
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                // リダイレクト先があれば移動
                $redirect = $_SESSION['admin_redirect'] ?? '/admin/';
                unset($_SESSION['admin_redirect']);
                header('Location: ' . $redirect);
                exit;
            } else {
                $error_message = '管理者権限がありません。';
            }
        }
    }
}

// CSRF トークンを生成
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ログイン - ReadNest Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .bg-admin-primary { background-color: #1a1a2e; }
        .text-admin-primary { color: #1a1a2e; }
        .bg-admin-secondary { background-color: #16213e; }
        .text-admin-accent { color: #e94560; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="bg-admin-primary p-6 text-center">
                <i class="fas fa-shield-alt text-white text-4xl mb-2"></i>
                <h1 class="text-2xl font-bold text-white">ReadNest Admin</h1>
                <p class="text-gray-300 text-sm mt-2">管理者ログイン</p>
            </div>
            
            <div class="p-8">
                <?php if ($error_message): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2"></i>
                        <p class="text-sm text-red-800"><?php echo safeHtml($error_message); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo safeHtml($_SESSION['csrf_token']); ?>">
                    
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            管理者メールアドレス
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   required
                                   autofocus
                                   value="<?php echo safeHtml($_POST['email'] ?? ''); ?>"
                                   class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-admin-primary focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            パスワード
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required
                                   class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-admin-primary focus:border-transparent">
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-admin-primary text-white font-medium py-3 rounded-md hover:bg-admin-secondary transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        ログイン
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-1"></i>
                        トップページに戻る
                    </a>
                </div>
            </div>
        </div>
        
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>管理者権限は admin@readnest.jp または icotfeels@gmail.com に付与されています</p>
        </div>
    </div>
</body>
</html>