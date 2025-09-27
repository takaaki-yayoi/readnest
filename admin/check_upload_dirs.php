<?php
/**
 * アップロードディレクトリの確認・作成
 */
require_once('../config.php');
require_once('../admin/admin_auth.php');

// 管理者認証
requireAdmin();

$upload_base_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
$book_covers_dir = $upload_base_dir . '/book_covers';
$message = '';

// 基本アップロードディレクトリの確認・作成
if (!is_dir($upload_base_dir)) {
    if (mkdir($upload_base_dir, 0755, true)) {
        $message .= "基本アップロードディレクトリを作成しました: $upload_base_dir<br>";
    } else {
        $message .= "エラー: 基本アップロードディレクトリの作成に失敗しました: $upload_base_dir<br>";
    }
} else {
    $message .= "基本アップロードディレクトリは既に存在します: $upload_base_dir<br>";
}

// 本の表紙用ディレクトリの確認・作成
if (!is_dir($book_covers_dir)) {
    if (mkdir($book_covers_dir, 0755, true)) {
        $message .= "本の表紙用ディレクトリを作成しました: $book_covers_dir<br>";
    } else {
        $message .= "エラー: 本の表紙用ディレクトリの作成に失敗しました: $book_covers_dir<br>";
    }
} else {
    $message .= "本の表紙用ディレクトリは既に存在します: $book_covers_dir<br>";
}

// パーミッションの確認
$message .= "<br><strong>パーミッション確認:</strong><br>";
if (is_writable($upload_base_dir)) {
    $message .= "✓ 基本アップロードディレクトリは書き込み可能です<br>";
} else {
    $message .= "✗ エラー: 基本アップロードディレクトリは書き込み不可です<br>";
}

if (is_writable($book_covers_dir)) {
    $message .= "✓ 本の表紙用ディレクトリは書き込み可能です<br>";
} else {
    $message .= "✗ エラー: 本の表紙用ディレクトリは書き込み不可です<br>";
}

// .htaccessファイルの作成（画像の直接実行を防ぐ）
$htaccess_content = <<<EOT
# 画像ファイルのみ許可
<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# PHPファイルの実行を無効化
<FilesMatch "\.php$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# ディレクトリリスティングを無効化
Options -Indexes
EOT;

$htaccess_path = $book_covers_dir . '/.htaccess';
if (!file_exists($htaccess_path)) {
    if (file_put_contents($htaccess_path, $htaccess_content)) {
        $message .= "<br>✓ .htaccessファイルを作成しました<br>";
    } else {
        $message .= "<br>✗ エラー: .htaccessファイルの作成に失敗しました<br>";
    }
} else {
    $message .= "<br>.htaccessファイルは既に存在します<br>";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>アップロードディレクトリ確認 - ReadNest管理画面</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .message {
            background-color: #f0f0f0;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>アップロードディレクトリ確認</h1>
    
    <div class="message">
        <?php echo $message; ?>
    </div>
    
    <p><a href="/admin/">管理画面トップに戻る</a></p>
</body>
</html>