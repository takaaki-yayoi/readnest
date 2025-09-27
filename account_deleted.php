<?php
/**
 * アカウント削除完了ページ
 * PHP 8.2.28対応版
 */

declare(strict_types=1);

// 最小限の設定を読み込み（ログイン不要）
require_once('config.php');

// すでにログアウト済みなのでログインチェックは不要

// ページタイトル設定
$d_site_title = "退会完了 - ReadNest";

// メタ情報
$g_meta_description = "ReadNestの退会手続きが完了しました。";
$g_meta_keyword = "退会完了,ReadNest";

// security.phpを読み込んでhtml関数を使用
require_once(dirname(__FILE__) . '/library/security.php');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo html($d_site_title); ?></title>
    <meta name="description" content="<?php echo html($g_meta_description); ?>">
    <meta name="keywords" content="<?php echo html($g_meta_keyword); ?>">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-readnest-primary { background-color: #4A5568; }
        .bg-readnest-accent { background-color: #718096; }
        .text-readnest-primary { color: #4A5568; }
        .hover\:bg-readnest-accent:hover { background-color: #718096; }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <i class="fas fa-check-circle text-6xl text-green-500"></i>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 mb-4">
                退会手続きが完了しました
            </h1>
            
            <p class="text-gray-600 mb-6">
                ReadNestをご利用いただき、ありがとうございました。<br>
                あなたのアカウントとすべてのデータは削除されました。
            </p>
            
            <div class="border-t pt-6">
                <p class="text-sm text-gray-500 mb-4">
                    またのご利用をお待ちしております。
                </p>
                
                <a href="/" 
                   class="inline-block bg-readnest-primary text-white px-6 py-3 rounded-md hover:bg-readnest-accent transition-colors">
                    <i class="fas fa-home mr-2"></i>トップページへ
                </a>
            </div>
        </div>
        
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>ご意見・ご要望がございましたら</p>
            <p>お問い合わせフォームよりお聞かせください。</p>
        </div>
    </div>
</body>
</html>