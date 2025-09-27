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

<script src="js/readnest.js"></script>
</head>
<body onload="document.getElementById('add_book_text').focus()">

<div id="container">

<div id="header">
<?php print $d_header; ?>
</div>

<div id="menuarea">
<?php print $d_sub_content; ?>
</div>

<div id="main">

<h1 class="page_title">どんな読書日記があるんだろう？</h1>

<p><?php print $d_message; ?></p>

<p><?php print html($d_total_hit); ?></p>

<font color="red"><?php echo $g_error; ?></font>
<form action='search_review.php' method='get'>
<p>キーワードで検索できます。</p>
<p><input id="add_book_text" type="text" name="keyword" size="30" value="<?php print html($d_keyword); ?>">&nbsp;<input type="submit" value="検索"><input type="button" value="リセット" onclick="javascript:clear_add_text()"></p>
</form>

<!--
<form action="" id="searchbox_005984096416503586900:4rvtmmspsly" onsubmit="return false;">
  <div>
    <input type="text" name="q" size="40"/>
    <input type="submit" value="検索"/>
  </div>
</form>
<script type="text/javascript" src="https://www.google.com/coop/cse/brand?form=searchbox_005984096416503586900%3A4rvtmmspsly&lang=ja"></script>

<div id="results_005984096416503586900:4rvtmmspsly" style="display:none">
  <div class="cse-closeResults"> 
    <a>&times; 閉じる</a>
  </div>
  <div class="cse-resultsContainer"></div>
</div>

<style type="text/css">
@import url(https://www.google.com/cse/api/overlay.css);
</style>

<script src="https://www.google.com/uds/api?file=uds.js&v=1.0&key=ABQIAAAA1U20kug7_CGYup2UkEhucRTgd_Wqbq04PVlT1P9DzZD89gTrRBQmiz1dlMWDFCGEjj4GMlNN0oyYZg&hl=ja" type="text/javascript"></script>
<script src="https://www.google.com/cse/api/overlay.js" type="text/javascript"></script>
<script type="text/javascript">
function OnLoad() {
  new CSEOverlay("005984096416503586900:4rvtmmspsly",
                 document.getElementById("searchbox_005984096416503586900:4rvtmmspsly"),
                 document.getElementById("results_005984096416503586900:4rvtmmspsly"));
}
GSearch.setOnLoadCallback(OnLoad);
</script>
-->

<?=$d_popular_review ?>

<?=$everyones_tag_cloud ?>

<?=$everyones_sakka_cloud ?>

<p class="pager"><? print $d_pager; ?></p>

<?php print html_raw($d_book_list); ?>

<p class="pager"><? print $d_pager; ?></p>

</div>

<div id="sub">
</div>

<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>