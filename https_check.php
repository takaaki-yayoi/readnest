<?php
/**
 * HTTPS設定確認ページ
 */

echo "<h1>HTTPS設定確認</h1>";

echo "<h2>現在の接続状況</h2>";
echo "<p>プロトコル: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP') . "</p>";
echo "<p>ポート: " . $_SERVER['SERVER_PORT'] . "</p>";
echo "<p>ホスト: " . $_SERVER['HTTP_HOST'] . "</p>";

echo "<h2>送信されているヘッダー</h2>";
$headers = [
    'Strict-Transport-Security',
    'Content-Security-Policy', 
    'X-XSS-Protection',
    'X-Content-Type-Options',
    'X-Frame-Options',
    'Referrer-Policy'
];

foreach ($headers as $header) {
    $value = '';
    foreach (headers_list() as $h) {
        if (stripos($h, $header . ':') === 0) {
            $value = trim(substr($h, strlen($header) + 1));
            break;
        }
    }
    echo "<p><strong>{$header}:</strong> " . ($value ?: '未設定') . "</p>";
}

echo "<h2>混在コンテンツチェック</h2>";
echo "<p>このページがHTTPSで表示されていて、かつ保護されていない通信の警告が出ない場合、設定は正常です。</p>";

// テスト用のHTTPSリソース読み込み
echo "<h3>テスト画像（HTTPS）</h3>";
echo "<img src='https://via.placeholder.com/100x100.png?text=HTTPS' alt='HTTPS Test Image' style='border: 1px solid green;'>";

echo "<h3>外部CDNリソーステスト</h3>";
echo "<p>Font Awesome: <i class='fas fa-check'></i></p>";

echo "<h2>推奨アクション</h2>";
echo "<ul>";
echo "<li>すべてのHTTPリンクをHTTPSに変更</li>";
echo "<li>外部リソース（画像、CSS、JS）をHTTPSで読み込み</li>";
echo "<li>サーバーレベルでHTTPS強制</li>";
echo "</ul>";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #2d3748; }
p { margin: 8px 0; }
.success { color: green; }
.error { color: red; }
</style>