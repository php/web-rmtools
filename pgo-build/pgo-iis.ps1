#%powershell1.0%

Param( $PHPBUILD="" )
$phppath = 'C:\pgo\'+$PHPBUILD+'\php-cgi.exe'

$apps = @( 'drupal', 'wordpress', 'mediawiki', 'joomla', 'phpbb', 'symfony' )
#$apps = @( 'drupal', 'wordpress', 'mediawiki', 'joomla', 'phpbb' )

$trans = invoke-expression -command "c:\pgo\php-nts-bin\php.exe c:\pgo\scripts\pgo.php printnum"
foreach ( $app in $apps )  {
	$num = 0
	foreach ( $t in $trans )  {
		$t = $t.split(":")
			if ( $t[0] -eq $app )  {
				[int]$num = $t[1].trim()
				c:\windows\system32\inetsrv\appcmd.exe set config -section:system.webServer/fastCgi /-"[fullPath='$phppath'].environmentVariables.[name='PHP_FCGI_MAX_REQUESTS']"
				c:\windows\system32\inetsrv\appcmd.exe set config -section:system.webServer/fastCgi /+"[fullPath='$phppath'].environmentVariables.[name='PHP_FCGI_MAX_REQUESTS',value='$num']"
				iisreset /stop
				iisreset /start
				invoke-expression -command "c:\pgo\php-nts-bin\php c:\pgo\scripts\pgo.php vhost 80 $t[0]"
				start-sleep -s 10
		}
	}
}
