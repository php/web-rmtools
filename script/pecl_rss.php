<?php

include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/PeclDb.php';

use rmtools as rm;

$longopts = array("help", "refresh", "dump-queue", "dump-all", "no-fetch", "force-fetch", "enable-pre");

$options = getopt(NULL, $longopts);

$refresh_db = isset($options['refresh']);
$dump_queue = isset($options['dump-queue']);
$dump_all = isset($options['dump-all']);
$help = isset($options['help']);
$no_fetch = isset($options['no-fetch']);
$force_fetch = isset($options['force-fetch']);
$enable_pre = isset($options['enable-pre']);

/* --help */
if ($_SERVER['argc'] <= 1 || $help) {
	echo "Usage: pecl_rss.php [OPTION] ..." . PHP_EOL;
	echo "  --refresh       Read new data from the PECL RSS feed and save it to db, optional." . PHP_EOL;
	echo "  --dump-queue    Dump the db rows with zero built timestamp and exit, optional." . PHP_EOL;
	echo "  --dump-all      Dump all the db rows, optional." . PHP_EOL;
	echo "  --no-fetch      Only update db, don't fetch. Only used with --refresh, optional." . PHP_EOL;
	echo "  --force-fetch   Fetch all the items and reupdate db. Only used with --refresh, optional." . PHP_EOL;
	echo "  --enable-pre    Create an additional copy of package to build against an unstable PHP version, optional." . PHP_EOL;
	echo "  --help          Show help and exit, optional." . PHP_EOL;
	echo PHP_EOL;
	echo "Example: pecl_rss --refresh" . PHP_EOL;
	echo PHP_EOL;
	exit(0);
}

if ($no_fetch && $force_fetch) {
	echo "Decide!" . PHP_EOL;
	sleep(3);
	echo "Either you need fetch or not :)" . PHP_EOL;
	exit(3);
}


$db_path = __DIR__ . '/../data/pecl.sqlite';
$db = new rm\PeclDb($db_path);

/* --dump-queue */
if ($dump_queue) {
	$db->dumpQueue();
	exit(0);
}

/* --dump-all */
if ($dump_all) {
	$db->dump();
	exit(0);
}

/* --refresh, need to wrap it all with ifs maybe*/
echo "Refreshing the data" . PHP_EOL;

$rss = 'https://pecl.php.net/feeds/latest.rss';

echo "Fetching $rss" . PHP_EOL;
$latest = simplexml_load_file($rss);
if (!isset($latest->item)) {
	echo "No items could be found in $rss" . PHP_EOL;
}

/* FIXME Use a separate config for this! */
$curl = 'C:\apps\bin\curl.exe';
$get_url_tpl = 'https://pecl.php.net/get/{name}/{version}';
$download_dir = 'c:\php-snap-build\in-pkg\release';
$download_dir_pre = 'c:\php-snap-build\in-pkg\snap-pre';

foreach($latest->item as $item) {
	if (!$item->title) {
		continue;
	}

	$tmp = explode(' ', (string)$item->title);
	$name = $tmp[0];
	$version = $tmp[1];

	if (!$name || !$version) {
		continue;
	}

	if (!$no_fetch) {
		$get_url = str_replace(
			array('{name}', '{version}'),
			array($name, $version),
			$get_url_tpl
		);

               /* XXX -k is needed as the host is good known, but the download can fail on
                       certain versions because of certs */
		$curl_cmd = $curl . ' -s -L -J -O -k ' . $get_url;
		$back = getcwd();

		chdir($download_dir);

		$suspects = glob(strtolower($name) . "-" . strtolower($version) . "*");

		if ($force_fetch) {
			if ($db->exists($name, $version)) {
				echo "<$name-$version> forcing download" . PHP_EOL;
			}

			foreach ($suspects as $f) {
				if (file_exists($f)) {
					unlink($f);
				}
			}
		} else if ($db->done($name, $version)) {
			echo "<$name-$version> is already done" . PHP_EOL;
			continue;
		} else if ($db->exists($name, $version)) {
			/* XXX no check if file exists here, but should be */
			echo "<$name-$version> is already in the queue" . PHP_EOL;
			continue;
		}

		if (!$suspects) {
			system($curl_cmd, $status);

			if ($enable_pre) {
				copy("$name-$version.tgz", "$download_dir_pre" . DIRECTORY_SEPARATOR . "$name-$version.tgz");
			}

			if ($status) {
				echo "<$name-$version> download failed" . PHP_EOL;
				chdir($back);
				continue;
			}
		}

		chdir($back);
	}

	if (!$db->exists($name, $version) && $db->add($name, $version, $force_fetch)) {
		echo "<$name-$version> added to the queue" . PHP_EOL;
	}
	/*
	 * when need more, look here (or /r) using name and version
	 * $url = 'https://pecl.php.net/rest/p/';
	 */
}

exit(0);
