<?php
require_once('config.php');

// 公開日記表示
$d_disclosed = create_disclosed_diary_part(6);

print $d_disclosed;
?>