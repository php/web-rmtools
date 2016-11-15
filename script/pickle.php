<?php

include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PickleBranch.php';
include __DIR__ . '/../include/Tools.php';
include __DIR__ . '/../include/PickleExt.php';
include __DIR__ . '/../include/PickleJob.php';

use rmtools as rm;


$longopts = array("config:", "package:", "job:", "upload", "is-snap", "first", "last");

$options = getopt(NULL, $longopts);

$branch_name = isset($options['config']) ? $options['config'] : NULL;
$pkg_path = isset($options['package']) ? $options['package'] : NULL;
$job_path = isset($options['job']) ? $options['job'] : NULL;
$upload = isset($options['upload']);
$is_snap = isset($options['is-snap']);
$is_last_run = isset($options['last']);
$is_first_run = isset($options['first']);

if (NULL == $branch_name || (NULL == $pkg_path && NULL == $job_path)) {
	echo "Usage: pickle.php [OPTION] ..." . PHP_EOL;
	echo "  --config         Configuration file name without suffix, required." . PHP_EOL;
	echo "  --package        Path to the package source, either this or --job required." . PHP_EOL;
	echo "  --job            Path to the package source, either this or --package required." . PHP_EOL;
	echo "  --upload         Upload the builds to the windows.php.net, optional." . PHP_EOL;
	echo "  --is-snap        We upload to releases by default, but this one goes to snaps, optional." . PHP_EOL;
	echo "  --first          This call is the first in the series for the same package file, optional." . PHP_EOL;
	echo "  --last           This call is the last in the series for the same package file, optional." . PHP_EOL;
	echo PHP_EOL;
	echo "Examples: " . PHP_EOL;
	echo PHP_EOL;
	echo "Just build, binaries and logs will stay in TMP_DIR" . PHP_EOL;
	echo "pecl --config=pickle --package=someext" . PHP_EOL;
	echo PHP_EOL;
	echo "Build and upload to windows.php.net/pickle/releases/some/1.0.0/" . PHP_EOL;
	echo "pecl --config=pickle70 --upload --package=some-1.0.0" . PHP_EOL;
}

if (NULL == $pkg_path) {
	/* TODO implement for PickleJob*/
	try {
		$job_data = rm\PickleJob::loadData($job_path);

		$pkg_path = $job_data["src"];
	} catch (Exception $e) {
		echo $e->getMessage();
		exit(3);
	}
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

$upload_status = array();

foreach ($builds as $build_name) {

	$build_error = 0;

	echo "Starting build $build_name" . PHP_EOL;

	//$build_config = $branch->config->getBuildFromName($build_name);

	try {
		$build = $branch->createBuildInstance($build_name);
		$ext = new rm\PickleExt($pkg_path, $build);
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;

		/* send error mail*/

		continue;
	}

	try {
		$ext->init();
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;

		/* send error mail*/

		continue;
	}

	echo "Starting pickle build" . PHP_EOL;

	try {
		$cmd = $build->getPickleCmdToRun($ext);
		echo "Pickle command is " . PHP_EOL . " $cmd" . PHP_EOL;

		$ret = $build->build($ext);
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;

		$ret = array("return_value" => 1, "log" => "");
		/* send error mail*/

		$build_error++;
	}

	if ($ret["return_value"]) {
	
		$build->clean();
		$ext->cleanup();
		$was_errors = true;

		echo "Build failed" . PHP_EOL;
		echo $ret["log"];

		$build_error++;
	}

	/* XXX check zipballs path before saying this */
	echo "Pickle build successful" . PHP_EOL;

	// add the deps license to the pickle archive ... or should pickle do that?

	/* upload logs and builds */

	$fl_base = "php_" . $ext->getName() . "-" . $ext->getVersion() . "-" . $build->branch->config->getBranch() . "-" . ($build->thread_safe ? "ts" : "nts") . "-" . $build->compiler . "-" . $build->architecture;

	$logs_zip = realpath(TMP_DIR . DIRECTORY_SEPARATOR . "$fl_base-logs.zip");
	$pkg_file = realpath(TMP_DIR . DIRECTORY_SEPARATOR . "$fl_base.zip");

	$upload_success = true;
	if ($upload) {
		try {
			$root = $is_snap ? 'snaps' : 'releases';
			$frag = $ext->getVendor() . "/" . $ext->getName();
			$target = '/' . $root . '/' .  $frag . '/' . $ext->getVersion();

			$pkgs_to_upload = $build_error ? array() : array($pkg_file);

			if ($build_error) {
				echo "Uploading logs" . PHP_EOL;
			} else {
				echo "Uploading '$pkg_file' and logs" . PHP_EOL;
			}

			if ($build_error && !file_exists($logs_zip)) {
				throw new Exception("Logs wasn't packaged, nothing to upload");
			}

			if (rm\upload_pickle_pkg_ftp_curl($pkgs_to_upload, array($logs_zip), $target)) {
				echo "Upload succeeded" . PHP_EOL;
				if (file_exists($logs_zip)) {
					unlink($logs_zip);
				}
				if (file_exists($pkg_file)) {
					unlink($pkg_file);
				}
			} else {
				throw new Exception("Upload failed");
			}
		} catch (Exception $e) {
			echo 'Error . ' . $e->getMessage() . PHP_EOL;
			$upload_success = false;
		}
	}
	/* keep recording upload status for every package, the code at the end is going to evaluate it  like in pecl. */
	$upload_status[$build_name] = $upload_success;

	/* notify pickle */

	echo PHP_EOL;
}

if ($is_last_run) {
	if (file_exists($job_path)) {
		unlink($job_path);
	}

	if (file_exists($pkg_path)) {
		unlink($pkg_path);
	}
}

exit(0);

