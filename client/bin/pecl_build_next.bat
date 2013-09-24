@ECHO OFF

echo pick the next PECL pkg and pass to pecl_build_all.bat 

SET BAT_DIR=%~dp0

cd c:\pecl-in-pkg

for /r %%i in (*) do (
	call %BAT_DIR%pecl_build_all.bat --upload --mail --package=%%i
	del %%i
	goto ONLY_ONE	
)

:ONLY_ONE
cd %BAT_DIR%
echo .

