<?php

namespace rmtools;

class PickleExt
{
	protected $name;
	protected $version;
	protected $pkg_uri;
	protected $lic_fnames = array();
	protected $zip_cmd = 'c:\php-sdk\bin\zip.exe';
	protected $unzip_cmd = 'c:\php-sdk\bin\unzip.exe';
	protected $deplister_cmd = 'c:\apps\bin\deplister.exe';
	protected $build;

	protected $pickle_cmd;

	public function __construct($pkg_uri, $build)
	{
		$this->pkg_uri = $pkg_uri;
		$this->build = $build;
	}


	public function init()
	{
		$this->pickle_cmd = $this->build->getPickleCmd();


		$cmd = "{$this->pickle_cmd} info {$this->pkg_uri}";
		$ret = exec_single_log($cmd, NULL, NULL);

		if (!preg_match(",Package name\s+\|\s+([^\s]+)\s+\|,", $ret["log"], $m)) {
			throw new \Exception("Couldn't parse extension name");
		}
		$this->name = $m[1];

		if (!preg_match(",Package version.+\|\s+([a-z0-9\.\-]+)\s+\|,i", $ret["log"], $m)) {
			throw new \Exception("Couldn't parse extension name");
		}
		$this->version = $m[1];
	}

	public function preparePackage()
	{

	}

	public function packLogs()
	{

	}

	public function check()
	{

	}

	protected function createTmpDir()
	{
		$tmp_path = tempnam(TMP_DIR, 'pickle');
		unlink($tmp_path);
		if (!file_exists($tmp_path) && !mkdir($tmp_path)) {
			throw new \Exception("Couldn't create temporary dir");
		}

		return $tmp_path;
	}

	public function getPkgUri()
	{
		return $this->pkg_uri;
	}

	public function cleanup()
	{
		// pass
	}
}


