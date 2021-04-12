# PHP RMTOOLS

Tools PHP release and snapshot build automation for Windows.

# License

The PHP RMTOOLS itself is licensed under the BSD 2-Clause license. With the usage of the other tools, you accept the respective licenses.

# Overview



# Requirements

- Visual Studio
- [PHP-SDK](https://github.com/OSTC/php-sdk-binary-tools)


# Usage

NOTE: All the paths in the usage exampled are on drive C: for simplicity. Locations of PHP SDK and RMTOOLS are customizable and are not bound to a firm path on the system. All the path configuration is editable in the corresponding branch ini files under `rmtools\data\config\branch`


## Preparing


- install [Git](https://git-scm.com/), alternatively - fetch the latest tags for RMTOOLS and PHP SDK
- `md c:\php-snap-build`
- `cd c:\php-snap-build`
- `git clone https://github.com/Microsoft/php-sdk-binary-tools.git php-sdk`
- `git clone https://github.com/php/web-rmtools.git rmtools`
- `md c:\php-snap-build\obj-x64`, or alternatively similar to `mklink /d obj-x64 d:\tmp-obj-x64`
- `md c:\php-snap-build\obj`, or alternatively similar to `mklink /d obj d:\tmp-obj`
- `md C:\php-snap-build\snap_master\vc14\x64`
- `md C:\php-snap-build\snap_master\vc14\x86`
- copy C:\php-snap-build\rmtools\bin\rmtools_setvars.bat-dist to C:\php-snap-build\rmtools\bin\rmtools_setvars.bat, set the appropriate values
- copy C:\php-snap-build\rmtools\data\config\credentials_ftps.php-dist to C:\php-snap-build\rmtools\data\config\credentials_ftps.php, set the appropriate values

## Building

With this configuration, for example for a VC14 64-bit build

- the build dir is C:\php-snap-build\snap_master\vc14\x64
- the object dir is C:\php-snap-build\obj-x64
- the package dir is C:\php-snap-build\obj-x64
- run `c:\php-snap-build\php-sdk\phpsdk-vc14-x64.bat -t c:\php-snap-build\rmtools\bin\snapshot_task.bat --task-args "<branch> <type>"`

`<branch>` is the name of one of the INI files in data\config\branch (e.g. `phpmaster`),
and `<type>` is the name of one of the sections in that INI file (e.g. `nts-windows-vc15-x64`).
`<type>` can also be `all`, to build all types defined in the given INI file.

To make a x86 build, the corresponding starter script from the PHP SDK needs to be used. 


