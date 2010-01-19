<?php
include __DIR__ . '/../include/config.php';

error_reporting(E_ALL|E_NOTICE);

include 'Storage.php';
include 'Base.php';

use rmtools as rm;

if ($argc < 2) {
	echo "Usage: updaterelease <release name>\n";
	exit();
}

$release = filter_var($argv[1], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);

if ($release == 'all') {
	try {
		$base = new rm\Base;
		$releases = $base->getAllReleases();
		foreach ($releases as $release) {
			$svn = new rm\Storage($release);
			$svn->updateRelease();
		}
	} catch (Exception $e) {
		echo 'An error occured: ',  $e->getMessage(), "\n";
	}

} else {
	try {
		$svn = new rm\Storage($release);
		$svn->updateRelease();
	} catch (Exception $e) {
		echo 'An error occured: ',  $e->getMessage(), "\n";
	}

}
