<?php
namespace rmtools;

include __DIR__ . '/PeclBuildVC.php';

/* Primitive branch which maps to some unversioned local source tree. It'll not care about any revisions, etc. */
class PeclBranch {
	public $config;
	public $builds;

	public function __construct($config_path)
	{
		if (!is_readable($config_path)) {
			throw new \Exception('Cannot open config data <' . $config_path . '>');
		}
		$this->config = new BranchConfig($config_path);
		$this->addBuildList();
	}

	private function addBuildList()
	{
		$builds = $this->config->getBuildList();

		if (!empty($builds)) {
			$this->builds = array();
			foreach ($builds as $n => $v) {
				$this->builds[] = $n;
			}
		} else {
				$this->builds = NULL;
		}
	}

	public function update()
	{
		// pass
	}

	public function hasNewRevision()
	{
		return false;
	}

	public function export($revision = false, $build_type = false, $zip = false, $is_zip = false)
	{
		// TODO
	}

	public function createSourceSnap($build_type = false, $revision = false)
	{
		return $this->export($revision, $build_type, true);
	}

	public function setLastRevisionExported($last_rev)
	{
		// pass
	}

	public function getLastRevisionExported()
	{
		return NULL;
	}

	public function getLastRevisionId()
	{
		return NULL;
	}

	public function getPreviousRevision()
	{
		return NULL;
	}

	function getBuildList($platform)
	{
		$builds = array();
		foreach ($this->builds as $build_name) {
			$build = $this->config->getBuildFromName($build_name);
			if (isset($build['platform']) && $build['platform'] == $platform) {
				$builds[] = $build_name;
			}
		}
		return $builds;
	}

	function createBuildInstance($build_name)
	{
		$build = NULL;
		$build_config = $this->config->getBuildFromName($build_name);

		if (!$build_config) {
			throw new \Exception("Invalid build name <$build_name>");
		}

		$compiler	= strtolower($build_config['compiler']);
		switch ($compiler) {
			case 'vs16':
			case 'vc15':
			case 'vc14':
			case 'vc12':
			case 'vc11':
				$build = new PeclBuildVC($this, $build_name);
				break;
			case 'icc':
			case 'gcc':
			case 'clang':
			default:
				throw new \Exception("$compiler not supported yet. Not implemented");
				break;
		}

		return $build;
	}
}
