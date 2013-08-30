<?php

namespace rmtools;

include_once __DIR__ . '/../include/Tools.php';
include_once __DIR__ . '/../include/PeclBranch.php';

class PeclExt
{
	protected $tgz_path;
	protected $name;
	protected $version;
	protected $build;
	protected $tar_cmd = 'c:\apps\git\bin\tar.exe';
	protected $gzip_cmd = 'c:\apps\git\bin\gzip.exe';

	public function __construct($tgz_path, $build)
	{
		if (!file_exists($tgz_path)) {
			throw new \Exception("'$tgz_path' does not exist");
		} else if ('.tgz' != substr($tgz_path, -4)) {
			throw new \Exception("Pecl package should end with .tgz");
		}

		/* Should open package.xml then and retry, but normally the below should work. */
		$tmp = explode('-', basename($tgz_path, '.tgz'));
		$this->name = $tmp[0];
		$this->version = $tmp[1];

		if (!$this->name || !$this->version) {
			throw new \Exception("Couldn't parse extension name or version from the filename");
		}

		$this->tgz_path = $tgz_path;
		$this->build = $build;
	}

	public function getPackageName()
	{
		// looks like php_http-2.0.0beta4-5.3-nts-vc9-x86
		return 'php_' . $this->name
			. '-' . $this->version 
			. '-' . $this->build->branch->config->getBranch()
			. '-' . ($this->build->thread_safe ? 'ts' : 'nts')
			. '-' . $this->build->compiler
			. '-' . $this->build->architecture;
	}

	public function unpack()
	{
		$tmp_path =  TMP_DIR . '/' . $this->getPackageName();
		if (!file_exists($tmp_path) && !mkdir($tmp_path)) {
			throw new \Exception("Couldn't create temporary dir");
		}


		$tmp_name =  $tmp_path . '/' . basename($this->tgz_path);
		if (!rename($this->tgz_path, $tmp_name)) {
			throw new \Exception("Couldn't move the tarball to '$tmp_name'");
		}

		$tar_name =  basename($this->tgz_path, '.tgz') . '.tar';

		/* The tar/gzip versions from the msys package won't work properly with
		the windows paths, but they will if running those just in the current dir.*/
		$old_cwd = getcwd();

		chdir($tmp_path);

		$gzip_cmd = $this->gzip_cmd . ' -df ' . basename($this->tgz_path);
		system($gzip_cmd, $ret);
		if ($ret) {
			throw new \Exception("Failed to guzip the tarball");
		}

		$tar_cmd = $this->tar_cmd . ' -xf ' . $tar_name;
		system($tar_cmd, $ret);
		if ($ret) {
			throw new \Exception("Failed to guzip the tarball");
		}
		unlink($tar_name);

		chdir($old_cwd);

		$ret = realpath($tmp_path . '/' .  basename($this->tgz_path, '.tgz'));

		$this->tgz_path = NULL;

		return $ret;
	}

	public function putSourcesIntoBranch()
	{
		$tmp_path = $this->unpack();
		$ext_dir = $this->build->getSourceDir() . DIRECTORY_SEPARATOR . 'ext';

		$this_ext_dir_path = $ext_dir . DIRECTORY_SEPARATOR . $this->name;
		$res = copy_r($tmp_path, $this_ext_dir_path);
		if (!$res) {
			throw new \Exception("Failed to copy to '$this_ext_dir_path'");
		}
		rmdir_rf($tmp_path);

		return $this_ext_dir_path;
	}

	public function __call($name, $args)
	{
		if ('get' == substr($name, 0, 3)) {
			$prop_name = strtolower(substr($name, 3));
			if (isset($this->$prop_name)) {
				return $this->$prop_name;
			} else {
				return NULL;
			}
		}

		throw new \Exception("Unknown dynamic method called");
	}
}
