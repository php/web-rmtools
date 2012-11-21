<?php

$zipfile = "$argv[1]";
$destination = "$argv[2]";
if ( !file_exists("$zipfile") || !file_exists("$destination") )  {
	print "Usage: unzip.php <zip_file> <destination>\n";
	exit(1);
}

$zip = new ZipArchive;
if ( $zip->open("$zipfile") === TRUE )  {
	$zip->extractTo( $destination );
	$zip->close();
}
else  {
	exit(1);
}
?>