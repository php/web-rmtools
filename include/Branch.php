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
	public $data = NULL;
	private $required_build_runs = 2;

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

		$this->data = $this->readdata();
		/*if ($this->requiredBuldRunsReached()) {
			$this->data->build_run = 0;
		}*/

		$this->addBuildList();
	}

	protected function readData()
	{
		if (file_exists($this->db_path)) {
			$data = json_decode(file_get_contents($this->db_path));
		} else {
			$data = new \StdClass;
			$data->revision_last = NULL;
			$data->revision_previous = NULL;
			$data->revision_last_exported = NULL;
			$data->build_run = 0;
		}
		
		return $data;
	}

	protected function writeData()
	{
		$json = json_encode($this->data, JSON_PRETTY_PRINT);
		return file_put_contents($this->db_path, $json);
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
		$last_id = $this->repo->getLastCommitId();
		
		if (!$last_id) {
			// XXX throw here
			echo "last revision id is empty\n";
			return false;
		}

		if ($this->data->build_run > 0 && $this->hasUnfinishedBuild()) {
			$this->data->build_run++;
		} else if ($this->hasNewRevision()) {
			$this->data->revision_previous = $this->data->revision_last;
			$this->data->revision_last = $last_id;
			$this->data->build_run = 1;
		} else {
			return false;
		}
		
		$this->writeData();
		
		return true;
	}

	public function hasUnfinishedBuild()
	{
		$exported = $this->getLastRevisionExported();
		$last = $this->getLastRevisionId();

		return !$this->requiredBuldRunsReached() || substr_compare($last, $exported, 0, strlen($exported)) != 0;
	}
	
	public function requiredBuldRunsReached()
	{
		return $this->data->build_run >= $this->required_build_runs;
	}

	public function hasNewRevision()
	{
		$last = $this->repo->getLastCommitId();

		return $last && !$this->isLastRevisionExported($last);
	}

	public function export($revision = false, $build_type = false, $zip = false, $is_zip = false)
	{
		$rev_name = $this->data->revision_last;
		if (strlen($this->data->revision_last) == 40) {
			$rev_name = substr($this->data->revision_last, 0, 7);
		}
		$dir_name = $this->config->getName() . '-src-' . ($build_type ? $build_type.'-' : $build_type) . 'r' . $rev_name;
		$build_dir = $this->config->getBuildDir();
		if (!file_exists($build_dir)) {
			throw new \Exception("Directory '$build_dir' doesn't exist");
		}
		$target = $build_dir . '/' . $dir_name;
		$exportfile = $this->repo->export($target);

		if (preg_match('/\.zip$/', $exportfile) > 0) {  // export function returned a .zip file.
			$is_zip = true;
		}
		if ($zip && !$is_zip) {
			$zip_path = $dir_name . '.zip';
			$cmd = "zip -q -r $zip_path $dir_name";
			$res = exec_single_log($cmd, $build_dir);
			if (!$res) {
				throw new \Exception("Export failed, svn exec failed to be ran");
			}
		}
		elseif ($is_zip === true)  {
			$cmd = 'unzip -q -o ' . $exportfile . ' -d ' . $build_dir;
			$res = exec_single_log($cmd);
			if (!$res) {
				throw new \Exception("Unzipping $exportfile failed.");
			}
			$gitname = $build_dir . '/php-src-' . strtoupper($this->config->getName()) . '-' . $rev_name;
			rename($gitname, $target);
		}

		$target = realpath($target);
		return $target;
	}

	public function createSourceSnap($build_type = false, $revision = false)
	{
		return $this->export($revision, $build_type, true);
	}

	public function setLastRevisionExported($last_rev)
	{
		/* Basically, we need two runs for x64 and x86, every run covers ts and nts.
			Only set the revision exported, if we're on last required build run. */
		if ($this->requiredBuldRunsReached()) {
			$this->data->revision_last_exported = $last_rev;
			$this->writeData();
		}
	}

	public function getLastRevisionExported()
	{
		return $this->data->revision_last_exported;
	}
	
	public function isLastRevisionExported($rev)
	{
		$last_exported = $this->getLastRevisionExported();

		return $last_exported && substr_compare($rev, $last_exported, 0, strlen($last_exported)) === 0;
	}

	public function getLastRevisionId()
	{
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
			case 'vc14':
			case 'vc12':
			case 'vc11':
			case 'vc9':
			case 'vc6':
				$build = new BuildVC($this, $build_name);
				break;
			case 'icc':
			case 'gcc':
			case 'clang':
				throw new \Exception("$compiler not supported yet. Not implemented");
				break;
		}

		return $build;
	}
}
