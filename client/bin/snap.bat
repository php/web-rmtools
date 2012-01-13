@ECHO OFF
SET BAT_DIR=%~dp0
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
SET BISON_SIMPLE=c:\php-sdk\bin\bison.simple

c:\php-sdk\php\php.exe -d extension_dir=c:\php-sdk\php\ext -d extension=php_openssl.dll  -d extension=php_curl.dll %BAT_DIR%\..\script\snap.php %*
SET PATH=%OLD_PATH%