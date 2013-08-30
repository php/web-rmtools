<?php
include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PeclBranch.php';
include __DIR__ . '/../include/Tools.php';

use rmtools as rm;

if ($argc < 3 || $argc > 4) {
	echo "Usage: snapshot <config name> </path/to/ext/tgz>\n";
	exit();
}

$branch_name = $argv[1];
$ext_tgz = $argv[2];
$config_path = __DIR__ . '/../data/config/pecl/' . $branch_name . '.ini';

if (!file_exists($ext_tgz)) {
	echo "'$ext_tgz' does not exist\n";
	exit(3);
}
if ('.tgz' != substr($ext_tgz, -4)) {
	echo "Pecl package should end with .tgz\n";
	exit(3);
}
$tmp = explode('-', basename($ext_tgz, '.tgz'));
$ext_name = $tmp[0];
$ext_ver = $tmp[1];
unset($tmp);

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

	// looks like php_http-2.0.0beta4-5.3-nts-vc9-x86
	$ext_build_name = 'php_' . $ext_name
			. '-' . $ext_ver 
			. '-' . $branch_name
			. '-' . ($build->thread_safe ? 'ts' : 'nts')
			. '-' . $build->compiler
			. '-' . $build->architecture;
	
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
		continue;	
		$build->makeArchive();
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
		echo $build->log_buildconf;
	}
	continue;

	/* PGO stuff would come here */

	$stats = $build->getStats();

	$json_filename = $build_config['name'] . '.json';

	$json_data = array(
		'stats' => $stats,
		'has_php_pkg'   => file_exists($build->archive_path),
		'has_debug_pkg' => file_exists($build->debug_path),
		'has_devel_pkg' => file_exists($build->devel_path),
		'has_test_pkg' => file_exists($build->test_path),
	);

	if ($stats['error'] > 0) {
		$has_build_errors = true;
		$build_errors[$build_config['name']] = $build->compiler_log_parser->getErrors();
		$json_data['build_error'] = $build_errors[$build_config['name']];
	}

	$json = json_encode($json_data);
	file_put_contents($toupload_dir . '/' . $json_filename, $json);
	rm\upload_build_result_ftp_curl($toupload_dir, $branch_name . '/r' . $last_rev);
			$build->clean();
	rmdir($build_src_path);
}

/*Upload the branch DB */
/*$try = 0;
do {
	$status = rm\upload_file_curl($branch->db_path, $branch_name . '/' . basename($branch->db_path));
	$try++;
} while ( $status === false && $try < 10 );*/

echo "Done.\n";

