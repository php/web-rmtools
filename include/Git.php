<?php
namespace rmtools;

class Git {
	public $repo_url;
	public $module;
	public $branch;
	private $git_cmd = 'c:\apps\git\bin\git.exe';
	private $tar_cmd = 'c:\apps\git\bin\tar.exe';

	public function __construct($repo_url)
	{
		$this->repo_url = $repo_url;

		if (!file_exists($this->git_cmd)) {
			$git_cmd = trim(shell_exec("where git.exe"));
			if (!$git_cmd) {
				throw new \Exception("Git binary not available");
			}
			$this->git_cmd = $git_cmd;
		}

		if (!file_exists($this->tar_cmd)) {
			$tar_cmd = trim(shell_exec("where tar.exe"));
			if (!$tar_cmd) {
				throw new \Exception("Tar binary not available");
			}
			$this->tar_cmd = $tar_cmd;
		}
	}

	function setModule($module) {
		$this->module = $module;
	}

	function setBranch($branch) {
		$this->branch = $branch;
	}

	public function export($dest, $revision = false)
	{
		$http_url = preg_replace('/git:\/\//', 'http://', $this->repo_url);
		$url = $http_url . '/?p=' . $this->module . ';a=snapshot;h=' . $this->branch . ';sf=zip';
		$dest .= '.zip';
		wget($url, $dest);
		return $dest;
	}

	public function info()
	{
	}

	public function getLastCommitId()
	{
		$try = 3;
		$cmd = '"' . $this->git_cmd . '" ls-remote ' . $this->repo_url . '/' . $this->module . ' ' . $this->branch;
		while ( $try > 0 )  {
			$res = exec_sep_log($cmd);
			if ($res && !empty($res['log_stdout']))  {
				break;
			}
			$try--;
		}
		if ($res && $res['return_value'] != 0) {
			throw new \Exception('git ls-remote failed <' . $this->repo_url . '/' . $this->module . ' ' . $this->branch . '>, ' . $res['log_stderr']);
		}

		$revision = preg_replace("/[\s\t]+.+/", "", $res['log_stdout']);
		$revision = trim($revision);
		return (string)$revision;
	}
}
