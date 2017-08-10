@ECHO OFF

rem must be on the env already
if "%PHP_SDK_ROOT_PATH%"=="" (
	echo PHP SDK is not setup
	exit /b 3
)
call %~dp0rmtools_setvars.bat

@ECHO ON
call %PHP_SDK_PHP_CMD% %PHP_RMTOOLS_SCRIPT_PATH%\snap.php %*
@ECHO OFF

