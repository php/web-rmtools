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
	protected $sdk_base;

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

		/* Use --no-ansi for now for better parsing if needed. Should tweak pickle to support --xml/--json */
		$this->pickle_cmd = PHP_BINARY . " " . $this->pickle_phar . " --no-ansi ";


		$this->compiler = $branch->config->builds[$build_name]['compiler'];
		$this->architecture = $branch->config->builds[$build_name]['arch'];
		$this->thread_safe = (boolean)$branch->config->builds[$build_name]['thread_safe'];
		$this->pecl_deps_base = $branch->config->builds[$build_name]['pecl_deps_base'];
		if (!file_exists($this->pecl_deps_base)) {
			throw new \Exception("Dependency libs not found under '{$this->pecl_deps_base}'");
		}
		$this->core_deps_base = $branch->config->builds[$build_name]['core_deps_base'];
		if (!file_exists($this->core_deps_base)) {
			throw new \Exception("Dependency libs not found under '{$this->core_deps_base}'");
		}


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
		$env["PATH"] = "{$this->sdk_path};" . $env["PATH"];
		
		$env["PATH"] = "c:\\apps\\git\\bin;c:\\apps\\bin;" . $env["PATH"];

		$this->env = $env;
	}

	public function __destruct()
	{
		$this->clean();
	}

	public function getPickleCmd()
	{
		return $this->pickle_cmd;
	}

	public function setSdkDir($sdk_dir)
	{
		$this->sdk_dir = $sdk_dir;
	}

	function clean()
	{
		if (is_dir($this->int_dir)) {
			/* XXX something seems to be missing in unlink(), .git has some ACLs so then
				PHP cannot handle the removal of the several items in it. Using the
				system commando helps with that. */
			$items = scandir($this->int_dir);
			foreach ($items as $item) {
				$dir = "{$this->int_dir}/$item";

				if (is_dir("$dir/.git")) {
					@shell_exec("del /F /S /Q /A " . realpath($dir) . DIRECTORY_SEPARATOR . ".git");
				}
			}

			rmdir_rf($this->int_dir);
		}
	}

	public function info()
	{

	}

	public function getPickleCmdToRun(PickleExt $ext)
	{
		$conf_opts = $ext->getConfigureOpts();
		if ($conf_opts && file_exists($conf_opts)) {
			$ext_config_opt = "--with-configure-options=$conf_opts";
		} else {
			$ext_config_opt = "--defaults";
		}

		/* XXX check if --quiet needed */
		$opts = " --binary "
			. " $ext_config_opt "
			. "--tmp-dir={$this->int_dir} "
			. "--pack-logs "
			. "release "
			. $ext->getPkgUri();

		$cmd = $this->pickle_cmd . " " . $opts;

		return $cmd;

	}


	/* XXX read the configure options from the extconfig, create the options file to feed pickle */
	public function build(PickleExt $ext)
	{
		$old_cwd = getcwd();

		chdir(TMP_DIR);

		$cmd = $this->getPickleCmdToRun($ext);

		$ret = exec_single_log($cmd, NULL, $this->env);

		$ext->cleanConfigureOpts();

		chdir($old_cwd);

		return $ret;
	}

	public function archive()
	{

	}

	public function getIntDir()
	{
		return $this->int_dir;
	}

	public function getEnv()
	{
		return $this->env;
	}
}

