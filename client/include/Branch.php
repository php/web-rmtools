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
	public $db_path;
	private $has_new_revision;
	public $data = NULL;

	public function __construct($config_path)
	{
		if (!is_readable($config_path)) {
			throw new \Exception('Cannot open config data <' . $config_path . '>');
		}
		$this->config = new BranchConfig($config_path);
		$this->repo = Repository::fromBranchConfig($this->config);
		$this->repo->setModule($this->config->getModule());
		$this->repo->setBranch($this->config->getRepoBranch());
		$this->db_path = __DIR__ . '/../data/db/' . $this->config->getName() . '.json';
		if (file_exists($this->db_path)) {
			$this->data = json_decode(file_get_contents($this->db_path));
		} else {
			$data = new \StdClass;
			$data->revision_last = NULL;
			$data->revision_previous = NULL;
			$data->revision_last_exported = NULL;
			$this->data = $data;
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
		$last_id =  $this->repo->getLastCommitId();
		if ($last_id != $this->data->revision_last) {
			$this->data->revision_previous = $this->data->revision_last;
			$this->data->revision_last = $last_id;
			$json = json_encode($this->data);
			file_put_contents($this->db_path, $json);
			$this->has_new_revision = true;
		}
	}

	public function hasNewRevision()
	{
		return $this->has_new_revision || ( $this->data->revision_previous == NULL);
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
            if (!$res) {
                throw new \Exception("Export failed, svn exec failed to be ran");
            }
		}
		$target = realpath($target);
		return $target;
	}

	public function createSourceSnap($revision = false)
	{
		return $this->export($revision, true);
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

	public function getPreviousRevision()
	{
		if (!$this->data->revision_previous) {
			return NULL;
		}
		return $this->data->revision_previous;
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
		switch ($compiler) {
			case 'vc9':
			case 'vc6':
				$build = new rmtools\BuildVC($this, $build_name);
				break;
			case 'icc':
			case 'gcc':
				throw new \Exception("$compiler not supported yet. Not implemented");
				break;
		}

		return $build;
	}
}
