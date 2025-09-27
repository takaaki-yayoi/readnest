<?php
/**
 * アップロードディレクトリのセットアップ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

require_once(dirname(__DIR__) . '/config.php');
require_once(__DIR__ . '/admin_auth.php');

requireAdmin();

$page_title = 'アップロードディレクトリセットアップ';
$message = '';
$message_type = '';

// セットアップ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup') {
    $upload_base = dirname(__DIR__) . '/img/user_uploads';
    $book_covers_dir = $upload_base . '/book_covers';
    
    $errors = [];
    $success = [];
    
    // ベースディレクトリ作成
    if (!is_dir($upload_base)) {
        if (mkdir($upload_base, 0755, true)) {
            $success[] = "ディレクトリ作成: $upload_base";
        } else {
            $errors[] = "ディレクトリ作成失敗: $upload_base";
        }
    } else {
        $success[] = "既存: $upload_base";
    }
    
    // 書籍カバー用ディレクトリ作成
    if (!is_dir($book_covers_dir)) {
        if (mkdir($book_covers_dir, 0755, true)) {
            $success[] = "ディレクトリ作成: $book_covers_dir";
        } else {
            $errors[] = "ディレクトリ作成失敗: $book_covers_dir";
        }
    } else {
        $success[] = "既存: $book_covers_dir";
    }
    
    // .htaccessファイル作成（直接アクセス制限）
    $htaccess_content = <<<'HTACCESS'
# 画像ファイルのみアクセス許可
<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# その他のファイルはアクセス拒否
<FilesMatch "^(?!.*\.(jpg|jpeg|png|gif|webp)$).*$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# ディレクトリ一覧の無効化
Options -Indexes
HTACCESS;
    
    $htaccess_path = $book_covers_dir . '/.htaccess';
    if (file_put_contents($htaccess_path, $htaccess_content)) {
        $success[] = ".htaccess作成: $htaccess_path";
    } else {
        $errors[] = ".htaccess作成失敗: $htaccess_path";
    }
    
    // index.htmlファイル作成（念のため）
    $index_content = '<!DOCTYPE html><html><head><title>Forbidden</title></head><body>Forbidden</body></html>';
    $index_path = $book_covers_dir . '/index.html';
    if (file_put_contents($index_path, $index_content)) {
        $success[] = "index.html作成: $index_path";
    } else {
        $errors[] = "index.html作成失敗: $index_path";
    }
    
    if (empty($errors)) {
        $message = "セットアップが完了しました。\n" . implode("\n", $success);
        $message_type = 'success';
    } else {
        $message = "エラーが発生しました:\n" . implode("\n", $errors);
        if (!empty($success)) {
            $message .= "\n\n成功:\n" . implode("\n", $success);
        }
        $message_type = 'error';
    }
}

// 現在の状態を確認
$upload_base = dirname(__DIR__) . '/img/user_uploads';
$book_covers_dir = $upload_base . '/book_covers';

$status = [
    'upload_base' => [
        'path' => $upload_base,
        'exists' => is_dir($upload_base),
        'writable' => is_dir($upload_base) ? is_writable($upload_base) : false
    ],
    'book_covers' => [
        'path' => $book_covers_dir,
        'exists' => is_dir($book_covers_dir),
        'writable' => is_dir($book_covers_dir) ? is_writable($book_covers_dir) : false
    ],
    'htaccess' => [
        'path' => $book_covers_dir . '/.htaccess',
        'exists' => file_exists($book_covers_dir . '/.htaccess')
    ]
];

// CSRFトークン生成
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include('layout/header.php');
?>

<div class="max-w-4xl mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">アップロードディレクトリセットアップ</h1>
    
    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?php 
        echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 
             'bg-red-100 text-red-700 border border-red-200'; 
    ?>">
        <pre><?php echo htmlspecialchars($message); ?></pre>
    </div>
    <?php endif; ?>
    
    <!-- 現在の状態 -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">現在の状態</h2>
        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="text-left py-2">項目</th>
                    <th class="text-left py-2">パス</th>
                    <th class="text-left py-2">状態</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b">
                    <td class="py-2">ベースディレクトリ</td>
                    <td class="py-2 text-sm font-mono"><?php echo htmlspecialchars($status['upload_base']['path']); ?></td>
                    <td class="py-2">
                        <?php if ($status['upload_base']['exists']): ?>
                            <span class="text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>存在
                                <?php if ($status['upload_base']['writable']): ?>
                                    / 書き込み可能
                                <?php else: ?>
                                    / <span class="text-red-600">書き込み不可</span>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>存在しない</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="border-b">
                    <td class="py-2">書籍カバーディレクトリ</td>
                    <td class="py-2 text-sm font-mono"><?php echo htmlspecialchars($status['book_covers']['path']); ?></td>
                    <td class="py-2">
                        <?php if ($status['book_covers']['exists']): ?>
                            <span class="text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>存在
                                <?php if ($status['book_covers']['writable']): ?>
                                    / 書き込み可能
                                <?php else: ?>
                                    / <span class="text-red-600">書き込み不可</span>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>存在しない</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="py-2">.htaccess</td>
                    <td class="py-2 text-sm font-mono"><?php echo htmlspecialchars($status['htaccess']['path']); ?></td>
                    <td class="py-2">
                        <?php if ($status['htaccess']['exists']): ?>
                            <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>存在</span>
                        <?php else: ?>
                            <span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>存在しない</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- セットアップボタン -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">セットアップ実行</h2>
        <p class="text-gray-600 mb-4">
            必要なディレクトリとセキュリティファイルを作成します。
        </p>
        
        <form method="post" onsubmit="return confirm('セットアップを実行しますか？');">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="setup">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-cogs mr-2"></i>セットアップ実行
            </button>
        </form>
    </div>
    
    <!-- 説明 -->
    <div class="bg-blue-50 rounded-lg p-6 mt-6">
        <h3 class="text-lg font-semibold mb-2">
            <i class="fas fa-info-circle mr-2"></i>セットアップ内容
        </h3>
        <ul class="list-disc list-inside space-y-1 text-sm text-gray-700">
            <li><code>/img/user_uploads/</code> ディレクトリを作成</li>
            <li><code>/img/user_uploads/book_covers/</code> ディレクトリを作成</li>
            <li>.htaccessファイルで画像ファイル以外のアクセスを制限</li>
            <li>index.htmlファイルでディレクトリ一覧を無効化</li>
        </ul>
    </div>
</div>

<?php include('layout/footer.php'); ?>