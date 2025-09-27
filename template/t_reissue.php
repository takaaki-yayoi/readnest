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
<p>以下の項目を記入して下さい。</p>
<font color="red"><?php echo $g_error; ?></font>
<form action='reissue.php' method='post'>
<p class="centering-label">emailアドレス</p>
<p style="text-align:center"><input type="text" name="email" size="30"></p>
<p class="centering-label">ニックネーム</p>
<p style="text-align:center"><input type="text" name="nickname" size="20"></p>

<p style="text-align:center"><input type="submit" value="再発行"></p>
</form>
</div>



<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>