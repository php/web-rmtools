<?php
namespace rmtools;

$rmtools_base = getenv('RMTOOLS_BASE_DIR');

if (!$rmtools_base) {
	$rmtools_base = '/home/web/rmtools.php.net';
}

define('TMP_DIR', $rmtools_base . '/tmp');
