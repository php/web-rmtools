<?php
include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PeclBranch.php';
include __DIR__ . '/../include/Tools.php';
include __DIR__ . '/../include/PeclExt.php';
include __DIR__ . '/../include/PeclDb.php';
include __DIR__ . '/../include/PeclMail.php';

use rmtools as rm;


$shortopts = NULL; //"c:p:mu";
$longopts = array("config:", "package:", "mail", "aggregate-mail", "upload", "is-snap", "first", "last", "force-name:", "force-version:", "force-email:",);

$options = getopt($shortopts, $longopts);

$branch_name = isset($options['config']) ? $options['config'] : NULL;
$pkg_path = isset($options['package']) ? $options['package'] : NULL;
$mail_maintainers = isset($options['mail']);
$upload = isset($options['upload']);
$is_snap = isset($options['is-snap']);
$force_name = isset($options['force-name']) ? $options['force-name'] : NULL;
$force_version = isset($options['force-version']) ? $options['force-version'] : NULL;
$force_email = isset($options['force-email']) ? $options['force-email'] : NULL;
$aggregate_mail = isset($options['aggregate-mail']);
$is_last_run = isset($options['last']);
$is_first_run = isset($options['first']);

$mail_maintainers = $mail_maintainers || $aggregate_mail;

if (NULL == $branch_name || NULL == $pkg_path) {
	echo "Usage: pecl.php [OPTION] ..." . PHP_EOL;
	echo "  --config         Configuration file name without suffix, required." . PHP_EOL;
	echo "  --package        Path to the PECL package, required." . PHP_EOL;
	echo "  --mail           Send build logs to the extension maintainers, one per build, optional." . PHP_EOL;
	echo "  --aggregate-mail Save data so it can be sent to extension maintainers aggregated, optional." . PHP_EOL;
	echo "  --upload         Upload the builds to the windows.php.net, optional." . PHP_EOL;
	echo "  --is-snap        We upload to releases by default, but this one goes to snaps, optional." . PHP_EOL;
	echo "  --force-name     Force this name instead of reading the package data, optional." . PHP_EOL;
	echo "  --force-version  Force this version instead of reading the package data, optional." . PHP_EOL;
	echo "  --force-email    Send the results to this email instead of any from package.xml, optional." . PHP_EOL;
	echo "  --first          This call is the first in the series for the same package file, optional." . PHP_EOL;
	echo "  --last           This call is the last in the series for the same package file, optional." . PHP_EOL;
	echo PHP_EOL;
	echo "Examples: " . PHP_EOL;
	echo PHP_EOL;
	echo "Just build, binaries and logs will stay in TMP_DIR" . PHP_EOL;
	echo "pecl --config=php55_x64 --package=c:\pecl_in_pkg\some-1.0.0.tgz" . PHP_EOL;
	echo PHP_EOL;
	echo "Build and upload to windows.php.net/pecl/releases/some/1.0.0/" . PHP_EOL;
	echo "pecl --config=php55_x64 --upload --package=c:\pecl_in_pkg\some-1.0.0.tgz" . PHP_EOL;
	echo PHP_EOL;
	echo "Build, upload and mail results after each build" . PHP_EOL;
	echo "pecl --config=php55_x64 --upload --package=c:\pecl_in_pkg\some-1.0.0.tgz" . PHP_EOL;
	echo PHP_EOL;
	echo "Build, upload and send an aggregated mail over both build runs" . PHP_EOL;
	echo "pecl --config=php54 --upload --aggregate-mail --package=c:\pecl_in_pkg\some-1.0.0.tgz --first" . PHP_EOL;
	echo "pecl --config=php55_x64 --upload --aggregate-mail --package=c:\pecl_in_pkg\some-1.0.0.tgz" . PHP_EOL;
	echo "pecl --config=php55_x86 --upload --aggregate-mail --package=c:\pecl_in_pkg\some-1.0.0.tgz --last" . PHP_EOL;
	echo PHP_EOL;
	exit(0);
}

define('MAIL_FROM', 'pecl-dev@lists.php.net');
define('MAIL_TO_FALLBACK', 'ab@php.net');

$config_path = __DIR__ . '/../data/config/pecl/' . $branch_name . '.ini';

$branch = new rm\PeclBranch($config_path);

$branch_name = $branch->config->getName();

/* Init things if --first was given */
if ($is_first_run) {
	echo PHP_EOL;
	echo "First invocation for <$pkg_path> started." . PHP_EOL . PHP_EOL;

	if ($aggregate_mail) {
		try {
			/* Not sure it's needed anymore, but let it persist */
			$mailer = new rm\PeclMail($pkg_path, $aggregate_mail);
			$mailer->saveState();
			unset($mailer);
		} catch (Exception $e) {
			echo 'Error: ' . $e->getMessage() . PHP_EOL;
			$was_errors = true;
		}
	}
}


echo PHP_EOL;
echo "Run started for <" . realpath($config_path) . ">" . PHP_EOL;
echo "Branch <$branch_name>" . PHP_EOL;

$build_dir_parent = $branch->config->getBuildLocation();

if (!is_dir($build_dir_parent)) {
	if(!mkdir($build_dir_parent, 0777, true)) {
		echo "Couldn't create build location";
		exit(-1);
	}
}

$builds = $branch->getBuildList('windows');

/* be optimistic */
$was_errors = false;

echo "Using <$pkg_path>" . PHP_EOL . PHP_EOL;

$upload_status = array();

/* Each windows configuration from the ini for the given PHP version will be built */
foreach ($builds as $build_name) {

	$build_error = 0;

	echo "Preparing to build" . PHP_EOL;

	$build_src_path = realpath($build_dir_parent . DIRECTORY_SEPARATOR . $branch->config->getBuildSrcSubdir());
	$log = rm\exec_single_log('mklink /J ' . $build_src_path . ' ' . $build_src_path);

	try {
		$build = $branch->createBuildInstance($build_name);
		if (!$build) {
			throw new \Exception("Build instance failed to instantiate");
		}
		
		$build->setSourceDir($build_src_path);
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;
		isset($build) && $build->clean();
		$was_errors = true;

		unset($build);

		/* no sense to continue as something in ext setup went wrong */
		/* XXX maibe a mail should be sent to ostc or alike */
		continue;
	}


	try {
		$ext = new rm\PeclExt($pkg_path, $build);
		$mailer = new rm\PeclMail($pkg_path, $aggregate_mail);
		$ext->checkSkipBuild();
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;

		if ($mail_maintainers) {
			$maintainer_mailto = $force_email ? $force_email: MAIL_TO_FALLBACK;

			if (!$aggregate_mail) {
				echo "Mailing info to <$maintainer_mailto>" . PHP_EOL;
			}
			$mail_pkg_name = isset($ext) ? $ext->getPackageName() : basename($pkg_path);
			/* Not initialized yet, so no ->getPackageName() */
			if (isset($mailer) && $mailer) {
				$mailer->xmail(
					MAIL_FROM,
					/* no chance to have the maintainers mailto at this stage */
					$maintainer_mailto,
					'[PECL-DEV] Windows build: ' . basename($pkg_path),
					$mail_pkg_name . " not started\nReason: " . $e->getMessage()
				);
			} else {
				rm\xmail(
					MAIL_FROM,
					/* no chance to have the maintainers mailto at this stage */
					$maintainer_mailto,
					'[PECL-DEV] Windows build: ' . basename($pkg_path),
					$mail_pkg_name . " not started\nReason: " . $e->getMessage()
				);
			}
		}

		$build->clean();
		if (isset($ext)) {
			$ext->cleanup();
		}
		$was_errors = true;

		unset($ext);
		unset($build);

		/* no sense to continue as something in ext setup went wrong */
		continue;
	}

	try {
		$ext->init($force_name, $force_version);

		if ($ext->sendToCoventry()) {
			echo "As per config, ignoring <" . $ext->getName() . ">" . PHP_EOL;
			goto Coventry;
		}


		$ext->setupNonCoreExtDeps();
		$ext->putSourcesIntoBranch();
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;
		$was_errors = true;

		if ($mail_maintainers) {
			$maintainer_mailto = $force_email;
			if (!$maintainer_mailto) {
				$maintainer_mailto = $ext->getToEmail();
				if (!$maintainer_mailto) {
					$maintainer_mailto = MAIL_TO_FALLBACK;
				}
			}

			
			if (!$aggregate_mail) {
				echo "Mailing info to <$maintainer_mailto>" . PHP_EOL;
			}

			$mailer->xmail(
				MAIL_FROM,
				$maintainer_mailto,
				'[PECL-DEV] Windows build: ' . basename($pkg_path),
				$ext->getPackageName() . " not started\nReason: " . $e->getMessage()
			);
		}

		$build->clean();
		$ext->cleanup();
		$was_errors = true;

		unset($ext);
		unset($build);

		/* no sense to continue as something in ext setup went wrong */
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

 	echo "Configured for <$ext_build_name>" . PHP_EOL;
	echo "Running build in <$build_src_path>" . PHP_EOL;
	try {
		$build->buildconf();

		$ext_conf_line = $ext->getConfigureLine();
		echo "Extension specific config: $ext_conf_line" . PHP_EOL;
		if ($branch->config->getPGO() == 1)  {
			echo "Creating PGI build" . PHP_EOL;
			$build->configure(' "--enable-pgi" ' . $ext_conf_line);
		}
		else {
			$build->configure($ext_conf_line);
		}

		$multiext_enabled = 0;
		foreach ($ext->getMultiExtensionNames() as $one_ext_name) {
			$multiext_enabled += preg_match(
				',^\|\s+' . preg_quote($one_ext_name) . '\s+\|\s+shared\s+\|,Sm',
				$build->log_configure
			);
		}
		if ($multiext_enabled < 1) {
			throw new Exception($ext->getName() . ' is not enabled, skip make phase');
		}

		$build->make();
		//$html_make_log = $build->getMakeLogParsed();
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;
		$build_error++;
	}

	/* XXX PGO stuff would come here */

	$log_base = $toupload_dir . '/logs';
	$buildconf_log_fname = $log_base . '/buildconf-' . $ext->getPackageName() . '.txt';
	$configure_log_fname = $log_base . '/configure-' . $ext->getPackageName() . '.txt';
	$make_log_fname = $log_base . '/make-' . $ext->getPackageName() . '.txt';
	$error_log_fname = NULL;

	file_put_contents($buildconf_log_fname, $build->log_buildconf);
	file_put_contents($configure_log_fname, $build->log_configure);
	file_put_contents($make_log_fname, $build->log_make);

	$stats = $build->getStats();

	if (isset($stats['error']) && $stats['error'] > 0) {
		$error_log_fname = $log_base . '/error-' . $ext->getPackageName() . '.txt';
		file_put_contents($error_log_fname, $build->compiler_log_parser->getErrors());
	}

	try {
		echo "Packaging the binaries" . PHP_EOL;
		$pkg_file = $ext->preparePackage();
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;
		$build_error++;
	}

	try {
		echo "Packaging the logs" . PHP_EOL;
		$logs_zip = $ext->packLogs(
			array(
				$buildconf_log_fname,
				$configure_log_fname,
				$make_log_fname,
				$error_log_fname,
			)
		);
	} catch (Exception $e) {
		echo 'Error: ' . $e->getMessage() . PHP_EOL;
		$build_error++;
	}

	$upload_success = true;
	if ($upload) {
		try {
			$root = $is_snap ? 'snaps' : 'releases';
			$target = '/' . $root . '/' .  $ext->getName() . '/' . $ext->getVersion();

			$pkgs_to_upload = $build_error ? array() : array($pkg_file);

			if ($build_error) {
				echo "Uploading logs" . PHP_EOL;
			} else {
				echo "Uploading '$pkg_file' and logs" . PHP_EOL;
			}

			if ($build_error && !isset($logs_zip)) {
				throw new Exception("Logs wasn't packaged, nothing to upload");
			}

			if (rm\upload_pecl_pkg_ftp_curl($pkgs_to_upload, array($logs_zip), $target)) {
				echo "Upload succeeded" . PHP_EOL;
			} else {
				throw new Exception("Upload failed");
			}
		} catch (Exception $e) {
			echo 'Error . ' . $e->getMessage() . PHP_EOL;
			$upload_success = false;
		}
	}
	$upload_status[$build_name] = $upload_success;

	if ($mail_maintainers) {
		try {
			$maintainer_mailto = $force_email;
			if (!$maintainer_mailto) {
				$maintainer_mailto = $ext->getToEmail();
				if (!$maintainer_mailto) {
					$maintainer_mailto = MAIL_TO_FALLBACK;
				}
			}

			if (!$aggregate_mail) {
				echo "Mailing logs to <$maintainer_mailto>" . PHP_EOL;
			} else {
				/* Save a couple of things so we can use them for aggregated mail */
				$last_ext_name = $ext->getName();
				$last_ext_version = $ext->getVersion();
			}

			$res = $ext->mailMaintainers(0 == $build_error, $is_snap, array($logs_zip), $mailer, $maintainer_mailto);
			if (!$res) {
				throw new \Exception("Mail operation failed");
			}
		} catch (Exception $e) {
			echo 'Error: ' . $e->getMessage() . PHP_EOL;
		}
	} 

Coventry:

	try {
		$db_path = __DIR__ . '/../data/pecl.sqlite';
		$db = new rm\PeclDb($db_path);
		$db->touch($ext->getName(), $ext->getVersion());
	} catch (Exception $e) {
			echo 'Error: ' . $e->getMessage() . PHP_EOL;
	}

	$build->clean();
	$ext->cleanup($upload && isset($upload_success) && $upload_success);
	if (isset($toupload_dir)) {
		rm\rmdir_rf($toupload_dir);
	}

	unset($ext);
	unset($build);
	unset($mailer);
	if (isset($db)) {
		unset($db);
	}

	echo "Done." . PHP_EOL . PHP_EOL;

	$was_errors = $was_errors || $build_error > 0;
}

echo "Run finished." . PHP_EOL . PHP_EOL;

/* Cleanup things if --last was given */
if ($is_last_run) {
	echo "Last invocation for <$pkg_path> finished." . PHP_EOL . PHP_EOL;

	if ($aggregate_mail) {
		try {
			$mailer = new rm\PeclMail($pkg_path, $aggregate_mail);

			echo "Sending aggregated report mail to <$maintainer_mailto>" . PHP_EOL;
		
			$seg = $is_snap ? 'snaps' : 'releases';
			$url = 'http://windows.php.net/downloads/pecl/' . $seg . '/';
			if (isset($last_ext_name) && isset($last_ext_name)) {
				$url .= $last_ext_name . '/' . $last_ext_version . '/';
			}


			$from = NULL;
			$to = NULL;
			if (isset($last_ext_name) && isset($last_ext_name)) {
				$subject = '[PECL-DEV] Windows build: ' . $last_ext_name . '-' . $last_ext_version;
			} else {
				$subject = '[PECL-DEV] Windows build: ' . basename($pkg_path);
			}

			$open = "\nFilename: " . basename($pkg_path) . "\n";
			if (isset($last_ext_name)) {
				$open .= "Extension name: $last_ext_name\n";
			}
			if (isset($last_ext_name)) {
				$open .= "Extension version: $last_ext_version\n";
			}
			$open .= "Build type: " . ($is_snap ? 'snapshot' : 'release') . "\n\n";
			$open .= "For each build combination and status please refer to the list below."; 

			$close = "";
			if ($upload) {
				$all_uploads_succeeded = true;
				foreach ($upload_status as $st) {
					$all_uploads_succeeded = $all_uploads_succeeded && $st;
				}
				$close = "Upload status: ";
				if ($all_uploads_succeeded) {
					$close .= "succceeded\n";
					$close .= "URL: $url\n\n";
				} else {
					$close .= "some uploads failed\n\n";
				}
			}
			$close .= "This mail is being sent to you because you are the lead developer in package.xml\n\n";
			$close .= "Have a nice day";

			$mailer-> mailAggregated($from, $to, $subject, $open, $close, false);
			$mailer->cleanup();
		} catch (Exception $e) {
			echo 'Error: ' . $e->getMessage() . PHP_EOL;
			$was_errors = true;
		}
	}
}

exit((int)$was_errors);

