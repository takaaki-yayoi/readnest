<?php
require_once('config.php');

//define('DEBUG', 1);

if(checkLogin()) {
  $user_id = $_SESSION['AUTH_USER'];
  
  // evaluate
  if(isset($_POST['book_tags']) && isset($_POST['book_id'])) {
    $tag_array = mb_split('[ 　]+', $_POST['book_tags']);
    $book_id = $_POST['book_id'];

    updateTag($user_id, $book_id, $tag_array);
  }
  
  $result_str = '<p style="font-size:x-small; text-align:center;">タグは登録されていません</p>';
  
  $current_tag = getTag($book_id);
  $tag_count = count($current_tag);
  
  if($tag_count != 0) {
    $result_str = createTagList($current_tag);
  }

  $bom = "\xef\xbb\xbf";
  print $bom;
  print $result_str;
}
?>