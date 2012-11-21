<?php
namespace rmtools;

class BranchConfig {
	private $config = NULL;
	private $config_keys = array(
		'ConfigureOptions' => 'configure_options',
		'BuildDir'		=> 'build_dir',
		'BuildLocation'	=> 'build_location',
		'SourceDir'		=> 'source_dir',
		'Include'		=> 'INCLUDE',
		'Lib'			=> 'LIB',
		'Path'			=> 'PATH',
		'Compiler'		=> 'compiler',
		'Architecture'	=> 'arch',
		'Branch'		=> 'branch',
		'Name'			=> 'name',
		'RepoName'		=> 'repo_name',
		'RepoBranch'	=> 'repo_branch',
		'Module' 		=> 'repo_module',
		'PGO' 			=> 'pgo',
		'Debug'		 	=> 'debug',
		'Appver' 		=> 'appver',
	);

	function __construct($path)
	{
		//$this->config = parse_ini_file($path, false, INI_SCANNER_RAW);
		$this->config = parse_ini_file($path, true,INI_SCANNER_RAW);
		if (!$this->config) {
			throw new \Exception('Cannot parse config file <' . $path . '>');
		}

		$builds = array();

		foreach ($this->config as $name => $entry) {
			if (substr($name, 0, 6) == 'build-') {
				$name = str_ireplace('build-', '', $name);
				$builds[$name] = $entry;
			}
		}
		$this->builds = $builds;
	}

	function __call($name, $key) {
		$name = str_replace('get', '', $name);
		if (!isset($this->config_keys[$name])) {
			throw new \Exception("Invalid config entry name <$name>");
		}

		$name = $this->config_keys[$name];
		return (isset($this->config[$name]) ? $this->config[$name] : '');
	}

	function getBuildList()
	{
		return $this->builds;
	}

	function getBuildFromName($build_name) {
		return isset($this->builds[$build_name]) ? $this->builds[$build_name] : NULL;
	}
	
	function getConfigureOptions($build_name)
	{
		if (isset($this->builds[$build_name]) && isset($this->builds[$build_name]['configure_options'])) {
			return $this->builds[$build_name]['configure_options'];
		} else {
			return '';
		}
	}
}
