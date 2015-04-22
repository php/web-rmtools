<?php

include __DIR__ . '/../data/config.php';
include __DIR__ . '/../include/Pickle.php';
include __DIR__ . '/../include/Tools.php';
//include __DIR__ . '/../include/PackagistExt.php';

use rmtools as rm;


/* parametrize */
$branch_name = "packagist55";

$config_path = __DIR__ . '/../data/config/packagist/' . $branch_name . '.ini';

$pickle = new rm\Pickle();
var_dump($pickle);

exit(0);

