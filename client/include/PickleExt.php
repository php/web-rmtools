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
	protected $composer_json = NULL;
	protected $composer_json_path = NULL;

	public function __construct($pkg_uri)
	{
		$this->pkg_uri = $pkg_uri;
	}


	public function init()
	{
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


