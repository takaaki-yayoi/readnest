<?php
require_once('config.php');

$tag_cloud = aggregateAllTag(5);
$d_tag_cloud = createAllTagList($tag_cloud);

print $d_tag_cloud;
?>