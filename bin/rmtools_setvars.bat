@echo off

set PHP_RMTOOLS_BIN_DIR=%~dp0
set PHP_RMTOOLS_BIN_PATH=%PHP_RMTOOLS_BIN_PATH:~0,-1%

for %%a in ("%PHP_RMTOOLS_BIN_PATH%") do set PHP_RMTOOLS_PATH=%%~dpa
rem remove trailing slash
set PHP_RMTOOLS_PATH=%PHP_RMTOOLS_PATH:~0,-1%

rem set PHP_RMTOOLS_BASE_DIR=c:\php-sdk\rmtools-client

set PATH=%PHP_RMTOOLS_BIN_PATH%;%PATH%

