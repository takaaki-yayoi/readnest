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
<link href="css/readnest.css" rel="stylesheet" type="text/css" />

<!-- SEO Tags -->
<?php if (isset($g_seo_tags)): ?>
<?= $g_seo_tags ?>
<?php endif; ?>

<!--
<script type="text/javascript" src="./amline/swfobject.js"></script>
-->

<script type="text/javascript" src="js/rounded_corners_lite.inc.js"></script>
<script type="text/javascript" src="js/readnest.js"></script>
<script type="text/javascript" src="js/round.js"></script>

<script type="text/javascript" src="js/Chart.js"></script>

<!--
<script type="text/javascript" src="/js/prototype.js"></script>
<script type="text/javascript" src="/js/scriptaculous/scriptaculous.js?load=effects,slider"></script>

<script type="text/javascript">
new Ajax.PeriodicalUpdater("read_book", "/new_read_books.php", {frequency: 30});
new Ajax.PeriodicalUpdater("tag_cloud", "/new_tag_cloud.php", {frequency: 30});
new Ajax.PeriodicalUpdater("sakka_cloud", "/new_sakka_cloud.php", {frequency: 30});
new Ajax.PeriodicalUpdater("new_event", "/new_progress.php?user_id=<?=$user_id ?>", {method: 'get', frequency: 30});
new Ajax.PeriodicalUpdater("new_review", "/new_created_review.php", {frequency: 30});
</script>
-->

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
<?php print $d_global_nav; ?>
</div>

<div id="main_top">


<table border="0">
<tr>
<td><?= $photo_part ?><p style="text-align:center;margin:1px;font-size:x-small"><?php print html($d_nickname); ?>さん</p></td>
<td>あなたはこれまでの人生で<?php print html($d_total_book_num); ?>冊の本を読み終え、<?php print html($d_total_page_num); ?>ページをめくりました。<br/><?php print $read_this_month_str; ?>
</td>
</tr>
<tr>
<td colspan="2"><div style="float:left"><span style="font-size:x-small">最近ページをめくった本</span>&nbsp;<?= $recent_updated_str ?></div>
<p style="float:right;font-size:x-small;margin:0"><a href="bookshelf.php?user_id=<?=$user_id ?>&status=<?=READING_NOW ?>"><img src="img/list_marker_red.gif" border="0">もっと見る</a></p></td>
</tr>
<tr>
<td colspan="2"><div style="float:left"><span style="font-size:x-small">最近読み終えた本</span>&nbsp;<?= html_raw($recent_finished_str) ?></div>
<p style="float:right;font-size:x-small;margin:0"><a href="bookshelf.php?user_id=<?= html($user_id) ?>&status=<?= html(READING_FINISH) ?>"><img src="img/list_marker_red.gif" border="0">もっと見る</a></p></td>
</tr>
<tr>
<td colspan="2" id="new_event"><span style="font-size:x-small" >最近の出来事</span>&nbsp;<?= html_raw($event_str) ?></td>
</tr>
<?= html_raw($comment_message) ?>
</table>

<h2>あなたのReadNest読書統計</h2>
<!--
<p style="text-align:center;margin:0px;">
<center>
<?php echo html_raw($graph_part); ?>
</center>
</p>
-->

<canvas id="myChart"></canvas>
<script type="text/javascript">

<?print $page_data_str?>

<?print $page_index_str?>

<?print $page_data_total_str?>

var ctx = document.getElementById('myChart').getContext('2d');
var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        datasets: [{
            type: 'bar',
            data: page_data,
            label: 'ページ',
            backgroundColor: "rgba(0,0,255,1)",

            // This binds the dataset to the left y axis
            yAxisID: 'left-y-axis'
        }, {
            type: 'line',
            data: page_total,
            label: 'ページ総数',
            backgroundColor: "rgba(255,0,0,0.4)",

            // This binds the dataset to the right y axis
            yAxisID: 'right-y-axis'
        }],
        labels: page_index
    },
    options: {
        scales: {
            yAxes: [{
                id: 'left-y-axis',
                type: 'linear',
                position: 'left'
            }, {
                id: 'right-y-axis',
                type: 'linear',
                position: 'right'
            }]
        }
    }
});

/*
var ctx = document.getElementById('myChart').getContext('2d');
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
  type: 'bar',
    labels: page_index,
    datasets: [{
      label: 'ページ',
      data: page_data,
      backgroundColor: "rgba(0,0,255,1)",
      yAxisID: "y-axis-1", // 追加
    }, {
      type: 'line',
      label: 'ページ総数',
      data: page_total,
      backgroundColor: "rgba(255,0,0,0.4)",
      //yAxisID: "y-axis-2", // 追加
    }],
    options: complexChartOption
  }
});
*/
</script>

<!--
<h2>あなたの最近のタグ</h2>
<?= $user_tags ?>
<p style="text-align:right;"><a href="profile.php?user_id=<?=$user_id ?>#book_tag"><img src="img/list_marker_red.gif" border="0">もっと見る</a></p>

<h2>最近気になる作家さん</h2>
<?= $favored_authors ?>
<p style="text-align:right;"><a href="profile.php?user_id=<?=$user_id ?>#sakka_cloud"><img src="img/list_marker_red.gif" border="0">もっと見る</a></p>
-->

<?print $g_announce;?>
</div>

<div id="sub">

<div style="text-align:center;">
<a href="https://itunes.apple.com/jp/app/id420224317?mt=8&amp;ls=1" target="_blank"><img border="0" src="/img/badge.png"></a>
</div>

<div class="round">
<p class="round-label">みんなの読書</p>
<p style="text-align:center" id="read_book"><?=$d_disclosed ?></p>
<p style="text-align:right;"><a href="disclosed_diary.php"><img src="img/list_marker_red.gif" border="0">もっと見る</a><br />
</p>
</div>

<div class="round">
<p class="round-label">新着レビュー</p>
<p style="text-align:center" id="new_review"><?=$d_new_review ?></p>
<p style="text-align:right;"><img src="img/list_marker_red.gif" border="0"><a href="new_review.php">もっと見る</a><br /></p>
<a href="popular_book.php"><img src="img/list_marker_red.gif" border="0">人気の本</a>&nbsp;/&nbsp;<a href="popular_review.php">人気のレビュー</a><br />
<a href="ranking.php"><img src="img/list_marker_red.gif" border="0">読書家ランキング</a>
</div>


<div class="round">
<p class="round-label">みんなのタグクラウド</p>
<p style="text-align:left" id="tag_cloud"><?=$d_tag_cloud ?></p>
<p style="text-align:right;"><a href="tag_cloud.php"><img src="img/list_marker_red.gif" border="0">もっと見る</a></p>
</p>
</div>

<div class="round">
<p class="round-label">みんなの作家クラウド</p>
<p style="text-align:left" id="sakka_cloud"><?=$d_sakka_cloud ?></p>
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