<?php


include __DIR__ . '/../include/PeclDb.php';

use rmtools as rm;

$longopts = array("refresh-db");

$options = getopt(NULL, $longopts);

$refresh_db = isset($options['']);

if ($_SERVER['argv'] <= 1) {
	echo "Usage: pecl_rss.php [OPTION] ..." . PHP_EOL;
	echo "  --refresh-db    Read new data from the PECL RSS feed and save it to db, optional" . PHP_EOL;
	echo PHP_EOL;
	echo "Example: pecl_rss --refresh-db" . PHP_EOL;
	echo PHP_EOL;
	exit(0);
}

$db_path = __DIR__ . '/../data/pecl.sqlite';

$rss = 'http://pecl.php.net/feeds/latest.rss';
$latest = simplexml_load_file($rss);
if (!isset($latest->item)) {
	echo "No items could be found in $rss" . PHP_EOL;
}

$db = new rm\PeclDb($db_path);

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

	if ($db->add($name, $version)) {
		echo "Read ext <$name> of version <$version>" . PHP_EOL;
	}
	/*
	 * when need more, look here (or /r) using name and version
	 * $url = 'http://pecl.php.net/rest/p/';
	 */
}

$db->dump();

