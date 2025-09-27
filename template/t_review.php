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
<script type="text/javascript" src="/js/prototype.js"></script>
<script type="text/javascript" src="/js/overlib/overlib.js"><!-- overLIB (c) Etik Bosrup --></script>

<script type="text/javascript" src="/js/rounded_corners_lite.inc.js"></script>
<script type="text/javascript" src="/js/readnest.js"></script>
<script type="text/javascript" src="/js/round.js"></script>

</head>
<body>

<div id="container">

<div id="header">
<?php print $d_header; ?>
</div>

<div id="menuarea">
<?php print $d_global_nav; ?>
</div>

<div id="main_top">

<h1 class="page_title"><?php print html($d_nickname); ?>さんの読書日記<span style="font-size:small">[この画面は公開されません]</span></h1>

<font color="red"><?php echo $g_error; ?></font>

<? print $d_switch; ?>

<p style="text-align:center"><?=$d_order_switch; ?></p>

<?php print html_raw($d_bookshelf); ?>
</div>

<div id="sub">

<div class="round">
</div>

</div>

<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>