<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
  die('reference for this file is not allowed.');
}

// パフォーマンスヘルパーを読み込み
require_once(dirname(__DIR__) . '/library/performance_helpers.php');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Resource Hints -->
<?= generateResourceHints([
    'https://www.googletagmanager.com' => 'preconnect',
    'https://images-na.ssl-images-amazon.com' => 'preconnect',
    'https://books.google.com' => 'dns-prefetch'
]) ?>

<!-- Critical CSS -->
<?= getCriticalCSS('book_detail') ?>

<!-- Primary Meta Tags -->
<title><?php echo html($d_site_title);?></title>
<meta name="description" content="<?= html($g_meta_description) ?>">

<!-- SEO Tags (Open Graph, Twitter Cards, Structured Data) -->
<?php if (isset($g_seo_tags)): ?>
<?= $g_seo_tags ?>
<?php endif; ?>

<!-- Preload Critical Resources -->
<?= generatePreloadTags([
    ['href' => '/css/readnest.css', 'as' => 'style'],
    ['href' => '/js/prototype.js', 'as' => 'script']
]) ?>

<!-- Stylesheets -->
<link href="/css/readnest.css" rel="stylesheet" type="text/css" />

<!-- Deferred Scripts -->
<?= deferScript('/js/prototype.js') ?>
<?= deferScript('/js/scriptaculous/scriptaculous.js?load=effects,slider') ?>
<?= deferScript('/js/overlib/overlib.js') ?>
<?= deferScript('/js/rounded_corners_lite.inc.js') ?>
<?= deferScript('/js/readnest.js') ?>
<?= deferScript('/js/round.js') ?>

<!-- Google Analytics (非同期) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5ZF3NGQ4QT"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-5ZF3NGQ4QT');
</script>

<!-- Page-specific JavaScript -->
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    <?= html_raw($script_part) ?>
    <?= html_raw($related_books_script) ?>
    
    // フォーカス設定を最適化
    const pageInput = document.getElementById('page_input_box');
    if (pageInput) {
        pageInput.focus();
        pageInput.select();
    }
});
</script>

</head>
<body<?= html_raw($on_load_script_part) ?>>

<div id="container">

<header id="header">
<?php print $d_header; ?>
</header>

<nav id="menuarea" role="navigation">
<?php print $d_global_nav; ?>
</nav>

<main id="main_top">

<h1 class="page_title"><?php echo html($name); ?><?= html_raw($refer_part) ?></h1>

<!-- 著者情報 -->
<p class="author-info"><?php echo html_raw($user_profile); ?></p>

<!-- 本の詳細情報 -->
<article class="book-detail">
    <?= html_raw($d_bookshelf) ?>
</article>

</main>

<aside id="sub" role="complementary">

<!-- 関連書籍 -->
<?php if (!empty($related_books)): ?>
<section class="related-books">
    <h2>関連する本</h2>
    <?= html_raw($related_books) ?>
</section>
<?php endif; ?>

<!-- この本を読んでいる人 -->
<?php if (!empty($readers_part)): ?>
<section class="book-readers">
    <h2>この本を読んでいる人</h2>
    <?= html_raw($readers_part) ?>
</section>
<?php endif; ?>

<!-- タグ -->
<?php if (!empty($tag_area)): ?>
<section class="book-tags">
    <h2>タグ</h2>
    <?= html_raw($tag_area) ?>
</section>
<?php endif; ?>

</aside>

<footer id="footer">
<?php print $d_footer; ?>
</footer>

</div><!-- end of container -->

<?php echo isset($g_analytics) ? $g_analytics : ""; ?>

<!-- Lazy Load Images Script -->
<script>
// 画像の遅延読み込みをサポート
if ('loading' in HTMLImageElement.prototype) {
    // ブラウザがネイティブサポートしている場合
    console.log('Native lazy loading supported');
} else {
    // ポリフィルを読み込む
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
    document.body.appendChild(script);
}
</script>

</body>
</html>