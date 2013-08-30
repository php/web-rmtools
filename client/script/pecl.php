<?php
include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PeclBranch.php';
include __DIR__ . '/../include/Tools.php';
include __DIR__ . '/../include/PeclExt.php';

use rmtools as rm;

if ($argc < 3 || $argc > 4) {
	echo "Usage: snapshot <config name> </path/to/ext/tgz>\n";
	exit();
}

$branch_name = $argv[1];
$ext_tgz = $argv[2];
$config_path = __DIR__ . '/../data/config/pecl/' . $branch_name . '.ini';


$branch = new rm\PeclBranch($config_path);

$branch_name = $branch->config->getName();

echo "Running <$config_path>\n";
echo "\t$branch_name\n";

$build_dir_parent = $branch->config->getBuildLocation();

if (!is_dir($build_dir_parent)) {
	echo "Invalid build location <$build_dir_parent>\n";
	exit(-1);
}

$builds = $branch->getBuildList('windows');

$has_build_errors = false;
$build_errors = array();

/* Each windows configuration from the ini for the given PHP version will be built */
foreach ($builds as $build_name) {

	$build_src_path = realpath($build_dir_parent . DIRECTORY_SEPARATOR . $branch->config->getBuildSrcSubdir());
	$log = rm\exec_single_log('mklink /J ' . $build_src_path . ' ' . $build_src_path);

	$build = $branch->createBuildInstance($build_name);

	try {
		$ext = new rm\PeclExt($ext_tgz, $build);
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
		exit(3);
	}

	// looks like php_http-2.0.0beta4-5.3-nts-vc9-x86
	$ext_build_name = $ext->getPackageName();

	echo "Starting build for $ext_build_name\n";
	echo "running build in <$build_src_path>\n";

	$toupload_dir = TMP_DIR . '/' . $ext_build_name;
	if (!is_dir($toupload_dir)) {
		mkdir($toupload_dir, 0655, true);
	}

	if (!is_dir($toupload_dir . '/logs')) {
		mkdir($toupload_dir . '/logs', 0655, true);
	}

	try {
		$build->setSourceDir($build_src_path);
		$ext->putSourcesIntoBranch();exit();
		$build->buildconf();
		if ($branch->config->getPGO() == 1)  {
			echo "Creating PGI build\n";
			$build->configure(' "--enable-pgi" ');
		}
		else {
			$build->configure();
		}
		$build->make();
		$html_make_log = $build->getMakeLogParsed();
		//$build->makeArchive();
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
		echo $build->log_buildconf;
	}
	continue;

	/* PGO stuff would come here */

	/*$stats = $build->getStats();

	if ($stats['error'] > 0) {
		$has_build_errors = true;
	}

	rm\upload_build_result_ftp_curl($toupload_dir, $branch_name . '/r' . $last_rev);*/
	$build->clean();
	//rmdir($build_src_path);
}

/*Upload the branch DB */
/*$try = 0;
do {
	$status = rm\upload_file_curl($branch->db_path, $branch_name . '/' . basename($branch->db_path));
	$try++;
} while ( $status === false && $try < 10 );*/

echo "Done.\n";

