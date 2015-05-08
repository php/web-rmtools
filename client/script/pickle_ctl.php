<?php

include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PickleDb.php';
include __DIR__ . '/../include/PickleJob.php';

use rmtools as rm;

$sync_host = "http://9fe756ee.ngrok.io";
$base_uri = "/packages.json";
$db_path = __DIR__ . '/../data/pickle.sqlite';

$longopts = array("help", "sync", "init-db");

$options = getopt(NULL, $longopts);

$help_cmd = isset($options['help']);
$sync_cmd = isset($options['sync']);
$init_cmd = isset($options['sync']);

/* --help */
if ($_SERVER['argc'] <= 1 || $help_cmd) {
	echo "Usage: pickle_ctl.php [OPTION] ..." . PHP_EOL;
	echo "  --help          Show help and exit, optional." . PHP_EOL;
	echo "  --sync          Fetch new jobs from pickleweb, optional." . PHP_EOL;


	echo PHP_EOL;
	echo "Example: pickle_ctl --sync" . PHP_EOL;
	echo PHP_EOL;
	exit(0);
}

if ($init_cmd) {
	try {
		$db = new rm\PickleDb($db_path, true);
		echo "Pickle DB initialized";
		exit(0);
	} catch (Exception $e) {
		echo "Error: " . $e->getMessage();
		exit(3);
	}
}

if ($sync_cmd) {
	$url = "$sync_host$base_uri";
	$__tmp = file_get_contents($url);

	if (!$__tmp) {
		echo "Empty content received from '$url'";
		exit(3);
	}

	$info = json_decode($__tmp, true);
	if (!$info) {
		echo "Couldn't decode JSON from '$url'";
		exit(3);
	}

	if (!isset($info["provider-includes"])) {
		echo "No provider includes found";
		exit(3);
	}

	$db = new rm\PickleDb($db_path);

	/* XXX What meaning does the $hash have? */
	foreach ($info["provider-includes"] as $uri => $hash) {
		$url = "$sync_host$uri";
		$__tmp = file_get_contents($url);

		if (!$__tmp) {
			echo "Empty content received from '$url'";
			continue;
		}

		$pkgs = json_decode($__tmp, true);
		if (!is_array($pkgs) || !isset($pkgs["providers"]) || !is_array($pkgs["providers"]) || empty($pkgs["providers"])) {
			echo "No packages provided from '$url'";
			continue;
		}
		$pkgs = $pkgs["providers"];

		foreach ($pkgs as $pkg => $phash) {

		}

		var_dump($pkgs);
	}

	var_dump($info);
}



exit(0);

