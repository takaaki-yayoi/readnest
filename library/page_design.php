<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
}


//$d_header = '<a href="index.php">ReadNest</a>';
$d_header = '<a href="/index.php"><img src="/img/logo.png" border="0" alt="ReadNest"/></a>';
$d_mobile_header = '<div style="text-align:center;"><img src="/img/logo.jpg" border="0" alt="ReadNest"/></div>';

$d_header_app = '<img src="/img/logo.png" border="0" alt="ReadNest"/>';


$g_meta_keyword = '読書,本棚,日記,本,感想,ReadNest';
$g_meta_description = 'ReadNestは、あなたの読書ライフをサポートする読書管理サービスです。簡単に読書日記が残せるサービスです。';


//  <a href="http://b.hatena.ne.jp/append?https://readnest.jp"><img alt="append.gif" src="img/append.gif" alt="このサイトをはてなブックマークする" title="このサイトをはてなブックマークする" width="16" height="12" border="0" /></a> | 

$current_year = date('Y');

$d_footer = <<< DOC_END
<a href="/help.php">ヘルプ</a>&nbsp;

<a href="/terms.php">利用規約</a>&nbsp;

<a href="/terms.php#privacy">プライバシーポリシー</a>&nbsp;


<a href="https://icotfeels.blog66.fc2.com/blog-category-18.html">作っている人の日記</a>&nbsp;

<a href="javascript:window.location='https://b.hatena.ne.jp/add?mode=confirm&title='+escape(document.title)+'&url='+escape(location.href)"><img alt="append.gif" src="/img/append.gif" alt="このサイトをはてなブックマークする" title="このサイトをはてなブックマークする" width="16" height="12" border="0" /></a>&nbsp;

<a href="javascript:location.href='https://del.icio.us/post?v=4;url='+encodeURIComponent('https://readnest.jp')+';title='+encodeURIComponent('ReadNest')" target="_blank"><img src="/img/delicious.gif" width="12" height="12" alt="このサイトを del.icio.us に追加" title="このサイトを del.icio.us に追加" style="border:0;" /></a>&nbsp;

<br />
<span style="font-size:x-small;">Copyright 2007, {$current_year} icot. All rights reserved.</span>
DOC_END;

$d_mobile_footer = '<div style="text-align:center;font-size:x-small;">Copyright 2007, ' . $current_year . ' icot.</div>';

$g_mobile_no_login_window = '<a href="/m/index.php" accesskey="0">ﾎｰﾑ[0]</a><br />';
$g_iphone_no_login_window = '<a href="/i/index.php">ホーム</a><br />';


$d_site_title = 'ReadNest';


// Googleログインボタンを含むログインウィンドウ
$google_login_button = '';
if (file_exists(BASEDIR . '/config/google_oauth.php')) {
    $google_login_button = '
<div style="text-align:center; margin: 10px 0;">
    <a href="/auth/google_login.php" style="display:inline-block; background:#4285f4; color:white; padding:8px 16px; text-decoration:none; border-radius:3px; font-size:14px; font-weight:500; box-shadow:0 2px 4px rgba(0,0,0,0.25);">
        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" style="width:18px; height:18px; margin-right:8px; vertical-align:middle;">
        Googleでログイン
    </a>
</div>
<div style="text-align:center; margin: 5px 0; font-size:x-small; color:#666;">または</div>';
}

$g_login_window = <<< DOC_END
<form action="/index.php" method="post" style="font-size:x-small">
{$google_login_button}
emailアドレス&nbsp;<input type="text" name="username">&nbsp;パスワード&nbsp;<input type="password" name="password"><input id="auto_check" type="checkbox" name="autologin" value="on"><label for="auto_check">次回からログインを省略する</label><input type="submit" value="ログイン">
</form>
<p style="text-align:right;font-size:x-small;margin:1px"><a href="/register.php"><img src="/img/list_marker_red.gif" border="0">ユーザー登録(無料)</a>&nbsp;<a href="/reissue.php"><img src="/img/list_marker_red.gif" border="0">パスワードを忘れた方はこちら</a></p>
<hr />
DOC_END;

$g_analytics = <<< DOC_END
<script type="text/javascript">
document.write(unescape("%3Cscript src='https://ssl.google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-9582172-1");
pageTracker._trackPageview();
} catch(err) {}</script>
DOC_END;

$g_mobile_announce = <<< DOC_END
<p><p class="date">2010年6月7日</p>&nbsp;携帯サイトでも読書家ランキングを参照できる様にしました。</p>
DOC_END;




// help messages
$d_help_pager_1 = '今までに読んだページ数に合わせてページ数の左右にある本の側面をマウスでなぞってみましょう。<br />「○○ページまで読みました！」ボタンの上にページ数が表示されますので、ちょうどいい所でクリックして下さい。ページ数が固定されます。固定を解除するには再度クリックしてください。ページ数が表示されている箇所から遠くなるほどページの増減量が大きくなります。微調整は左右の＋、−ボタンで行えます。<br />そして「○○ページまで読みました！」ボタンをクリックすることでページ数が記録されます。<br />途中で一言残しておきたい時には「ちょっと感想」に一言を入力してから「○○ページまで読みました！」ボタンをクリックして下さい。';

$d_help_pager_2 = '今までに読んだページ数に合わせてグラフの上をマウスでなぞってみましょう。<br />「○○ページまで読みました！」ボタンの上にページ数が表示されますので、ちょうどいい所でクリックして下さい。ページ数が固定されます。固定を解除するには再度クリックしてください。微調整は左右の＋、−ボタンで行えます。<br />そして「○○ページまで読みました！」ボタンをクリックすることでページ数が記録されます。<br />途中で一言残しておきたい時には「ちょっと感想」に一言を入力してから「○○ページまで読みました！」ボタンをクリックして下さい。';

$d_help_pager_3 = '読み進めたページ数(全角も可)を入力して「ページまで読みました！」ボタンをクリックすることでページ数が記録されます。<br />途中で一言残しておきたい時には「ちょっと感想」に一言を入力してから「ページまで読みました！」ボタンをクリックして下さい。';




function status_list() {
  global $g_book_status_array;
  
  $ret_str = '<select name="status_list">';
  
  foreach($g_book_status_array as $key=>$value) {
    $ret_str .= "<option value=\"$key\">$value";
  }
  
  $ret_str .= '</select>';

  return $ret_str;
}


// create page list
function page_list($id, $total_page, $current_page='') {

  $ret_str = '';
  
  $ratio = progress_ratio($total_page, $current_page);
  
  for($i = 1; $i <= 100; $i++) {
  
    if($i <= $ratio)
      $ret_str .= "<a id=\"progress_{$id}_$i\" href=\"#\" style=\"color:red;\" onclick=\"toggle_pager({$id})\" onMouseOver=\"redraw_progress_bar($id, " . ceil($total_page * $i / 100) . ", $total_page, $i)\">|</a>";
    else
      $ret_str .= "<a id=\"progress_{$id}_$i\" href=\"#\" style=\"color:gray;\" onclick=\"toggle_pager({$id})\" onMouseOver=\"redraw_progress_bar($id, " . ceil($total_page * $i / 100) . ", $total_page, $i)\">|</a>";
      
    //if ($i % 100 == 0)
    //  $ret_str .= "<br/ >\n";
  
  }
  
  if($ratio == 100)
    $ratio_color = 'red';
  else {
    $ratio_color = 'gray';

    if($ratio < 10)
      $ratio = '<span style="color:white">00</span>' . $ratio;
    else
      $ratio = '<span style="color:white">0</span>' . $ratio;
  }
  
  
  $ret_str .= "&nbsp;<span id=\"page_{$id}\" style=\"color:{$ratio_color}\" onclick=\"toggle_pager({$id})\" onMouseOver=\"redraw_progress_bar($id, $total_page, $total_page, 100)\">" . $ratio . '%</span>';
  
  return $ret_str;
}


function page_list2($id, $total_page, $current_page='') {

  $ret_str = '';
  $ret_str .= "<center><table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr style=\"border-width:0; vertical-align:bottom;\">";
  
  //$event = 'onmouseover';
  $event = 'onmousemove';
  //$event = 'onclick';
  
  $td_style = "style=\"border-width:0;padding:0 0 0 1px;\"";
  
  $ret_str .= "<td $td_style><span onclick=\"toggle_pager({$id})\" $event=\"plus_page2($id, $total_page, 50)\"><img title=\"50ページ進む\" src=\"/img/left1.jpg\"></span></td>";
  $ret_str .= "<td $td_style><span onclick=\"toggle_pager({$id})\" $event=\"plus_page2($id, $total_page, 10)\"><img title=\"10ページ進む\" src=\"/img/left2.jpg\"></span></td>";
  $ret_str .= "<td $td_style><span onclick=\"toggle_pager({$id})\" $event=\"plus_page2($id, $total_page, 1)\"><img title=\"1ページ進む\" src=\"/img/left3.jpg\"></span></td>";
  
  $ret_str .= "<td style=\"border-width:0 0 1px 1px; border-color:grey; width:70px;\"><span id=\"page_{$id}\">$current_page</span></td>";

  $ret_str .= "<td $td_style><span onclick=\"toggle_pager({$id})\" $event=\"minus_page2($id, 1)\"><img title=\"1ページ戻る\" src=\"/img/right1.jpg\"></td>";
  $ret_str .= "<td $td_style><span onclick=\"toggle_pager({$id})\" $event=\"minus_page2($id, 10)\"><img title=\"10ページ戻る\" src=\"/img/right2.jpg\"></td>";
  $ret_str .= "<td $td_style><span onclick=\"toggle_pager({$id})\" $event=\"minus_page2($id, 50)\"><img title=\"50ページ戻る\" src=\"/img/right3.jpg\"></td>";

  $ret_str .= "</tr></table></center>";

  return $ret_str;
}

function rating_list($current_rating) {
  global $g_star_array;
  
  //d($g_star_array);

  $ret_str = '<select name="rating">';
  
  for($i = 0; $i <= 5; $i++) {
    if($i == $current_rating)
      $selected = 'selected';
    else
      $selected = '';
  
    $ret_str .= "<option value=\"$i\" $selected>" . $g_star_array[$i];
  }

  $ret_str .= '</select>';
  
  return $ret_str;
}


function order_list($current_order, $file) {
  global $g_order_array;
  
  $ret_str = '<ul class="order_list">';
  
  foreach ($g_order_array as $key => $value) { 

    if($value != $current_order)
      $ret_str .= "<li><a href=\"{$file}&order=$value\">" . $key . "</a></li>";
    else
      $ret_str .= "<li><b>" . $key . "</b></li>";
  }

  $ret_str .= '</ul>';
  
  return $ret_str;
}


// calcurate progress ratio
function progress_ratio($total_page, $current_page) {
  if($total_page != 0)
    $ratio = ceil($current_page / $total_page * 100);
  else
    $ratio = 100;

  return $ratio;
}


// create progres bar (static)
/*
function create_bar1($ratio) {
  $bar_str = '';
  
  $bar_str .= '<font color="red" style="font-size:x-small">';
  for($i = 0; $i < $ratio; $i++)
    $bar_str .= '|';
    
  $bar_str .= '</font>';

  $bar_str .= '<font color="gray" style="font-size:x-small">';

  for($i = $ratio; $i < 100; $i++)
    $bar_str .= '|';

  $bar_str .= '</font>';

  return $bar_str;
}
*/

// create progres bar (static)
function create_bar($ratio) {
  $style_gray_bar = 'background-color:#ccc; border-top:#ccc solid 2px; border-left:#ccc solid 2px; border-right:#aaa solid 2px; border-bottom:#aaa solid 2px;';
  $style_red_bar = 'background-color:#f00; border-top:#fbb solid 2px; border-left:#fbb solid 2px; border-right:#c00 solid 2px; border-bottom:#c00 solid 2px;';
  $style_blue_bar = 'background-color:#00f; border-top:#bbf solid 2px; border-left:#bbf solid 2px; border-right:#00c solid 2px; border-bottom:#00c solid 2px;';

  // red or blue bar
  if($ratio == 100) {
    $bar_str = "<div style=\"float:left; width: " . ($ratio * 2) . "px; $style_red_bar\">&nbsp;</div>";
  } else if($ratio != 0) {
    $bar_str = "<div style=\"float:left; width: " . ($ratio * 2) . "px; $style_blue_bar\">&nbsp;</div>";
  // not read yet
  } else {
    $bar_str = "<div style=\"float:left; width: " . (100 * 2) . "px; $style_gray_bar\">&nbsp;</div>";
  }
  
  // gray bar
  if($ratio != 100 && $ratio != 0) {
    $bar_str .= "<div style=\"float:left; width: " . ((100 - $ratio)) * 2 . "px; $style_gray_bar\">&nbsp;</div>";
  }
  
  return $bar_str;
}

function create_bar_mobile($ratio) {
  $num = floor($ratio / 10);
  
  if($ratio == 100) {
    $color = 'red';
  } else {
    $color = 'blue';
  }

  $bar_str = "<span style=\"color:$color;font-size:x-small;\">";

  for($i = 0; $i < $num; $i++) {
    $bar_str .= '■';
  }

  $bar_str .= '&nbsp;</span>';

  return $bar_str;
}

// create pager
function createPager($current_page, $max_page, $status, $file_name, $keyword='', $mode = '') {
  $ret_str = '';
  
  if($mode == 'mobile') {
    $max_display_page_num = 10;

    $left_access_key = 'accesskey="4"';
    $right_access_key = 'accesskey="6"';

    //$left_access_key_img = '&#xE6E5;';
    //$right_access_key_img = '&#xE6E7;';
    $left_access_key_img = '[4]';
    $right_access_key_img = '[6]';
    
    $separator_pager = '&nbsp;';
  
  }else if($mode == 'iphone') {
    $max_display_page_num = 10;

    $left_access_key = '';
    $right_access_key = '';

    $left_access_key_img = '';
    $right_access_key_img = '';
    
    $separator_pager = '&nbsp;';
    
  } else {
    $max_display_page_num = 10;

    $left_access_key = '';
    $right_access_key = '';

    $left_access_key_img = '';
    $right_access_key_img = '';

    $separator_pager = '';
  }
  
  $right_arrow = '<img src="/img/arrow_fat_right.gif" border="0" />';
  $left_arrow = '<img src="/img/arrow_fat_left.gif" border="0" />';
  
  if (strpos($file_name, '?') != false) {
    $separator = '&';
  } else {
    $separator = '?';
  }
  
  if($max_page == 0)
    return '';

  if($max_page == 1)
    return '';
    
  if($current_page == 1) {
    $ret_str = "<span class=\"pager_item\">$left_arrow</span>";
  } else {

    if($status != '') {
      $ret_str = "<a $left_access_key href=\"$file_name{$separator}page=" . ($current_page - 1) . "&status=$status\"><span class=\"pager_item\">$left_arrow</span></a>$left_access_key_img";
    } else if($keyword != '') {
      $ret_str = "<a $left_access_key href=\"$file_name{$separator}page=" . ($current_page - 1) . "&keyword=$keyword\"><span class=\"pager_item\">$left_arrow</span></a>$left_access_key_img";
    } else {
      $ret_str = "<a $left_access_key href=\"$file_name{$separator}page=" . ($current_page - 1) . "\"><span class=\"pager_item\">$left_arrow</span></a>$left_access_key_img";
    }
  }

  if($max_page <= $max_display_page_num) {
    for($i = 1; $i <= $max_page; $i++) {
      if($i == $current_page)
        $ret_str .= "<span class=\"pager_item\"><b>$i</b></span>";
      else {
        if($status != '') {
          $ret_str .= "<a href=\"$file_name{$separator}page=$i&status=$status\"><span class=\"pager_item\">$i</span></a>";
        } else if($keyword != '') {
          $ret_str .= "<a href=\"$file_name{$separator}page=$i&keyword=$keyword\"><span class=\"pager_item\">$i</span></a>";
        } else {
          $ret_str .= "<a href=\"$file_name{$separator}page=$i\"><span class=\"pager_item\">$i</span></a>";
        }
      }
      
      $ret_str .= $separator_pager;
    }
  } else {
    if($current_page < $max_display_page_num) {
      $start_point = 1;
      $end_point = $max_display_page_num;
    } else if($current_page + ceil($max_display_page_num / 2) < $max_page) {
      $start_point = $current_page - ceil($max_display_page_num / 2);
      $end_point = $current_page + ceil($max_display_page_num / 2);
    } else {
      $start_point = $max_page - $max_display_page_num;
      $end_point = $max_page;
    
    }
  
    for($i = $start_point; $i <= $end_point; $i++) {
      if($i == $current_page)
        $ret_str .= "<span class=\"pager_item\"><b>$i</b></span>";
      else {
        if($status != '') {
          $ret_str .= "<a href=\"$file_name{$separator}page=$i&status=$status\"><span class=\"pager_item\">$i</span></a>";
        } else if($keyword != '') {
          $ret_str .= "<a href=\"$file_name{$separator}page=$i&keyword=$keyword\"><span class=\"pager_item\">$i</span></a>";
        } else {
          $ret_str .= "<a href=\"$file_name{$separator}page=$i\"><span class=\"pager_item\">$i</span></a>";
        }
      }

      $ret_str .= $separator_pager;
    }

  }

  if($current_page == $max_page) {
    $ret_str .= "<span class=\"pager_item\">$right_arrow</span>";
  } else {
    if($status != '') {
      $ret_str .= "$right_access_key_img<a $right_access_key href=\"$file_name{$separator}page=" . ($current_page + 1) . "&status=$status\"><span class=\"pager_item\">$right_arrow</span></a>";
    } else if($keyword != '') {
      $ret_str .= "$right_access_key_img<a $right_access_key href=\"$file_name{$separator}page=" . ($current_page + 1) . "&keyword=$keyword\"><span class=\"pager_item\">$right_arrow</span></a>";
    } else {
      $ret_str .= "$right_access_key_img<a $right_access_key href=\"$file_name{$separator}page=" . ($current_page + 1) . "\"><span class=\"pager_item\">$right_arrow</span></a>";
    }

  }

  return $ret_str;
}


// create display switch on bookshelf
function createDisplaySwith($current_status, $user_id, $mode = '') {
  $flag_array = array(BUY_SOMEDAY, NOT_STARTED, READING_NOW, READING_FINISH, READ_BEFORE);
  //$icon_array = array('&#xE69C;', '&#xE69F;', '&#xE69E;', '&#xE69D;', '&#xE6A0;');
  $icon_array = array('', '', '', '', '');
  $title_array = array('いつか買う', '読んでない', '読んでるとこ', '読み終わった', '昔読んだ');
  
  $flag_num = count($flag_array);

  $result_array = getBookshelfNum($user_id);
  //d($result_array);

  if($mode == 'mobile') {
    $ret_str = '';

    for($i = 0; $i < $flag_num; $i++) {
      if($current_status != $flag_array[$i]) {
        $ret_str .= $icon_array[$i] . "<a href=\"bookshelf.php?user_id=$user_id&status=" . $flag_array[$i] . "\">" . $title_array[$i] . "(" . $result_array[$flag_array[$i]] . ")</a><br />\n";
      } else {
        $ret_str .= $icon_array[$i] . "<span style=\"color:#000; font-weight: bold;\">" . $title_array[$i] . "(" . $result_array[$flag_array[$i]] . ")</span><br />\n";
      }
    }

  } else if($mode == 'iphone') {
    $ret_str = '';

    for($i = 0; $i < $flag_num; $i++) {
      if($current_status != $flag_array[$i]) {
        $ret_str .= "<a href=\"bookshelf.php?user_id=$user_id&status=" . $flag_array[$i] . "\">" . $title_array[$i] . "(" . $result_array[$flag_array[$i]] . ")</a><br />\n";
      } else {
        $ret_str .= "<span style=\"color:#000; font-weight: bold;\">" . $title_array[$i] . "(" . $result_array[$flag_array[$i]] . ")</span><br />\n";
      }
    }
    
  } else {
    $ret_str = '<div id="status_switch"><ul>';
  
    for($i = 0; $i < $flag_num; $i++) {
      if($current_status != $flag_array[$i]) {
        $ret_str .= "<li><a href=\"bookshelf.php?user_id=$user_id&status=" . $flag_array[$i] . "\">" . $title_array[$i] . "(" . $result_array[$flag_array[$i]] . ")</a></li>\n";
      } else {
        $ret_str .= "<li><a href=\"bookshelf.php?user_id=$user_id&status=" . $flag_array[$i] . "\" style=\"color:#000; font-weight: bold;\">" . $title_array[$i] . "(" . $result_array[$flag_array[$i]] . ")</a></li>\n";
      }
    }
  
    $ret_str .= '</ul></div>';
  
  }
  
  return $ret_str;
}


// using overlib
function popup($text, $popup) {
  return "<a href=\"javascript:void%200\" onmouseover=\"return overlib('" . $popup . "');\" onmouseout=\"return nd();\">" . $text . "</a>";
}

// create global navigation
function createGlobalNav($user_id, $mode = '') {
  $unread_num = getUnreadBooknum($user_id);
  
  $user_inf = getUserInformation($user_id);
  $address = $user_inf['email'];
  
  if($unread_num != 0) {
    $b_start = '<strong>';
    $b_count = "<font color=\"black\">({$unread_num})</font>";
    $b_end = '</strong>';
  } else {
    $b_start = '';
    $b_count = '';
    $b_end = '';
  }

  if($mode == 'mobile') {
    $global_nav =     '<div style="text-align:center;font-size:x-small;background-color:' . SECTION_COLOR1 . '">ﾒﾆｭｰ</div>' . 

                      '<a href="/m/set_easy_login.php">簡単ﾛｸﾞｲﾝの設定</a><br />' . 
                      '<a href="/m/index.php" accesskey="0">ﾎｰﾑ[0]</a><br />' . 
                      "<a href=\"/m/bookshelf.php?user_id=$user_id&status=2\" accesskey=\"1\">{$b_start}本棚を見る$b_count{$b_end}[1]</a><br />" . 
                      //'<li><a href="/m/search_review.php">読書日記を検索</a></li>' . 
                      '<a href="/m/add_book.php" accesskey="3">本を追加[3]</a><br />' . 
                      '<a href="/m/disclosed_diary.php" accesskey="9">公開読書日記[9]</a><br />' . 
                      '<a href="/m/ranking.php" accesskey="7">読書家ﾗﾝｷﾝｸﾞ[7]</a><br />' . 
                      //"<li><a href=\"/m/diary.php?user_id=$user_id\">読書日記</a></li>" . 
                      //'<li><a href="/m/account.php">アカウント管理</a></li>' . 
                      '<a href="/m/logout.php">ﾛｸﾞｱｳﾄ</a><br />';

  } else if($mode == 'iphone') {
    $global_nav =     '<div style="text-align:center;font-size:x-small;background-color:' . SECTION_COLOR1 . '">メニュー</div>' . 

                      '<a href="/i/index.php">ホーム</a><br /><br />' . 
                      "<a href=\"/i/bookshelf.php?user_id=$user_id&status=2\">{$b_start}本棚を見る$b_count{$b_end}</a><br /><br />" . 
                      //'<li><a href="/m/search_review.php">読書日記を検索</a></li>' . 
                      '<a href="/i/add_book.php">本を追加</a><br /><br />' . 
                      '<a href="/i/disclosed_diary.php">公開読書日記</a><br /><br />' . 
                      '<a href="/i/ranking.php">読書家ランキング</a><br /><br />' . 
                      //"<li><a href=\"/m/diary.php?user_id=$user_id\">読書日記</a></li>" . 
                      //'<li><a href="/m/account.php">アカウント管理</a></li>' . 
                      '<div style="text-align:right;"><a href="/i/index.php?action=logout">ログアウト</a></div>';

  } else {
    $filename = $_SERVER['SCRIPT_NAME'];
    
    if($filename == '/index.php' || $filename == '')
      $index_part = '<li class="selected"><span>ホーム</span></li>';
    else
      $index_part = '<li><a href="/index.php">ホーム</a></li>';

    if($filename == '/bookshelf.php')
      $bookshelf_part = "<li class=\"selected\"><span>本棚を見る$b_count</span></li>";
    else
      $bookshelf_part = "<li><a href=\"/bookshelf.php?user_id=$user_id&status=2\">本棚を見る$b_count</a></li>";

    if($filename == '/add_book.php')
      $add_book_part = '<li class="selected"><span>本を追加</span></li>';
    else
      $add_book_part = '<li><a href="/add_book.php">本を追加</a></li>';

    if($filename == '/diary.php' || $filename == '/review_cal.php')
      $diary_part = '<li class="selected"><span>読書日記</span></li>';
    else
      $diary_part = "<li><a href=\"/diary.php?user_id=$user_id\">読書日記</a></li>";

    if($filename == '/search_review.php')
      $search_part = '<li class="selected"><span>日記検索</span></li>';
    else
      $search_part = '<li><a href="/search_review.php">日記検索</a></li>';

    if($filename == '/disclosed_diary.php')
      $disclosed_diary_part = '<li class="selected"><span>みんなの日記</span></li>';
    else
      $disclosed_diary_part = '<li><a href="/disclosed_diary.php">みんなの日記</a></li>';

    if($filename == '/account.php')
      $account_part = '<li class="selected"><span>アカウント</span></li>';
    else
      $account_part = '<li><a href="/account.php">アカウント</a></li>';

    if($filename == '/help.php')
      $help_part = '<li class="selected"><span>ヘルプ</span></li>';
    else
      $help_part = '<li><a href="/help.php">ヘルプ</a></li>';
  
    $global_nav = '<ul class="menu_nav">' . 
                      $index_part . 
                      $bookshelf_part . 
                      $add_book_part . 
                      $diary_part . 
                      $search_part . 
                      $disclosed_diary_part . 
                      $account_part . 
                      $help_part . 
                      '<li><a href="/index.php?action=logout">ログアウト</a></li></ul>';
                      
  }

  return $global_nav;
}


// convert url to link
function replaceURL($text) {
  //$text = ereg_replace("(http)(://[[:alnum:]\S\$\+\?\.-=_%,:@!#~*/&]+)","<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>", $text);
  
  $text = preg_replace_callback('/(http?)(:\/\/[-_.!~*\'a-zA-Z0-9;\/?:\@&=+\$,%#]+)/', "cutURL", $text);
  //$text = mb_ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/[:alnum:]]","<a href=\"\\0\" target=\"_blank\">\\0</a>", $text);
  
  return $text;

}


function cutURL($matches) {
  $target = $matches[0];
  
  $max_length = 50;
  
  if(strlen($target) > $max_length)
    $ret = "<a href=\"$target\" target=\"_blank\" title=\"[$target]にジャンプ\">" . substr($target, 0, $max_length) . '...</a>';
  else
    $ret = "<a href=\"$target\" target=\"_blank\" title=\"[$target]にジャンプ\">$target</a>";
  
  return $ret;
}

// ajax込みの入力インタフェース生成
function createEvaluateForm($id, $book_id, $current_rating, $book_memo) {
  global $g_star_array;

  $memo_box_name = "memo_box_$id";
  $display_box_name = "memo_display_$id";
  $static_rating_box_name = "static_eval_$id";

  if($current_rating != '') {
    $star = $g_star_array[$current_rating];
  } else {
    $star = '未評価';
  }

  if($book_memo == '') {
    $display_book_memo = '編集するにはここをクリックしてください。';
  } else {
    $display_book_memo = '';
  }
  
  $evaluate_button = "<div id=\"$static_rating_box_name\">" . 
                     "<p class=\"bookdetail-label\">評価</p>" . $star . "<br />" . 
                     "<p class=\"bookdetail-label\">感想</p>" . 
                     "</div>\n" .

                     "<p id=\"$display_box_name\" align=\"left\" onmouseover=\"changeColor(this)\" onmouseout=\"revertColor(this)\" onclick=\"change2Textbox(this, '$memo_box_name', '$static_rating_box_name')\">" . $display_book_memo . nl2br($book_memo) . "</p>" . 

                     "<form id=\"$memo_box_name\" style=\"display:none\">\n" . 

                     "<p class=\"bookdetail-label\">評価</p>" . 
                     rating_list($current_rating) . 
                     "<br />\n" . 

                     "<p class=\"bookdetail-label\">感想</p>" . 
                     "<textarea name=\"memo\" rows=\"10\" cols=\"60\" >$book_memo</textarea><br>" . 

                     "<input type=\"hidden\" name=\"book_id\" value=\"$book_id\">\n" . 
                     "<input type=\"hidden\" name=\"action\" value=\"evaluate\">\n" . 

                     "<input type=\"button\" value=\"保存\" onClick=\"modMemo('$memo_box_name', '$display_box_name', '$static_rating_box_name')\">\n" . 
                     "&nbsp;<input type=\"button\" value=\"キャンセル\" onClick=\"modMemoCancel('$memo_box_name', '$display_box_name', '$static_rating_box_name')\">\n" . 
                     "</form>\n";

  return $evaluate_button;
}


function create_disclosed_diary_part($max_num, $mode = '') {
  global $g_book_status_array;

  if ($max_num == '') $max_num = 20;

  $result = getDisclosedDiary();
  $d_disclosed = '';
  
  if($result != NULL) {
    $d_disclosed = '';
    
    for($i = 0; $i < $max_num; $i++) {
      if($i == count($result)) break;

      $book_id = $result[$i]['book_id'];
      $event_date = $result[$i]['event_date'];
      $status_id = $result[$i]['event'];
      $memo = $result[$i]['memo'];
      $page = $result[$i]['page'];
      $user_id = $result[$i]['user_id'];

      $nickname = getNickname($user_id);

      $user_info_array = getUserInformation($user_id);

      $status = $g_book_status_array[$status_id];
      $event_date = date('Y/m/d H:i:s', $event_date);

      // get book information
      $book_array = getBookInformation($book_id);
  
      $amazon_id =  $book_array['amazon_id'];
      $book_memo =  $book_array['memo'];
      $book_rating =  $book_array['rating'];
      $image_url = $book_array['image_url'];
      $name = htmlspecialchars($book_array['name']);

      if($mode == 'mobile')
        $detail_url = "/m/book/$book_id";
      else if($mode == 'iphone')
        $detail_url = "/i/book/$book_id";
      else
        $detail_url = "/book/$book_id";
      
      $link_title = "{$name} by {$nickname}さん";

      if($mode == 'mobile' || $mode == 'iphone') {
        $image_part = '<img src="/img/list_marker_red.gif" border="0">' . "<a href=\"" . $detail_url. "\">{$name}</a><span style=\"font-size:x-small;\">&nbsp;{$nickname}さん</span><br />";
      } else {
        if($image_url != '') {
          $image_part = "<a href=\"" . $detail_url. "\" title=\"$link_title\"><img class=\"list_img\" src=\"$image_url\" border=0 onerror=\"this.src='/img/noimage.jpg'\"></a>";
        } else {
          $image_part = "<a href=\"" . $detail_url. "\" title=\"$link_title\"><img class=\"list_img\" src=\"/img/noimage.jpg\" border=0></a>";
        }
      }
      
      $d_disclosed .= $image_part;
    }
    
  } else {
    $d_disclosed = '';
  }
  
  return $d_disclosed;
}


function create_new_review_part($max_num, $mode = '') {
  global $g_book_status_array;

  if ($max_num == '') $max_num = 20;

  $result = getNewReview('', $max_num);
  $d_disclosed = '';
  
  if($result != NULL) {
    $d_disclosed = '';
    
    for($i = 0; $i < $max_num; $i++) {
      if($i == count($result)) break;
  
      $book_id = $result[$i]['book_id'];
      $amazon_id =  $result[$i]['amazon_id'];
      $book_memo =  $result[$i]['memo'];
      $book_rating =  $result[$i]['rating'];
      $image_url = $result[$i]['image_url'];
      $name = htmlspecialchars($result[$i]['name']);
      $user_id = $result[$i]['user_id'];

      $nickname = getNickname($user_id);

      if($mode == 'mobile')
        $detail_url = "/m/book/$book_id";
      else if($mode == 'iphone')
        $detail_url = "/i/book/$book_id";
      else
        $detail_url = "/book/$book_id";
      
      $link_title = "{$name} by {$nickname}さん";

      if($mode == 'mobile' || $mode == 'iphone') {
        $image_part = '<img class=\"list_img\"src="/img/list_marker_red.gif" border="0">' . "<a href=\"" . $detail_url. "\">{$name}</a><span style=\"font-size:x-small;\">&nbsp;{$nickname}さん</span><br />";
      } else {
        if($image_url != '') {
          $image_part = "<a href=\"" . $detail_url. "\" title=\"$link_title\"><img class=\"list_img\" src=\"$image_url\" border=0 onerror=\"this.src='/img/noimage.jpg'\"></a>";
        } else {
          $image_part = "<a href=\"" . $detail_url. "\" title=\"$link_title\"><img class=\"list_img\" src=\"/img/noimage.jpg\" border=0></a>";
        }
      }
      
      $d_disclosed .= $image_part;
    }
    
  } else {
    $d_disclosed = '';
  }
  
  return $d_disclosed;
}


// ページリンクリスト生成
function create_link_book_list($linked_from, $owner_flag, $mode = '') {
  $result_str = '';

  $linked_books = getBookLinkFrom($linked_from);
  
  for($i = 0; $i < count($linked_books); $i ++) {
    $to_id = $linked_books[$i]['to_book'];
    $relation_id = $linked_books[$i]['relation_id'];
    
    $book_inf = getBookInformation($to_id);
    
    if($book_inf != NULL) {
      $linking_book_name = $book_inf['name'];
      
      $form_id = "related_book_{$relation_id}";
      
      if($mode == 'mobile')
        $result_str .= "<a href=\"/m/book/$to_id\">$linking_book_name</a>";
      else if($mode == 'iphone')
        $result_str .= "<a href=\"/i/book/$to_id\">$linking_book_name</a>";
      else
        $result_str .= "<a href=\"/book/$to_id\">$linking_book_name</a>";
      
     if($owner_flag) { 

        if($mode != 'mobile' && $mode != 'iphone') {
          $result_str .= "&nbsp;<a href=\"javascript:removeBookLink('$form_id')\"><img src=\"/img/cross.gif\" border=\"0\"></a><br />\n";
        } else {
          $result_str .= "<br />\n";
        }
        
        $result_str .= "<form id=\"$form_id\"><input type=\"hidden\" name=\"relation_id\" value=\"$relation_id\">\n";
        $result_str .= "<input type=\"hidden\" name=\"link_from\" value=\"$linked_from\"></form>\n";
      } else {
        $result_str .= "<br />";
      }
    }
  }

  $linked_books = getBookLinkTo($linked_from);
  
  for($i = 0; $i < count($linked_books); $i ++) {
    $from_id = $linked_books[$i]['from_book'];
    $relation_id = $linked_books[$i]['relation_id'];
    
    $book_inf = getBookInformation($from_id);
    
    if($book_inf != NULL) {
      $linking_book_name = $book_inf['name'];
      
      $form_id = "related_book_{$relation_id}";
      
      if($mode == 'mobile')
        $result_str .= "<a href=\"/m/book/$from_id\">$linking_book_name</a>";
      else if($mode == 'iphone')
        $result_str .= "<a href=\"/i/book/$from_id\">$linking_book_name</a>";
      else
        $result_str .= "<a href=\"/book/$from_id\">$linking_book_name</a>";
      
      if($owner_flag) { 
        if($mode != 'mobile' && $mode != 'iphone') {
          $result_str .= "&nbsp;<a href=\"javascript:removeBookLink('$form_id')\"><img src=\"/img/cross.gif\" border=\"0\"></a><br />\n";
        } else {
          $result_str .= "<br />\n";
        }
        
        $result_str .= "<form id=\"$form_id\"><input type=\"hidden\" name=\"relation_id\" value=\"$relation_id\">\n";
        $result_str .= "<input type=\"hidden\" name=\"link_from\" value=\"$linked_from\"></form>\n";
      } else {
        $result_str .= "<br />";
      }
    }
  }
  
  return $result_str;
}


// decorate book shelf
function createBookshelf($user_id, $status, $page, $source_file) {
  // get bookshelf
  $result = getBookshelf($user_id, $status);

  $max_page = ceil(count($result) / BOOKS_PER_PAGE);
  
  // pager control
  if(!empty($page))
    $current_page = $page;
  else
    $current_page = 1;
  
  if(is_numeric($current_page) && $current_page > 0) {
    if($current_page <= $max_page)
      $page = $current_page;
    else 
      $page = 1;
  } else {
    $page = 1;
  }

  // create switch
  $d_switch = createDisplaySwith($status, $user_id);
  
  // create pager
  $d_pager = createPager($page, $max_page, $status, $source_file);

  $d_bookshelf = '<table border="1">';

  if($status == READING_NOW)
    $d_bookshelf .= '<tr><th>image</th><th>タイトル</th><th>残りページ数</th></tr>';
  else
    $d_bookshelf .= '<tr><th>image</th><th>タイトル</th><th>総ページ数</th></tr>';


  //d(BOOKS_PER_PAGE * $page);
  $start_point = ($page - 1) * BOOKS_PER_PAGE;
  $end_point = BOOKS_PER_PAGE * $page;
  
  for($i = $start_point; $i < $end_point; $i++) {
  
    if($i == count($result)) break;
  
    $book_id = $result[$i]['book_id'];
    $name = $result[$i]['name'];
    $book_memo = $result[$i]['memo'];
    $total_page = $result[$i]['total_page'];
    $current_page = $result[$i]['current_page'];
    $amazon_id = $result[$i]['amazon_id'];
    $create_date = $result[$i]['create_date'];
    $update_date = $result[$i]['update_date'];
    $status_id = $result[$i]['status'];
    $detail_url = $result[$i]['detail_url'];
    $image_url = $result[$i]['image_url'];
    $current_rating = $result[$i]['rating'];

    $detail_url = "/book/$book_id";
    
    if($status_id == READING_FINISH || $status_id == READ_BEFORE) {
      $ratio = 100;
    } else {
      $ratio = progress_ratio($total_page, $current_page);
    }
    
    $bar_str = create_bar($ratio);
    $create_date = date('Y/m/d H:i:s', $create_date);

    // default page number
    if($current_page == 0) $current_page = 1;
    
    if($image_url != '') {
      $image_part = "<a href=\"" . $detail_url. "\"><img src=\"$image_url\" border=0></a>";
    } else {
      $image_part = "<a href=\"" . $detail_url. "\"><img src=\"/img/noimage.jpg\" border=0></a>";
    }
    
    $book_detail_link = "<a href=\"" . $detail_url. "\">$name</a>";
    
    if($status_id == READING_NOW)
      $d_bookshelf .= "<tr><td>$image_part</td><td>$book_detail_link&nbsp;<p class=\"date\">on $create_date</p><br>$bar_str&nbsp;{$ratio}%</td><td>" . ($total_page - $current_page) . "</td></tr>";
    else
      $d_bookshelf .= "<tr><td>$image_part</td><td>$book_detail_link&nbsp;<p class=\"date\">on $create_date</p><br>$bar_str&nbsp;{$ratio}%</td><td>$total_page</td></tr>";

  }
  
  $d_bookshelf .= '</table>';
  
  $d_bookshelf = $d_switch . $d_bookshelf . $d_pager;
  
  return $d_bookshelf;
}


// decorate favorite books
function getFavoriteBookList($books, $mode, $user_id) {
  $favorite_str = '';
  
  global $g_book_total_num;
  global $g_book_array;
  
  if($books == '') {
    $favorite_str = '登録されていません';
  } else {
    
    $j = 0;
    
    foreach($books as $item) {
    
      $book_info = getBookFromRepository($item);
    
      if(getBookFromRepository($item) == NULL) {
    
        $g_book_array = array();
      
        $req = create_amazon_url('ItemLookup', $item, 1);
            
        analyseAmazonXML($req);
  
        $image = $g_book_array[0]['SmallImage'];
        $name = $g_book_array[0]['Title'];
        $author = $g_book_array[0]['Author'];
        
        addBookToRepository($item, $name, $image, $author);
      } else {

        $image = $book_info['image_url'];
        $name = $book_info['title'];
        $author = $book_info['author'];
      }
      
      if($author != '') $name .= " by $author";

      if($mode != 'script') {
        $read_book_info = asin2id($item, $user_id);
        
        if($read_book_info != NULL) {
          $id = $read_book_info['book_id'];
        
          $link = "/book/$id";
          $style = 'border:1px solid #9c0; padding:1px;';
          $name .= '[レビューページにジャンプします]';
        } else {
          $link = "https://www.amazon.co.jp/dp/$item";
          $style = '';
          $name .= '[Amazonにジャンプします]';
        }
        
      }

      if($mode != 'script') {
        $book_image = "<img id=\"book_{$item}_$j\" class=\"cart-items\" src=\"$image\" title=\"$name\" style=\"$style\" border=\"0\" onerror=\"this.src='/img/noimage.jpg'\"/>";
        $favorite_str .= "<a href=\"$link\">" . $book_image . '</a>';
      } else {
        $book_image = "<img id=\"book_{$item}_$j\" class=\"cart-items\" src=\"$image\" title=\"$name\" border=\"0\" onerror=\"this.src='/img/noimage.jpg'\"/>";
        $favorite_str .= $book_image;
      
        $favorite_str .= "<script type=\"text/javascript\">";
        $favorite_str .= "new Draggable(\"book_{$item}_$j\", {revert:1});";
        $favorite_str .= "</script>";
      }
      
      $j++;
    }
  }
  
  return $favorite_str;
}

// aggregate your favorite author
function aggregateFavoriteAuthor($user_id, $num = '') {
  global $g_book_array;
  
  $ret_str = '';
  $author_array = array();

  $bookshelf_array = getBookshelf($user_id, '');
  
  $count_bookshelf = count($bookshelf_array);
  
  if($num != '' && $num < $count_bookshelf) {
    $max_count = $num;
  } else {
    $max_count = $count_bookshelf;
  }
  
  for($i = 0; $i < $count_bookshelf; $i++) {
    $amazon_id = $bookshelf_array[$i]['amazon_id'];
    
    //print "$amazon_id<br />\n";

    // repository operation
    $book_info = getBookFromRepository($amazon_id);
    
    if($book_info == NULL) {
      $g_book_array = array();
      
      $req = create_amazon_url('ItemLookup', $amazon_id, 1);
            
      analyseAmazonXML($req);
  
      $image = $g_book_array[0]['SmallImage'];
      $name = $g_book_array[0]['Title'];
      $author = $g_book_array[0]['Author'];
        
      addBookToRepository($amazon_id, $name, $image, $author);

      $book_info = getBookFromRepository($amazon_id);
    }

    $image = $book_info['image_url'];
    $name = $book_info['title'];
    $author = $book_info['author'];
    
    if($author != '') {
      if(array_key_exists($author, $author_array)) {
        $author_array[$author]++;
      } else {
        $author_array[$author] = 1;
      }
    }
  }
  
  //d($author_array);
  
  $item_count = 0;
  foreach($author_array as $author => $count) {
    if($item_count == $max_count) break;
  
    $size = log($count) * TAG_MAX + TAG_OFFSET;
    
    $link = "bookshelf.php?user_id=$user_id&author=$author";
    
    $ret_str .= "<a href=\"$link\" title=\"{$count}books\" style=\"font-size:{$size}px\">$author</a>&nbsp;";
    
    $item_count++;
  }
  
  return $ret_str;
}



function createAllSakkaCloud($limit = '') {
  $ret_str = '';
  //$result = getAllBookshelf($limit);
  $result = getStoredSakkaCloud($limit);
  $author_count = count($result);
  
  for($j = 0; $j < $author_count; $j++) {
  
    $count = $result[$j]['author_count'];
    $author = $result[$j]['author'];

    $size = log($count) * TAG_MAX + TAG_OFFSET;
    
    $link = "/author/$author";
    
    if($count == 1)
      $suffix = 'book';
    else
      $suffix = 'books';
      
    $ret_str .= "<a href=\"$link\" title=\"{$count} $suffix\" style=\"font-size:{$size}px\" rel=\"tag\">$author</a>&nbsp;";
  }

  return $ret_str;
}


// create tag list
function createTagList($current_tag, $mode = '') {

  $tag_count = count($current_tag);

  $result_str = "";
  for($i = 0; $i < $tag_count; $i++) {
    $tag_item = $current_tag[$i]["tag"];
    $user_id = $current_tag[$i]["user_id"];
    
    if($mode == 'list') {
      $result_str .= '<img src="/img/list_marker_red.gif" class="noborder"><a href="/bookshelf.php?user_id=' . $user_id . '&tag=' . urlencode($tag_item) . '">' . htmlspecialchars($tag_item) . '</a>&nbsp;' . "\n";
    } else if($mode == 'favor') {
      $result_str .= '<a href="javascript:setTag(\'' . htmlspecialchars($tag_item, ENT_QUOTES) . '\')">' . htmlspecialchars($tag_item) . '</a>&nbsp;' . "\n";
    } else {
      $result_str .= '<img src="/img/list_marker_red.gif"><a href="/bookshelf.php?user_id=' . $user_id . '&tag=' . urlencode($tag_item) . '">' . htmlspecialchars($tag_item) . '</a><br />' . "\n";
    }
  }
  //if($mode == '') $result_str .= "<br />\n";

  return $result_str;
}

// create user's favored tags
function createUsersTag($user_id, $limit = '') {
  $result = aggregateTag($user_id, $limit);

  $tag_count = count($result);
  if($tag_count == 0) {
    $result_str = "タグは登録されていません。";
  } else {
    $result_str = "";
    for($i = 0; $i < $tag_count; $i++) {
      $tag_item = $result[$i]["tag"];
      $count = $result[$i]["tag_count"];

      $size = log($count) * TAG_MAX + TAG_OFFSET;

      $result_str .= "<a href=\"/bookshelf.php?user_id=$user_id&tag=$tag_item\" style=\"font-size:{$size}px\" title=\"{$count}books\">$tag_item</a>&nbsp;";
    }
  }
  
  return $result_str;
}


// create tag list
function createAllTagList($current_tag) {

  $tag_count = count($current_tag);

  $result_str = "";
  for($i = 0; $i < $tag_count; $i++) {
    $tag_item = $current_tag[$i]["tag"];
    $count = $current_tag[$i]["tag_count"];

    $size = log($count) * TAG_MAX + TAG_OFFSET;
    
    // ログイン状態に応じてリンク先を変更
    if (checkLogin()) {
      // ログインしている場合は本棚のタグ検索ページに遷移
      $link_url = "/bookshelf.php?search_type=tag&search_word=" . urlencode($tag_item);
    } else {
      // ログインしていない場合は一般的なタグ検索ページに遷移
      $link_url = "/tag/" . urlencode($tag_item);
    }
    
    $result_str .= "<a href=\"$link_url\" style=\"font-size:{$size}px\" rel=\"tag\">$tag_item</a>&nbsp;\n";

  }
  //if($mode == '') $result_str .= "<br />\n";

  return $result_str;
}


function convertSecond($second) {
  if($second < 60) {
    $ret = "<u>未読{$second}秒</u>";
  } else if($second < 60 * 60) {
    $ret = '<u>未読' . floor($second / 60) . '分</u>';
  } else if($second < 60 * 60 * 24) {
    $ret = '<u>未読' . floor($second / 60 / 60) . '時間</u>';
  } else if($second < 60 * 60 * 24 * 30) {
    $ret = '<u>未読' . floor($second / 60 / 60 / 24) . '日</u>';
  } else if($second < 60 * 60 * 24 * 30 * 12) {
    $ret = '<span style="color:blue;"><u>未読' . floor($second / 60 / 60 / 24 / 30) . 'ヶ月</u></span>';
  } else {
    $ret = '<span style="color:red;"><u>未読1年以上</u></span>';
  }
  
  return $ret;
}

function divideToWords($str) {

  $url = 'https://jlp.yahooapis.jp/MAService/V2/parse';
  $appid = 'dj00aiZpPTBBOTI3eXpybEJqbiZzPWNvbnN1bWVyc2VjcmV0Jng9Y2I-';

   $data = array('appid'=>$appid,
                 'sentence'=>$str,
                 'results'=>'ma',
                 'response'=>'surface',
                 'filter'=>'9',
           );

  $target_url = $url . '?' . http_build_query($data);
  
  //$result = file_get_contents($target_url);
  $result = @simplexml_load_file($target_url);
  
  $result_array = array();
  
  foreach ($result->ma_result->word_list->word as $item) {
    //echo "{$item->surface}<br>";
    $word = $item->surface[0] . '';
    
    array_push($result_array, array('tag'=>$word, 'user_id'=>0));
  }

  return $result_array;
}








?>