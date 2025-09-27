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

<title><?php echo html($d_site_title);?>｜ReadNest - あなたの読書の巣</title>
<link href="css/readnest.css" rel="stylesheet" type="text/css" />
<meta name="Keywords" content="<?=$g_meta_keyword ?>">
<meta name="Description" content="<?=$g_meta_description ?>">

<script type="text/javascript" src="js/rounded_corners_lite.inc.js"></script>
<script type="text/javascript" src="js/readnest.js"></script>
<script type="text/javascript" src="js/round.js"></script>

<script type="text/javascript" src="/js/prototype.js"></script>
<script type="text/javascript" src="/js/scriptaculous/scriptaculous.js?load=effects,slider"></script>

<script type="text/javascript">
new Ajax.PeriodicalUpdater("read_book", "/new_read_books.php", {frequency: 30});
new Ajax.PeriodicalUpdater("tag_cloud", "/new_tag_cloud.php", {frequency: 30});
new Ajax.PeriodicalUpdater("sakka_cloud", "/new_sakka_cloud.php", {frequency: 30});
new Ajax.PeriodicalUpdater("new_review", "/new_created_review.php", {frequency: 30});
</script>

<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({
          google_ad_client: "ca-pub-1460229468388733",
          enable_page_level_ads: true
     });
</script>

</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-N6MRQPH9"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<div id="container">

<div id="header">
<?php print $d_header; ?>
</div>

<div id="menuarea">
<?php print $g_login_window; ?>
</div>
<div id="main_top">
ReadNest - あなたの読書の巣へようこそ！
<hr />
<p>読書進捗の記録、レビューの投稿、本棚の整理ができる居心地の良い空間です。読書仲間とのつながりも楽しめる、あなただけの読書の巣を作りましょう。</p>

<h2>ReadNestでできること</h2>
<ul class="top_list" >
<li><img src="img/list_marker_red.gif"><a href="/help.php#add_book">読書状況の見える化</a></li>
<p>読書中の本、読み終えた本、これから読みたい本を整理して管理できます。バーコードリーダーで簡単に手持ちの本を登録できます。</p>
<li><img src="img/list_marker_red.gif">読書進捗の記録</li>
<p>読んだページ数や冊数を記録し、読書の歴史をグラフで確認できます。着実な読書習慣を身につけましょう。</p>
<li><img src="img/list_marker_red.gif">レビューと交流</li>
<p>読み終えた本の感想やレビューを記録し、他の読書家と交流できます。新しい本との出会いも楽しめます。</p>
</ul>
<div style="text-align:right;"><img src="img/list_marker_red.gif" border="0">くわしくは<a href="help.php">こちら</a>から</div>

<?print $g_announce;?>
<hr />
</div>

<div id="sub">

<div style="text-align:center;">
<a href="https://itunes.apple.com/jp/app/id420224317?mt=8&amp;ls=1" target="_blank"><img border="0" src="/img/badge.png"></a>
</div>


<div class="round">
<p class="round-label">みんなの読書</p>
<p style="text-align:center" id="read_book"><?print $d_disclosed;?></p>
<p style="text-align:right;"><a href="disclosed_diary.php"><img src="img/list_marker_red.gif" border="0">もっと見る</a><br />
</p>
</div>

<div class="round">
<p class="round-label">新着レビュー</p>
<p style="text-align:center" id="new_review"><?=$d_new_review ?></p>
<img src="img/list_marker_red.gif" border="0"><a href="popular_review.php">人気のレビュー</a>&nbsp;/&nbsp;<a href="new_review.php">新着レビュー</a><br />
<a href="popular_book.php"><img src="img/list_marker_red.gif" border="0">人気の本</a><br />
<a href="search_review.php"><img src="img/list_marker_red.gif" border="0">読書検索</a>&nbsp;/&nbsp;<a href="ranking.php">読書家ランキング</a>
</div>


<div class="round">
<p class="round-label">みんなのタグクラウド</p>
<p style="text-align:left" id="tag_cloud"><?print $d_tag_cloud;?>
<p style="text-align:right;"><a href="tag_cloud.php"><img src="img/list_marker_red.gif" border="0">もっと見る</a></p>
</p>
</div>

<div class="round">
<p class="round-label">みんなの作家クラウド</p>
<p style="text-align:left" id="sakka_cloud"><?= $d_sakka_cloud ?>
<p style="text-align:right;"><a href="sakka_cloud.php"><img src="img/list_marker_red.gif" border="0">もっと見る</a></p>
</p>
</div>

</div>

<div id="footer">
<?php print $d_footer; ?>
</div>

</div><!-- end of container -->


<?php echo isset($g_analytics) ? $g_analytics : ""; ?>
</body>
</html>