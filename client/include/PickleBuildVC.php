<?php

namespace rmtools;

include __DIR__ . '/BranchConfig.php';
include __DIR__ . '/MakeLogParserVC.php';

class PickleBuildVC
{
	public $branch;
	public $build_name;
	protected $pickle_phar = 'c:\\apps\\bin\\pickle.phar';
	protected $env;
	protected $sdk_base = "C:\\php-devel\\nts";

	protected $int_dir;
	protected $log_dir;

	protected $pickle_cmd;

	public function __construct(PickleBranch $branch, $build_name)
	{
		$this->branch = $branch;
		$this->build_name = $build_name;

		$this->int_dir = tempnam(TMP_DIR, 'pickle');
		unlink($this->int_dir);
		if (!file_exists($this->int_dir) && !mkdir($this->int_dir)) {
			throw new \Exception("Couldn't create temporary dir");
		}
		
		$this->log_dir = $this->int_dir . DIRECTORY_SEPARATOR . "pickle_logs";
		if (!mkdir($this->log_dir)) {
			throw new \Exception("Couldn't create log dir");
		}

		$this->pickle_cmd = PHP_BINARY . " " . $this->pickle_phar;
	}

	public function __destruct()
	{
		$this->clean();
	}

	function clean()
	{
		if (is_dir($this->int_dir)) {
			rmdir_rf($this->int_dir);
		}
	}


	public function packLogs()
	{

	}

	public function info()
	{

	}

	public function build()
	{
		$cmd = $this->pickle_cmd;

		$opts = "install ";
		$opts .= "--save-logs=" . $this->log_dir;

		$cmd = $this->pickle_cmd . " " . $opts;
	}

	public function archive()
	{

	}
}

