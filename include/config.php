<?php
namespace rmtools;
set_include_path('.:' . __DIR__);
define('MASTER_AUTH_TOKEN', getenv('MASTER_AUTH_TOKEN'));
define('SVN_REPO_PATH', __DIR__ . '/../repos/php');
define('DB_PATH', __DIR__ . '/../data');
define('TPL_PATH', __DIR__ . '/../template');
