@ECHO OFF

SET BAT_DIR=%~dp0

set RMTOOLS_BASE_DIR=c:\php-sdk\rmtools-client

set PECL_PHP_CMD=c:\php-sdk\php\php.exe -d memory_limit=2G

if "%1"=="" goto :help
if "%1"=="--help" goto :help
if "%1"=="-h" goto :help
if "%1"=="/?" goto :help
goto :skip_help

:help
%PECL_PHP_CMD% %BAT_DIR%\..\script\pickle.php
GOTO EXIT_LOCKED

:skip_help

CALL c:\php-sdk\bin\phpsdk_setvars.bat

copy c:\php-sdk\rmtools.base\data\config\credentials_ftps.php %RMTOOLS_BASE_DIR%\data\config\ 

REM VC9 Support
SET PSDK_61_DIR=C:\Program Files\Microsoft SDKs\Windows\v6.1
SET VC9_DIR=C:\Program Files (x86)\Microsoft Visual Studio 9.0
SET VC9_SHELL=C:\WINDOWS\system32\cmd.exe /E:ON /V:ON /T:0E /K "C:\Program Files\Microsoft SDKs\Windows\v6.1\Bin\SetEnv.Cmd"
SET VC9_INCLUDE=%PSDK_61_DIR%\include;%VC9_DIR%\VC\ATLMFC\INCLUDE;%VC9_DIR%\VC\INCLUDE
SET VC9_LIB=%PSDK_61_DIR%\lib;%VC9_DIR%\VC\ATLMFC\LIB;%VC9_DIR%\VC\LIB
SET VC9_PATH=%VC9_DIR%\Common7\IDE;%VC9_DIR%\VC\BIN;%VC9_DIR%\Common7\Tools;%VC9_DIR%\VC\VCPackages;%PSDK_61_DIR%\bin;C:\WINDOWS\Microsoft.NET\Framework\v3.5;C:\WINDOWS\Microsoft.NET\Framework\v2.0.50727;C:\WINDOWS\system32;C:\WINDOWS;C:\WINDOWS\System32\Wbem
SET VC9_X64_INCLUDE=%VC9_DIR%\VC\Include;C:\Program Files\Microsoft SDKs\Windows\v6.1\Include;C:\Program Files\Microsoft SDKs\Windows\v6.1\Include\gl;%VC9_DIR%VC\ATLMFC\INCLUDE; 
SET VC9_X64_LIB=%VC9_DIR%\VC\Lib\amd64;C:\Program Files\Microsoft SDKs\Windows\v6.1\Lib\x64;%VC9_DIR%\VC\ATLMFC\LIB\AMD64; 
SET VC9_X64_PATH=%VC9_DIR%\VC\Bin\x86_amd64;%VC9_DIR%\VC\Bin;%VC9_DIR%\VC\vcpackages;%VC9_DIR%\Common7\IDE;C:\Program Files\Microsoft SDKs\Windows\v6.1\Bin;C:\WINDOWS\Microsoft.NET\Framework64\v3.5;C:\WINDOWS\Microsoft.NET\Framework\v3.5;C:\WINDOWS\Microsoft.NET\Framework64\v2.0.50727;C:\WINDOWS\Microsoft.NET\Framework\v2.0.50727;C:\Perl\site\bin;C:\Perl\bin;C:\Program Files\PHP\;C:\WINDOWS\system32;C:\WINDOWS;C:\WINDOWS\System32\Wbem;c:\win2k3cd;C:\Program Files\cvsnt;C:\Program Files\WinSCP\;C:\Program Files\CVSNT\ 
SET OLD_PATH=%PATH%
SET PATH=%PATH%;%VC9_PATH%
SET LIB=%VC9_LIB%
SET INCLUDE=%VC9_INCLUDE%

REM VC11 Support
SET VC11_SHELL=%comspec% /k ""C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\vcvarsall.bat"" x86
SET PSDK_80_DIR=C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0

SET VC11_DIR=C:\Program Files (x86)\Microsoft Visual Studio 11.0
SET VC11_INCLUDE=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\INCLUDE;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\INCLUDE;C:\Program Files (x86)\Windows Kits\8.0\include\shared;C:\Program Files (x86)\Windows Kits\8.0\include\um;C:\Program Files (x86)\Windows Kits\8.0\include\winrt;
SET VC11_LIB=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\LIB;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\LIB;C:\Program Files (x86)\Windows Kits\8.0\lib\win8\um\x86;
SET LIBPATH=C:\Windows\Microsoft.NET\Framework\v4.0.30319;C:\Windows\Microsoft.NET\Framework\v3.5;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\LIB;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\LIB;C:\Program Files (x86)\Windows Kits\8.0\References\CommonConfiguration\Neutral;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0\ExtensionSDKs\Microsoft.VCLibs\11.0\References\CommonConfiguration\neutral;
SET INCLUDE=%VC11_INCLUDE%
SET LIB=%VC11_LIB%

SET VCINSTALLDIR=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\
SET VisualStudioVersion=11.0
SET VS110COMNTOOLS=C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\Tools\
SET VSINSTALLDIR=C:\Program Files (x86)\Microsoft Visual Studio 11.0\
SET DevEnvDir=C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE\
SET ExtensionSdkDir=C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0\ExtensionSDKs
SET WindowsSdkDir=C:\Program Files (x86)\Windows Kits\8.0\
SET WindowsSdkDir_35=C:\Program Files (x86)\Microsoft SDKs\Windows\v7.0A\Bin\
SET WindowsSdkDir_old=C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0A\

SET VC11_PATH=C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE\CommonExtensions\Microsoft\TestWindow;C:\Program Files (x86)\Microsoft SDKs\F#\3.0\Framework\v4.0\;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VSTSDB\Deploy;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE\;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\BIN;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\Tools;C:\Windows\Microsoft.NET\Framework\v4.0.30319;C:\Windows\Microsoft.NET\Framework\v3.5;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\VCPackages;C:\Program Files (x86)\HTML Help Workshop;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Team Tools\Performance Tools;C:\Program Files (x86)\Windows Kits\8.0\bin\x86;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0A\bin\NETFX 4.0 Tools;C:\Program Files (x86)\Microsoft SDKs\Windows\v7.0A\Bin\;C:\Windows\system32;C:\Windows;C:\Windows\System32\Wbem;C:\Windows\System32\WindowsPowerShell\v1.0\;C:\Program Files\Microsoft\Web Platform Installer\;C:\Program Files (x86)\Microsoft ASP.NET\ASP.NET WebPages\v1.0\;C:\Program Files\Microsoft SQL Server\110\Tools\Binn\;C:\Program Files (x86)\Windows Kits\8.0\Windows Performance Toolkit\
SET OLD_PATH=%PATH%
SET PATH=%PATH%;%VC11_PATH%

SET VC11_X64_PATH=C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE\CommonExtensions\Microsoft\TestWindow;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\BIN\amd64;C:\Windows\Microsoft.NET\Framework64\v4.0.30319;C:\Windows\Microsoft.NET\Framework64\v3.5;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\VCPackages;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\Tools;C:\Program Files (x86)\HTML Help Workshop;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Team Tools\Performance Tools\x64;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Team Tools\Performance Tools;C:\Program Files (x86)\Windows Kits\8.0\bin\x64;C:\Program Files (x86)\Windows Kits\8.0\bin\x86;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0A\bin\NETFX 4.0 Tools\x64;C:\Program Files (x86)\Microsoft SDKs\Windows\v7.0A\Bin\x64;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0A\bin\NETFX4.0 Tools;C:\Program Files (x86)\Microsoft SDKs\Windows\v7.0A\Bin\;C:\Windows\system32;C:\Windows;C:\Windows\System32\Wbem;C:\Windows\System32\WindowsPowerShell\v1.0\;C:\Program Files\Microsoft\Web Platform Installer\;C:\Program Files (x86)\Microsoft ASP.NET\ASP.NET Web Pages\v1.0\;C:\Program Files\Microsoft SQL Server\110\Tools\Binn\;C:\Program Files (x86)\Windows Kits\8.0\Windows Performance Toolkit\
SET VC11_X64_INCLUDE=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\INCLUDE;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\INCLUDE;C:\Program Files (x86)\Windows Kits\8.0\include\shared;C:\Program Files (x86)\Windows Kits\8.0\include\um;C:\Program Files (x86)\Windows Kits\8.0\include\winrt;
SET VC11_X64_LIB=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\LIB\amd64;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\LIB\amd64;C:\Program Files (x86)\Windows Kits\8.0\lib\win8\um\x64;
SET VC11_X64_LIBPATH=C:\Windows\Microsoft.NET\Framework64\v4.0.30319;C:\Windows\Microsoft.NET\Framework64\v3.5;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\LIB\amd64;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\LIB\amd64;C:\Program Files (x86)\Windows Kits\8.0\References\CommonConfiguration\Neutral;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0\ExtensionSDKs\Microsoft.VCLibs\11.0\References\CommonConfiguration\neutral;
SET VC11_X64_SHELL=%comspec% /k ""C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\vcvarsall.bat"" amd64

REM Run pecl.php
SET BISON_SIMPLE=c:\php-sdk\bin\bison.simple
rem appending the git path to the end, this should reduce negative effects of command names clashing, fe "find". Pickle embedded within the other PHP scripts seems not be able to pickup the correct git.exe with proc_open(). For the last solution one can try installing msysgit globally on the system with the current installer, that will put git.exe on the path globally.
set PATH=%PATH%;c:\\apps\\git\\bin
@ECHO ON
%PECL_PHP_CMD% %BAT_DIR%..\script\pickle.php %*
@ECHO OFF
SET PATH=%OLD_PATH%

:EXIT_LOCKED
echo .

