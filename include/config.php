<?php
namespace rmtools;
set_include_path('.:' . __DIR__);
define('MASTER_AUTH_TOKEN', getenv('MASTER_AUTH_TOKEN'));
define('SVN_REPO_PATH', '/home/pierre/repos/phprepo/php');
define('DB_PATH', '/home/pierre/public_html/rmtools/data');
define('TPL_PATH', __DIR__ . '/../template');

