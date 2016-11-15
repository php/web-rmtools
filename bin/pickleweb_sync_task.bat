@echo off

SET BAT_DIR=%~dp0

set LOG_FILE=c:\php-sdk\logs\task-pickleweb-sync.log

set PECL_RSS_CMD=c:\php-sdk\php\php.exe -d extension_dir=c:\php-sdk\php\ext -d extension=php_openssl.dll -d extension=php_curl.dll -d extension=php_sqlite3.dll -d date.timezone=UTC %BAT_DIR%\..\script\pickleweb_ctl.php %* >> %LOG_FILE% 2<&1


if "%1"=="" goto :help
if "%1"=="--help" goto :help
if "%1"=="-h" goto :help
if "%1"=="/?" goto :help
goto :skip_help

:help
echo ==========================================================
echo This is the PECL RSS task script. You can see the help
echo output of the underlaying worker below. This script will
echo fetch the items from the current RSS feed, download and
echo put them into the build queue. 
echo ==========================================================
call %BAT_DIR%pickle_ctl.bat
GOTO EXIT_LOCKED

:skip_help

IF EXIST c:\php-sdk\locks\pickleweb-sync.lock (
ECHO Pickleweb sync is already running.
GOTO EXIT_LOCKED
)

ECHO running > c:\php-sdk\locks\pickleweb-sync.lock 


call %BAT_DIR%pickle_ctl.bat %* >> %LOG_FILE% 2<&1

echo Done.>> %LOG_FILE%

del c:\php-sdk\locks\pickleweb-sync.lock >> %LOG_FILE% 2<&1

:EXIT_LOCKED
echo .

