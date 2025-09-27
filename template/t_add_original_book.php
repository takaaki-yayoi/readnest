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
<link href="css/readnest.css?20190812_11" rel="stylesheet" type="text/css" />

<script src="js/readnest.js"></script>

</head>
<body>

<div id="container">

<div id="header">
<?php print $d_header; ?>
</div>

<div id="menuarea">
<?php print $d_global_nav; ?>
</div>

<br clear="both">

<div id="main">

<h1 class="page_title">本棚に本を追加</h1>

<p><?php print $d_message; ?></p>

<font color="red"><?php echo $g_error; ?></font>


  <div class="book_form">
    <form action='<?php echo html($_SERVER['PHP_SELF']); ?>' method='POST'>
    <table class="book_add_table" border="0" align="center">
      <tr><td><label>タイトル</label></td>
      <td><input type="text" name="title" value="<?= html($book_name) ?>"></td></tr>
      <tr><td><label>著者</label></td>
      <td><input type="text" name="author" value="<?= html($author) ?>"></td></tr>
      <tr><td><label>ページ数</label></td>
      <td><input type="text" name="number_of_pages" value="<?= html($number_of_pages) ?>"></td></tr>
      <td><input type="hidden" name="confirm" value=""></td></tr>
      <tr><td><label>状態</label></td>
      <td><?php print(status_list()); ?></td></tr>
      <tr><td colspan="2"><input type="submit" value="追加"></td></tr>
    </table>
    </form>
  </div>

</div>

<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>