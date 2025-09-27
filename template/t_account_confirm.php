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

<div id="menuarea">
<?php print $d_global_nav; ?>
</div>

<div id="main">

<h1 class="page_title">アカウント管理（確認）</h1>

<p>以下の内容でよろしいですか。</p>
<font color="red"><?php echo $g_error; ?></font>
<form action='account.php' method='post'>
<p class="centering-label">emailアドレス</p>
<p style="text-align:center"><?php print(html($d_email)); ?></p>

<p class="centering-label">ニックネーム</p>
<p style="text-align:center"><?php print(html($d_nickname)); ?></p>

<p class="centering-label">パスワード</p>
<p style="text-align:center">表示されません</p>

<p class="centering-label">読破数目標(年間)</p>
<p style="text-align:center"><?php print(html($books_per_year)); ?></p>

<p class="centering-label">amazonアソシエイトID</p>
<p style="text-align:center"><?php print(html($d_amazon_id)); ?></p>

<p class="centering-label">読書日記を</p>
<p style="text-align:center"><?php print(html($d_diary_policy)); ?></p>

<p class="centering-label">プロフィール写真</p>
<p style="text-align:center"><?php print($image_part); ?></p>

<p class="centering-label">自己紹介</p>
<p style="text-align:center"><?php print(nl2br(html($introduction))); ?></p>


<input type="hidden" name="email1" value="<?php print(html($email1)); ?>" />
<input type="hidden" name="nickname" value="<?php print(html($nickname)); ?>" />
<input type="hidden" name="password1" value="<?php print(html($password1)); ?>" />
<input type="hidden" name="books_per_year" value="<?php print(html($books_per_year)); ?>" />
<input type="hidden" name="amazon_id" value="<?php print(html($amazon_id)); ?>" />
<input type="hidden" name="diary_policy" value="<?php print(html($diary_policy)); ?>" />
<input type="hidden" name="remove_photo" value="<?php print(html($remove_photo)); ?>" />
<input type="hidden" name="introduction" value="<?php print(html($introduction)); ?>" />
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