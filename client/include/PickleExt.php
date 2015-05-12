<?php

namespace rmtools;

class PickleExt
{
	protected $name;
	protected $vendor;
	protected $version;
	protected $pkg_uri;
	protected $lic_fnames = array();
	protected $zip_cmd = 'c:\php-sdk\bin\zip.exe';
	protected $unzip_cmd = 'c:\php-sdk\bin\unzip.exe';
	protected $deplister_cmd = 'c:\apps\bin\deplister.exe';
	protected $build;
	protected $pkg_config;
	protected $conf_opts;

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
		$ret = exec_single_log($cmd, NULL, $this->build->getEnv());

		if ($ret["return_value"]) {
			throw new \Exception("'$cmd' resulted  error: '" . $ret["log"] . "'");
		}

		if (!preg_match(",Package name\s+\|\s+([^\s]+)\s+\|,", $ret["log"], $m)) {
			throw new \Exception("Couldn't parse extension name");
		}
		$this->setupNames($m[1]);

		if (!preg_match(",Package version.+\|\s+([a-z0-9\.\-]+)\s+\|,i", $ret["log"], $m)) {
			throw new \Exception("Couldn't parse extension version");
		}
		$this->version = $m[1];
	}

	public function getName()
	{
		return $this->name;
	}

	public function getVendor()
	{
		return $this->vendor;
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
		$this->cleanConfigureOpts();
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

	/* Depending on other extensions is neither handled nor supported by phpize.
		This will need significant change in the PHP build, but still doable.
		Or, it'll need some quite tricky business inside rmtools, still doable
		as well. */
	public function getConfigureOpts()
	{
		$config = $this->getPackageConfig();

		/* Ext isn't known, force pickle --defaults */
		if (!$config) {
			return NULL;
		}

		$conf = $this->buildConfigureLine($config);
		$conf .= " --enable-debug-pack";

		$fn = $this->build->getIntDir() . DIRECTORY_SEPARATOR . $this->getName() . ".conf";
		if (strlen($conf) != file_put_contents($fn, $conf)) {
			throw new \Exception("Error writing build config into '$fn'");
		}

		$this->conf_opts = $fn;
		return $this->conf_opts;
	}

	public function cleanConfigureOpts()
	{
		if (file_exists($this->conf_opts)) {
			unlink($this->conf_opts);
		}
	}

	protected function buildConfigureLine(array $data)
	{
		$ret = '';
		$ignore_main_opt = false;

		if (!isset($data['type'])
			|| !in_array($data['type'], array('with', 'enable'))) {
			throw new \Exception("Unknown extention configure type, expected enable/with");
		}

		$main_opt = '--' . $data['type'] . '-' . str_replace('_', '-', $this->name);

		if (isset($data['opts']) && $data['opts']) {
			$data['opts'] = !is_array($data['opts']) ? array($data['opts']) : $data['opts'];
			foreach($data['opts'] as $opt) {
				if ($opt) {
					/* XXX simple check for opt syntax */
					$ret .= ' "' . $opt . '" ';
				}
				/* the main enable/with option was overridden in the ini */
				if (strstr($opt, "$main_opt=") !== false) {
					$ignore_main_opt = true;
				}
			}
		} else {
			$data['opts'] = array();
		}

		$ignore_main_opt = $ignore_main_opt || isset($data['no_conf']);

		if (!$ignore_main_opt) {
			$ret .=  ' "' . $main_opt . '=shared" ';
		}

		/* XXX this defines the core libraries as extra, maybe there's a better way. */
		$extra_lib = array($this->build->core_deps_base . DIRECTORY_SEPARATOR . "lib");
		$extra_inc = array($this->build->core_deps_base . DIRECTORY_SEPARATOR . "include");

		if (isset($data['libs']) && $data['libs']) {
			$data['libs'] = !is_array($data['libs']) ? array($data['libs']) : $data['libs'];
			$deps_path = $this->build->pecl_deps_base;

			foreach($data['libs'] as $lib) {
				if (!$lib) {
					continue;
				}

				$lib_conf = $this->getLibraryConfig($lib);

				$lib_path = $deps_path . DIRECTORY_SEPARATOR . $lib;

				$some_lib_path = $lib_path . DIRECTORY_SEPARATOR . 'lib';
				if (!file_exists($some_lib_path)) {
					throw new \Exception("Path '$some_lib_path' doesn't exist");
				}
				$extra_lib[] = $some_lib_path;	

				$some_lib_inc_path =  $lib_path . DIRECTORY_SEPARATOR . 'include';
				if (!file_exists($some_lib_inc_path)) {
					throw new \Exception("Path '$some_lib_inc_path' doesn't exist");
				}
				$extra_inc[] = $some_lib_inc_path;	

				/* If expand_include not set, consider true. */
				if (!isset($lib_conf['expand_include']) || $lib_conf['expand_include']) {
					$dirs = glob("$some_lib_inc_path/*", GLOB_ONLYDIR);
					foreach ($dirs as $dir) {
						$extra_inc[] = $dir;
					}
				}
			}
		} else {
			$data['libs'] = array();
		}

		$ret .= ' "--with-extra-libs=' . implode(';', $extra_lib) . '" '
			. ' "--with-extra-includes=' . implode(';', $extra_inc) . '" ';

		$this->configure_data = $data;

		return $ret;
	}

	public function getLibraryConfig($name)
	{
		$ret = array();

		$known_path = __DIR__ . '/../data/config/pickle/libs.ini';
		$lib_conf = parse_ini_file($known_path, true, INI_SCANNER_RAW);

		if (isset($lib_conf[$name]) && is_array($lib_conf[$name])) {
			$ret = $lib_conf[$name];
		}

		return $ret;
	}

	protected function setupNames($full_name)
	{
		if (preg_match(",(.+)/(.+),", $full_name, $m)) {
			$this->name = $m[2];
			$this->vendor = $m[1];
		} else {
			throw new \Exception("Couldn't parse vendor from '$full_name'");
		}
	}
}

