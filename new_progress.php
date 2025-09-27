<?php
require_once('config.php');

$user_id = $_GET['user_id'];

// get recent event
$event_array = getNewestEvent($user_id);

$event_str = '特にないです。';

if($event_array != NULL) {
  $target_user_id = $event_array['user_id'];
  $target_book_id = $event_array['book_id'];
  $target_event = $event_array['event'];
  $target_book_page = $event_array['page'];
  $target_event_date = $event_array['event_date'];

  $target_user_name = getNickname($target_user_id);

  $target_book_array = getBookInformation($target_book_id);
  $target_book_name = htmlspecialchars($target_book_array['name']);

  if($target_event == READING_FINISH) {
    $message = 'を読み終えました。';
  } else {
    $message = "を${target_book_page}ページまで読みました。";
  }

  $book_link = "<a href=\"/book/$target_book_id\">$target_book_name</a>";

  $event_str = "<a href=\"/profile.php?user_id=$target_user_id\">${target_user_name}さん</a>が${book_link}$message";
}
  
print '<span style="font-size:x-small" >最近の出来事</span>&nbsp;' . $event_str;
?>