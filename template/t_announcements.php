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
<meta name="Keywords" content="<?=$g_meta_keyword ?>">
<meta name="Description" content="<?=$g_meta_description ?>">
<script type="text/javascript" src="./amline/swfobject.js"></script>
<script type="text/javascript" src="js/rounded_corners_lite.inc.js"></script>
<script type="text/javascript" src="js/readnest.js"></script>
<script type="text/javascript" src="js/round.js"></script>

<script type="text/javascript" src="/js/prototype.js"></script>
<script type="text/javascript" src="/js/scriptaculous/scriptaculous.js?load=effects,slider"></script>


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

<?print $g_announce;?>
</div>

<div id="sub">

</div>

</div>

<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>