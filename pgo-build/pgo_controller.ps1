#%powershell1.0%
#
# File: pgo_controller.ps1
# Description:
# 	- Deploy PGI build on remote server
#	- Set up IIS and Apache with PGI builds of PHP
#	- Run training scripts to collect profiling data
#	- Collect .pgc files
#
## Example: pgo_controller.ps1 -PHPBUILD C:\obj\ts-windows-vc9-x86\Release_TS\php-5.4.0RC6-dev-Win32-VC9-x86.zip -PHPVER php-5.4

Param( $PHPBUILD="", $PHPVER="" )
if ( ($PHPBUILD -eq "") -or ($PHPVER -eq "") )  {
	write-output "Usage: pgo_controller.ps1 -PHPBUILD <path_to_.zip> -PHPVER <php_ver>"
	exit
}

$SERVER = "php-pgo01"
$WebSvrPHPLoc = "\\$SERVER\pgo"
$WebSvrApacheLoc = "\\$SERVER\Apache2"
$RemoteBaseDir = "C:\pgo"
$RemotePHPBin = "C:\pgo\php-nts-bin\php.exe"

$BaseDir = "c:\php-sdk\pgo-build"
$LocalPHPBin = "C:\php-sdk\php\php.exe"
$BaseBuildDir = "c:\php-sdk"
$ObjDir = "$PHPBUILD\..\obj"

## Import needed functions
Set-Location $BaseDir
. .\setup-utils.ps1

## Simple logging function.
Function logger ( $msg )  {
	$logfile = "$BaseDir\log.txt"
	$msg = (get-date -format "yyyy-MM-dd HH:mm:ss")+" $msg"
	$msg | Out-File -Encoding ASCII -Append $logfile
}

###################################################################################
## Setup PHP and run the profiling tools.
##

$build = ""
$PHPBUILD = $PHPBUILD -replace '/', '\'
$build = $PHPBUILD.split('\')
$build = [string]$build[($build.length-1)]
$build = $build -ireplace "\.zip", ""

$trans = invoke-expression -command "$LocalPHPBin $WebSvrPHPLoc\scripts\pgo.php printnum"
$trans = [string]$trans
$trans = $trans.split(':')
$trans = $trans[$trans.length-1].trim()

$exts = $build.split('-')
$exts = 'pecl-'+$exts[1]
$exts = ($PHPBUILD -replace '\\php\-\d.+$', '') + '\' + $exts

## Locking here.
$lockfile = 'php-ts'
if ( $PHPBUILD -match "nts" )  {
	$lockfile = 'php-nts'
}
if ((set-lock $lockfile) -eq $false )  {
	logger "PGO Controller: set-lock($lockfile) returned error."
	write-output "PGO Controller: set-lock($lockfile) returned error."
	exit
}

## Stop relevant web services on the server
logger "PGO Controller: Starting PHP configuration."
if ( $PHPBUILD -match "nts" )  {
	$( winrs -r:$SERVER net stop w3svc )
}
else  {
	$( winrs -r:$SERVER net stop Apache2.2 )
}

## Copy and set up PHP runtime.
if ( (setup-php $exts $PHPBUILD) -eq $false )  {
	logger "PGO Controller: setup-php() returned error."
	write-output "PGO Controller: setup-php() returned error."
	remove-lock $lockfile
	exit
}

## Set up IIS or Apache
if ( $PHPBUILD -match "nts" )  {
	if ( (setup-iis $build $trans) -eq $false )  {
		logger "PGO Controller: setup-iis() returned error."
		write-output "PGO Controller: setup-iis() returned error."
		remove-lock $lockfile
		exit
	}
}
else  {
	if ( (setup-apache($build)) -eq $false )  {
		logger "PGO Controller: setup-apache() returned error."
		write-output "PGO Controller: setup-apache() returned error."
		remove-lock $lockfile
		exit
	}
}

if ( $PHPBUILD -match "nts" )  {
	$phpini = "$BaseDir/ini/$PHPVER-pgo-nts.ini"
	if ( (php-configure $build $phpini) -eq $false )  {
		logger "PGP Controller: php-configure() returned error: $build, $phpini"
		remove-lock $lockfile
		exit
	}
	$( winrs -r:$SERVER net stop w3svc "&" net start w3svc )
	$( winrs -r:$SERVER C:/windows/system32/inetsrv/appcmd stop site /site.name:"Default Web Site" )
	$( winrs -r:$SERVER C:/windows/system32/inetsrv/appcmd start site /site.name:"drupal" )
	$( winrs -r:$SERVER C:/windows/system32/inetsrv/appcmd start site /site.name:"joomla" )
	$( winrs -r:$SERVER C:/windows/system32/inetsrv/appcmd start site /site.name:"mediawiki" )
	$( winrs -r:$SERVER C:/windows/system32/inetsrv/appcmd start site /site.name:"phpbb" )
	$( winrs -r:$SERVER C:/windows/system32/inetsrv/appcmd start site /site.name:"wordpress" )
	$( winrs -r:$SERVER C:/windows/system32/inetsrv/appcmd start site /site.name:"symfony" )
	$( winrs -r:$SERVER powershell C:\pgo\scripts\pgo-iis.ps1 -PHPBUILD $build )
}
else  {
	$phpini = "$BaseDir/ini/$PHPVER-pgo-ts.ini"
	if ( (test-path "$exts\php_apc.dll") -eq $true )  {
		$phpini = "$BaseDir/ini/$PHPVER-pgo-ts-apc.ini"
	}
	if ( (php-configure $build $phpini) -eq $false )  {
		logger "PGO Controller: php-configure() returned error: $build, $phpini"
		remove-lock $lockfile
		exit
	}
	$( winrs -r:$SERVER net stop Apache2.2 "&" net start Apache2.2 )
	$( winrs -r:$SERVER $RemotePHPBin C:\pgo\scripts\pgo.php localhost 8080 )
}

if ( $PHPBUILD -match "nts" )  {
	$( winrs -r:$SERVER net stop w3svc )
}
else  {
	$( winrs -r:$SERVER net stop Apache2.2 )
}

## Important - need to give instrumentation time to
## create .pgc files after stopping the server.
Start-Sleep -s 10

## Collect the .pgc files
$LocalBuildDir = $PHPBUILD -replace "$build\.zip", ''
remove-item "$LocalBuildDir/*.pgc" -force
copy-item -force "$WebSvrPHPLoc/$build/*.pgc" -destination $LocalBuildDir
copy-item -force "$WebSvrPHPLoc/$build/ext/*.pgc" -destination $LocalBuildDir

## Remove lock
remove-lock $lockfile

