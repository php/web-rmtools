<?php
include __DIR__ . '/../../include/config.php';
error_reporting(E_ALL|E_NOTICE);

include 'Storage.php';
include 'Base.php';

use rmtools as rm;
$release_name = filter_input(INPUT_GET, 'release', FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$svn = new rm\Storage($release_name);
$json = $svn->exportAsJson();

//$json = '{"replyCode":201, "replyText":"Data follows","data": ' . $json . '}';
//var_dump(json_decode($json));
echo $json;
