<?php
include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PeclBranch.php';
include __DIR__ . '/../include/Tools.php';
include __DIR__ . '/../include/PeclExt.php';

use rmtools as rm;


$shortopts = NULL; //"c:p:mu";
$longopts = array("config:", "package:", "mail", "upload");

$options = getopt($shortopts, $longopts);

$branch_name = isset($options['config']) ? $options['config'] : NULL;
$ext_tgz = isset($options['package']) ? $options['package'] : NULL;
$mail_maintainers = isset($options['mail']);
$upload = isset($options['upload']);

if (NULL == $branch_name || NULL == $ext_tgz) {
	echo "Usage: pecl.php [OPTION] ...\n";
	echo "  --config     Configuration file name without suffix, required.\n";
	echo "  --package    Path to the PECL package, required.\n";
	echo "  --mail       Send build logs to the extension maintainers, optional\n";
	echo "  --upload     Upload the builds to the windows.hpp.net, optional\n";
	echo "\n";
	echo "Example: pecl --config=php55_x64 --package=c:\pecl_in_pkg\some-1.0.0.tgz\n";
	echo "\n";
	exit(0);
}

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

echo "Using <$ext_tgz>\n";

/* Each windows configuration from the ini for the given PHP version will be built */
foreach ($builds as $build_name) {

	echo "Preparing to build \n";

	$build_src_path = realpath($build_dir_parent . DIRECTORY_SEPARATOR . $branch->config->getBuildSrcSubdir());
	$log = rm\exec_single_log('mklink /J ' . $build_src_path . ' ' . $build_src_path);

	$build = $branch->createBuildInstance($build_name);
	$build->setSourceDir($build_src_path);

	try {
		$ext = new rm\PeclExt($ext_tgz, $build);
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
		$build->clean();

		/* XXX mail the ext dev what the error was, if it's something in the check
			phase like missing config.w32, it's interesting for sure.
			and no sense to continue as something in ext setup went wrong */
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

 	echo "Configured for '$ext_build_name'\n";
	echo "Running build in <$build_src_path>\n";
	try {
		$ext->putSourcesIntoBranch();

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
			throw new Exception($ext->getName() . ' is not enabled, skip make phase');
		}

		$build->make();
		//$html_make_log = $build->getMakeLogParsed();
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
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
	rm\rmdir_rf($toupload_dir);

	echo "\n";
}

echo "Done.\n";

