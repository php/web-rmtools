@ECHO OFF
set RMTOOLS_BASE_DIR=d:\php-sdk\rmtools-client

cd %RMTOOLS_BASE_DIR%
copy %RMTOOLS_BASE_DIR%\data\db\* D:\php-sdk\rmtools.base\data\db\

rmdir /q /s rmtools-client

svn export --quiet https://svn.php.net/repository/web/php-rmtools/trunk/client rmtools-client

copy D:\php-sdk\rmtools.base\data\config\credentials_ftps.php %RMTOOLS_BASE_DIR%\data\config\
copy D:\php-sdk\rmtools.base\data\db\* %RMTOOLS_BASE_DIR%\data\db\

IF EXIST %RMTOOLS_BASE_DIR%\data\snaps.lock (
	ECHO Snapshot script is already running > %RMTOOLS_BASE_DIR%\data\snaps.lock
	GOTO EXIT_LOCKED
)

ECHO running > %RMTOOLS_BASE_DIR%\data\snaps.lock
d:\php-sdk\rmtools-client\bin\snap.bat php53
rem d:\php-sdk\mtools-client\bin\snap.bat phptrunk

del %RMTOOLS_BASE_DIR%\data\snaps.lock


:EXIT_LOCKED
