<body>
<h1><?php echo "Tasks for RM: $username";?><br /></h1>
<?php foreach ($releases as $release_name) { ?>
<?php $release = $base->getRelease($release_name); ?>
<h2>Release <?php echo $release_name; ?><br /></h2>
<ul>
<li><a href="index.php?mode=list&release=<?php echo $release_name; ?>">Merges (updated on <?php echo $release['last_update'];?>)</a></li>
<li><a href="index.php?mode=list&nojs=1">Merges (no js)</a></li>
<li><a href="">Builds</a></li>
</ul>
<?php } ?>
