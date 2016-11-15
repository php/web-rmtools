<?php
$old_section = '';
$row = 0;
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="log_style.css">
<title><?php echo $title; ?></title>
</head>
<body>
	<h1><?php echo $title; ?></h1>
	<div id="log">
		<table>
			<tr><th colspan="2">File:Line</th><th>Level</th><th>Code</th><th>Message</th></tr>
<?php foreach ($log as $entry) {
	if ($entry['section'] != $old_section) {
?>
			<tr class="dir"><td colspan="5"><?php echo $entry['section']; ?></td></tr>
<?php
		$old_section = $entry['section'];
	}

	if ($row++ % 2 == 1) {
		$row_style = ' class="alt"';
	} else {
		$row_style = '';
	}
	switch($entry['level']) {
		case 'error':
		case 'fatal':
			$level_style = ' class="error"';
			break;

		case 'warning':
			$level_style = ' class="warning"';
			break;

		case 'lib':
			$level_style = ' class="lib"';
			break;
		default:
			$level_style = '';
	}

	if ($row++ % 2 == 1) {
		$row_style = ' class="alt"';
	} else {
		$row_style = '';
	}
?>
			<tr<?php echo $row_style?>><td>&nbsp;</td><td><?php echo $entry['file'] . ':' . $entry['line']; ?></td><td<?php echo $level_style?>><?php echo $entry['level']; ?><td><?php echo $entry['code']; ?></td><td><?php echo $entry['message']; ?></td></tr>
<?php } ?>
		</table>
	</div>
</body>
</html>
