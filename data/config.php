<?php
namespace rmtools;

$required_exts = array(
	'openssl',
	'curl',
	'sqlite3',
	'simplexml',
	'dom',
	'json',
	'mbstring',
	'zlib',
);
foreach ($required_exts as $ext) {
	if (!extension_loaded($ext)) {
		die("'$ext' extension is not loaded but required, full rquired extensions list: " . implode(", ", $required_exts));
	}
}

$tmp = getenv('PHP_RMTOOLS_TMP_PATH');
if (!$tmp) {
	throw new \Exception("Temporary path '$tmp' doesn't exist");
}
define('TMP_DIR', $tmp);

/* XXX might remove this later */
$custom_env = array();

