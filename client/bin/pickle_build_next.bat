@ECHO OFF

rem pick the next pickle pkg and pass to pickle_build_all.bat 
rem first try releases, if there aren't any, look for snaps

rem in contrary to pecl, the pickle build bot doesn't download
rem the releases itself, but get the job files created by the
rem peclweb job script

SET BAT_DIR=%~dp0


rem jobs first, sourceballs then (will be rare supposedly);
rem btw aggregate mail and co isn't implemented, just left as it'll maybe need to be

cd c:\pickle-in-job

for /r %%i in (*) do (
	call %BAT_DIR%pickle_build_all.bat --upload --aggregate-mail --job=%%i
	del %%i
	goto ONLY_ONE
)


cd c:\pickle-in-snap-job

for /r %%i in (*) do (
	call %BAT_DIR%pickle_build_all.bat --upload --is-snap --aggregate-mail --job=%%i
	del %%i
	goto ONLY_ONE
)


cd c:\pickle-in-pkg

for /r %%i in (*) do (
	call %BAT_DIR%pickle_build_all.bat --upload --aggregate-mail --package=%%i
	del %%i
	goto ONLY_ONE
)

rem cd c:\pickle-in-pkg-nomail

rem for /r %%i in (*) do (
rem 	call %BAT_DIR%pickle_build_all.bat --upload --package=%%i
rem 	del %%i
rem 	goto ONLY_ONE
rem )

cd c:\pickle-in-snap

for /r %%i in (*) do (
	call %BAT_DIR%pickle_build_all.bat --upload --is-snap --aggregate-mail --package=%%i
	del %%i
	goto ONLY_ONE
)

rem cd c:\pickle-in-snap-nomail

rem for /r %%i in (*) do (
rem 	call %BAT_DIR%pickle_build_all.bat --upload --is-snap --package=%%i
rem 	del %%i
rem 	goto ONLY_ONE
rem )

:ONLY_ONE
cd %BAT_DIR%
echo .

