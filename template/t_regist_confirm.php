<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>

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

<title><?php echo html($d_site_title);?></title>
<link href="css/readnest.css" rel="stylesheet" type="text/css" />
</head>
<body>

<div id="container">

<div id="header">
<?php print $d_header; ?>
</div>

<div id="main">
<p>以下の内容でよろしいですか。本登録用メールが送信されます。</p>
<font color="red"><?php echo $g_error; ?></font>
<form action='register.php' method='post'>
<p class="centering-label">emailアドレス</p>
<p style="text-align:center"><?php print(html($email1)); ?></p>

<p class="centering-label">ニックネーム</p>
<p style="text-align:center"><?php print(html($nickname)); ?></p>

<p class="centering-label">パスワード</p>
<p style="text-align:center">表示されません</p>

<input type="hidden" name="email1" value="<?php print(html($email1)); ?>" />
<input type="hidden" name="nickname" value="<?php print(html($nickname)); ?>" />
<input type="hidden" name="password1" value="<?php print(html($password1)); ?>" />
<input type="hidden" name="confirm" value="yes" />
<p style="text-align:center"><input type="submit" value="登録"><input type="button" value="修正" onclick="javascript:history.back()"></p>
</form>
</div>



<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>