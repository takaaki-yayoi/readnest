<?php
require_once('config.php');

//define('DEBUG', 1);

if(isset($_GET['mode'])) {
  $mode = $_GET['mode'];
} else {
  $mode = '';
}


if(isset($_GET['user_id'])) {
  $user_id = $_GET['user_id'];
  $user_array = getUserInformation($user_id);
} else {
  exit;
}

if($user_array != NULL) {
  $profile_photo = $user_array['photo'];
  $profile_photo_state = $user_array['photo_state'];
  $profile_photo_mime = $user_array['photo_mime'];
    
  if($profile_photo != NULL) {
    
    header("Content-type: $profile_photo_mime");

    if($mode == 'thumbnail') {

      // データベースから直接画像を読み込む
      $image = imagecreatefromstring($profile_photo);
      if (!$image) {
        exit;
      }
      
      $width = ImageSX($image); //横幅（ピクセル）
      $height = ImageSY($image); //縦幅（ピクセル）
      
      // サムネイルサイズを150ピクセルに変更（より高解像度に）
      $new_width = 150;
      $rate = $new_width / $width; //圧縮比
      $new_height = $rate * $height;
      
      $new_image = ImageCreateTrueColor($new_width, $new_height);
      
      // ImageCopyResampledを使用して高品質なリサイズ
      ImageCopyResampled($new_image,$image,0,0,0,0,$new_width,$new_height,$width,$height);

      if($profile_photo_mime == 'image/jpeg') {
        // JPEG品質を90に設定
        ImageJPEG($new_image, null, 90);
      } else if($profile_photo_mime == 'image/gif') {
        ImageGIF($new_image);
      } else if($profile_photo_mime == 'image/png') {
        // PNG圧縮レベルを1に設定（0-9、低いほど高品質）
        ImagePNG($new_image, null, 1);
      } else {
        exit;
      }

    } else if($mode == 'icon') {

      // データベースから直接画像を読み込む
      $image = imagecreatefromstring($profile_photo);
      if (!$image) {
        exit;
      }
      
      $width = ImageSX($image); //横幅（ピクセル）
      $height = ImageSY($image); //縦幅（ピクセル）
      
      // アイコンサイズを48ピクセルに変更（Retina対応のため大きめに）
      $new_width = 48;
      $rate = $new_width / $width; //圧縮比
      $new_height = $rate * $height;
      
      $new_image = ImageCreateTrueColor($new_width, $new_height);
      
      // ImageCopyResampledを使用して高品質なリサイズ
      ImageCopyResampled($new_image,$image,0,0,0,0,$new_width,$new_height,$width,$height);

      if($profile_photo_mime == 'image/jpeg') {
        // JPEG品質を90に設定
        ImageJPEG($new_image, null, 90);
      } else if($profile_photo_mime == 'image/gif') {
        ImageGIF($new_image);
      } else if($profile_photo_mime == 'image/png') {
        // PNG圧縮レベルを1に設定（0-9、低いほど高品質）
        ImagePNG($new_image, null, 1);
      } else {
        exit;
      }
      
    } else {
      print $profile_photo;
    }
  }
}
?>