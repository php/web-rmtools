<?php
namespace rmtools;

include __DIR__ . '/BranchConfig.php';
include __DIR__ . '/Repository.php';
include __DIR__ . '/BuildVC.php';

class Branch {
	const REQUIRED_BUILDS_NUM = 4;
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
			$fd = fopen($this->db_path, "rb");
			if (!$fd) {
				throw new \Exception("Failed to open {$this->db_path}.");
			}
			flock($fd, LOCK_SH);
			$j = "";
			while(!feof($fd)) {
				$j .= fread($fd, 1024);
			}
			$data = json_decode($j);
			flock($fd, LOCK_UN);
			fclose($fd);
		} else {
			$data = new \StdClass;
			$data->revision_last = NULL;
			$data->revision_previous = NULL;
			$data->revision_last_exported = NULL;
			$data->build_num = 0;
			$data->builds = array();
		}
		
		if ($data->build_num > self::REQUIRED_BUILDS_NUM) {
			throw new \Exception("Inconsistent db, build number can't be {$data->build_num}.");
		}

		return $data;
	}

	protected function writeData()
	{
		$json = json_encode($this->data, JSON_PRETTY_PRINT);
		return file_put_contents($this->db_path, $json, LOCK_EX);
	}

	private function addBuildList($build_name = NULL)
	{
		$builds = $this->config->getBuildList();

		if (!empty($builds)) {
			if ($build_name) {
				if (in_array($build_name, $this->data->builds) && $this->hasUnfinishedBuild()) {
					throw new \Exception("Builds for '$build_name' are already done or in progress");
				}
				$found = 0;
				foreach ($builds as $n => $v) {
					if ($n == $build_name) {
						$found = 1;
						$this->data->builds[] = $n;
						break;
					}
				}
				if (!$found) {
					throw new \Exception("Build name '$build_name' is not on the supported build list.");
				}
			} else {
				foreach ($builds as $n => $v) {
					if (in_array($n, $this->data->builds) && $this->hasUnfinishedBuild()) {
						throw new \Exception("Builds for '$n' are already done or in progress");
					}
					$this->data->builds[] = $n;
				}
			}
		} else {
				throw new \Exception("No build configuration");
		}
	}

	public function update($build_name = NULL)
	{
		$last_id = $this->repo->getLastCommitId();
		
		if (!$last_id) {
			throw new \Exception("last revision id is empty");
		}

		/* Update this only for the first build for the current revision. */
		if (0 == $this->numBuildsRunning() && $this->hasNewRevision() || NULL == $this->data->revision_last) {
			$this->data->revision_previous = $this->data->revision_last;
			$this->data->revision_last = $last_id;
		}
		
		if ($this->hasUnfinishedBuild()) {
			$this->addBuildList($build_name);
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
		$data = $this->readdata();
		$this->data->build_num = $data->build_num;
		return $this->data->build_num == self::REQUIRED_BUILDS_NUM;
	}
	
	public function hasNewRevision()
	{
		$last = $this->repo->getLastCommitId();

		return $last && !$this->isLastRevisionExported($last) || is_null($this->data->revision_last);
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
			$extract_dir = $build_dir . DIRECTORY_SEPARATOR . "$build_type-$rev_name-tmp-unzip";
			if (true !== mkdir($extract_dir)) {
				throw new \Exception("Could not create temporary exctract dir under '$extract_dir'.");
			}

			$cmd = 'unzip -q -o ' . $exportfile . ' -d ' . $extract_dir;
			$res = exec_single_log($cmd);
			if (!$res) {
				throw new \Exception("Unzipping $exportfile failed.");
			}
			$gitname = $extract_dir . '/php-src-' . strtoupper($this->config->getName()) . '-' . $rev_name;
			if (true !== rename($gitname, $target)) {
				throw new \Exception("Failed to rename '$gitname' to '$target'");
			}

			if (true !== rmdir($extract_dir)) {
				throw new \Exception("Could not remove temporary exctract dir under '$extract_dir'.");
			}
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
			case 'icc':
			case 'gcc':
			case 'clang':
				throw new \Exception("$compiler not supported yet. Not implemented.");
			default:
				$build = new BuildVC($this, $build_name);
		}

		return $build;
	}

	function buildFinished()
	{
		$this->data = $this->readdata();
		$this->data->build_num++;
		$this->writeData();
	}

	function resetBuildInfo()
	{
		if (self::REQUIRED_BUILDS_NUM <= $branch->numBuildsRunning()) {
			$this->data = $this->readdata();
			$this->data->build_num = 0;
			$this->data->builds = array();
			$this->writeData();
		}
	}

	function numBuildsRunning()
	{
		return count($this->data->builds);
	}
}
