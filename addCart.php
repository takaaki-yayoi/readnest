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

  // upto 10 items
  $current_books = getFavoriteBook($user_id);
  
  if(count($current_books) < MAX_FAVORITE_BOOKS) {
    addFavoriteBook($user_id, $product_id);
    $warning_message = '';
  } else {
    $warning_message = '<p class="warning">最大' . MAX_FAVORITE_BOOKS . '冊まで登録できます。</p>';
  }

  $books = getFavoriteBook($user_id);

  $bom = "\xef\xbb\xbf";
  print $bom;
  print getFavoriteBookList($books, 'script', $user_id);
  print $warning_message;
}
?>