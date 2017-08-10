@echo off

call %~dp0rmtools_setvars.bat

set LOG_FILE=%PHP_RMTOOLS_LOG_PATH%\task-pecl-rss.log


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
call %PHP_RMTOOLS_BIN_PATH%\pecl_rss.bat
GOTO EXIT_LOCKED

:skip_help

IF EXIST %PHP_RMTOOLS_LOCK_PATH%\pecl-rss.lock (
ECHO Pecl build script is already running.
GOTO EXIT_LOCKED
)

ECHO running > %PHP_RMTOOLS_LOCK_PATH%\pecl-rss.lock


call %PHP_RMTOOLS_BIN_PATH%\pecl_rss.bat %* >> %LOG_FILE% 2<&1

echo Done.>> %LOG_FILE%

del %PHP_RMTOOLS_LOCK_PATH%\pecl-rss.lock >> %LOG_FILE% 2<&1

:EXIT_LOCKED
echo .

