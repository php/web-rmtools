@echo off

call %~dp0rmtools_setvars.bat

set PHP_DIR=%PHP_RMTOOLS_PHP_SDK_ROOT_PATH%\bin\php
set PECL_RSS_CMD=%PHP_DiR%\php.exe -c %PHP_DIR%\php.ini -d extension_dir=%PHP_DIR%\ext

if "%1"=="" goto :help
if "%1"=="--help" goto :help
if "%1"=="-h" goto :help
if "%1"=="/?" goto :help
goto :skip_help

:help
%PECL_RSS_CMD% %PHP_RMTOOLS_SCRIPT_PATH%\pecl_rss.php
GOTO EXIT_LOCKED

:skip_help


%PECL_RSS_CMD% %PHP_RMTOOLS_SCRIPT_PATH%\pecl_rss.php %*

:EXIT_LOCKED
echo .

