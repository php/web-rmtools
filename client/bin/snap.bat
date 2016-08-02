@ECHO OFF
SET BAT_DIR=%~dp0

REM --------VC9--------------
REM SET PSDK_61_DIR=C:\Program Files\Microsoft SDKs\Windows\v6.1
REM SET VC9_DIR=C:\Program Files (x86)\Microsoft Visual Studio 9.0
REM SET VC9_SHELL=C:\WINDOWS\system32\cmd.exe /E:ON /V:ON /T:0E /K "C:\Program Files\Microsoft SDKs\Windows\v6.1\Bin\SetEnv.Cmd"
REM SET VC9_INCLUDE=%PSDK_61_DIR%\include;%VC9_DIR%\VC\ATLMFC\INCLUDE;%VC9_DIR%\VC\INCLUDE
REM SET VC9_LIB=%PSDK_61_DIR%\lib;%VC9_DIR%\VC\ATLMFC\LIB;%VC9_DIR%\VC\LIB
REM SET VC9_PATH=%VC9_DIR%\Common7\IDE;%VC9_DIR%\VC\BIN;%VC9_DIR%\Common7\Tools;%VC9_DIR%\VC\VCPackages;%PSDK_61_DIR%\bin;C:\WINDOWS\Microsoft.NET\Framework\v3.5;C:\WINDOWS\Microsoft.NET\Framework\v2.0.50727;C:\WINDOWS\system32;C:\WINDOWS;C:\WINDOWS\System32\Wbem

REM SET VC9_X64_INCLUDE=%VC9_DIR%\VC\Include;C:\Program Files\Microsoft SDKs\Windows\v6.1\Include;C:\Program Files\Microsoft SDKs\Windows\v6.1\Include\gl;%VC9_DIR%VC\ATLMFC\INCLUDE; 
REM SET VC9_X64_LIB=%VC9_DIR%\VC\Lib\amd64;C:\Program Files\Microsoft SDKs\Windows\v6.1\Lib\x64;%VC9_DIR%\VC\ATLMFC\LIB\AMD64; 
REM SET VC9_X64_PATH=%VC9_DIR%\VC\Bin\x86_amd64;%VC9_DIR%\VC\Bin;%VC9_DIR%\VC\vcpackages;%VC9_DIR%\Common7\IDE;C:\Program Files\Microsoft SDKs\Windows\v6.1\Bin;C:\WINDOWS\Microsoft.NET\Framework64\v3.5;C:\WINDOWS\Microsoft.NET\Framework\v3.5;C:\WINDOWS\Microsoft.NET\Framework64\v2.0.50727;C:\WINDOWS\Microsoft.NET\Framework\v2.0.50727;C:\Perl\site\bin;C:\Perl\bin;C:\Program Files\PHP\;C:\WINDOWS\system32;C:\WINDOWS;C:\WINDOWS\System32\Wbem;c:\win2k3cd;C:\Program Files\cvsnt;C:\Program Files\WinSCP\;C:\Program Files\CVSNT\ 

REM SET OLD_PATH=%PATH%
REM SET PATH=%PATH%;%VC9_PATH%
REM SET LIB=%VC9_LIB%
REM SET INCLUDE=%VC9_INCLUDE%

REM SET BISON_SIMPLE=c:\php-sdk\bin\bison.simple
REM c:\php-sdk\php\php.exe -d extension_dir=c:\php-sdk\php\ext -d extension=php_openssl.dll  -d extension=php_curl.dll %BAT_DIR%\..\script\snap.php %*
REM SET PATH=%OLD_PATH%


REM --------VC11--------------
REM SET VC11_SHELL=%comspec% /k ""C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\vcvarsall.bat"" x86

REM SET PSDK_80_DIR=C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0
REM SET VC11_DIR=C:\Program Files (x86)\Microsoft Visual Studio 11.0

REM SET VC11_INCLUDE=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\INCLUDE;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\INCLUDE;C:\Program Files (x86)\Windows Kits\8.0\include\shared;C:\Program Files (x86)\Windows Kits\8.0\include\um;C:\Program Files (x86)\Windows Kits\8.0\include\winrt;
REM SET VC11_LIB=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\LIB;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\LIB;C:\Program Files (x86)\Windows Kits\8.0\lib\win8\um\x86;
REM SET LIBPATH=C:\Windows\Microsoft.NET\Framework\v4.0.30319;C:\Windows\Microsoft.NET\Framework\v3.5;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\LIB;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\LIB;C:\Program Files (x86)\Windows Kits\8.0\References\CommonConfiguration\Neutral;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0\ExtensionSDKs\Microsoft.VCLibs\11.0\References\CommonConfiguration\neutral;
REM SET INCLUDE=%VC11_INCLUDE%
REM SET LIB=%VC11_LIB%

REM SET VCINSTALLDIR=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\
REM SET VisualStudioVersion=11.0
REM SET VS110COMNTOOLS=C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\Tools\
REM SET VSINSTALLDIR=C:\Program Files (x86)\Microsoft Visual Studio 11.0\
REM SET DevEnvDir=C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE\
REM SET ExtensionSdkDir=C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0\ExtensionSDKs
REM SET WindowsSdkDir=C:\Program Files (x86)\Windows Kits\8.0\
REM SET WindowsSdkDir_35=C:\Program Files (x86)\Microsoft SDKs\Windows\v7.0A\Bin\
REM SET WindowsSdkDir_old=C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0A\

REM SET VC11_PATH=C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE\CommonExtensions\Microsoft\TestWindow;C:\Program Files (x86)\Microsoft SDKs\F#\3.0\Framework\v4.0\;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VSTSDB\Deploy;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE\;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\BIN;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\Tools;C:\Windows\Microsoft.NET\Framework\v4.0.30319;C:\Windows\Microsoft.NET\Framework\v3.5;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\VCPackages;C:\Program Files (x86)\HTML Help Workshop;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Team Tools\Performance Tools;C:\Program Files (x86)\Windows Kits\8.0\bin\x86;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0A\bin\NETFX 4.0 Tools;C:\Program Files (x86)\Microsoft SDKs\Windows\v7.0A\Bin\;C:\Windows\system32;C:\Windows;C:\Windows\System32\Wbem;C:\Windows\System32\WindowsPowerShell\v1.0\;C:\Program Files\Microsoft\Web Platform Installer\;C:\Program Files (x86)\Microsoft ASP.NET\ASP.NET WebPages\v1.0\;C:\Program Files\Microsoft SQL Server\110\Tools\Binn\;C:\Program Files (x86)\Windows Kits\8.0\Windows Performance Toolkit\
REM SET OLD_PATH=%PATH%
REM SET PATH=%PATH%;%VC11_PATH%

REM SET VC11_X64_PATH=C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE\CommonExtensions\Microsoft\TestWindow;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\BIN\amd64;C:\Windows\Microsoft.NET\Framework64\v4.0.30319;C:\Windows\Microsoft.NET\Framework64\v3.5;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\VCPackages;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\IDE;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Common7\Tools;C:\Program Files (x86)\HTML Help Workshop;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Team Tools\Performance Tools\x64;C:\Program Files (x86)\Microsoft Visual Studio 11.0\Team Tools\Performance Tools;C:\Program Files (x86)\Windows Kits\8.0\bin\x64;C:\Program Files (x86)\Windows Kits\8.0\bin\x86;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0A\bin\NETFX 4.0 Tools\x64;C:\Program Files (x86)\Microsoft SDKs\Windows\v7.0A\Bin\x64;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0A\bin\NETFX4.0 Tools;C:\Program Files (x86)\Microsoft SDKs\Windows\v7.0A\Bin\;C:\Windows\system32;C:\Windows;C:\Windows\System32\Wbem;C:\Windows\System32\WindowsPowerShell\v1.0\;C:\Program Files\Microsoft\Web Platform Installer\;C:\Program Files (x86)\Microsoft ASP.NET\ASP.NET Web Pages\v1.0\;C:\Program Files\Microsoft SQL Server\110\Tools\Binn\;C:\Program Files (x86)\Windows Kits\8.0\Windows Performance Toolkit\;C:\apps\apache24\php_deps
REM SET VC11_X64_INCLUDE=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\INCLUDE;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\INCLUDE;C:\Program Files (x86)\Windows Kits\8.0\include\shared;C:\Program Files (x86)\Windows Kits\8.0\include\um;C:\Program Files (x86)\Windows Kits\8.0\include\winrt;
REM SET VC11_X64_LIB=C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\LIB\amd64;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\LIB\amd64;C:\Program Files (x86)\Windows Kits\8.0\lib\win8\um\x64;
REM SET VC11_X64_LIBPATH=C:\Windows\Microsoft.NET\Framework64\v4.0.30319;C:\Windows\Microsoft.NET\Framework64\v3.5;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\LIB\amd64;C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\ATLMFC\LIB\amd64;C:\Program Files (x86)\Windows Kits\8.0\References\CommonConfiguration\Neutral;C:\Program Files (x86)\Microsoft SDKs\Windows\v8.0\ExtensionSDKs\Microsoft.VCLibs\11.0\References\CommonConfiguration\neutral;
REM SET VC11_X64_SHELL=%comspec% /k ""C:\Program Files (x86)\Microsoft Visual Studio 11.0\VC\vcvarsall.bat"" amd64


REM --------VC14--------------
SET VC14_SHELL=%comspec% /k ""C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\vcvarsall.bat"" x86
SET PSDK_81_DIR=C:\Program Files (x86)\Windows Kits\8.1\
SET VC14_DIR=C:\Program Files (x86)\Microsoft Visual Studio 14.0\

SET DevEnvDir=C:\Program Files (x86)\Microsoft Visual Studio 14.0\Common7\IDE\
SET VC14_INCLUDE=C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\INCLUDE;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\ATLMFC\INCLUDE;C:\Program Files (x86)\Windows Kits\10\include\10.0.10240.0\ucrt;C:\Program Files (x86)\Windows Kits\NETFXSDK\4.6.1\include\um;C:\Program Files (x86)\Windows Kits\10\include\10.0.10240.0\shared;C:\Program Files (x86)\Windows Kits\10\include\10.0.10240.0\um;C:\Program Files (x86)\Windows Kits\10\include\10.0.10240.0\winrt; 

SET VC14_LIB=C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\LIB;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\ATLMFC\LIB;C:\Program Files (x86)\Windows Kits\10\lib\10.0.10240.0\ucrt\x86;C:\Program Files (x86)\Windows Kits\NETFXSDK\4.6.1\lib\um\x86;C:\Program Files (x86)\Windows Kits\10\lib\10.0.10240.0\um\x86; 

SET LIBPATH=C:\Windows\Microsoft.NET\Framework\v4.0.30319;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\LIB;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\ATLMFC\LIB;References\CommonConfiguration\Neutral;\Microsoft.VCLibs\14.0\References\CommonConfiguration\neutral;

SET INCLUDE=%VC14_INCLUDE%
SET LIB=%VC14_LIB%

SET VCINSTALLDIR=C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC
SET VisualStudioVersion=14.0
SET UniversalCRTSdkDir=C:\Program Files (x86)\Windows Kits\10\
SET VCINSTALLDIR=C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\
SET VS140COMNTOOLS=C:\Program Files (x86)\Microsoft Visual Studio 14.0\Common7\Tools\
SET VSINSTALLDIR=C:\Program Files (x86)\Microsoft Visual Studio 14.0\
SET WindowsSdkDir=C:\Program Files (x86)\Windows Kits\8.1\
SET WindowsSDK_ExecutablePath_x64=C:\Program Files (x86)\Microsoft SDKs\Windows\v10.0A\bin\NETFX 4.6 Tools\x64\
SET WindowsSDK_ExecutablePath_x86=C:\Program Files (x86)\Microsoft SDKs\Windows\v10.0A\bin\NETFX 4.6 Tools\

SET VC14_PATH=C:\Program Files (x86)\Microsoft Visual Studio 14.0\Common7\IDE\CommonExtensions\Microsoft\TestWindow;C:\Program Files (x86)\MSBuild\14.0\bin;C:\Program Files (x86)\Microsoft Visual Studio 14.0\Common7\IDE\;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\BIN;C:\Program Files (x86)\Microsoft Visual Studio 14.0\Common7\Tools;C:\Windows\Microsoft.NET\Framework\v4.0.30319;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\VCPackages;C:\Program Files (x86)\HTML Help Workshop;C:\Program Files (x86)\Microsoft Visual Studio 14.0\Team Tools\Performance Tools;C:\Program Files (x86)\Windows Kits\10\bin\x86;C:\Program Files (x86)\Microsoft SDKs\Windows\v10.0A\bin\NETFX 4.6.1 Tools\;C:\Program Files (x86)\Mail Enable\BIN;C:\Windows\system32;C:\Windows;C:\Windows\System32\Wbem;C:\Windows\System32\WindowsPowerShell\v1.0\;C:\Program Files\Microsoft\Web Platform Installer\;C:\Program Files (x86)\Microsoft ASP.NET\ASP.NET Web Pages\v1.0\;C:\Program Files\Microsoft SQL Server\110\Tools\Binn\;C:\apps\bin;C:\Program Files\TortoiseSVN\bin;C:\Program Files (x86)\Mail Enable\BIN64;C:\Program Files\Microsoft SQL Server\120\Tools\Binn\;%USERPROFILE%\.dnx\bin;C:\Program Files\Microsoft DNX\Dnvm\;C:\Program Files (x86)\Windows Kits\10\Windows Performance Toolkit\;C:\Users\anatol\.dnx\bin;C:\Program Files (x86)\CMake 2.8\bin 

SET OLD_PATH=%PATH%
SET PATH=%PATH%;%VC14_PATH%

SET VC14_X64_PATH=C:\Program Files (x86)\Microsoft Visual Studio 14.0\Common7\IDE\CommonExtensions\Microsoft\TestWindow;C:\Program Files (x86)\MSBuild\14.0\bin\amd64;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\BIN\amd64;C:\Windows\Microsoft.NET\Framework64\v4.0.30319;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\VCPackages;C:\Program Files (x86)\Microsoft Visual Studio 14.0\Common7\IDE;C:\Program Files (x86)\Microsoft Visual Studio 14.0\Common7\Tools;C:\Program Files (x86)\HTML Help Workshop;C:\Program Files (x86)\Microsoft Visual Studio 14.0\Team Tools\Performance Tools\x64;C:\Program Files (x86)\Microsoft Visual Studio 14.0\Team Tools\Performance Tools;C:\Program Files (x86)\Windows Kits\10\bin\x64;C:\Program Files (x86)\Windows Kits\10\bin\x86;C:\Program Files (x86)\Microsoft SDKs\Windows\v10.0A\bin\NETFX 4.6.1 Tools\x64\;C:\Program Files (x86)\Mail Enable\BIN;C:\Windows\system32;C:\Windows;C:\Windows\System32\Wbem;C:\Windows\System32\WindowsPowerShell\v1.0\;C:\Program Files\Microsoft\Web Platform Installer\;C:\Program Files (x86)\Microsoft ASP.NET\ASP.NET Web Pages\v1.0\;C:\Program Files\Microsoft SQL Server\110\Tools\Binn\;C:\apps\bin;C:\Program Files\TortoiseSVN\bin;C:\Program Files (x86)\Mail Enable\BIN64;C:\Program Files\Microsoft SQL Server\120\Tools\Binn\;%USERPROFILE%\.dnx\bin;C:\Program Files\Microsoft DNX\Dnvm\;C:\Program Files (x86)\Windows Kits\10\Windows Performance Toolkit\;C:\Users\anatol\.dnx\bin;C:\Program Files (x86)\CMake 2.8\bin 

SET VC14_X64_INCLUDE=C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\INCLUDE;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\ATLMFC\INCLUDE;C:\Program Files (x86)\Windows Kits\10\include\10.0.10240.0\ucrt;C:\Program Files (x86)\Windows Kits\NETFXSDK\4.6.1\include\um;C:\Program Files (x86)\Windows Kits\10\include\10.0.10240.0\shared;C:\Program Files (x86)\Windows Kits\10\include\10.0.10240.0\um;C:\Program Files (x86)\Windows Kits\10\include\10.0.10240.0\winrt; 

SET VC14_X64_LIB=C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\LIB\amd64;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\ATLMFC\LIB\amd64;C:\Program Files (x86)\Windows Kits\10\lib\10.0.10240.0\ucrt\x64;C:\Program Files (x86)\Windows Kits\NETFXSDK\4.6.1\lib\um\x64;C:\Program Files (x86)\Windows Kits\10\lib\10.0.10240.0\um\x64; 

SET VC14_X64_LIBPATH=C:\Windows\Microsoft.NET\Framework64\v4.0.30319;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\LIB\amd64;C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\ATLMFC\LIB\amd64;References\CommonConfiguration\Neutral;\Microsoft.VCLibs\14.0\References\CommonConfiguration\neutral;
SET VC14_X64_SHELL=%comspec% /k ""C:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\vcvarsall.bat"" amd64


REM --------BUILD--------------
SET BISON_SIMPLE=c:\php-sdk\bin\bison.simple
c:\php-sdk\php\php.exe -d extension_dir=c:\php-sdk\php\ext -d extension=php_openssl.dll  -d extension=php_curl.dll %BAT_DIR%\..\script\snap.php %*
SET PATH=%OLD_PATH%

