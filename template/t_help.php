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
<body>

<div id="container">

<div id="header">
<?php print $d_header; ?>
</div>

<div id="menuarea">
<?php print $d_sub_content; ?>
</div>

<div id="main">

<font color="red"><?php echo $g_error; ?></font>

<h2>使い方</h2>
<p>
こんな感じで使ってます。
<ol>
<li><a href="#add_book">本棚に本を追加する</a></li>
<li><a href="#bookshelf">本棚を見る</a></li>
<li><a href="#mine_diary">自分の読書日記を見る</a></li>
<li><a href="#search_review">皆の読書日記を検索する</a></li>
<li><a href="#all_diary">皆の読書日記を見る</a></li>
<li><a href="#account">アカウント情報を変更する</a></li>
<li><a href="#mobile">携帯電話から利用する</a></li>

</ol>

<h3><a name="add_book">本棚に本を追加する</a></h3>
<ol>
<li>上側のメニューから「本を追加」を選び、探したい本を検索します。その本がどのような状態(いつか買う、買ったけどまだ読んでない、等)なのかを選択するリストが表示されますので、その状態を選択してから「本棚に入れる」ボタンを押します。</li>

<li>たまに「総ページ数」が取得できない場合があります。この場合、お手数ですが本のページ数を入力いただけると、進み具合を後で確認することができます。</li>

<img src="img/help/add_book.jpg" border="1" width="80%"><br /><br />

<li>バーコード読み取りによる検索を行うことができます。なお、本機能を利用するためにはWebカメラ搭載のPCである必要があります。</li>
<li>本を追加する画面で、右上にある「バーコードリーダーを表示」をクリックすると以下のようなバーコードリーダーが表示されますので、アクセスを許可します。Flash Playerの設定（バーコードリーダー右クリックで「設定」）で設定を保存しておくと次回から自動的にカメラが起動します。</li>
<img src="img/help/book_barcode.jpg" border="1"><br /><br />

<li>カメラが起動したら、本のバーコードをカメラに当てるようにして、バーコードがバーコードリーダーの赤い線に重なるようにします。読み取りが成功すると検索結果が表示されます。なお、読み取り可能なのはJANコードです。大抵の場合二つあるバーコードのうち上の方です。</li>

</ol>

<h3><a name="bookshelf">本棚を見る</a></h3>
<ol>
<li>追加された本を確認するには、上側のメニューから「本棚を見る」を選びます。</li>
<li>本棚には追加された本の一覧が表示されます。上のメニューから本の状態ごとに表示を切り替えられます。状態の右側には本の冊数が表示されています。</li>
<img src="img/help/bookshelf3.jpg" border="1" width="80%"><br /><br />

<li>本棚では読み進んだページ数を入力できます。</li>

<li>読みかけの本であれば本のタイトルをクリックして表示される詳細画面で、中央にある入力ボックスに読み進んだページを入力できます。</li><br />
<img src="img/help/bookshelf2_new.jpg" border="1" width="80%"><br /><br />

<li>「ページまで読みました！」ボタンを押すと変更が反映され、棒グラフに何パーセント読んだのかが表示されます。</li>

<!--
<li>読みかけの本であれば本のタイトルをクリックして表示される詳細画面で、中央にあるグラフ上をカーソルでなぞることで、読み進んだページを入力できます。クリックでページ番号を固定、解除できます。「＋」「−」ボタンで微調整できます。</li><br />
<img src="img/help/bookshelf2.jpg" border="1" width="80%"><br /><br />

<li>「○○ページ読みました！」ボタンを押すと変更が反映され、棒グラフに何パーセント読んだのかが表示されます。</li>
-->

<li>この時、ちょっとしたメモを残すこともできます。</li>
<li>読み終った本には感想、評価を入力することができます。</li>
<li>過去に読んだ本とリンクすることができます。ブックリンクから本を選択し、「リンクする」ボタンを押すことで、本同士がリンクされます。削除する際は、リンク右隣の<img src="img/cross.gif">をクリックしてください。くわしくは<a href="https://icotfeels.blog66.fc2.com/blog-entry-1218.html" target="_blank">こちら</a>。</li>
<img src="img/help/book_link.jpg" border="1" width="80%"><br /><br />
<li>本棚から本を削除する場合には「この本を削除」を押して下さい。確認メッセージが出ますので「OK」を押せば本棚から本が削除されます。</li>
</ol>

<h3><a name="mine_diary">自分の読書日記を見る</a></h3>
<ol>
<li>メニューから「読書日記」を選択すると、これまでに自分が読み進めたページ数を含め、読書日記が表示されます。</li>
</ol>



<h3><a name="search_review">皆の読書日記を検索する</a></h3>
<ol>
<li>メニューから「日記を検索」を選択すると、皆さんの読書日記を検索することができます。</li>
</ol>


<h3><a name="all_diary">皆の読書日記を見る</a></h3>
<ol>
<li>メニューから「公開読書日記」を選択すると、皆さんの読書日記を参照することができます。ここから自分の本棚に本を追加することもできます。</li>
</ol>


<h3><a name="account">アカウント情報を変更する</a></h3>
<ol>
<li>ユーザ情報を編集したい場合には、メニューから「アカウント」を選択して下さい。ニックネーム、メールアドレス、年間読破数（くわしくは<a href="https://icotfeels.blog66.fc2.com/blog-entry-1158.html" target="_blank">こちら</a>）、アマゾンのアソシエイトID、日記の公開設定などが行えます。アソシエイトIDはPCサイトで表示される「amazon.co.jpで買う」ボタンに反映されます。</li>
</ol>


<h3><a name="mobile">携帯電話から利用する</a></h3>
ReadNestを携帯電話から利用する事が可能です。以下のQRコードを読み取っていただければ、携帯サイトにアクセスできます。なお、携帯電話でReadNestにアクセスすれば自動的に専用サイトに転送されます。
<center><img width="100px" src="https://readnest.jp/qrcode/dokusho.php?user_id=<?=$address ?>"></center>
利用できる機能は以下の通りです。
<ol>
<li>本棚への本の追加</li>
<li>ページめくり</li>
<li>感想の記入</li>
<li>公開日記の参照</li>
</ol>
<span style="color:red">
注意事項
<ol>
<li>docomo、au、softbank携帯で利用する場合には事前にPCサイトからユーザ登録をしておく必要があります。なお、試験運用のため不具合がある場合があります。</li>
</ol>
</span>
</p>

iPhoneアプリを公開しました。こちらからダウンロードできます。<br/><br/>

<div>
<a href="https://itunes.apple.com/jp/app/id420224317?mt=8&amp;ls=1" target="_blank">iTunes App Store で見つかる iPhone、iPod touch、iPad 対応 ReadNest</a><br/><br/>

<a href="https://itunes.apple.com/jp/app/id420224317?mt=8&amp;ls=1" target="_blank"><img border="0" src="/img/badge.png"></a>
</div>



<h2><a name="privacy_policy">プライバシーポリシー</a></h2>
1. 情報の取得<br/>
「<?=$d_site_title ?>」では、次の方法でユーザーの情報を取得しています。<br/>
<br/>
［登録（Registrations）］<br/>
「ReadNest」では、一部のコンテンツについて、ユーザー名やメールアドレスなどをご登録いただく場合があります。これらの情報は、サービスご利用時に、ご利用者の確認・照会のために使用されます。<br/>
<br/>
［クッキー（Cookies）］<br/>
「ReadNest」では、一部のコンテンツについて、情報の収集にクッキーを使用しています。クッキーは、ユーザーがサイトを訪れた際に、そのユーザーのコンピュータ内に記録されます。ただし、記録される情報には、ユーザー名やメールアドレスなど、個人を特定するものは一切含まれません。<br/>
<br/>
また、「ReadNest」では、ユーザーの方々がどのようなサービスに興味をお持ちなのかを分析したり、ウェブ上での効果的な広告の配信のためにこれらを利用させていただく場合があります。もしこうしたクッキーを利用した情報収集に抵抗をお感じでしたら、ご使用のブラウザでクッキーの受け入れ拒否に設定をすることも可能です。ただし、その際はコンテンツによってはサービスが正しく機能しない場合もありますので、あらかじめご了承ください。<br/>
<br/>
2. 情報の利用<br/>
一部のコンテンツでご登録いただいた情報は、「ReadNest」での、より魅力的で価値のあるサービスの開発・提供のために利用されます。「ReadNest」では、ユーザー本人の許可なく第三者に個人情報を開示いたしません。また、法律の適用を受ける場合や法的強制力のある請求以外には、いかなる個人情報も開示いたしません。<br/>

<br/>
<br/>
1. Information acquisition<br/>
ReadNest acquires user information using the following methods.<br/>
<br/>
［Registration］<br/>
ReadNest requires user name and mail address for part of contents. These information are used for user confirmation/identification.<br/>
<br/>
［Cookies］<br/>
ReadNest uses cookies for information retrieval. Cookies are stored in your PC when you visit this site. The cookies do not contain user name or mail address that can identify users.<br/>
<br/>
ReadNest shall use those information to offer adequate advertisement and to analyse user prefereneces. You can turn cookies off not to be retrieved your information by cookies by switching off your browser configuration. However, this might cause mulfunctioning of this service.<br/>
<br/>
2. Information usage<br/>
Information your registered is only for this service improvement. These information are not disclosed to 3rd party unless disclosure is required by legal application or law enforcement.<br/>
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