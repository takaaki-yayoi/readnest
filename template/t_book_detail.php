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

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-N6MRQPH9');</script>
<!-- End Google Tag Manager -->

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5ZF3NGQ4QT"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-5ZF3NGQ4QT');
</script>

<title><?php echo html($d_site_title);?></title>
<meta name="description" content="<?= html($g_meta_description) ?>">
<link href="/css/readnest.css" rel="stylesheet" type="text/css" />

<!-- SEO Tags -->
<?php if (isset($g_seo_tags)): ?>
<?= $g_seo_tags ?>
<?php endif; ?>
<script type="text/javascript" src="/js/prototype.js"></script>
<script type="text/javascript" src="/js/scriptaculous/scriptaculous.js?load=effects,slider"></script>
<script type="text/javascript" src="/js/overlib/overlib.js"><!-- overLIB (c) Etik Bosrup --></script>
<script type="text/javascript" src="/js/rounded_corners_lite.inc.js"></script>
<script type="text/javascript" src="/js/readnest.js"></script>
<script type="text/javascript" src="/js/round.js"></script>

<script type="text/javascript">
<?= html_raw($script_part) ?>
<?= html_raw($related_books_script) ?>
</script>


<script type="text/javascript">
Event.observe(document, "dom:loaded", function(){
    $('page_input_box').focus();
    $('page_input_box').select();
},false);
</script>

</head>
<body<?= html_raw($on_load_script_part) ?>>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-N6MRQPH9"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<div id="container">

<div id="header">
<?php print $d_header; ?>
</div>

<div id="menuarea">
<?php print $d_global_nav; ?>
</div>

<div id="main_top">

<h1 class="page_title"><?php echo html($name); ?><?= html_raw($refer_part) ?></h1>
<p><?php echo html_raw($user_profile); ?></p>

<?= html_raw($d_bookshelf) ?>
</div>

<div id="sub">

<!--<?= html_raw($related_books) ?>-->
<?= html_raw($readers_part) ?>
<?= html_raw($tag_area) ?>



</div>

<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>