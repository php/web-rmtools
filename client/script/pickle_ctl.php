<?php

include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PickleDb.php';
include __DIR__ . '/../include/PickleWeb.php';

use rmtools as rm;

$sync_host = "http://16e0a43d.ngrok.io";
$db_path = __DIR__ . '/../data/pickle.sqlite';

$longopts = array("help", "sync", "init-db");

$options = getopt(NULL, $longopts);

$help_cmd = isset($options['help']);
$sync_cmd = isset($options['sync']);
$init_cmd = isset($options['init-db']);

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
	echo "Warning: the original DB will be overwritten! Press c^C to abort.\n";
	for ($i = 0; $i < 8; $i++) {
		echo ".";
		sleep(1);
	}
	echo "\n";
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

	try {
		$pw = new PickleWeb($sync_host);
		$info = $pw->fetchProviders();
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
		exit(3);
	}

	var_dump($info);

	$db = new rm\PickleDb($db_path);


}



exit(0);

