@ECHO OFF

echo pick the next PECL pkg and pass to pecl_build_all.bat 

SET BAT_DIR=%~dp0

%PECL_PHP_CMD% %BAT_DIR%\..\script\pecl_mail.php

cd c:\pecl-in-pkg

for /r %%i in (*) do (
	call %BAT_DIR%pecl_build_all.bat --upload --aggregate-mail --package=%%i
	del %%i
	goto ONLY_ONE	
)

:ONLY_ONE
cd %BAT_DIR%
echo .

