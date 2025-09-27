<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
}

function validate_regist_email($email1, $email2) {
  global $g_error;
  global $g_db;
  
  $valid_flag = true;
  
  if($email1 == '') {
    $g_error .= 'メールアドレスを入力して下さい。<br/>';
    $valid_flag = false;
  }
  
  if($email2 == '') {
    $g_error .= 'メールアドレス(確認)を入力して下さい。<br/>';
    $valid_flag = false;
  }
  
  if($email1 != $email2) {
    $g_error .= 'メールアドレスが一致しません。<br/>';
    $valid_flag = false;
  }
  
  // duplicate check
  $select_sql = 'select email from b_user where email=?';

  if(defined('DEBUG')) { d($select_sql); }
  $result = $g_db->getOne($select_sql, array($email1));
  if(DB::isError($result)) {
    trigger_error($result->getMessage());
  }
  
  if($result != NULL) {
    $g_error .= 'そのメールアドレスはすでに登録されています。<br/>';
    $valid_flag = false;
  }
  
  return $valid_flag;
}


function validate_password($password1, $password2) {
  global $g_error;
  
  $valid_flag = true;
  
  if($password1 == '') {
    $g_error .= 'パスワードを入力して下さい。<br/>';
    $valid_flag = false;
  }
  
  if($password2 == '') {
    $g_error .= 'パスワード(確認)を入力して下さい。<br/>';
    $valid_flag = false;
  }
  
  if($password1 != $password2) {
    $g_error .= 'パスワードが一致しません。<br/>';
    $valid_flag = false;
  }
  
  return $valid_flag;
}


function validate_nickname($nickname) {
  global $g_error;
  
  $valid_flag = true;
  
  if($nickname == '') {
    $g_error .= 'ニックネームを入力して下さい。<br/>';
    $valid_flag = false;
  }
  
  return $valid_flag;
}

function validate_books_per_year($books_per_year) {
  global $g_error;
  
  $valid_flag = true;

  if(!is_numeric($books_per_year)) {
    $g_error .= '数字を指定してください。<br/>';
    $valid_flag = false;
  } else if($books_per_year < 0) {
    $g_error .= '0以上の数字を指定してください。<br/>';
    $valid_flag = false;
  }

  return $valid_flag;
}



function validate_profile_photo($file_path, $file_size) {
  global $g_error;
  
  $valid_flag = true;
  
  if(!is_uploaded_file($file_path)) {
    $g_error .= '不正なファイルです。<br/>';
    $valid_flag = false;
  }
  
  $validated_filesize = filesize($file_path);
  
  if($file_size != $validated_filesize) {
    $g_error .= '不正なファイルです。<br/>';
    $valid_flag = false;
  }
  
  if($validated_filesize > MAX_PHOTO_FILE_SIZE) {
    $g_error .= 'ファイルサイズは最大' . number_format(MAX_PHOTO_FILE_SIZE) . 'バイトまでです。<br/>';
    $valid_flag = false;
  }

  list($width, $height, $type, $attr) = getimagesize($file_path);
  
  if($type != 1 && $type != 2) {
    $g_error .= 'ファイルタイプはGIF、JPEGのみとなっています。<br/>';
    $valid_flag = false;
  }
  
  if($type == 1) {
    $validated_file_type = 'image/gif';
  } else if($type == 2) {
    $validated_file_type = 'image/jpeg';
  } else {
    $validated_file_type = '';
  }

  return array($valid_flag, $validated_file_type, $validated_filesize);
}



function validate_book_title($title) {
  global $g_error;
  
  $valid_flag = true;
  
  if($title == '') {
    $g_error .= '本のタイトルを入力して下さい。<br/>';
    $valid_flag = false;
  }
  
  return $valid_flag;
}


function validate_book_author($author) {
  global $g_error;
  
  $valid_flag = true;
  
  if($author == '') {
    $g_error .= '著者を入力して下さい。<br/>';
    $valid_flag = false;
  }
  
  return $valid_flag;
}

function validate_book_page($book_page) {
  global $g_error;
  
  $valid_flag = true;

  if(!is_numeric($book_page)) {
    $g_error .= 'ページ数には数字を指定してください。<br/>';
    $valid_flag = false;
  } else if($book_page < 0) {
    $g_error .= 'ページ数には0以上の数字を指定してください。<br/>';
    $valid_flag = false;
  }

  return $valid_flag;
}



/**
 * ランダムな文字列を生成する。
 * @param int $nLengthRequired 必要な文字列長。省略すると 8 文字
 * @return String ランダムな文字列
 */
function getRandomString($nLengthRequired = 8){
  $sCharList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_";
  mt_srand();
  $sRes = "";
  for($i = 0; $i < $nLengthRequired; $i++)
      $sRes .= $sCharList[mt_rand(0, strlen($sCharList) - 1)];
  return $sRes;
}


function agent2mobile_id() {
  $mobile_type = checkMobileType();

  if($mobile_type == 'docomo') {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    if(strstr($user_agent, 'ser') != false) {
      list($prefix, $mobile_id) = explode('ser', $user_agent);

      $mobile_id = mb_eregi_replace('\)$', '', $mobile_id);
      return $mobile_id;
    } else {
      return false;
    }
  } else if($mobile_type == 'ez') {
    $id = isset($_SERVER['HTTP_X_UP_SUBNO']) ? $_SERVER['HTTP_X_UP_SUBNO'] : false;
    return $id;
  } else if($mobile_type == 'sb') {
    $id = isset($_SERVER['HTTP_X_JPHONE_UID']) ? $_SERVER['HTTP_X_JPHONE_UID'] : false;
    return $id;
  } else {
    return false;
  }
}



function html($str) {
  // 新しいXSS対策関数を使用
  require_once(dirname(__FILE__) . '/security_enhanced.php');
  return XSS::escape($str);
}

function html_raw($str) {
  return $str;
}
?>