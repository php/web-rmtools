<?php

include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PickleBranch.php';
include __DIR__ . '/../include/Pickle.php';
include __DIR__ . '/../include/Tools.php';
//include __DIR__ . '/../include/PackagistExt.php';

use rmtools as rm;


/* parametrize */
$branch_name = "packagist70";

$config_path = __DIR__ . '/../data/config/packagist/' . $branch_name . '.ini';

$branch = new rm\PickleBranch($config_path);

$branch_name = $branch->config->getName();
$builds = $branch->getBuildList('windows');

$pickle = new rm\Pickle();



//var_dump($pickle);
//var_dump($branch_name);
//var_dump($builds);

foreach ($builds as $build_name) {

	echo "Starting build" . PHP_EOL;

	//$build_config = $branch->config->getBuildFromName($build_name);

	$build = $branch->createBuildInstance($build_name);

	var_dump($build_name);
	var_dump($build);
	//var_dump($build_config);
}

exit(0);

