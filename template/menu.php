<body>
<h1><?php echo "Tasks for RM: $username";?><br /></h1>
<?php foreach ($releases as $release) { ?>
<h2>Release <?php echo $release; ?><br /></h2>
<ul>
<li><a href="index.php?mode=list">Merges</a></li>
<li><a href="index.php?mode=list&nojs=1">Merges (no js)</a></li>
<li><a href="">Builds</a></li>
<?php } ?>