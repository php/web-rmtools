<?php

include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PickleBranch.php';
include __DIR__ . '/../include/Tools.php';
include __DIR__ . '/../include/PickleExt.php';

use rmtools as rm;


/* parametrize */
$branch_name = "pickle70";
$pkg_path = "c:\\tmp\\varnish-1.2.1.tgz";

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

	$build = $branch->createBuildInstance($build_name);
	$ext = new rm\PickleExt($pkg_path, $build);

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

	echo "Pickle build successful, packaging logs" . PHP_EOL;

	//$build->packLogs();
	//$build->archive();

	/* upload logs and builds */
	/* notify pickle */

	//var_dump($build_name);
	//var_dump($build);
	//var_dump($ext);
	//var_dump($build_config);


	echo PHP_EOL;
}

exit(0);

