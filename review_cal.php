<?php
require_once('config.php');

//define('DEBUG', 1);

$d_order_switch = '';
$d_bookshelf = '';

if(checkLogin()) {
  $user_id = $_SESSION['AUTH_USER'];
  $d_nickname = getNickname($user_id);

  $d_global_nav = createGlobalNav($user_id);
  
  // get bookshelf
  $result = getReadBook($user_id, SORT_RATING);
  
  // get bookshelf statistics
  list($d_total_book_num, $d_total_page_num) = getBookshelfStat($user_id);
  
  if($d_total_page_num == '') $d_total_page_num = 0;
  $d_total_book_num = number_format($d_total_book_num);
  $d_total_page_num = number_format($d_total_page_num);

  $d_pager = '';

  $d_switch = "<p style=\"text-align:right;\"><img src=\"img/list_marker_red.gif\" border=\"0\"><a href=\"diary.php?user_id=$user_id\">一覧表示</a></p>";

  
  if(!isset($_GET['year']) && !isset($_GET['month'])) {
  
    //今日の日付(年、月、日)を$dateへ配列として代入
    $date = date('Y n d');
    //date('Y n d')を date('2037 12 31')とすると、
    //2037年12月のカレンダーが表示できます
    //ちなみに、2038年問題は非対応。

  } else {
    $year = $_GET['year'];
    $month = $_GET['month'];
    
    if(!is_numeric($year) || $year > 2037 || $year < 1900){
      $date = date('Y n d');
    } else if(!is_numeric($month) || $month > 12 || $month < 1){
      $date = date('Y n d');
    } else {
    
      $target_day = date('d');
        
      if (!checkdate($month, $target_day, $year)) {
        $date = "$year $month 01";
      } else {
        $date = "$year $month $target_day";
      }
    }
  }

  $date = explode(' ', $date);

  //明示的に変数を整数型へ変換
  $date[MONTH] = (int) $date[MONTH];
  $date[YEAR] = (int) $date[YEAR];
  $date[DAY] = (int) $date[DAY];

  //今月の日数、最初の日、最後の日の曜日を得る
  $days = date('d', mktime(0, 0, 0, $date[MONTH]+1, 0, $date[YEAR]));
  $first_day = date('w', mktime(0, 0, 0, $date[MONTH], 1, $date[YEAR]));
  $last_day = date('w', mktime(0, 0, 0, $date[MONTH], $days, $date[YEAR]));

  //最後の週の曜日を得る
  $last_week_days = ($days + $first_day) % 7;

  if ($last_week_days == 0){
    $weeks = ($days + $first_day) / 7;
  }else{
    $weeks = ceil(($days + $first_day) / 7);
  }

  $weeks = (int) $weeks;
  $last_day = (int) $last_day;
  $first_day = (int) $first_day;
  
  // pager
  if($date[MONTH] == 1) {
    $prev_target_year = $date[YEAR] - 1;
    $prev_target_month = 12;
  } else {
    $prev_target_year = $date[YEAR];
    $prev_target_month = $date[MONTH] - 1;
  }

  if($date[MONTH] == 12) {
    $next_target_year = $date[YEAR] + 1;
    $next_target_month = 1;
  } else {
    $next_target_year = $date[YEAR];
    $next_target_month = $date[MONTH] + 1;
  }
  
  $prev_pager = "<a href=\"review_cal.php?year=${prev_target_year}&month=$prev_target_month\"><img src=\"img/arrow_fat_left.gif\" border=\"0\" /></a>";
  $next_pager = "<a href=\"review_cal.php?year=${next_target_year}&month=$next_target_month\"><img src=\"img/arrow_fat_right.gif\" border=\"0\" /></a>";



  //カレンダーを表として出力する
  $d_bookshelf .=  '<div style="float:left;"><table>';
  $d_bookshelf .= '<tr><td colspan="7" align="center">' . $prev_pager . '&nbsp;' .  $date[YEAR] . '年' . $date[MONTH] . '月&nbsp;' . $next_pager . '</td></tr>';
  //$d_bookshelf = '';
  
  $width = '80px';
  
  $d_bookshelf .= "<tr><th style=\"width:$width\">日</th>
                   <th style=\"width:$width\">月</th>
                   <th style=\"width:$width\">火</th>
                   <th style=\"width:$width\">水</th>
                   <th style=\"width:$width\">木</th>
                   <th style=\"width:$width\">金</th>
                   <th style=\"width:$width\">土</th>";

  $d_bookshelf .= '</tr>';

  $read_num_month = 0;

  $i = $j = $day = 0;

  while ($i < $weeks){
    $d_bookshelf .= '<tr>' . "\r\n";
    $j = 0;
    
    while ($j < 7){
      $d_bookshelf .= '<td';
      
      if (($i == 0 && $j < $first_day) || ($i == $weeks - 1 && $j > $last_day)){
        $d_bookshelf .= '> ';
      } else {
        $d_bookshelf .= ' valign="top">';

        $day++;
        
        $time_stamp = mktime(0, 0, 0, $date[MONTH], $day, $date[YEAR]);
        
        //$tmp = date('Y/m/d H:i:s', $time_stamp);
        //$tmp = date('Y/m/d H:i:s', time());
        
        $target_books = getFinishedBooks($user_id, $time_stamp);
        $book_part = '';
        
        //d($target_books);
        
        $read_num_month += count($target_books);
        
        for($book_count = 0; $book_count < count($target_books); $book_count++) {
          $book_id = $target_books[$book_count]['book_id'];
          
          $book_info = getBookInformation($book_id);

          if($book_info != NULL) {
            $book_id = $book_info['book_id'];
            $name = $book_info['name'];
            $book_memo = $book_info['memo'];
            $total_page = $book_info['total_page'];
            $current_page = $book_info['current_page'];
            $amazon_id = $book_info['amazon_id'];
            $create_date = $book_info['create_date'];
            $update_date = $book_info['update_date'];
            $status_id = $book_info['status'];
            $detail_url = $book_info['detail_url'];
            $image_url = $book_info['image_url'];
            $current_rating = $book_info['rating'];
            $user_id = $book_info['user_id'];
            
            $detail_url = "/book/$book_id";
            
            if($current_rating != '') {
              $star = $g_star_array[$current_rating];
            } else {
              $star = '未評価';
            }
        
            $book_memo = preg_replace("/\r|\n/", "", $book_memo);

            if($image_url != '') {
              //$image_part = popup("<img src=\"$image_url\" border=0>", "$name<br />$star<br />$book_memo");
              $image_part = "<a title=\"$name\" href=\"" . $detail_url. "\"><img class=\"list_img\" src=\"$image_url\" border=0 onerror=\"this.src='/img/noimage.jpg'\"></a>";

            } else {
              //$image_part = popup("<img src=\"/img/noimage.jpg\" border=0>", "$name<br />$star<br />$book_memo");
              $image_part = "<a title=\"$name\" href=\"" . $detail_url. "\"><img class=\"list_img\" src=\"/img/noimage.jpg\" border=0></a>";

            }

            $book_part .= $image_part . '<br />';
          }
          
        }

        $d_bookshelf .= '<center>' . $day . "</center><br />$book_part";
        
      }
      
      $d_bookshelf .= '</td>';
      $j++;
    }
    
    $d_bookshelf .=  '</tr>'."\r\n";
    $i++;
  }
  $d_bookshelf .= '</table>';

  $d_bookshelf .= "</div>";
  
  // 月当たり目標冊数取得
  $user_inf_array = getUserInformation($user_id);
  $books_per_year = $user_inf_array['books_per_year'];
  
  if($books_per_year != NULL) {
    $books_per_month = ceil($books_per_year / 12);
    
    $books_str = '&nbsp;/&nbsp;<span style="color:red; font-size:20px">' . $books_per_month . '</span>';
  } else {
    $books_str = '';
  }

  $d_order_switch = '<span style="color:blue; font-size:20px">' . $read_num_month . '</span>' . $books_str;

  require_once TPLDIR . 't_review_cal.php';
} else {
  require_once TPLDIR . 't_login.php';
}

?>

