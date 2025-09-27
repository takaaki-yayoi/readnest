<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
}

define("CHARSET", mb_internal_encoding());
//define("DEBUG", 1);

$g_book_total_num = '';
$g_book_total_pages = '';

$g_book_array = array();

$g_book_title = '';
$g_book_detail_url = '';
$g_book_image = '';
$g_book_asin = '';
$g_book_number_of_page = '';
$g_book_author = '';

$g_book_height = 0;
$g_book_weight = 0;


// parsing and return result and array
// necessary paramerters:
// $g_book_total_num TotalResults
// $g_book_total_pages TotalPages
// $g_book_array
//    $g_book_title Title
//    $g_book_detail_url DetailPageURL
//    $g_book_image SmallImage
//    $g_book_asin ASIN
//    $g_book_number_of_page NumberOfPages
//    $g_book_author Author
function analyseAmazonXML($url) {
  global $g_book_total_num;
  global $g_book_total_pages;

  global $g_book_array;
  
  restore_error_handler();
  
  $options = array(
    'http' => array(
        'header' => 'User-Agent: Entrylist crawler',
    ),
  );
  $context = stream_context_create($options);

  $xml_content = @file_get_contents($url, false, $context);
  
  if($xml_content == FALSE) {
      $count = 0;
      do {
        if($count == 5 && $xml_content == FALSE) {
          return;
        }
        sleep(5);
        $xml_content = file_get_contents($url, false, $context);
        $count++;
      } while($xml_content == FALSE);
  }
  
  $dom_object = simplexml_load_string($xml_content);

  //ini_set('user_agent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0) Gecko/20100101 Firefox/9.0');
  //$dom_object = simplexml_load_file($url);

  //d($dom_object);

  foreach ($dom_object->Items as $items) {
    $g_book_total_num += $items->TotalResults;
  }

  foreach ($dom_object->Items as $items) {
    $g_book_total_pages += $items->TotalPages;
  }
  
  foreach ($dom_object->Items as $items) {
    foreach ($items->Item as $item) {

      //d($item->SmallImage->URL);

      array_push($g_book_array, array('Title'=>$item->ItemAttributes->Title, 
                                      'DetailPageURL'=>$item->DetailPageURL, 
                                      'SmallImage'=>$item->SmallImage->URL, 
                                      'MeduimImage'=>$item->MediumImage->URL, 
                                      'ASIN'=>$item->ASIN, 
                                      'NumberOfPages'=>$item->ItemAttributes->NumberOfPages, 
                                      'Author'=>$item->ItemAttributes->Author, 
                                      'Height'=>$item->ItemAttributes->PackageDimensions->Height, 
                                      'Weight'=>$item->ItemAttributes->PackageDimensions->Weight, 
                                      ));
    }
  }
}



function analyseRakutenXML($url) {
  global $g_book_total_num;
  global $g_book_total_pages;

  global $g_book_array;
  
  restore_error_handler();
  
  $options = array(
    'http' => array(
        'header' => 'User-Agent: Entrylist crawler',
    ),
  );
  $context = stream_context_create($options);

  $xml_content = @file_get_contents($url, false, $context);
  
  if($xml_content == FALSE) {
      $count = 0;
      do {
        if($count == 5 && $xml_content == FALSE) {
          return;
        }
        sleep(5);
        $xml_content = file_get_contents($url, false, $context);
        $count++;
      } while($xml_content == FALSE);
  }
  
  $dom_object = simplexml_load_string($xml_content);

  $g_book_total_num += $dom_object->count;
  $g_book_total_pages += $dom_object->pageCount;
  
  foreach ($dom_object->Items as $items) {
    foreach ($items->Item as $item) {

      //d($item->SmallImage->URL);

      array_push($g_book_array, array('Title'=>$item->title, 
                                      'DetailPageURL'=>$item->affiliateUrl, 
                                      'SmallImage'=>$item->smallImageUrl, 
                                      'MeduimImage'=>$item->mediumImageUrl, 
                                      'ASIN'=>'rakuten-' . $item->isbn, 
                                      'ISBN'=>$item->isbn, 
                                      'NumberOfPages'=>0, 
                                      'Author'=>$item->author, 
                                      ));
    }
  }
}


function analyseGoogleJSON($url) {
  global $g_book_total_num;
  global $g_book_total_pages;

  global $g_book_array;
  
  restore_error_handler();
/*
  $option = [
        CURLOPT_RETURNTRANSFER => true, //文字列として返す
        CURLOPT_TIMEOUT        => 3, // タイムアウト時間
    ];
*/
  $ch = curl_init($url);
  //curl_setopt_array($ch, $option);
  curl_setopt( $ch, CURLOPT_URL, $url ); // 2. オプションを設定
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

  $json    = curl_exec($ch);
  $info    = curl_getinfo($ch);
  $errorNo = curl_errno($ch);
/*
    // OK以外はエラーなので空白配列を返す
    if ($errorNo !== CURLE_OK) {
        // 詳しくエラーハンドリングしたい場合はerrorNoで確認
        // タイムアウトの場合はCURLE_OPERATION_TIMEDOUT
        return [];
    }

    // 200以外のステータスコードは失敗とみなし空配列を返す
    if ($info['http_code'] !== 200) {
        return [];
    }
*/
  // 文字列から変換
  $jsonArray = json_decode($json, true);
  
  $g_book_total_num = $jsonArray["totalItems"];
  $g_book_total_pages = floor($jsonArray["totalItems"] / BOOKS_PER_PAGE);
  
  foreach ($jsonArray["items"] as $item) {

    array_push($g_book_array, array('Title'=>$item["volumeInfo"]["title"], 
                                      'DetailPageURL'=>$item["volumeInfo"]["previewLink"], 
                                      'SmallImage'=>$item["volumeInfo"]["imageLinks"]["smallThumbnail"], 
                                      'LargeImage'=>$item["volumeInfo"]["imageLinks"]["thumbnail"], 
                                      //'MeduimImage'=>$item->mediumImageUrl, 
                                      'ASIN'=>'google-' . $item["id"], 
                                      'ISBN'=>$item["volumeInfo"]["industryIdentifiers"][1]["identifier"], 
                                      'NumberOfPages'=>$item["volumeInfo"]["pageCount"], 
                                      'Author'=>$item["volumeInfo"]["authors"][0], 
                                      ));
  }
}

// parsing amazon api response for php4
function parseTree($obj) {
  global $g_book_total_num;
  global $g_book_total_pages;

  global $g_book_array;

  global $g_book_title;
  global $g_book_detail_url;
  global $g_book_image;
  global $g_book_asin;
  global $g_book_number_of_page;
  global $g_book_author;

  global $g_book_height;
  global $g_book_weight;

  if($g_book_title != '' && 
     $g_book_detail_url != '' && 
     $g_book_asin != ''
  ) {

    array_push($g_book_array, array('Title'=>$g_book_title, 
                                    'DetailPageURL'=>$g_book_detail_url, 
                                    'SmallImage'=>$g_book_image, 
                                    'ASIN'=>$g_book_asin, 
                                    'NumberOfPages'=>$g_book_number_of_page, 
                                    'Author'=>$g_book_author, 
                                    'Height'=>$g_book_height, 
                                    'Weight'=>$g_book_weight, 
                                    ));

    $g_book_title = '';
    $g_book_detail_url = '';
    $g_book_image = '';
    $g_book_asin = '';
    $g_book_number_of_page = '';
    $g_book_author = '';
    $g_book_height = '';
    $g_book_weight = '';
  }

  switch ($obj->type) {
    case XML_ELEMENT_NODE:
      $element_name = mb_convert_encoding($obj->tagname, CHARSET, "utf-8");
    
      //echo "要素名：" . $element_name . "<br>";

      // get TotalResults
      if($element_name == 'TotalResults') {
        $target_nodes = $obj->children();
        $target_content = $target_nodes[0]->get_content();
        $g_book_total_num += $target_content;
      }

      // get TotalPages
      if($element_name == 'TotalPages') {
        $target_nodes = $obj->children();
        $target_content = $target_nodes[0]->get_content();
        $g_book_total_pages += $target_content;
      }

      
      // get Title
      if($element_name == 'Title') {
        $target_nodes = $obj->children();
        $target_content = $target_nodes[0]->get_content();
        $g_book_title = $target_content;
      }

      // get DETAILPAGEURL
      if($element_name == 'DetailPageURL') {
        $target_nodes = $obj->children();
        $target_content = $target_nodes[0]->get_content();
        $g_book_detail_url = $target_content;
      }

      // get ASIN
      if($element_name == 'ASIN') {
        $target_nodes = $obj->children();
        $target_content = $target_nodes[0]->get_content();
        $g_book_asin = $target_content;
      }

      // get image
      if($element_name == 'SmallImage') {
        $target_nodes = $obj->children();
        $target_nodes2 = $target_nodes[0]->children();
        $target_content = $target_nodes2[0]->get_content();
        $g_book_image = $target_content;
      }

      // get author
      if($element_name == 'Author') {
        $target_nodes = $obj->children();
        $target_content = $target_nodes[0]->get_content();
        $g_book_author = $target_content;
      }

      // get number of page
      if($element_name == 'NumberOfPages') {
        $target_nodes = $obj->children();
        $target_content = $target_nodes[0]->get_content();
        $g_book_number_of_page = $target_content;
      }

      // get height
      if($element_name == 'Height') {
        $target_nodes = $obj->children();
        $target_content = $target_nodes[0]->get_content();
        $g_book_height = $target_content;
      }

      // get weight
      if($element_name == 'Weight') {
        $target_nodes = $obj->children();
        $target_content = $target_nodes[0]->get_content();
        $g_book_weight = $target_content;
      }

      // new book node
      if($element_name == 'Item') {
        if($g_book_title != '' && 
          $g_book_detail_url != '' && 
          $g_book_asin != ''
          ) {

            array_push($g_book_array, array('Title'=>$g_book_title, 
                                            'DetailPageURL'=>$g_book_detail_url, 
                                            'SmallImage'=>'', 
                                            'ASIN'=>$g_book_asin, 
                                            'NumberOfPages'=>$g_book_number_of_page, 
                                            'Author'=>$g_book_author, 
                                            'Height'=>$g_book_height, 
                                            'Weight'=>$g_book_weight, 
                                           ));

           $g_book_title = '';
           $g_book_detail_url = '';
           $g_book_image = '';
           $g_book_asin = '';
           $g_book_number_of_page = '';
           $g_book_author = '';
           $g_book_height = '';
           $g_book_weight = '';
        }
      }

      // カレント要素の属性の解析
      $attr = $obj->attributes();
      for ($i = 0; $i < count($attr); $i++) {
        parseTree($attr[$i]);
      }
  
      // カレント要素の子要素の解析
      $child = $obj->children();
      for ($i = 0; $i < count($child); $i++) {
        parseTree($child[$i]);
      }
      break;
  
    case XML_ATTRIBUTE_NODE:
      $attribute_name =  mb_convert_encoding($obj->name(), CHARSET, "utf-8");
      $attribute_value = mb_convert_encoding($obj->value(), CHARSET, "utf-8");
    
      /*
      echo "　属性：" . $attribute_name . " = "
                      . $attribute_value
                      . "<br>";
      */
      break;
  
    case XML_TEXT_NODE:
      if (trim($obj->get_content()) != "") {
        $content = mb_convert_encoding($obj->get_content(), CHARSET, "utf-8");
        //echo "　内容：" . $content . "<br>";
      }
      break;
  }
}







?>