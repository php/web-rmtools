<?php
include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/Branch.php';
include __DIR__ . '/../include/Tools.php';

use rmtools as rm;

if ($argc < 2 || $argc > 4) {
	echo "Usage: snapshot <branch> <config name> [force 1/0]\n";
	exit();
}

$have_build_run = false;
$branch_name = $argv[1];
$build_type = "all"; /* $argv[2] */ /* Build both ts and NTS for one given arch. This also complies with the top level script, otherwise it'd need a special SDK setup. */
$force = isset($argv[3]) && $argv[3] ? true : false;
$sdk_arch = getenv("PHP_SDK_ARCH");
if (!$sdk_arch) {
	throw new \Exception("Arch is empty, the SDK might not have been setup. ");
}
$config_path = __DIR__ . '/../data/config/branch/' . $sdk_arch . '/' . $branch_name . '.ini';

$err_msg = NULL;
try {
	$branch = new rm\Branch($config_path);
} catch (\Exception $e) {
	$err_msg = $e->getMessage();
	goto out_here;
}

$branch_name = $branch->config->getName();
$branch_name_short = $branch->config->getBranch();

echo "Running <" . realpath($config_path) . ">\n";
echo "\t$branch_name\n";

if ($branch->hasNewRevision() || $branch->hasUnfinishedBuild() || $force) {


/* Prepared rewritten part, which might be helpful for PGO integration later. */
/*$builds_top = $branch->getBuildList('windows');
for ($i = 0; $i < count($builds_top) && ($force || $branch->hasNewRevision()); $i++) {
	if (preg_match(",(ts|nts),", $builds_top[$i], $m)) {
		$build_type = $m[0];
	} else {
		echo "Unknown build type '{$builds_top[$i]}', skip\n";
		continue;
	}*/
	
	try {
		if (!$branch->update()) {
			goto out_here;
		}
	} catch (\Exception $e) {
		$err_msg = $e->getMessage();
		goto out_here;
	}

	$last_rev = $branch->getLastRevisionId();
	$prev_rev = $branch->getPreviousRevision();

	echo "\tprevious revision was: $prev_rev\n";
	echo "\tlast revision is: $last_rev\n";
	
	$have_build_run = true;
	echo "processing revision $last_rev\n";

	$build_dir_parent = $branch->config->getBuildLocation();

	if (!is_dir($build_dir_parent)) {
		if(!mkdir($build_dir_parent, 0777, true)) {
			echo "Couldn't create build location";
			exit(-1);
		}
	}

	if (strlen($last_rev) == 40) {
		$last_rev = substr($last_rev, 0, 7);
	}
	$src_original_path =  $branch->createSourceSnap($build_type);

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
		if (strcmp($build_type, 'all') != 0) {
			if (substr_compare($build_name, $build_type, 0, 2) != 0) {  // i.e. nts-windows-vc9-x86
				continue;
			}
			else  {
				echo "Starting build for $build_name\n";
			}
		}

		$build_src_path = realpath($build_dir_parent) . DIRECTORY_SEPARATOR . $build_name;
		$log = rm\exec_single_log('mklink /J ' . $build_src_path . ' ' . $src_original_path);
		if (!file_exists($build_src_path)) {
			throw new \Exception("Couldn't link '$src_original_path' to '$build_src_path'");
		}

		$build = $branch->createBuildInstance($build_name);
		try {
			$build->setSourceDir($build_src_path);

			echo "Updating dependencies\n";
			/* XXX Pass stability from script arg. */
			$ret = $build->updateDeps("staging");
			echo $ret["log"] . "\n";

			echo "running build in <$build_src_path>\n";
			$build->buildconf();
			if ($branch->config->getPGO() == 1)  {
				/* For now it is enough to just get a very same
				build of PHP to setup the environment. This
				only needs to be done once for setup. In further
				also, if there are any difference with TS/NTS,
				 there might be some separate setup needed. */
				if (!$build->isPgoSetup()) {
					echo "Preparing PGO training environment\n";
					$build->configure();
					$build->make();
					$build->pgoInit();
				}
				echo "Creating PGI build\n";
				$build->configure(' "--enable-pgi" ');
			}
			else {
				$build->configure();
			}
			$build->make();
			/* $html_make_log = $build->getMakeLogParsed(); */
		} catch (Exception $e) {
			echo $e->getMessage() . "\n";
			echo $build->log_buildconf;
		}
		if ($branch->config->getPGO() == 1)  {
			echo "Creating PGO build\n";
			try {
				$build->pgoTrain();
				$build->make(' clean-pgo');
				$build->configure(' "--with-pgo" ', false);
				$build->make();
				$html_make_log = $build->getMakeLogParsed();
			} catch (Exception $e) {
				echo $e->getMessage() . "\n";
				echo $build->log_buildconf;
			}
		}

		try {
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
			$tmp = str_replace(array('-ts', '-nts'), array('',''),  $build_name);
			copy($build->test_path, $toupload_dir . '/php-test-pack-' . $branch_name_short . '-' . $tmp . '-r'. $last_rev . '.zip');
		}

		file_put_contents($toupload_dir . '/logs/buildconf-' . $build_name . '-r'. $last_rev . '.txt', $build->log_buildconf);
		file_put_contents($toupload_dir . '/logs/configure-' . $build_name . '-r'. $last_rev . '.txt', $build->log_configure);
		file_put_contents($toupload_dir . '/logs/make-'      . $build_name . '-r'. $last_rev . '.txt', $build->log_make);
		file_put_contents($toupload_dir . '/logs/archive-'   . $build_name . '-r'. $last_rev . '.txt', $build->log_archive);
		if ($branch->config->getPGO() == 1)  {
			file_put_contents($toupload_dir . '/logs/pgo-'   . $build_name . '-r'. $last_rev . '.txt', $build->log_pgo);
		}

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
			'has_test_pkg' => file_exists($build->test_path),
		);

		if ($stats['error'] > 0) {
			$has_build_errors = true;
			$build_errors[$build_name] = $build->compiler_log_parser->getErrors();
			$json_data['build_error'] = $build_errors[$build_name];
		}

		$json = json_encode($json_data);
		file_put_contents($toupload_dir . '/' . $json_filename, $json);
		
//			$build->clean();
		rmdir($build_src_path);
	}

	/* Only upload once, and then cleanup. */
	if ($branch->requiredBuildRunsReached()) {
		$src_dir = $branch_name . '/r' . $last_rev;
		rm\upload_build_result_ftp_curl($toupload_dir, $src_dir);
		rm\rmdir_rf($toupload_dir);
	}
	
	$branch->setLastRevisionExported($last_rev);

}

out_here:
if ($err_msg) {
	echo "$err_msg\n";
} else if (!$have_build_run) {
	echo "no new revision.\n";
}

if ($have_build_run) {
	/*Upload the branch DB */
	$try = 0;
	do {
		$status = rm\upload_file_curl($branch->db_path, $branch_name . '/' . basename($branch->db_path));
		$try++;
	} while ( $status === false && $try < 10 );
}

//if ($has_build_errors) {
//	rm\send_error_notification($branch_name, $build_errors, $branch->getPreviousRevision(), $last_rev, 'http://windows.php.net/downloads/snaps/' . $branch_name . '/r' . $last_rev);
//} else {
	/* if no error, let update the snapshot page */
//	rm\update_snapshot_page();
//}

echo "Done.\n";
