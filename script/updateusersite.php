<?php
include __DIR__ . '/../include/config.php';

include 'Storage.php';
include 'Base.php';

function save_contents($file, $contents) {
	$temp_name = tempnam(TMP_DIR, 'updatepub');
	file_put_contents($temp_name, $contents);
	chmod($temp_name, 0644);
	rename($temp_name, $file);
}

use rmtools as rm;
$extra_head = $error = FALSE;
$error = false;
$tpl = 'menu_public.php';
$base = new rm\Base;

try {
	$releases = $base->getAllReleases();
} catch (\Exception $e) {
	$error = true;
}

if (!$error) {
	ob_start();
	include TPL_PATH . '/header_public.php';
	include TPL_PATH . '/menu_public.php';
	include TPL_PATH . '/footer.php';
	$menu_html = ob_get_clean();
	save_contents(WWW_ROOT . '/index.html', $menu_html);
} else {
	// Add log
}

reset($releases);
$extra_head = TPL_PATH. '/revision_list_extra_head_public.php';
foreach ($releases as $release_name) {
	ob_start();
	include TPL_PATH . '/header_public.php';
	include TPL_PATH . '/revision_list_public.php';
	include TPL_PATH . '/footer.php';
	$list_html = ob_get_clean();
	save_contents(WWW_ROOT . '/list.' . $release_name .'.html', $list_html);
	$svn = new rm\Storage($release_name);
	$json = $svn->exportAsJson();
	save_contents(WWW_ROOT . '/json/' . $release_name .'.json', $json);
}
