#%powershell1.0%
#
# File: setup-utils.ps1
# Description: Utility functions for configuring PHP, IIS and Apache
#

#
## Description: Unzip the PHP download onto the webserver
#
Function setup-php ( $extdir="", $phploc )  {

	logger "setup-php(): Setting up PHP with exts=$extdir and php=$phploc"

	## Unzip the PHP files
	$phploc = $phploc -replace '/', '\'
	$phpdir = $phploc.split('\')
	$phpdir = [string]$phpdir[($phpdir.length-1)]
	$phpdir = $phpdir -ireplace "\.zip", ""

	if ( (test-path "$WebSvrPHPLoc\$phpdir") -eq $true )  {
		logger "setup-php(): The directory $WebSvrPHPLoc\$phpdir exists, removing."
		remove-item "$WebSvrPHPLoc\$phpdir" -Recurse -Force
	}

	logger "setup-php(): Unzipping $phploc into $WebSvrPHPLoc\$phpdir"
	new-item -path $WebSvrPHPLoc -name $phpdir -type directory -force
	$shell = new-object -com shell.application
	$zipsource = $shell.namespace( "$phploc" )
	$destination = $shell.namespace( "$WebSvrPHPLoc\$phpdir" )
	$destination.Copyhere( $zipsource.items(), 0x14 )
	if ( (test-path "$WebSvrPHPLoc\$phpdir\php-cgi.exe") -eq $false )  {
		return $false
	}

	## For now try to guess the right exts to copy
#	if ( $phploc -match "php\-5\.3" )  {
#		copy-item -Force "$extdir/5.3/*" -destination "$WebSvrPHPLoc\$phpdir\ext\" -recurse
#	}
#	elseif ( $phploc -match "php\-5\.4" )  {
#		copy-item "$extdir/5.4/*" -destination "$WebSvrPHPLoc\$phpdir\ext\" -recurse
#	}
}


#
## Description: Configure PHP for Apache.
#
Function setup-apache( $phppath="" )  {
	if ( $phppath -eq "" )  {
		return $false
	}
	logger "setup-apache(): Setting up Apache using PHP=$phppath"

	$phpdir = $RemoteBaseDir -replace '\\', '/'

	$conffile = "$WebSvrApacheLoc/conf/extra/httpd-php.conf"
	$config = "LoadModule php5_module `"$phpdir/$phppath/php5apache2_2.dll`"`n"
	$config += "AddType application/x-httpd-php .php`n"
	$config += "PHPIniDir `"$phpdir/$phppath`"`n"

	$config | Out-File -encoding ASCII $conffile
	if ( (test-path $conffile) -eq $false )  {
		return $false
	}
}


#
## Description: Configure PHP for IIS.
#
Function setup-iis( $phppath="", $trans=0 )  {
	logger "setup-iis(): Setting up IIS with PHP=$phppath, Transactions=$trans"
	if ( ($phppath -eq "") -or ($trans -eq "") )  {
		return $false
	}

	## Clear any current PHP handlers
	$( winrs -r:$SERVER "%windir%\system32\inetsrv\appcmd clear config /section:system.webServer/fastCGI" )
	$( winrs -r:$SERVER "%windir%\system32\inetsrv\appcmd set config /section:system.webServer/handlers /-[name='PHP_via_FastCGI']" )

	## Set up the PHP handler
	$( winrs -r:$SERVER "%windir%\system32\inetsrv\appcmd set config /section:system.webServer/fastCGI /+[fullPath=`'$RemoteBaseDir\$phppath\php-cgi.exe`']" )
	$( winrs -r:$SERVER "%windir%\system32\inetsrv\appcmd set config /section:system.webServer/handlers /+[name='PHP_via_FastCGI',path='*.php',verb='*',modules='FastCgiModule',scriptProcessor=`'$RemoteBaseDir\$phppath\php-cgi.exe`',resourceType='Unspecified']" )
	$( winrs -r:$SERVER "%windir%\system32\inetsrv\appcmd set config /section:system.webServer/handlers /accessPolicy:Read,Script" )

	## Configure FastCGI variables
	$( winrs -r:$SERVER "%windir%\system32\inetsrv\appcmd set config -section:system.webServer/fastCgi /[fullPath=`'$RemoteBaseDir\$phppath\php-cgi.exe`'].instanceMaxRequests:10000" )
	$( winrs -r:$SERVER "%windir%\system32\inetsrv\appcmd set config -section:system.webServer/fastCgi /[fullPath=`'$RemoteBaseDir\$phppath\php-cgi.exe`'].MaxInstances:1" )
	$( winrs -r:$SERVER "%windir%\system32\inetsrv\appcmd.exe set config -section:system.webServer/fastCgi /+`"[fullPath=`'$RemoteBaseDir\$phppath\php-cgi.exe`'].environmentVariables.[name='PHP_FCGI_MAX_REQUESTS',value=`'$trans`']`"" )
	$( winrs -r:$SERVER "%windir%\system32\inetsrv\appcmd.exe set config -section:system.webServer/fastCgi /+`"[fullPath=`'$RemoteBaseDir\$phppath\php-cgi.exe`'].environmentVariables.[name='PHPRC',value=`'$RemoteBaseDir\$phppath\php.ini`']`"" )
}


#
## Description: Copy a php.ini onto the webserver.
#
function php-configure( $phppath="", $phpini="" )  {
	if ( ($phppath -eq "") -or ($phpini -eq "") )  {
		return $false
	}
	logger "php-configure(): Configuring PHP with PHP=$phppath and INI=$phpini"
	copy-item "$phpini" -destination "$WebSvrPHPLoc/$phppath/php.ini"

	$phpdir = $RemoteBaseDir -replace '\\', '/'
	if ( $phppath -ne "nts" )  {
		$contents = (get-content "$WebSvrPHPLoc/$phppath/php.ini")
		out-file -encoding ASCII -Force "$WebSvrPHPLoc/$phppath/php.ini"
		Foreach ( $line in $contents )  {
			if ( $line -match "^extension_dir" )  {
				$line = "extension_dir = `"$phpdir/$phppath/ext`""
			}
			$line | out-file -encoding ASCII -append "$WebSvrPHPLoc/$phppath/php.ini"
		}
	}
}
