@ECHO OFF

rem pick the next PECL pkg and pass to pecl_build_all.bat 
rem first try releases, if there aren't any, look for snaps

if not exist %~dp0rmtools_setvars.bat (
	echo RMTOOLS is not setup, create %~dp0rmtools_setvars.bat
	exit /b 3
)
call %dp0rmtools_setvars.bat 

if not exist %PHP_RMTOOLS_PECL_IN_PKG_PATH% (
	echo %PHP_RMTOOLS_PECL_IN_PKG_PATH% does not exist
	exit /b 3
)
cd %PHP_RMTOOLS_PECL_IN_PKG_PATH%

for /r %%i in (*) do (
	call "%PHP_RMTOOLS_BIN_PATH%\pecl_build_all.bat" --upload --aggregate-mail --package=%%i
	del %%i
	goto ONLY_ONE
)

if not exist %PHP_RMTOOLS_PECL_IN_PKG_NOMAIL_PATH% (
	echo %PHP_RMTOOLS_PECL_IN_PKG_NOMAIL_PATH% does not exist
	exit /b 3
)
cd %PHP_RMTOOLS_PECL_IN_PKG_NOMAIL_PATH%

for /r %%i in (*) do (
	call "%PHP_RMTOOLS_BIN_PATH%\pecl_build_all.bat" --upload --package=%%i
	del %%i
	goto ONLY_ONE
)

if not exist %PHP_RMTOOLS_PECL_IN_SNAP_PATH% (
	echo %PHP_RMTOOLS_PECL_IN_SNAP_PATH% does not exist
	exit /b 3
)
cd %PHP_RMTOOLS_PECL_IN_SNAP_PATH%

for /r %%i in (*) do (
	call "%PHP_RMTOOLS_BIN_PATH%\pecl_build_all.bat" --upload --is-snap --aggregate-mail --package=%%i
	del %%i
	goto ONLY_ONE
)

if not exist %PHP_RMTOOLS_PECL_IN_SNAP_NOMAIL_PATH% (
	echo %PHP_RMTOOLS_PECL_IN_SNAP_NOMAIL_PATH% does not exist
	exit /b 3
)
cd %PHP_RMTOOLS_PECL_IN_SNAP_NOMAIL_PATH%

for /r %%i in (*) do (
	call "%PHP_RMTOOLS_BIN_PATH%\pecl_build_all.bat" --upload --is-snap --package=%%i
	del %%i
	goto ONLY_ONE
)

if not exist %PHP_RMTOOLS_PECL_IN_SNAP_PRE_PATH% (
	echo %PHP_RMTOOLS_PECL_IN_SNAP_PRE_PATH% does not exist
	exit /b 3
)
cd %PHP_RMTOOLS_PECL_IN_SNAP_PRE_PATH%

for /r %%i in (*) do (
	call "%PHP_RMTOOLS_BIN_PATH%\pecl_snap_pre.bat" --upload --is-snap --aggregate-mail --package=%%i
	del %%i
	goto ONLY_ONE
)

if not exist %PHP_RMTOOLS_PECL_IN_SNAP_NOMAIL_PRE_PATH% (
	echo %PHP_RMTOOLS_PECL_IN_SNAP_NOMAIL_PRE_PATH% does not exist
	exit /b 3
)
cd %PHP_RMTOOLS_PECL_IN_SNAP_NOMAIL_PRE_PATH%

for /r %%i in (*) do (
	call "%PHP_RMTOOLS_BIN_PATH%\pecl_snap_pre.bat" --upload --is-snap --package=%%i
	del %%i
	goto ONLY_ONE
)

:ONLY_ONE
cd %PHP_RMTOOLS_BIN_PATH%
echo .

