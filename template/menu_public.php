<h1>List of active branches or releases<br /></h1>
<?php foreach ($releases as $release_name) { ?>
<?php $release = $base->getRelease($release_name); ?>
<h2>Release <?php echo $release_name; ?><br /></h2>
<ul>
<li><a href="list.<?php echo $release_name; ?>.html">Merges (updated on <?php echo $release['release_last_update'];?>)</a></li>
</ul>
<?php } ?>
