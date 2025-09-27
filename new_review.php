<?php
require_once('config.php');

//define('DEBUG', 1);
$everyones_tag_cloud = '';
$everyones_sakka_cloud = '';

if(checkLogin()) {
  $user_id = $_SESSION['AUTH_USER'];
  $d_nickname = getNickname($user_id);

  $d_message = '';
  
  $d_global_nav = createGlobalNav($user_id);

  $d_sub_content = $d_global_nav;

} else {
  $d_message = '';
  $d_sub_content = $g_login_window;
}

$d_popular_review = '';
$d_pager = '';

$popular_reviews = getNewReview('', '');

$max_page = ceil(count($popular_reviews) / BOOKS_PER_PAGE);
      
// pager control
if(isset($_GET['page']))
  $current_page = $_GET['page'];
else if (isset($_POST['page']))
  $current_page = $_POST['page'];
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

// create pager
$d_pager = createPager($page, $max_page, '', "new_review.php");


$d_popular_review .= '<ul class="diary_list">';

$count_review = count($popular_reviews);

$start_point = ($page - 1) * BOOKS_PER_PAGE;
$end_point = BOOKS_PER_PAGE * $page;
      
for($i = $start_point; $i < $end_point; $i++) {
  if($i == $count_review) break;

  $book_id = $popular_reviews[$i]['book_id'];
  $name = $popular_reviews[$i]['name'];
  $book_memo = $popular_reviews[$i]['memo'];
  $total_page = $popular_reviews[$i]['total_page'];
  $current_page = $popular_reviews[$i]['current_page'];
  $amazon_id = $popular_reviews[$i]['amazon_id'];
  $create_date = $popular_reviews[$i]['create_date'];
  $update_date = $popular_reviews[$i]['update_date'];
  $status_id = $popular_reviews[$i]['status'];
  $detail_url = $popular_reviews[$i]['detail_url'];
  $image_url = $popular_reviews[$i]['image_url'];
  $user_id = $popular_reviews[$i]['user_id'];
  $number_of_refer = $popular_reviews[$i]['number_of_refer'];
  $current_rating = $popular_reviews[$i]['rating'];

  $memo_updated = $popular_reviews[$i]['memo_updated'];
  
  if($memo_updated != 0) {
    // $memo_updatedが文字列の場合はstrtotime()で変換
    $timestamp = is_numeric($memo_updated) ? $memo_updated : strtotime($memo_updated);
    $update_date_conv = date('Y/m/d H:i:s', $timestamp);
  } else {
    // $update_dateが文字列の場合はstrtotime()で変換
    $timestamp = is_numeric($update_date) ? $update_date : strtotime($update_date);
    $update_date_conv = date('Y/m/d H:i:s', $timestamp);
  }

  if(mb_strlen($book_memo) > 50) {
    $book_memo = mb_substr(nl2br($book_memo), 0, 50) . '…';
  } else {
    $book_memo = nl2br($book_memo);
  }
  
  if($current_rating != '') {
    $star = '[' . $g_star_array[$current_rating] . ']&nbsp;';
  } else {
    $star = '[未評価]&nbsp;';
  }
  
  $d_nickname = getNickname($user_id);

  $detail_url = "/book/$book_id";
  $title_part = '';
  $refer_part = "&nbsp;<span style=\"font-size:x-small\">[${update_date_conv}更新]</span>";


  if($image_url != '') {
    $image_part = "<p class=\"imgBox\"><a href=\"" . $detail_url. "\" $title_part><img class=\"list_img\" src=\"$image_url\" border=0 onerror=\"this.src='/img/noimage.jpg'\"></a></p>";
  } else {
    $image_part = "<p class=\"imgBox\"><a href=\"" . $detail_url. "\" $title_part><img class=\"list_img\" src=\"/img/noimage.jpg\" border=0></a></p>";
  }

  $d_popular_review .= "<li>$image_part<p class=\"textArea\"><a href=\"$detail_url\" $title_part>$name</a> by <a href=\"profile.php?user_id=$user_id\">${d_nickname}さん</a>$refer_part<p style=\"font-size:x-small\">$star$book_memo</p></p></li>\n";
}

$d_popular_review .= '</ul>';

require_once TPLDIR . 't_new_review.php';
exit;
?>