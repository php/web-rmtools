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

/* Each windows configuration from the ini for the given PHP version will be built */
foreach ($builds as $build_name) {

	$build_src_path = realpath($build_dir_parent . DIRECTORY_SEPARATOR . $branch->config->getBuildSrcSubdir());
	$log = rm\exec_single_log('mklink /J ' . $build_src_path . ' ' . $build_src_path);

	$build = $branch->createBuildInstance($build_name);

	try {
		$ext = new rm\PeclExt($ext_tgz, $build);
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
		continue;
	}

	// looks like php_http-2.0.0beta4-5.3-nts-vc9-x86
	$ext_build_name = $ext->getPackageName();


	$toupload_dir = TMP_DIR . '/' . $ext_build_name;
	if (!is_dir($toupload_dir)) {
		mkdir($toupload_dir, 0655, true);
	}

	if (!is_dir($toupload_dir . '/logs')) {
		mkdir($toupload_dir . '/logs', 0655, true);
	}

	echo "Preparing to build '$ext_build_name'\n";

	try {
		$build->setSourceDir($build_src_path);

		$ext->unpack();
		$ext->check();
		$ext->putSourcesIntoBranch();

	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
		$build->clean();
		$ext->cleanup();
		/*rm\rmdir_rf($toupload_dir);*/

		/* XXX mail the ext dev what the error was, if it's something in the check
			phase like missing config.w32, it's interesting for sure.
			and no sense to continue as something in ext setup went wrong */
		continue;
	}

	echo "Running build in <$build_src_path>\n";
	try {

		$build->buildconf();

		$ext_conf_line = $ext->getConfigureLine();
		echo "Extension specific config: $ext_conf_line\n";
		if ($branch->config->getPGO() == 1)  {
			echo "Creating PGI build\n";
			$build->configure(' "--enable-pgi" ' . $ext_conf_line);
		}
		else {
			$build->configure($ext_conf_line);
		}

		if (!preg_match(',^\|\s+' . $ext->getName() . '\s+\|\s+shared\s+\|,Sm', $build->log_configure)) {
			throw new Exception($ext->getName() . 'is not enabled, skip make phase');
		}

		$build->make();
		//$html_make_log = $build->getMakeLogParsed();
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
		echo $build->log_buildconf;
	}

	/* XXX PGO stuff would come here */

	$log_base = $toupload_dir . '/logs';
	file_put_contents($log_base . '/buildconf-' . $ext->getPackageName() . '.txt', $build->log_buildconf);
	file_put_contents($log_base . '/configure-' . $ext->getPackageName() . '.txt', $build->log_configure);
	file_put_contents($log_base . '/make-' . $ext->getPackageName() . '.txt', $build->log_make);

	$stats = $build->getStats();

	if ($stats['error'] > 0) {
		file_put_contents($log_base . '/error-' . $ext->getPackageName() . '.txt', $build->compiler_log_parser->getErrors());
	}

	try {
		$pkg_file = $ext->preparePackage();
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
	}

	/*rm\upload_build_result_ftp_curl($toupload_dir, $branch_name . '/r' . $last_rev);*/
	/* XXX mail the logs from above */

	$build->clean();
	$ext->cleanup();
	/*rm\rmdir_rf($toupload_dir);*/
}

echo "Done.\n";

