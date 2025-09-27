<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
}

ob_start();

// PHP 8.2対応: 非推奨エラーは除外
set_error_handler('error_handler', E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
// for php 4
//set_error_handler('error_handler');

function error_handler($errno, $errstr, $errfile, $errline) {
  global $d_site_title, $d_header, $d_footer;
  
  // PHP 8.2対応: 非推奨エラーは無視
  if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
    return true;
  }

  $errlev = array(
    E_USER_ERROR=>'FATAL',
    E_ERROR=>'FATAL',
    E_USER_WARNING=>'WARNING',
    E_WARNING=>'WARNING',
    E_USER_NOTICE=>'NOTICE',
    E_NOTICE=>'NOTICE',
    E_DEPRECATED=>'DEPRECATED',
    E_USER_DEPRECATED=>'DEPRECATED',
    E_STRICT=>'STRICT',
  );
  
  ob_start();
  debug_print_backtrace();
  $trace = ob_get_clean();
  
  if(empty($_SESSION)) {
    $_SESSION = array();
  }
  
  // エラータイプを取得（未定義の場合はUNKNOWNとする）
  $errorType = isset($errlev[$errno]) ? $errlev[$errno] : 'UNKNOWN(' . $errno . ')';
  
  $msg = 'DATE: ' . date('Y-m-d H:i:s') . PHP_EOL . 
         'TYPE: ' . $errorType . PHP_EOL . 
         'FILE: ' . $errfile . PHP_EOL . 
         'LINE: ' . $errline . PHP_EOL . 
         'ERROR: ' . $errstr . PHP_EOL . 
         '-----------------------------------------' . PHP_EOL .
         $trace . PHP_EOL . 
         '-------------$_GET-----------------------' . PHP_EOL . 
         print_r($_GET, true) . PHP_EOL . 
         '-------------$_POST---------------------------' . PHP_EOL .
         print_r($_POST, true) . PHP_EOL . 
         '-------------$_COOKIE----------------------------' . PHP_EOL .
         print_r($_COOKIE, true) . PHP_EOL . 
         '-------------$_SESSION----------------------------' . PHP_EOL .
         print_r($_SESSION, true) . PHP_EOL . 
         '-------------$_SERVER----------------------------' . PHP_EOL .
         print_r($_SERVER, true) . PHP_EOL;

  // ローカル環境とプロダクション環境で異なるログパス
  $logPath = '/home/icotfeels/readnest.jp/log/dokusho_error_log.txt';
  if (strpos(__DIR__, '/Users/') === 0) {
    // ローカル環境の場合は標準エラーログに出力
    error_log($msg);
  } else {
    // プロダクション環境の場合はファイルに出力
    @error_log($msg, 3, $logPath);
  }
  
  if(defined('DEBUG')){
    echo '<pre>' . $msg . '</pre>';
  } else {
    ob_clean();
    
    // エラー情報を設定
    $error_title = 'システムエラー';
    $error_message = 'システムエラーが発生しました。申し訳ございませんが、しばらくしてから再度お試しください。';
    $error_code = '500';
    $error_file = $errfile;
    $error_line = $errline;
    $error_trace = $trace;
    
    // モダンテンプレートを優先的に使用
    if (file_exists(TPLDIR . 'modern/t_error.php')) {
      require TPLDIR . 'modern/t_error.php';
    } else {
      require TPLDIR . 't_error.php';
    }
  }
  exit;
}
?>