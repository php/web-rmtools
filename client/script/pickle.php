<?php

include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PickleBranch.php';
include __DIR__ . '/../include/Tools.php';
include __DIR__ . '/../include/PickleExt.php';

use rmtools as rm;


$longopts = array("config:", "package:", "upload", "is-snap", "first", "last");

$options = getopt(NULL, $longopts);

$branch_name = isset($options['config']) ? $options['config'] : NULL;
$pkg_path = isset($options['package']) ? $options['package'] : NULL;
$upload = isset($options['upload']);
$is_snap = isset($options['is-snap']);
$is_last_run = isset($options['last']);
$is_first_run = isset($options['first']);

if (NULL == $branch_name || NULL == $pkg_path) {
	echo "Usage: pickle.php [OPTION] ..." . PHP_EOL;
	echo "  --config         Configuration file name without suffix, required." . PHP_EOL;
	echo "  --package        Path to the PECL package, required." . PHP_EOL;
	echo "  --upload         Upload the builds to the windows.php.net, optional." . PHP_EOL;
	echo "  --is-snap        We upload to releases by default, but this one goes to snaps, optional." . PHP_EOL;
	echo "  --first          This call is the first in the series for the same package file, optional." . PHP_EOL;
	echo "  --last           This call is the last in the series for the same package file, optional." . PHP_EOL;
	echo PHP_EOL;
}



$config_path = __DIR__ . '/../data/config/pickle/' . $branch_name . '.ini';

$branch = new rm\PickleBranch($config_path);

$branch_name = $branch->config->getName();
$builds = $branch->getBuildList('windows');


//var_dump($pickle);
//var_dump($branch_name);
//var_dump($builds);

$was_errors = false;

echo "Using <$pkg_path>" . PHP_EOL . PHP_EOL;

foreach ($builds as $build_name) {

	echo "Starting build" . PHP_EOL;

	//$build_config = $branch->config->getBuildFromName($build_name);

	try {
		$build = $branch->createBuildInstance($build_name);
		$ext = new rm\PickleExt($pkg_path, $build);
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;

		/* send error mail*/

		continue;
	}

	$ext->init();

	echo "Running pickle build" . PHP_EOL;

	$ret = $build->build($ext);
	if ($ret["return_value"]) {
	
		/* XXX build->clean() is gonna remove all the temp files, pack the logs before */
		$build->clean();
		//$ext->cleanup();
		$was_errors = true;

		unset($ext);
		unset($build);

		echo $ret["log"];

		continue;
	}

	/* XXX check zipballs path before saying this */
	echo "Pickle build successful" . PHP_EOL;

	// add the deps license to the pickle archive ... or should pickle do that?

	/* upload logs and builds */
	/* notify pickle */

	//var_dump($build_name);
	//var_dump($build);
	//var_dump($ext);
	//var_dump($build_config);


	echo PHP_EOL;
}

exit(0);

