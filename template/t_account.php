<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

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

<title><?php echo html($d_site_title);?></title>
<link href="css/readnest.css" rel="stylesheet" type="text/css" />
<script src="js/readnest.js"></script>
</head>
<body>

<div id="container">

<div id="header">
<?php echo html_raw($d_header); ?>
</div>

<div id="menuarea">
<?php echo html_raw($d_global_nav); ?>
</div>

<div id="main">

<h1 class="page_title">アカウント管理</h1>

<p>以下の項目を変更することができます。</p>
<p><a href="profile.php?user_id=<?= html($user_id) ?>"><img src="img/16-member.png" border="0">プロフィールを確認</a></p>
<font color="red"><?php echo html($g_error); ?></font>
<form action='account.php' method='post' enctype="multipart/form-data">
<p class="centering-label">現emailアドレス</p>
<p style="text-align:center"><?php print(html($d_email)); ?></p>
<p class="centering-label">新emailアドレス</p>
<p style="text-align:center"><input type="text" name="email1" size="30"></p>
<p class="centering-label">新emailアドレス(確認)</p>
<p style="text-align:center"><input type="text" name="email2" size="30"></p>

<p class="centering-label">現ニックネーム</p>
<p style="text-align:center"><?php print(html($d_nickname)); ?></p>
<p class="centering-label">新ニックネーム</p>
<p style="text-align:center"><input type="text" name="nickname" size="20"></p>

<p class="centering-label">新パスワード</p>
<p style="text-align:center"><input type="password" name="password1" size="20"></p>

<p class="centering-label">新パスワード(確認)</p>
<p style="text-align:center"><input type="password" name="password2" size="20"></p>

<p class="centering-label">読破数目標(年間)</p>
<p style="text-align:center"><input type="text" name="books_per_year" size="20" value="<?php print html($books_per_year); ?>"></p>

<p class="centering-label">あなたのamazonアソシエイトID</p>
<p style="text-align:center"><input type="text" name="amazon_id" size="20" value="<?php print html($d_amazon_id); ?>"></p>

<p class="centering-label">読書日記、プロフィールを</p>
<p style="text-align:center"><input type="radio" name="diary_policy" value="1" id="open" <?php echo html_raw($d_open_policy); ?>><label for="open">公開する</label><input type="radio" name="diary_policy" value="0" id="close" <?php echo html_raw($d_close_policy); ?>><label for="close">公開しない</label></p>

<p class="centering-label">プロフィール写真</p>
<?= html_raw($photo_part) ?>
<p style="text-align:center"><input type="file" name="profile_photo"></p>

<p class="centering-label">自己紹介</p>
<p style="text-align:center"><textarea  rows="10" cols="60" name="introduction"><?= html($introduction) ?></textarea></p>

<p style="text-align:center"><input type="submit" value="確認画面"></p>
</form>

<p><a href="quit.php"><img src="img/list_marker_red.gif" border="0"><?php print html($d_site_title); ?>から退会する。</a></p>

</div>

<div id="sub">
</div>

<div id="footer">
<?php echo html_raw($d_footer); ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>