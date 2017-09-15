@ECHO OFF

if not exist %~dp0rmtools_setvars.bat (
	echo RMTOOLS is not setup, create %~dp0rmtools_setvars.bat
	exit /b 3
)

rem must be on the env already
if "%PHP_SDK_ROOT_PATH%"=="" (
	echo PHP SDK is not setup
	exit /b 3
)
call %~dp0rmtools_setvars.bat

for /f "tokens=2-8 delims=.:/ " %%a in ("%date% %time%") do set cur_date=%%c-%%a-%%b_%%d-%%e-%%f.%%g

set PART=%*
set LOG_FILE=%PHP_RMTOOLS_LOG_PATH%\task-%PART: =-%-%cur_date%.log
set LOCK_FILE=%PHP_RMTOOLS_LOCK_PATH%\snaps.lock

rem IF EXIST %LOCK_FILE% (
rem 	ECHO Snapshot script is already running.
rem 	GOTO EXIT_LOCKED
rem )

ECHO running > %LOCK_FILE% 

if not exist "%PHP_RMTOOLS_ROOT_PATH%\data\config\credentials_ftps.php" (
	echo FTP config %PHP_SDK_ROOT_PATH%\data\config\credentials_ftps.php not found >> %LOG_FILE% 2<&1
	del %LOCK_FILE% >> %LOG_FILE% 2<&1
	exit /b 3
)

call %PHP_RMTOOLS_BIN_PATH%\snap.bat %* >> %LOG_FILE% 2<&1

rem del %LOCK_FILE% >> %LOG_FILE% 2<&1

echo Done.>> %LOG_FILE%

:EXIT_LOCKED
echo .

