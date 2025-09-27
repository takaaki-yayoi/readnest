<?php
require_once('config.php');

//define('DEBUG', 1);
if(checkLogin()) {
  $user_id = $_SESSION['AUTH_USER'];
    
  $books = getFavoriteBook($user_id);

  $bom = "\xef\xbb\xbf";
  print $bom;
  print getFavoriteBookList($books, 'script', $user_id);
}
?>