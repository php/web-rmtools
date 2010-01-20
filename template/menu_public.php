<h1>List of active branches or releases<br /></h1>
<?php foreach ($releases as $release_name) { 
	$latest_link = SNAPS_PATH . "/php-$release_name-src-latest.zip";
	if (file_exists($latest_link)) {
		$snapshot_src_link = str_replace(WWW_ROOT, '', $latest_link);
	} else {
		$snapshot_src_link = false;
	}
?>
<?php $release = $base->getRelease($release_name); ?>
<h2>Release <?php echo $release_name; ?><br /></h2>
<ul>
<li><a href="list.<?php echo $release_name; ?>.html">Merges (updated on <?php echo $release['release_last_update'];?>, last revision: <?php echo $release['release_last_revision'];?>)</a></li>
<?php if ($snapshot_src_link) { ?>
<li><a href="<?php echo $snapshot_src_link;?>">Source snapshot</a></li>
<?php } ?>
</ul>
<?php } ?>
