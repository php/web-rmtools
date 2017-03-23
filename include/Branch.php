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
		}
		
		return $data;
	}

	protected function writeData()
	{
		$json = json_encode($this->data, JSON_PRETTY_PRINT);
		return file_put_contents($this->db_path, $json, LOCK_EX);
	}

	private function addBuildList()
	{
		$builds = $this->config->getBuildList();

		if (!empty($builds)) {
			foreach ($builds as $n => $v) {
				if (in_array($n, $this->data->builds) && $this->hasUnfinishedBuild()) {
					throw new \Exception("Builds for '$n' are already done or in progress");
				}
				$this->data->builds[] = $n;
			}
		} else {
				throw new \Exception("No build configuration");
		}
	}

	public function update()
	{
		$last_id = $this->repo->getLastCommitId();
		
		if (!$last_id) {
			throw new \Exception("last revision id is empty");
		}
	
		if ($this->requiredBuildRunsReached() && $this->hasNewRevision()) {
			$this->data->revision_previous = $this->data->revision_last;
			$this->data->revision_last = $last_id;
		}

		if ($this->requiredBuildRunsReached()) {
			$this->data->builds = array();
		}
		
		if ($this->hasUnfinishedBuild()) {
			$this->addBuildList();
		}
		
		$this->writeData();
		
		return true;
	}

	public function hasUnfinishedBuild()
	{
		return !$this->requiredBuildRunsReached() || $this->hasNewRevision();
	}
	
	public function requiredBuildRunsReached()
	{
		/* XXX 4 stands for all the combinations, scan the files to get this number from there instead of hardcoding. */
		if (!isset($this->data->builds) || empty($this->data->builds)) {
			return true;
		}
		
		return count($this->data->builds) == 4;
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
		if ($this->requiredBuildRunsReached()) {
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
		/* XXX this might need to be changed, if the builds have to be done single at run. Then it'll
			need to check the thread safety argument as well. */
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
			/* XXX scan the configs for compatible compiler list*/
			case 'vc15':
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
			default:
				if ($compiler) {
					throw new \Exception("$compiler not supported yet. Not implemented.");
				} else {
					throw new \Exception("Unknown or unsupported compiler passed.");
				}
				break;
		}

		return $build;
	}
}
