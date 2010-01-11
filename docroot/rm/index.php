<?php
include __DIR__ . '/../../include/config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL|E_NOTICE);

include 'Storage.php';
include 'Base.php';

use rmtools as rm;
$extra_head = $error = FALSE;

include 'login.php';

$release_name = filter_input(INPUT_GET, 'release', FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$title = $release_name;
try {
	$svn = new rm\Storage($release_name);
} catch (Exception $e) {
	$error = $e->getMessage();
	$tpl = 'error.php';
}
$mode = filter_input(INPUT_GET, 'mode', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);

switch ($mode) {
	case 'list':
		$nojs = filter_input(INPUT_GET, 'nojs', FILTER_VALIDATE_INT);
		if ($nojs) {
			$tpl = 'revision_list.php';
			$svn_log = $svn->getAll();
		} else {
			$extra_head = 'revision_list_yui_extra_head.php';
			$tpl = 'revision_list_yui.php';
		}

		$base = new rm\Base;
		$release = $base->getRelease($release_name);
		break;

	case 'edit':
		$json = filter_input(INPUT_GET, 'json', FILTER_VALIDATE_INT);
		if ($json) {
			$rev = filter_input(INPUT_POST, 'revision', FILTER_VALIDATE_INT);
		} else {
			$rev = filter_input(INPUT_GET, 'rev', FILTER_VALIDATE_INT);
		}
		if ($rev) {
			$revision = $svn->getOne($rev);
		}
		if (!$revision) {
			if ($json) {
				header('HTTP/1.0 404 Not Found');
			}
			$error = "$revision cannot be found.";
			$tpl = 'error.php';
		} else {
			if (!empty($_POST)) {
				$revision['comment'] = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
				$revision['news'] = filter_input(INPUT_POST, 'news', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
				$revision['status'] = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT, array('min_range'=>0. , 'max_range'=>2));
				try {
					if (!$svn->updateRevision($revision)) {
						if ($json) {
							header('HTTP/1.0 500 Internal Server Error');
						}
						$error = "Failed to update revision: $revision";
						$tpl = 'error.php';
						break;
					}

					if ($json) {
						echo json_encode($revision);
						exit();
					} else {
						header('Location: index.php');
						exit();
					}
				} catch (\Exception $e) {
						if ($json) {
							header('HTTP/1.0 500 Internal Server Error');
							exit();
						}
						$error = "Failed to update revision: $revision";
						$tpl = 'error.php';
				}
				break;
			}

			$tpl = 'edit_revision.php';
		}
		break;

	case 'menu':
	default:
		$tpl = 'menu.php';
		$base = new rm\Base;
		try {
			$releases = $base->getReleaseForRM($username);
		} catch (\Exception $e) {
			$error = "$username has no RM role in any active release.";
			$tpl = 'error.php';
		}
}
include TPL_PATH . '/header.php';
include TPL_PATH . '/'. $tpl;
include TPL_PATH . '/footer.php';
