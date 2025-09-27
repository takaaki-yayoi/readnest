<?php
/**
 * Google連携確認ページのクラシックテンプレート
 */

if(!defined('CONFIG')) {
    error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
    die('reference for this file is not allowed.');
}

$existing_user = $google_link_data['existing_user'];
$google_user_info = $google_link_data['google_user_info'];
$error_message = $google_link_data['error_message'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5ZF3NGQ4QT"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-5ZF3NGQ4QT');
</script>

<title><?php echo html($page_title);?>｜ReadNest - あなたの読書の巣</title>
<link href="/css/readnest.css" rel="stylesheet" type="text/css" />
<meta name="Keywords" content="<?=$g_meta_keyword ?>">
<meta name="Description" content="<?=$g_meta_description ?>">

<script type="text/javascript" src="/js/rounded_corners_lite.inc.js"></script>
<script type="text/javascript" src="/js/readnest.js"></script>
<script type="text/javascript" src="/js/round.js"></script>

<style type="text/css">
.google-link-box {
    margin: 20px auto;
    padding: 20px;
    width: 500px;
    background: #ffffff;
    border: 1px solid #cccccc;
    border-radius: 5px;
}

.google-info {
    background: #f5f5f5;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.google-info img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    vertical-align: middle;
    margin-right: 10px;
}

.error-box {
    background: #ffebee;
    border: 1px solid #ffcccc;
    color: #cc0000;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.form-row {
    margin-bottom: 15px;
}

.form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-row input[type="password"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #cccccc;
    border-radius: 3px;
    box-sizing: border-box;
}

.button-row {
    text-align: center;
    margin-top: 20px;
}

.button-row input[type="submit"] {
    margin: 0 5px;
    padding: 8px 20px;
    border-radius: 3px;
    border: 1px solid;
    cursor: pointer;
}

.button-primary {
    background: #4285f4;
    color: white;
    border-color: #357ae8;
}

.button-secondary {
    background: #f0f0f0;
    color: #333;
    border-color: #cccccc;
}
</style>
</head>
<body>

<div id="container">

<div id="header">
<?php print $d_header; ?>
</div>

<div id="main">
    <div class="google-link-box">
        <h2>Googleアカウント連携確認</h2>
        
        <div class="google-info">
            <h3>Googleアカウント情報</h3>
            <?php if (isset($google_user_info['picture'])): ?>
            <img src="<?php echo html($google_user_info['picture']); ?>" alt="">
            <?php endif; ?>
            <strong><?php echo html($google_user_info['name']); ?></strong><br>
            <?php echo html($google_user_info['email']); ?>
        </div>
        
        <p>このGoogleアカウントは、既存のReadNestアカウント「<strong><?php echo html($existing_user['nickname']); ?></strong>」と同じメールアドレスです。</p>
        <p>アカウントを連携すると、今後Googleアカウントでログインできるようになります。</p>
        
        <?php if ($error_message): ?>
        <div class="error-box">
            <?php echo html($error_message); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <label for="password">ReadNestアカウントのパスワード</label>
                <input type="password" id="password" name="password" required 
                       placeholder="現在のパスワードを入力">
            </div>
            
            <div class="button-row">
                <input type="submit" name="link_account" value="アカウントを連携する" 
                       class="button-primary" onclick="this.form.elements['link_account'].value='yes';">
                <input type="submit" name="link_account" value="キャンセル" 
                       class="button-secondary" onclick="this.form.elements['link_account'].value='no';">
            </div>
        </form>
    </div>
</div>

<div id="sub">
</div>

<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>