@ECHO OFF

rem must be on the env already
if "%PHP_SDK_ROOT_PATH%"=="" (
	echo PHP SDK is not setup
	exit /b 3
)
call %~dp0rmtools_setvars.bat

if not exist "%PHP_RMTOOLS_ROOT_PATH%\data\config\credentials_ftps.php" (
	echo FTP config %PHP_SDK_ROOT_PATH%\data\config\credentials_ftps.php not found
	exit /b 3
)

@ECHO ON
%PHP_SDK_PHP_CMD% %PHP_RMTOOLS_SCRIPT_PATH%\snap.php %*
@ECHO OFF

