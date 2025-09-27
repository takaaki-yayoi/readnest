<?php
if(!defined('CONFIG')) {
  error_log('direct access detected. file:' . __FILE__ . ' line:' . __LINE__ . ' ip:' . $_SERVER['REMOTE_ADDR'] . ' agent:' . $_SERVER['HTTP_USER_AGENT']);
}

define('SECTION_COLOR1', '#99ff99');

function checkMobileType() {

  if(strstr($_SERVER['HTTP_USER_AGENT'],"DoCoMo")){
    $type = 'docomo';
  } else if (strstr($_SERVER['HTTP_USER_AGENT'],"SoftBank")){
    $type = 'sb';
  } else if (strstr($_SERVER['HTTP_USER_AGENT'],"KDDI")){
    $type = 'ez';
  } else if(strstr($_SERVER['HTTP_USER_AGENT'],"UP.Browser")){
    $type = 'ez';
  } else {
    $type = 'pc';
  }

  return $type;
}

?>