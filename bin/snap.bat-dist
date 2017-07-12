@echo off

if not exist %~dp0rmtools_setvars.bat (
	echo RMTOOLS is not setup, create %~dp0rmtools_setvars.bat
	exit /b 3
)
call %~dp0rmtools_setvars.bat

call %PHP_RMTOOLS_PHP_SDK_ROOT_PATH%\phpsdk-vc15-x86.bat -t %PHP_RMTOOLS_BIN_PATH%\bin\snapshot_task.bat --task-args "phpmaster all"
