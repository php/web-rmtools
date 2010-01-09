<?php
set_include_path('.:/home/web/rmtools.php.net/include');
error_reporting(E_ALL|E_NOTICE);

include 'config.php';
include 'Storage.php';
include 'Base.php';

use rmtools as rm;

$svn = new rm\Storage('5.3.2');
$json = $svn->exportAsJson();

//$json = '{"replyCode":201, "replyText":"Data follows","data": ' . $json . '}';
//var_dump(json_decode($json));
echo $json;
