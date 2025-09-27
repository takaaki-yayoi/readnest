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
<meta name="description" content="<?= html($g_meta_description) ?>">
<link href="css/readnest.css" rel="stylesheet" type="text/css" />

<!-- SEO Tags -->
<?php if (isset($g_seo_tags)): ?>
<?= $g_seo_tags ?>
<?php endif; ?>

<script src="js/readnest.js"></script>
<script type="text/javascript" src="/js/prototype.js"></script>
<script type="text/javascript" src="/js/overlib/overlib.js"><!-- overLIB (c) Etik Bosrup --></script>

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

<h1 class="page_title"><?php echo html($profile_title); ?></h1>
<?= html_raw($edit_link) ?>
<p><?= html($user_profile_book) ?></p>
<table border="0">
<tr>
<td><?= html_raw($photo_part) ?></td>
<td><?php echo html_raw($user_profile); ?>
</td>
</tr>
</table>
<h2><?php print html($nickname); ?>さんのお気に入りの本</h2>
<?= html_raw($favored_books) ?>

<h2><a name="book_tag">タグ(クリックすると本棚を検索できます)</a></h2>
<?= html_raw($user_tags) ?>

<h2><a name="sakka_cloud">作家クラウド(クリックすると本棚を検索できます)</a></h2>
<?= html_raw($favored_authors) ?>

<hr />
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