@ECHO OFF

SET BAT_DIR=%~dp0

set yyyy=%date:~6,4%
set mm=%date:~3,2%
set dd=%date:~0,2%

set hh=%time:~0,2%
if %hh% lss 10 (set hh=0%time:~1,1%)
set nn=%time:~3,2%
set ss=%time:~6,2%
set cur_date=%yyyy%%mm%%dd%-%hh%%nn%%ss%

set LOG_FILE=c:\php-sdk\logs\task-pickle-%cur_date%.log
set RMTOOLS_BASE_DIR=c:\php-sdk\rmtools-client

if "%1"=="" goto :help
if "%1"=="--help" goto :help
if "%1"=="-h" goto :help
if "%1"=="/?" goto :help
goto :skip_help

:help
echo ==========================================================
echo This is the pickle build batch script. You can see the help
echo output of the underlaying worker below. Note that you HAVE
echo TO ommit the --config option when running this batch.
echo ==========================================================
%BAT_DIR%pickle.bat
GOTO EXIT_LOCKED

:skip_help

IF EXIST c:\php-sdk\locks\pickle.lock (
ECHO pickle build script is already running.
GOTO EXIT_LOCKED
)

ECHO running > c:\php-sdk\locks\pickle.lock 

rem Notice the --first and the --last calls marked, that's important
rem to maintain the state between call for the same package. For instance
rem if --aggregate-mail is used.
call %BAT_DIR%pickle.bat --config=pickle70 --first %* >> %LOG_FILE% 2<&1
call %BAT_DIR%pickle.bat --config=pickle56 %* >> %LOG_FILE% 2<&1
call %BAT_DIR%pickle.bat --config=pickle55 %* >> %LOG_FILE% 2<&1
call %BAT_DIR%pickle.bat --config=pickle54 %* >> %LOG_FILE% 2<&1
call %BAT_DIR%pickle.bat --config=pickle53 --last %* >> %LOG_FILE% 2<&1

echo Done.>> %LOG_FILE%

del c:\php-sdk\locks\pickle.lock >> %LOG_FILE% 2<&1

:EXIT_LOCKED
echo .

