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
<body onload="document.getElementById('add_book_text').focus()">

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

<p style="text-align:right; font-size:x-small"><img src="img/list_marker_red.gif" border="0"><a href="add_original_book.php">検索にヒットしない本を追加</a></p>

<p><?php print $d_message; ?></p>

<font color="red"><?php echo $g_error; ?></font>
<form action='<? print $_SERVER['PHP_SELF']; ?>' method='get'>

<p>キーワード検索&nbsp;&nbsp;<input id="add_book_text" type="text" name="keyword" size="30" value="<?php print html($d_keyword); ?>">&nbsp;<input type="submit" value="検索"><input type="button" value="リセット" onclick="javascript:clear_add_text()"></p>
</form>

<p><?php print html($d_total_hit); ?></p>

<p class="pager"><? print $d_pager; ?></p>

<?php print html_raw($d_book_list); ?>

<p class="pager"><? print $d_pager; ?></p>

</div>

<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>