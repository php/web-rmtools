<?php
namespace rmtools;

include __DIR__ . '/BranchConfig.php';
include __DIR__ . '/Repository.php';
include __DIR__ . '/BuildVC.php';

class Branch {
	public $last_revision;
	public $last_revision_has_snap;
	public $config;
	private $repo;
	protected $db_path;

	public function __construct($config_path)
	{
		if (!is_readable($config_path)) {
			throw new \Exception('Cannot open config data <' . $path . '>');
		}
		$this->config = new BranchConfig($config_path);
		$this->repo = Repository::fromBranchConfig($this->config);
		$this->repo->setModule($this->config->getModule());
		$this->repo->setBranch($this->config->getBranch());
		$this->db_path = __DIR__ . '/../data/db/' . $this->config->getName() . '.json';
		if (file_exists($this->db_path)) {
			$this->data = json_decode(file_get_contents($this->db_path));
		} else {
			$this->data = array(
					'revision_last'						=> NULL,
					'revision_previous'				=> NULL,
					'revision_last_exported'	=> NULL,
				);
		}
		$this->addBuildList();
	}

	private function addBuildList()
	{
		$builds = $this->config->getBuildList();

		if (!empty($builds)) {
			$this->data->builds = array();
			foreach ($builds as $n => $v) {
				$this->data->builds[] = $n;
			}
		} else {
				$this->data->builds = NULL;
		}
	}

	public function update()
	{
		$this->data->revision_previous = $this->data->revision_last;
		$this->data->revision_last = $this->repo->getLastCommitId();
		$json = json_encode($this->data);
		file_put_contents($this->db_path, $json);
	}

	public function hasNewRevision()
	{
		return ($this->data->revision_last == $this->data->revision_previous ||  $this->data->revision_previous == NULL);
	}

	public function export($revision = false, $zip = false)
	{
		$dir_name = $this->config->getName() . '-src-r' . $this->data->revision_last;
		$target = $this->config->getBuildDir() . '/' . $dir_name;
		$this->repo->export($target);
		if ($zip) {
			$zip_path = $dir_name . '.zip';
			$cmd = "zip -q -r $zip_path $dir_name";
			$res = exec_single_log($cmd, $this->config->getBuildDir());
		}
		$target = realpath($target);
		return $target;
	}

	public function createSourceSnap($revision = false)
	{
		return $this->export($revision = $revision, true);
	}

	public function setLastRevisionExported($last_rev)
	{
		$this->data->revision_last_exported = $last_rev;
		$json = json_encode($this->data);
		file_put_contents($this->db_path, $json);
	}

	public function getLastRevisionExported()
	{
		return $this->data->revision_last_exported;
	}

	public function getLastRevisionId()
	{
		if (!$this->data->revision_last) {
			$this->update();
		}
		return $this->data->revision_last;
	}

	function getBuildList($platform)
	{
		$builds = array();
		foreach ($this->data->builds as $build_name) {
			$build = $this->config->getBuildFromName($build_name);
			if (isset($build['platform']) && $build['platform'] == $platform) {
				$builds[] = $build_name;
			}
		}
		return $builds;
	}

	function createBuildInstance($build_name)
	{
		$build = $this->config->getBuildFromName($build_name);

		if (!$build) {
			throw new \Exception("Invalid build name <$build_name>");
		}

		$compiler	= strtolower($build['compiler']);
		$arch			= strtolower($build['arch']);
		switch ($compiler) {
			case 'vc9':
			case 'vc6':
				$class_name =  'rmtools\BuildVC';
				break;
			case 'icc':
				break;
			case 'gcc':
				break;
		}
		$build = new $class_name($this, $build_name);
		return $build;
	}
}
