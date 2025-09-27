<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
}

// セッションがまだ開始されていない場合のみ設定を変更
if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.name', 'DOKUSHO');
  ini_set('session.use_only_cookies', 'on');
  // session.bug_compact_42 は PHP 8.2 で廃止されたため削除
  // session.hash_function は PHP 7.1 で廃止されたため削除  
  // session.hash_bit_per_character は PHP 7.1 で廃止されたため削除
  ini_set('session.cookie_lifetime', 0);
  
  //ini_set('session.cache_limiter', 'private');
}

define('SESS_EXPIRES', 7200);
define('SESS_REGEN', 3600);

// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
  if(!session_start()) {
    trigger_error('session start failed.');
  }
}

$current_time = time();

if(empty($_SESSION['access'])) {
  $_SESSION['access'] = $current_time;
  $_SESSION['AUTH'] = null;
} else {
  if($_SESSION['access'] < $current_time - SESS_EXPIRES) {
    if(!session_destroy()) {
      error_log('session destruction failed.');
    }
    header('Location: https://' . $_SERVER['HTTP_HOST']);
    exit;
  }
  
  if($_SESSION['access'] < $current_time - SESS_REGEN) {
    if(!session_regenerate_id(true)) {
      trigger_error('session regeneration failed.', E_USER_NOTICE);
    }
  }
  
  $_SESSION['access'] = $current_time;
}


// check login
function checkLogin() {
  global $g_error;

  // login navigation
  if(isset($_POST['username']) && isset($_POST['password'])) {
    $user_id = authUser($_POST['username'], $_POST['password']);

    //d($user_id);

    if($user_id != NULL) {
      $_SESSION['AUTH_USER'] = $user_id;
      session_regenerate_id(true);

      // automatic login setting
      if(isset($_POST['autologin']) && $_POST['autologin'] == 'on') {
        setAutoLogin($user_id);
      } else {
        unsetAutoLogin();
      }
      
      // redirection controll
      if(isset($_SERVER["HTTP_REFERER"])) {
        $target_url = $_SERVER["HTTP_REFERER"];
        header("location: $target_url");
      }
      
      return true;

    } else {
      $g_error = 'パスワードが違います。';
      return false;
    }
  }
  
  // normal navigation
  if(empty($_SESSION['AUTH_USER'])) {
  
    if(!empty($_COOKIE['AUTOLOGIN'])) {
      if(defined('DEBUG')) echo 'auto login trying...<br />';
    
      $user_id = getUserByAutologiKey($_COOKIE['AUTOLOGIN']);

      if($user_id != NULL) {
        session_regenerate_id(true);
        $_SESSION['AUTH_USER'] = $user_id;
        return true;
      }
    }

    return false;

  } else {
    return true;
  }
}


function setAutoLogin($user_id) {
  $autologin_key = setAutoLoginKey($user_id);
         
  if(!setcookie('AUTOLOGIN', $autologin_key, time() + AUTO_LOGIN_DURATION)) {
    trigger_error('setting autologin failed.');
  }
}

function unsetAutoLogin() {
  if(!setcookie('AUTOLOGIN', '', 0)) {
    trigger_error('setting autologin failed.');
  }
}
?>