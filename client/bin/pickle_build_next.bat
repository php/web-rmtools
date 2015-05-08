@ECHO OFF

rem pick the next pickle pkg and pass to pickle_build_all.bat 
rem first try releases, if there aren't any, look for snaps

rem in contrary to pecl, the pickle build bot doesn't download
rem the releases itself, but get the job files created by the
rem peclweb job script

SET BAT_DIR=%~dp0


rem XXX besides the directories with tarballs, what comes is to implement the pickle.com communication

cd c:\pickle-in-pkg

for /r %%i in (*) do (
	call %BAT_DIR%pickle_build_all.bat --upload --aggregate-mail --package=%%i
	del %%i
	goto ONLY_ONE
)

cd c:\pickle-in-pkg-nomail

for /r %%i in (*) do (
	call %BAT_DIR%pickle_build_all.bat --upload --package=%%i
	del %%i
	goto ONLY_ONE
)

cd c:\pickle-in-snap

for /r %%i in (*) do (
	call %BAT_DIR%pickle_build_all.bat --upload --is-snap --aggregate-mail --package=%%i
	del %%i
	goto ONLY_ONE
)

cd c:\pickle-in-snap-nomail

for /r %%i in (*) do (
	call %BAT_DIR%pickle_build_all.bat --upload --is-snap --package=%%i
	del %%i
	goto ONLY_ONE
)

:ONLY_ONE
cd %BAT_DIR%
echo .

