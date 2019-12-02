@ECHO OFF

if not exist %~dp0rmtools_setvars.bat (
	echo RMTOOLS is not setup, create %~dp0rmtools_setvars.bat
	exit /b 3
)
call %~dp0rmtools_setvars.bat 

for /f "tokens=1-8 delims=-" %%a in ('powershell get-date -format "{yyyy-MM-dd-HH-mm-ss-ff}"') do set cur_date=%%a-%%b-%%c_%%d-%%e-%%f.%%g

set LOG_FILE=%PHP_RMTOOLS_LOG_PATH%\task-pecl-%cur_date%.log
set LOCK_FILE=%PHP_RMTOOLS_LOCK_PATH%\pecl.lock

if "%1"=="" goto :help
if "%1"=="--help" goto :help
if "%1"=="-h" goto :help
if "%1"=="/?" goto :help
goto :skip_help

:help
echo ==========================================================
echo This is the PECL build batch script. You can see the help
echo output of the underlaying worker below. Note that you HAVE
echo TO ommit the --config option when running this batch.
echo ==========================================================
call "%PHP_RMTOOLS_PHP_SDK_ROOT_PATH%\phpsdk-starter.bat" -c vc14 -a x64 -t "%PHP_RMTOOLS_BIN_PATH%\pecl.bat"
echo .
exit /b 0

:skip_help

IF EXIST "%LOCK_FILE%" (
	ECHO Pecl build script is already running.
	echo .
	exit /b 3
)

ECHO running > "%LOCK_FILE%"

rem Notice the --first and the --last calls marked, that's important
rem to maintain the state between call for the same package. For instance
rem if --aggregate-mail is used.
call "%PHP_RMTOOLS_PHP_SDK_ROOT_PATH%\phpsdk-starter.bat" -c vc15 -a x64 -t "%PHP_RMTOOLS_BIN_PATH%\pecl.bat" --task-args "--config=pecl74_x64 --first %*" >> "%LOG_FILE%" 2<&1
call "%PHP_RMTOOLS_PHP_SDK_ROOT_PATH%\phpsdk-starter.bat" -c vc15 -a x86 -t "%PHP_RMTOOLS_BIN_PATH%\pecl.bat" --task-args "--config=pecl74_x86 %*" >> "%LOG_FILE%" 2<&1
call "%PHP_RMTOOLS_PHP_SDK_ROOT_PATH%\phpsdk-starter.bat" -c vc15 -a x64 -t "%PHP_RMTOOLS_BIN_PATH%\pecl.bat" --task-args "--config=pecl73_x64 %*" >> "%LOG_FILE%" 2<&1
call "%PHP_RMTOOLS_PHP_SDK_ROOT_PATH%\phpsdk-starter.bat" -c vc15 -a x86 -t "%PHP_RMTOOLS_BIN_PATH%\pecl.bat" --task-args "--config=pecl73_x86 %*" >> "%LOG_FILE%" 2<&1
call "%PHP_RMTOOLS_PHP_SDK_ROOT_PATH%\phpsdk-starter.bat" -c vc15 -a x64 -t "%PHP_RMTOOLS_BIN_PATH%\pecl.bat" --task-args "--config=pecl72_x64 %*" >> "%LOG_FILE%" 2<&1
call "%PHP_RMTOOLS_PHP_SDK_ROOT_PATH%\phpsdk-starter.bat" -c vc15 -a x86 -t "%PHP_RMTOOLS_BIN_PATH%\pecl.bat" --task-args "--config=pecl72_x86 --last %*" >> "%LOG_FILE%" 2<&1

echo Done.>> "%LOG_FILE%"

del "%LOCK_FILE%" >> "%LOG_FILE%" 2<&1

echo .
exit /b 0

