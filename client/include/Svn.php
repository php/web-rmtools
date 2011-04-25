<?php
namespace rmtools;

class Svn {
	public $repo_url;
	public $module;
	public $branch;
	private $svn_cmd = 'svn';

	public function __construct($repo_url)
	{
		$this->repo_url = $repo_url;
	}

	function setModule($module) {
		$this->module = $module;
	}

	function setBranch($branch) {
		$this->branch = $branch;
	}

	public function export($dest, $revision = false)
	{
		$cmd = $this->svn_cmd . ' export -q ' . $this->repo_url . $this->module.  $this->branch . ' ' . $dest;
		$res = exec_single_log($cmd);
		if ($res === FALSE) {
			throw new \Exception('svn export failed <' . $this->repo_url . '/' . $this->module. '/' . $this->branch . '>');
		}
		if ($res['log']) {
			file_put_contents('g:/temp/obj/svnlog.txt', $res['log']);
		}
	}

	public function info()
	{
	}

	public function getLastCommitId()
	{
		$cmd = $this->svn_cmd . ' info --xml ' . $this->repo_url . $this->module. $this->branch;

		$res = exec_sep_log($cmd);
		if ($res && is_null($res['log_stdout'])) {
			throw new \Exception('svn log failed <' . $this->repo_url . '/' . $this->module. '/' . $this->branch . '>');
		}

		$sx = new \SimpleXMLElement($res['log_stdout']);
		if (!$sx) {
			throw new \Exception('svn log failed ' . $path);
		}
		$revision = (int)($sx->entry->commit['revision']);
		return $revision;
	}
}
