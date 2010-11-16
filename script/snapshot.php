<?php
include __DIR__ . '/../include/config.php';
error_reporting(E_ALL|E_NOTICE);

include 'Storage.php';
include 'Base.php';

use rmtools as rm;

if ($argc < 2 || $argc > 3) {
	echo "Usage: snapshot <release name, or all for all releases> <snap absolute path>\n";
	exit();
}

$release = filter_var($argv[1], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
$force = isset($argv[2]) && $argv[2] == 'force' ? true : false;

if (!$force && $argc == 3) {
	$filename = filter_var($argv[2], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
} else {
	$filename = FALSE;
}

if (!is_dir(SNAPS_PATH)) {
	if (!mkdir(SNAPS_PATH)) {
		exit('Snap dir does not exist and cannot be created: ' . SNAPS_PATH);
	}
}

if ($release == 'all') {
	try {
		$base = new rm\Base;
		$releases = $base->getAllReleases();
		foreach ($releases as $release) {
			$svn = new rm\Storage($release);
			$svn->createSnapshot($filename, $force);
		}
	} catch (Exception $e) {
		echo 'An error occured: ',  $e->getMessage(), "\n";
	}
} else {
	try {
		$svn = new rm\Storage($release);
		$filename = $svn->createSnapshot($filename, $force);
	} catch (Exception $e) {
		echo 'An error occured: ',  $e->getMessage(), "\n";
	}
}
echo "done.\n";
