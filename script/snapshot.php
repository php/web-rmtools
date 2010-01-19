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

if ($argc == 3) {
	$filename = filter_var($argv[2], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
} else {
	$filename = FALSE;
}

if ($release == 'all') {
	try {
		$base = new rm\Base;
		$releases = $base->getAllReleases();
		print_r($releases);
		foreach ($releases as $release) {
			$svn = new rm\Storage($release);
			$svn->createSnapshot($filename, true);
		}
	} catch (Exception $e) {
		echo 'An error occured: ',  $e->getMessage(), "\n";
	}
} else {
	try {
		$svn = new rm\Storage($release);
		$filename = $svn->createSnapshot($filename);
	} catch (Exception $e) {
		echo 'An error occured: ',  $e->getMessage(), "\n";
	}
}
echo "done.\n";
