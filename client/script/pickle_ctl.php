<?php

include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PickleWeb.php';
include __DIR__ . '/../include/PickleDb.php';
include __DIR__ . '/../include/PickleJob.php';

use rmtools as rm;

$sync_host = "http://aa382bdf.ngrok.io";
$db_dir = __DIR__ . '/../data/pickle_db';
$job_dir = 'c:\pickle-in-job';

$longopts = array("help", "sync", "init-db");

$options = getopt(NULL, $longopts);

$help_cmd = isset($options['help']);
$sync_cmd = isset($options['sync']);

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


if ($sync_cmd) {

	

	try {
		$aa = new rm\PickleJob($job_dir);
		/* XXX handle the finished jobs first. */
		// $aa->cleanup();

		$pw = new rm\PickleWeb($sync_host, new rm\PickleDb($db_dir));

		if (!$pw->updatesAvailable()) {
			echo "No updates available";
			exit(0);
		}

		$news = (array)$pw->getNewTags();

		foreach ($news as $job) {
			$aa->add($job);
		}
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
		exit(3);
	}

}



exit(0);

