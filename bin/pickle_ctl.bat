@echo off

SET BAT_DIR=%~dp0

set PECL_RSS_CMD=c:\php-sdk\php\php.exe

if "%1"=="" goto :help
if "%1"=="--help" goto :help
if "%1"=="-h" goto :help
if "%1"=="/?" goto :help
goto :skip_help

:help
%PECL_RSS_CMD% %BAT_DIR%\..\script\pickle_ctl.php
GOTO EXIT_LOCKED

:skip_help


%PECL_RSS_CMD% %BAT_DIR%\..\script\pickle_ctl.php %*

:EXIT_LOCKED
echo .

