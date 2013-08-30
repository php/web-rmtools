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
	protected $zip_cmd = 'c:\php-sdk\bin\zip.exe';
	protected $tmp_extract = NULL;
	protected $tmp_package_xml = NULL;
	protected $ext_dir_in_src_path = NULL;

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
		$tmp_path =  TMP_DIR . '/' . $this->getPackageName() . '-unpack';
		if (!file_exists($tmp_path) && !mkdir($tmp_path)) {
			throw new \Exception("Couldn't create temporary dir");
		}

		$tmp_name =  $tmp_path . '/' . basename($this->tgz_path);
		if (!copy($this->tgz_path, $tmp_name)) {
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

		$this->tmp_extract = realpath($tmp_path . '/' .  basename($this->tgz_path, '.tgz'));

		if (file_exists($tmp_path . DIRECTORY_SEPARATOR . 'package.xml')) {
			$this->tmp_package_xml = $tmp_path . DIRECTORY_SEPARATOR . 'package.xml';
		}

		$this->tgz_path = NULL;

		return $this->tmp_extract;
	}

	public function putSourcesIntoBranch()
	{
		if (!$this->tmp_extract) {
			$this->tmp_extract = $this->unpack();
		}
		$ext_dir = $this->build->getSourceDir() . DIRECTORY_SEPARATOR . 'ext';

		$this->ext_dir_in_src_path = $ext_dir . DIRECTORY_SEPARATOR . $this->name;
		$res = copy_r($this->tmp_extract, $this->ext_dir_in_src_path);
		if (!$res) {
			throw new \Exception("Failed to copy to '{$this->ext_dir_in_src_path}'");
		}

		return $this->ext_dir_in_src_path;
	}

	public function getConfigureLine()
	{
		/* XXX check if it's enable or with,
			what deps it has
			what additional options it has
			what non core exts it deps on 
		*/

		$ret = '';

		$ret = ' "--enable-' . $this->name . '=shared" ';


		return $ret;
	}

	public function preparePackage()
	{
		/* XXX check if there are any dep dll/pdb to put together */
		$sub = $this->build->thread_safe ? 'Release_TS' : 'Release';
		$base = $this->build->getObjDir() . DIRECTORY_SEPARATOR . $sub;
		$target = TMP_DIR . DIRECTORY_SEPARATOR . $this->getPackageName();

		$dll_name = 'php_' . $this->name . '.dll';
		$dll_file = $target . DIRECTORY_SEPARATOR . $dll_name;
		if (!copy($base . DIRECTORY_SEPARATOR . $dll_name, $dll_file)) {
			throw new \Exception("Couldn't copy '$dll_name' into '$target'");
		}
		
		$pdb_name = 'php_' . $this->name . '.pdb';
		$pdb_file = $target . DIRECTORY_SEPARATOR . $pdb_name;
		if (!copy($base . DIRECTORY_SEPARATOR . $pdb_name, $pdb_file)) {
			throw new \Exception("Couldn't copy '$pdb_name' into '$target'");
		}


		/* pack */
		$zip_file = TMP_DIR . DIRECTORY_SEPARATOR . $this->getPackageName() . '.zip';
		$zip_cmd = $this->zip_cmd . ' -9 -D -j ' . $zip_file . ' ' . $dll_file . ' ' . $pdb_file;
		system($zip_cmd, $status);
		if ($status) {
			throw new \Exception("Couldn't zip files for $zip_file");
		}

		return $zip_file;
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

	public function cleanup()
	{
		if ($this->tmp_extract) {
			rmdir_rf(dirname($this->tmp_extract));
		}
		/*if ($this->tmp_package_xml) {
			unlink($this->tmp_package_xml);
		}*/
		if ($this->ext_dir_in_src_path) {
			rmdir_rf($this->ext_dir_in_src_path);
		}
	}

	public function check()
	{
		if (!$this->tmp_extract) {
			throw new \Exception("Tarball isn't yet extracted");
		}

		if (!file_exists($this->tmp_extract . DIRECTORY_SEPARATOR . 'config.w32')) {
			throw new \Exception("config.w32 not found");
		}
	}
}

