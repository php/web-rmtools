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
	protected $pkg_config;

	protected $pickle_cmd;

	public function __construct($pkg_uri, $build)
	{
		if (!file_exists($pkg_uri)) {
			throw new \Exception("'$pkg_uri' does not exist");
		} 

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

	public function getName()
	{
		return $this->name;
	}

	public function getVersion()
	{
		return $this->version;
	}

	public function preparePackage()
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

	protected function complexPkgNameMatch($cnf_name) {
		$full_set = array(
			$this->build->branch->config->getBranch(),
			($this->build->thread_safe ? 'ts' : 'nts'),
			$this->build->compiler,
			$this->build->architecture,
		);

if (!function_exists('rmtools\combinations')) {
		function combinations($arr, $level, &$result, $that, $curr=array()) {
			for($i = 0; $i < count($arr); $i++) {
				$new = array_merge($curr, array($arr[$i]));
				if($level == 1) {
					/* preserve order */
					sort($new);
					/* no repititions */
					$new = array_unique($new);
					$name = $that->getName() . '-' . implode('-', $new);
					if (!in_array($name, $result)) {
						$result[] = $name;
					}
				} else {
					combinations($arr, $level - 1, $result, $that, $new);
				}
			}
		}
}

		$names = array();
		for ($i = 0; $i<count($full_set); $i++) {
			combinations($full_set, $i+1, $names, $this);
		}

		foreach ($names as $name) {
			if ($name === $cnf_name) {
				return true;
			}
		}

		return false;
	}

	public function getPackageConfig()
	{
		$config = NULL;

		if ($this->pkg_config) {
			return $this->pkg_config;
		}

		$known_path = __DIR__ . '/../data/config/pickle/exts.ini';
		$exts = parse_ini_file($known_path, true, INI_SCANNER_RAW);

		/* Check for like myext-5.3-ts-vc9-x86 */
		foreach ($exts as $name => $conf) {
			if ($this->complexPkgNameMatch($name)) {
				$config = $conf;
				break;
			}
		}

		/* XXX this here might be redundant */
		if (!$config) {
			foreach ($exts as $name => $conf) {
				if ($name === $this->name) {
					$config = $conf;
					break;
				}
			}
		}

		$this->pkg_config = $config;

		return $config;
	}

	/* ignore me */
	public function sendToCoventry()
	{
		$config = $this->getPackageConfig();

		return $config && isset($config['ignore']);
	}
}


