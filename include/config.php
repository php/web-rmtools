<?php
namespace rmtools;

set_include_path('.:' . __DIR__);

$rmtools_base = getenv('RMTOOLS_BASE_DIR');

if (!$rmtools_base) {
	$rmtools_base = '/home/web/rmtools.php.net';
}

define('MASTER_AUTH_TOKEN', getenv('MASTER_AUTH_TOKEN'));
define('SVN_REPO_PATH', $rmtools_base . '/repos/php');
define('SVN_REPO_URL', 'http://svn.php.net/repository/php/php-src');
define('DB_PATH', $rmtools_base . '/data');
define('TPL_PATH', $rmtools_base . '/template');
define('SNAPS_PATH', $rmtools_base . '/snaps');
