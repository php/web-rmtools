<?php
error_reporting(E_ALL|E_NOTICE);
include __DIR__ . '/../include/config.php';

include 'Storage.php';
include 'Base.php';
use rmtools as rm;

if ($argc < 4) {
	echo "Usage: updatebranch <release name> <release branch name> <dev branch name> <last revision> <rm1> <rm2>...
<release name>\tname of the new release (5.3.3)
<release branch name>\tname of the new release branch (PHP_5_3_3)
<dev branch name>\tname of the new branch name (PHP_5_3)
<last revision>\tLast revision commited before the branch was created (PHP_5_3)
";
	exit();
}

$release_name = filter_var($argv[1], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
$release_branch_name = filter_var($argv[2], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
$dev_branch_name = filter_var($argv[3], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
$revision = filter_var($argv[4], FILTER_VALIDATE_INT);

if (!rm\Svn::isValidBranch($release_branch_name)) {
	echo "$release_branch_name does not exist or is not valid.\n";
	exit();
}

if (!rm\Svn::isValidBranch($dev_branch_name)) {
	echo "$dev_branch_name does not exist or is not valid.\n";
	exit();
}

try {
	$svn = new rm\Base();
	$svn->createNewRelease($release_name, $release_branch_name, $dev_branch_name, $revision);

	// Rest of the arguments are RM handles
	if ($argv == 4) {
		exit();
	}

	for ($i = 5; $i < $argc; $i++) {
		$handle = filter_var($argv[$i], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
		$svn->addRmToRelease($release_name, $handle);
	}

} catch (Exception $e) {
	echo 'An error occured: ',  $e->getMessage(), "\n";
}
