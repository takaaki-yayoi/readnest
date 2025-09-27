<?php
require_once('config.php');

//define('DEBUG', 1);

if(checkLogin()) {
  $user_id = $_SESSION['AUTH_USER'];
  
  $product = $_POST['id'];
  if(empty($product)) {
    exit;
  }
  $tmp = explode('_', $product);
  $product_id = $tmp[1];

  removeFavoriteBook($user_id, $product_id);
  
  $books = getFavoriteBook($user_id);

  $bom = "\xef\xbb\xbf";
  print $bom;
  print getFavoriteBookList($books, 'script', $user_id);
}
?>