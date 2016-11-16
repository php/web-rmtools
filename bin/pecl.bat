@ECHO OFF

rem must be on the env already
if "%PHP_SDK_ROOT_PATH%"=="" (
	echo PHP SDK is not setup
	exit /b 3
)
call %~dp0rmtools_setvars.bat

if "%1"=="" goto :help
if "%1"=="--help" goto :help
if "%1"=="-h" goto :help
if "%1"=="/?" goto :help
goto :skip_help

:help
%PHP_SDK_PHP_CMD% %PHP_RMTOOLS_SCRIPT_PATH%\pecl.php
GOTO EXIT_LOCKED

:skip_help

if not exist "%PHP_RMTOOLS_ROOT_PATH%\data\config\credentials_ftps.php" (
	echo FTP config %PHP_SDK_ROOT_PATH%\data\config\credentials_ftps.php not found
	exit /b 3
)

REM Run pecl.php
@ECHO ON
%PHP_SDK_PHP_CMD% %PHP_RMTOOLS_SCRIPT_PATH%\pecl.php %*
@ECHO OFF

:EXIT_LOCKED
echo .

