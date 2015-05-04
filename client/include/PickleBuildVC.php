<?php

namespace rmtools;

include __DIR__ . '/BranchConfig.php';
include __DIR__ . '/MakeLogParserVC.php';

class PickleBuildVC
{
	public $branch;
	public $build_name;
	/* now use the dev pickle version, as it needs some adjustments for the build bots */
	/* protected $pickle_phar = 'c:\\apps\\bin\\pickle.phar'; */
	protected $pickle_phar = 'c:\\php-sdk\\pickle\\bin\pickle';
	protected $env;
	protected $sdk_base = "C:\\php-devel\\nts";

	protected $int_dir;
	protected $log_dir;
	protected $sdk_dir;

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

		/* Use --ansi for now for better parsing if needed. Should tweak pickle to support --xml/--json */
		$this->pickle_cmd = PHP_BINARY . " " . $this->pickle_phar . " --ansi ";


		$this->compiler = $branch->config->builds[$build_name]['compiler'];
		$this->architecture = $branch->config->builds[$build_name]['arch'];
		$this->thread_safe = (boolean)$branch->config->builds[$build_name]['thread_safe'];


		$vc_env_prefix = strtoupper($this->compiler);
		if ($this->architecture == 'x64') {
			$vc_env_prefix .= '_X64_';
		} else {
			$vc_env_prefix .= '_';
		}

		$path = getenv($vc_env_prefix . 'PATH');
		if (empty($path)) {
			include __DIR__ . '/../data/config.php';
			/* use default config */
			$env = $custom_env;
		} else {
			$env = array();
			$env['PATH'] = getenv($vc_env_prefix . 'PATH') . ';' . getenv('PATH') ;
			$env['INCLUDE'] = getenv($vc_env_prefix . 'INCLUDE');
			$env['LIB'] = getenv($vc_env_prefix . 'LIB');
		}

		if (!$env['INCLUDE'] || !$env['LIB']) {
			$env['INCLUDE'] = getenv('INCLUDE');
			$env['LIB'] = getenv('LIB');
		}


		$env['TMP'] = $env['TEMP'] = getenv('TEMP');
		$env['SystemDrive'] = getenv('SystemDrive');
		$env['SystemRoot'] = getenv('SystemRoot');
		if (!isset($env['BISON_SIMPLE'])) {
			$env['BISON_SIMPLE'] = getenv('BISON_SIMPLE');
		}

		$env['CPU'] = "i386";
		$env['APPVER'] = "6.0";
		if ($branch->config->getDebug() == 0) {
			$env['NODEBUG'] = "1";
		}
		if (strcasecmp($this->architecture, 'x64') == 0) {
			$env['CPU'] = "AMD64";
		}

		$this->sdk_path = $branch->config->getBuildFromName($build_name)["sdk_path"];
		$env["PATH"] .= ";{$this->sdk_path}";
		
		$this->env = $env;
	}

	public function __destruct()
	{
//		$this->clean();
	}

	public function setSdkDir($sdk_dir)
	{
		$this->sdk_dir = $sdk_dir;
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

	public function build(PickleExt $ext)
	{
		$cmd = $this->pickle_cmd;
		$old_cwd = getcwd();

		chdir(TMP_DIR);

		/* XXX check if --quiet needed */
		$opts = " --binary "
			. " --defaults " /* XXX if no force options was supplied, use --defaults. The logic is to implement. */
			. "--tmp-dir={$this->int_dir} "
			. "--save-logs=" . $this->log_dir . " "
			. "release "
			. $ext->getPkgUri();

		$cmd = $this->pickle_cmd . " " . $opts;

		$ret = exec_single_log($cmd, NULL, $this->env);

		chdir($old_cwd);
	}

	public function archive()
	{

	}
}

