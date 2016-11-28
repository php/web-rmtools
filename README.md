# PHP RMTOOLS

Tools PHP release and snapshot build automation

# License

The PHP RMTOOLS itself is licensed under the BSD 2-Clause license. With the usage of the other tools, you accept the respective licenses.

# Overview



# Requirements

- Visual Studio
- [PHP-SDK](https://github.com/OSTC/php-sdk-binary-tools)


# Usage

NOTE: All the paths in the usage exampled are on drive C: for simplicity. Locations of PHP SDK and RMTOOLS are customizable and are not bound to a firm path on the system. All the path configuration is editable in the corresponding branch ini files under `rmtools\data\config\branch`


## Preparing


- install [Git](https://git-scm.com/)
- `md c:\php-snap-build`
- `cd c:\php-snap-build`
- `git clone https://github.com/OSTC/php-sdk-binary-tools.git --branch new_binary_tools php-sdk`
- `git clone https://github.com/OSTC/web-rmtools.git --branch new_sdk_compliance rmtools`
- `md c:\php-snap-build\obj-x64`, or alternatively similar to `mklink /d obj-x64 d:\tmp-obj-x64`
- `md c:\php-snap-build\obj`, or alternatively similar to `mklink /d obj d:\tmp-obj`
- `md C:\php-snap-build\snap_master\vc14\x64`
- `md C:\php-snap-build\snap_master\vc14\x86`
- copy C:\php-snap-build\rmtools\data\config\credentials_ftps.php-dist to C:\php-snap-build\rmtools\data\config\credentials_ftps.php, set the appropriate values

## Buiding

With this configuration, for example for a 64-bit build

- the build dir is C:\php-snap-build\snap_master\vc14\x64
- the object dir is C:\php-snap-build\obj-x64
- the package dir is C:\php-snap-build\obj-x64
- run `c:\php-snap-build\php-sdk\phpsdk-vc14-x64.bat -t c:\php-snap\build\bin\snapshot_task.bat --task-args "phpmaster all"`

To make a x86 build, the corresponding starter script from the PHP SDK needs to be used. 


