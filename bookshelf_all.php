<?php
require_once('config.php');

//define('DEBUG', 1);
$login_flag = false;

if(checkLogin()) {
  $mine_user_id = $_SESSION['AUTH_USER'];
  $d_nickname = getNickname($mine_user_id);
  
  $d_global_nav = createGlobalNav($mine_user_id);

  $login_flag = true;

} else {
  $mine_user_id = '';
  $d_nickname = 'ゲスト';

  $d_global_nav = $g_login_window;
}

$author_str = '';
$user_introduction = '';
$page_title = '';
$d_switch = '';

// author specified?
if(isset($_GET['author']) || isset($_GET['tag'])) {

  if(isset($_GET['author'])) {
    $author = html($_GET['author']);
    $result = searchAllBookshelfByAuthor($author);
  
    // get wiki data
    $wiki_data = getWiki($author);
    
    if($wiki_data != '') {
      $d_switch .= $wiki_data;
      $d_switch .= '<br />';
    }
   
    // 作家情報ページへのリンク（ログイン不要）
    $d_switch .= "<a href=\"/author.php?name=" . urlencode($author) . "\"><img src=\"/img/list_marker_red.gif\" border=\"0\">${author}の作家情報を見る</a>";
    
  
    $page_title = "皆の本棚にある${author}の本";
  } else if(isset($_GET['tag'])){
  
    $tag = html($_GET['tag']);
    $result = searchAllBookshelfByTag($tag);

    // get wiki data
    $wiki_data = getWiki($tag);
    
    if($wiki_data != '') {
      $d_switch .= $wiki_data;
      $d_switch .= '<br />';
    }
   
    $page_title = "皆の本棚にあるタグ「${tag}」の本";
  
  }
  
  $max_page = ceil(count($result) / BOOKS_PER_PAGE);

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
  
  if(isset($_GET['author'])) {
    $author = $_GET['author'];
      
    // create pager
    $d_pager = createPager($page, $max_page, '', "/bookshelf_all.php?author=$author");

  } else if(isset($_GET['tag'])) {
    $tag = $_GET['tag'];
      
    // create pager
    $d_pager = createPager($page, $max_page, '', "/bookshelf_all.php?tag=$tag");
  }
      
  $d_bookshelf = '<ul class="diary_list">';
      
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
    $number_of_refer = $result[$i]['number_of_refer'];
    $user_id = $result[$i]['user_id'];

    $d_targent_nickname = getNickname($user_id);
    
    if($user_id == $mine_user_id) {
      $target_user_name = 'あなたの本棚';
    } else {
      $target_user_name = "<a href=\"/profile.php?user_id=$user_id\">{$d_targent_nickname}さん</a>の本棚";
    }
    
    // check comment number
    $book_comments = getComment($book_id);
    $comment_count = count($book_comments);
    if($comment_count != 0) {
      $comment_part = "&nbsp;<span style=\"font-size:x-small; color:red\">[{$comment_count}件のコメント]</span>";
    } else {
      $comment_part = '';
    }
    
    $detail_url = "/book/$book_id";

    // 設定済みタグ取得
    $current_tags = getTag($book_id);
    $tag_count = count($current_tags);
        
    if($tag_count == 0) {
      $tag_message = '';
    } else {
      $tag_message = createTagList($current_tags, 'list');
    }
        
    if($status_id == READING_FINISH || $status_id == READ_BEFORE) {
      $ratio = 100;
    } else {
      $ratio = progress_ratio($total_page, $current_page);
    }
        
    $bar_str = create_bar($ratio);
    $create_date = date('Y/m/d H:i:s', $create_date);
    $update_date = date('Y/m/d H:i:s', $update_date);
    
    // default page number
    if($current_page == 0) $current_page = 1;
        
    if($image_url != '') {
      // check image existence
      $image_part = "<a href=\"" . $detail_url. "\"><img src=\"$image_url\" border=0 onerror=\"this.src='/img/noimage.jpg'\"></a>";
    } else {
      $image_part = "<a href=\"" . $detail_url. "\"><img src=\"/img/noimage.jpg\" border=0></a>";
    }
        
    $book_detail_link = "<a href=\"" . $detail_url. "\">$name</a>";
        
    $summary_length = 40;
  
    if(mb_strlen($book_memo) > $summary_length) {
     $book_memo = mb_substr($book_memo, 0, $summary_length) . '…';
    }

    if($status_id == READING_NOW) {
      $page_label = '残りページ数';
      $page_num_part = $total_page - $current_page;
      $bar_part = "$bar_str&nbsp;${ratio}%";
    } else {
      $page_label = '総ページ数';
      $page_num_part = $total_page;
      $bar_part = '';
    }
        
    $d_bookshelf .= "<li><p class=\"imgBox\">$image_part</p><p class=\"textArea\">$book_detail_link$comment_part" . 
                        "$bar_part" . 
                        "<p style=\"font-size:x-small\">$tag_message</p>" . 
                        "<p style=\"font-size:x-small\">$book_memo</p>" . 
                        "<p class=\"diary_entry\">${target_user_name}&nbsp;[$page_label:$page_num_part] $update_date 更新</p></p>" . 
                        "</li>\n";
    
  }
      
  $d_bookshelf .= '</ul>';
  
  require_once TPLDIR . 't_bookshelf.php';
  
} else {
   header('Location: https://readnest.jp/index.php');
   exit;
}

?>