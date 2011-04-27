@ECHO OFF
set yyyy=%date:~6,4%
set mm=%date:~3,2%
set dd=%date:~0,2%

set hh=%time:~0,2%
if %hh% lss 10 (set hh=0%time:~1,1%)
set nn=%time:~3,2%
set ss=%time:~6,2%
set cur_date=%yyyy%%mm%%dd%-%hh%%nn%%ss%

set LOG_FILE=D:\php-sdk\logs\task-%cur_date%.log
set RMTOOLS_BASE_DIR=d:\php-sdk\rmtools-client

IF EXIST D:\php-sdk\locks\snaps.lock (
	ECHO Snapshot script is already running. >> %LOG_FILE%
	GOTO EXIT_LOCKED
)

ECHO running >> %LOG_FILE%

CALL d:\php-sdk\bin\phpsdk_setvars.bat

rmdir /q /s %RMTOOLS_BASE_DIR% >> %LOG_FILE% 2<&1

svn export --quiet --force https://svn.php.net/repository/web/php-rmtools/trunk/client %RMTOOLS_BASE_DIR% >> %LOG_FILE% 2<&1

copy D:\php-sdk\rmtools.base\data\config\credentials_ftps.php %RMTOOLS_BASE_DIR%\data\config\ >> %LOG_FILE% 2<&1
copy D:\php-sdk\rmtools.base\data\db\* %RMTOOLS_BASE_DIR%\data\db\ >> %LOG_FILE% 2<&1

d:\php-sdk\rmtools-client\bin\snap.bat php53 >> %LOG_FILE% 2<&1
rem d:\php-sdk\mtools-client\bin\snap.bat phptrunk

copy %RMTOOLS_BASE_DIR%\data\db\* D:\php-sdk\rmtools.base\data\db\ >> %LOG_FILE% 2<&1
del %RMTOOLS_BASE_DIR%\data\snaps.lock >> %LOG_FILE% 2<&1

:EXIT_LOCKED
echo Done.>> %LOG_FILE%