<?php
require_once('config.php');

$valid_flag = true;

if(checkLogin()) {
  $user_id = $_SESSION['AUTH_USER'];
  $d_nickname = getNickname($user_id);
  $d_global_nav = createGlobalNav($user_id);

  if(empty($_POST)) {
    require_once TPLDIR . 't_quit.php';
  } else {
    if(isset($_POST['confirm'])) {
      $confirm = $_POST['confirm'];
    } else {
      $confirm = '';
    }

    // conform
    if($confirm != 'yes') {
      require_once TPLDIR . 't_quit.php';

    } else {
    // upadte
      deleteUserInformation($user_id);
      session_destroy();
      unsetAutoLogin();
      
      require_once TPLDIR . 't_quit_complete.php';
    }

  }
} else {
  require_once TPLDIR . 't_login.php';
}
?>