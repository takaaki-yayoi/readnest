<?php
require_once('config.php');

//define('DEBUG', 1);

if(checkLogin()) {
  $user_id = $_SESSION['AUTH_USER'];
  
  // evaluate
  if(isset($_POST['link_book']) && isset($_POST['link_from'])) {
    $linked_to = $_POST['link_book'];
    $linked_from = $_POST['link_from'];
        
    addBookLink($user_id, $linked_from, $linked_to);
  } else if(isset($_POST['asin']) && isset($_POST['link_from'])) {
    $book_asin = $_POST['asin'];
    $linked_from = $_POST['link_from'];

    $book_name = $_POST['product_name'];
    $number_of_pages = $_POST['number_of_pages'];
    $detail_url = $_POST['detail_url'];
    $image_url = $_POST['image_url'];
    $author = $_POST['author'];
    $book_isbn = $_POST['isbn'];
    
    // status controll
    if(isset($_GET['status']) && ($_GET['status'] == BUY_SOMEDAY || $_GET['status'] == READ_BEFORE)) {
      $status = $_GET['status'];
    } else {
      $status = BUY_SOMEDAY;
    }

    if(!is_bookmarked($user_id, $book_asin)) {
      //$book_id = createBook($user_id, $book_name, $book_asin, '', $number_of_pages, $status, $detail_url, $image_url);
      $book_id = createBook($user_id, $book_name, $book_asin, $book_isbn, $author, '', $number_of_pages, $status, $detail_url, $image_url);

      addBookLink($user_id, $linked_from, $book_id);
    } else {
      $result_str = "すでに本棚にあります。";
    }

  }

  $result_str = create_link_book_list($linked_from, true);

  $bom = "\xef\xbb\xbf";
  print $bom;
  print $result_str;
}
?>