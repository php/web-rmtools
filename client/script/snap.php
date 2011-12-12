<?php
include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/Branch.php';
include __DIR__ . '/../include/Tools.php';

use rmtools as rm;

if ($argc < 2 || $argc > 3) {
	echo "Usage: snapshot <config name> [force 1/0]\n";
	exit();
}

$new_rev = false;
$branch_name = $argv[1];
$force = isset($argv[2]) ? true : false;
$config_path = __DIR__ . '/../data/config/branch/' . $branch_name . '.ini';

$branch = new rm\Branch($config_path);
$branch->update();

$branch_name = $branch->config->getName();
$branch_name_short = $branch->config->getBranch();
$last_rev = $branch->getLastRevisionId();

echo "Running <$config_path>\n";
echo "\t$branch_name\n";
echo "\tprevious revision was: " . $branch->getPreviousRevision() . "\n";
echo "\tlast revision is: " . $branch->getLastRevisionId() . "\n";

if ($force || $branch->hasNewRevision()) {
	if ($force || $last_rev != $branch->getLastRevisionExported()) {
		$new_rev = true;
		echo "processing revision $last_rev\n";
		$src_original_path =  $branch->createSourceSnap();
		$branch->setLastRevisionExported($last_rev);

		$build_dir_parent = $branch->config->getBuildLocation();

		if (!is_dir($build_dir_parent)) {
			echo "Invalid build location <$build_dir_parent>\n";
			exit(-1);
		}

		$toupload_dir = TMP_DIR . '/' . $branch_name . '/r' . $last_rev . '-builds/';
		if (!is_dir($toupload_dir)) {
			mkdir($toupload_dir, 0655, true);
		}

		if (!is_dir($toupload_dir . '/logs')) {
			mkdir($toupload_dir . '/logs', 0655, true);
		}

		copy($src_original_path . '.zip', $toupload_dir . '/' . $branch_name . '-src-r'. $last_rev . '.zip');

		$builds = $branch->getBuildList('windows');

		$has_build_errors = false;
		$build_errors = array();

		foreach ($builds as $build_name) {
			$build_src_path = realpath($build_dir_parent) . DIRECTORY_SEPARATOR . $build_name;
			$log = rm\exec_single_log('mklink /J ' . $build_src_path . ' ' . $src_original_path);

			$build = $branch->createBuildInstance($build_name);
			echo "running build in <$build_src_path>\n";
			try {
				$build->setSourceDir($build_src_path);
				$build->buildconf();
				$build->configure();
				$build->make();
				$html_make_log = $build->getMakeLogParsed();
				$build->makeArchive();
			} catch (Exception $e) {
				echo $e->getMessage() . "\n";
				echo $build->log_buildconf;
			}

			if ($build->archive_path) {
				copy($build->archive_path, $toupload_dir . '/php-' . $branch_name_short . '-' . $build_name . '-r'. $last_rev . '.zip');
			}
			if ($build->archive_path) {
				copy($build->devel_path, $toupload_dir . '/php-devel-pack-' . $branch_name_short . '-' . $build_name . '-r'. $last_rev . '.zip');
			}
			if ($build->archive_path) {
				copy($build->debug_path, $toupload_dir . '/php-debug-pack-' . $branch_name_short . '-' . $build_name . '-r'. $last_rev . '.zip');
			}
			if ($build->test_path) {
				copy($build->test_path, $toupload_dir . '/php-test-pack-' . $branch_name_short . '-' . $build_name . '-r'. $last_rev . '.zip');
			}

			file_put_contents($toupload_dir . '/logs/buildconf-' . $build_name . '-r'. $last_rev . '.txt', $build->log_buildconf);
			file_put_contents($toupload_dir . '/logs/configure-' . $build_name . '-r'. $last_rev . '.txt', $build->log_configure);
			file_put_contents($toupload_dir . '/logs/make-'      . $build_name . '-r'. $last_rev . '.txt', $build->log_make);
			file_put_contents($toupload_dir . '/logs/archive-'   . $build_name . '-r'. $last_rev . '.txt', $build->log_archive);

			$html_make_log = $build->getMakeLogParsed();
			file_put_contents($toupload_dir . '/logs/make-' . $build_name . '-r'. $last_rev . '.html', $html_make_log);
			copy(__DIR__ . '/../template/log_style.css', $toupload_dir . '/logs/log_style.css');

			$stats = $build->getStats();

			$json_filename = $build_name . '.json';

			$json_data = array(
				'stats' => $stats,
				'has_php_pkg'   => file_exists($build->archive_path),
				'has_debug_pkg' => file_exists($build->debug_path),
				'has_devel_pkg' => file_exists($build->devel_path),
			);

			if ($stats['error'] > 0) {
				$has_build_errors = true;
				$build_errors[$build_name] = $build->compiler_log_parser->getErrors();
				$json_data['build_error'] = $build_errors[$build_name];
			}

			$json = json_encode($json_data);
			file_put_contents($toupload_dir . '/' . $json_filename, $json);

			rm\upload_build_result_ftp_curl($toupload_dir, $branch_name . '/r' . $last_rev);
//			$build->clean();
//			rmdir($build_src_path);
		}
	}
}

if (!$new_rev) {
	echo "no new revision.\n";
}

/*Upload the branch DB */
//rm\upload_file($branch->db_path, $branch_name . '/' . basename($branch->db_path));

if ($has_build_errors) {
	rm\send_error_notification($branch_name, $build_errors, $branch->getPreviousRevision(), $last_rev, 'http://windows.php.net/downloads/snaps/' . $branch_name . '/r' . $last_rev);
} else {
	/* if no error, let update the snapshot page */
//	rm\update_snapshot_page();
}

echo "Done.\n";
