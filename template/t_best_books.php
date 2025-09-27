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
<script src="js/readnest.js"></script>
<script type="text/javascript" src="/js/prototype.js"></script>
<script type="text/javascript" src="/js/scriptaculous/scriptaculous.js?load=effects,dragdrop"></script>

<script type="text/javascript">
window.onload = function() {
  
  /*
  Sortable.create('list',
    {onChange: function(element) 
      {
        //$(resultsort).innerHTML = Sortable.serialize(element.parentNode);
        document.getElementById('result_sort').innerHTML = Sortable.serialize(element.parentNode);
      }
    }
  );
  */
  
  <?php echo $script_part; ?>
  
  Droppables.add('cart', 
    {accept: 'products', 
     hoverclass: 'cart-active',
     onDrop:function(element, droppable) 
     {new Ajax.Updater('items', 'addCart.php',
       {asynchronous: true,
       evalScripts: true,
       onLoading: function(request) {Element.show('indicator')},
       onComplete:function(request, json) 
       {Element.hide('indicator')},
       parameters:'id='+encodeURIComponent(element.id)
       })}});
  
  Droppables.add('wastebin', 
    {accept: 'cart-items', 
     hoverclass: 'wastebin-active',
     onDrop:function(element, droppable) 
     {new Ajax.Updater('items', 'removeCart.php',
       {asynchronous: true,
       evalScripts: true,
       onLoading: function(request) {Element.show('indicator')},
       onComplete:function(request, json) 
       {Element.hide('indicator')},
       parameters:'id='+encodeURIComponent(element.id)
       })}});

  new Ajax.Updater('items', 'showCart.php', 
                  {asynchronous: true, 
                   evalScripts: true});
  
  document.getElementById('add_book_text').focus();
}

</script>

<style type="text/css">
#list {
  list-style-image:none;
  list-style-type:none;
  margin: 0;
  padding: 0;
  width: 580px;
}

#list li {
  border: solid 1px #9c0;
  background-color: transparent;
  margin: 0em 0em 0.4em 0em;
  padding: 0.4em;
  cursor: move;
  clear: both;
  height: 80px;
}

#list li img{
  float:left;
  margin:2px;
}

.cart {
  border: 1px dotted #9c0;
  padding: 1px;
  width: 584px;
  height: 100px;
}
.cart-active {
  padding: 1px;
  border: 1px solid #f00;
  width: 584px;
  height: 100px;
}

.wastebin {
  margin: 2px 0 0 0;
  border: 1px dotted #bbb;
  background-color: #eee;
  width: 586px;
  height: 50px;
}
.wastebin-active {
  margin: 2px 0 0 0;
  border: 1px solid #f00;
  background-color: #eee;
  width: 586px;
  height: 50px;
}

</style>
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

<h1 class="page_title">大切な大切な本は…</h1>

<p><?php print $d_message; ?></p>

<p><?php print html($d_total_hit); ?></p>

<font color="red"><?php echo $g_error; ?></font>
<form action='best_books.php' method='get'>
<p>本の名前や著者名で検索できます。</p>
<p><input id="add_book_text" type="text" name="keyword" size="30" value="<?php print html($d_keyword); ?>"><input type="submit" value="検索"><input type="button" value="リセット" onclick="javascript:clear_add_text()"></p>
</form>

<p class="pager"><? print $d_pager; ?></p>

<?php print html_raw($d_book_list); ?>

<p class="pager"><? print $d_pager; ?></p>

<div id="result_sort"></div>

<p>あなたにとって大切な本を、検索結果から下の枠線内にドラッグすることで最大<?php print MAX_FAVORITE_BOOKS;?>冊まで保存できます。保存した結果は<a href="profile.php?user_id=<?php print $user_id; ?>">プロフィール画面</a>で表示されます。</p>
<div id="cart" class="cart">
  <div id="items"></div>
</div>

<div id="wastebin" class="wastebin">
削除する場合にはここにドロップしてください
</div>

<div style="height:20px">
<p id="indicator" style="display:none">
<img src="/img/ajax-loader.gif">更新しています
</p>
</div>

<span style="font-size:x-small">あなたのお気に入りの本をブログに貼付けることができます。</span><br />
<input style="font-size:x-small" type="text" size="40" onclick="this.select()" value="<iframe frameborder=&quot;0&quot; style=&quot;border: 1px solid #999999; width: 180px; height: 285px;margin: 10px;&quot; src=&quot;https://readnest.jp/favor_book_blogparts.php?user_id=<?= $user_id ?>&quot;></iframe>">

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