<?php
require_once('config.php');

//define('DEBUG', 1);

if(checkLogin()) {
  $user_id = $_SESSION['AUTH_USER'];
  
  // evaluate
  if(isset($_POST['relation_id'])) {
    $relation_id = $_POST['relation_id'];
    $linked_from = $_POST['link_from'];
        
    removeBookLink($user_id, $relation_id);
  }

  $result_str = create_link_book_list($linked_from, true);

  $bom = "\xef\xbb\xbf";
  print $bom;
  print $result_str;
}
?>