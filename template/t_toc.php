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
<meta name="Keywords" content="読書,グラフ,本棚,日記">
<script src="js/readnest.js"></script>
<script src="js/prototype.js"></script>

<script text="text/javascript">
var search_keyword = '石黒 耀';
Event.observe(document, "dom:loaded", load_related_books);
</script>

</head>
<body>

<div id="container">

<div id="header">
<?php print $d_header; ?>
</div>

<div id="main">

<h1 class="page_title">索引です</h1>

<font color="red"><?php echo $g_error; ?></font>

<?=$toc_str ?>

</div>

<div id="sub">
<? print $d_sub_content; ?>

<div id="related_books">
</div>

</div>

<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->


</body>
</html>