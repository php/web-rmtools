@echo off

SET BAT_DIR=%~dp0

set PECL_RSS_CMD=c:\php-sdk\php\php.exe -d extension_dir=c:\php-sdk\php\ext -d extension=php_openssl.dll -d extension=php_curl.dll -d extension=php_sqlite3.dll -d date.timezone=UTC

if "%1"=="" goto :help
if "%1"=="--help" goto :help
if "%1"=="-h" goto :help
if "%1"=="/?" goto :help
goto :skip_help

:help
%PECL_RSS_CMD% %BAT_DIR%\..\script\pecl_rss.php
GOTO EXIT_LOCKED

:skip_help


%PECL_RSS_CMD% %BAT_DIR%\..\script\pecl_rss.php %*

:EXIT_LOCKED
echo .

