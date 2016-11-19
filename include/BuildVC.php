<?php
namespace rmtools;
include __DIR__ . '/MakeLogParserVC.php';

class BuildVC {
	public $branch;
	public $build_name;
	public $env;
	private $obj_dir;
	private $build_dir;
	protected $old_cwd;
	public $logs = array();

	public $archive_path = false;
	public $debug_path = false;
	public $devel_path = false;
	public $test_path = false;

	public $compiler_log_parser;
	public $stats;
	public $log_buildconf;
	public $log_configure;
	public $log_make;
	public $log_archive;

	public $zip_devel_filename;
	public $zip_debug_filename;
	public $zip_test_filename;

	function __construct(Branch $branch, $build_name)
	{
		/* XXX this has to be merged with PeclBuildVC and any other, be it per trait or inheritance. */
		$build_dir = $branch->config->getBuildDir();
		if (!file_exists($build_dir)) {
			throw new \Exception("Directory '$build_dir' doesn't exist");
		}

		$this->branch = $branch;
		$this->build_name = $build_name;
		$this->obj_dir = $build_dir . '/' . $this->build_name;
		$this->compiler = $this->branch->config->getCompiler();
		$this->architecture = $this->branch->config->getArchitecture();

		$sdk_arch = getenv("PHP_SDK_ARCH");
		if (strtolower($this->architecture) != strtolower($sdk_arch)) {
			throw new \Exception("Arch mismatch. PHP SDK is configured for '$sdk_arch', while the current RMTOOLS config targets '{$this->architecture}'");
		}

		$sdk_vc = getenv("PHP_SDK_VC");
		if (strtolower($this->compiler) != strtolower($sdk_vc)) {
			throw new \Exception("Compiler mismatch. PHP SDK is configured for '$sdk_vc', while the current RMTOOLS config targets '{$this->compiler}'");
		}

		$env = array();
		$env['PATH'] = getenv('PATH') ;
		$env['INCLUDE'] = getenv('INCLUDE');
		$env['LIB'] = getenv('LIB');

		$env['TMP'] = $env['TEMP'] = getenv('TEMP');
		$env['SystemDrive'] = getenv('SystemDrive');
		$env['SystemRoot'] = getenv('SystemRoot');

		/* XXX Not sure, in how far the below is needed. */
		/*
		$env['CPU'] = "i386";
		$env['APPVER'] = "5.01";  // setenv /xp
		if (strcasecmp($this->architecture, 'x64') == 0) {
			$env['CPU'] = "AMD64";
		}
		if (strcmp($branch->config->getAppver(), '2008') == 0) {
			$env['APPVER'] = "6.0";
		}
		 */
		if ($branch->config->getDebug() == 0) {
			$env['NODEBUG'] = "1";
		}

		$this->env = $env;
	}

	function setSourceDir($src_dir)
	{
		$this->build_dir = $src_dir;
	}

	private function addLogsToArchive()
	{
			$zip = new \ZipArchive();
			if ($zip->open($this->archive_path) === FALSE) {
				throw new \Exception('cannot open archive');
			}
			$zip->addFromString('logs\buildconf.txt', $this->log_buildconf);
			$zip->addFromString('logs\configure.txt', $this->log_configure);
			$zip->addFromString('logs\make.txt', $this->log_make);
			$zip->addFromString('logs\archive.txt', $this->log_archive);
			$zip->close();
	}

	function updateDeps(string $stability = "stable")
	{
		$branch = $this->branch->config->getBranch();
		$cmd = "phpsdk_deps -u -s $stability -b $branch -d " . dirname($this->build_dir) . "/deps";
		$ret = exec_single_log($cmd, $this->build_dir, $this->env);
		if (!$ret) {
			throw new \Exception('dependencies update failed');
		}

		return $ret;
	}

	function buildconf()
	{
		$cmd = 'buildconf';
		$ret = exec_single_log($cmd, $this->build_dir, $this->env);
		if (!$ret) {
			throw new \Exception('buildconf failed');
		}
		$this->log_buildconf = $ret['log'];
	}

	function configure($extra = false, $rm_obj = true)
	{
		$args = $this->branch->config->getConfigureOptions($this->build_name) . ($extra ?: $extra);
		$cmd = 'configure ' . $args . ' --enable-object-out-dir=' . $this->obj_dir;
		/* old build may have been stoped */
		if (is_dir($this->obj_dir) && $rm_obj === true) {
			rmdir_rf($this->obj_dir);
		}
		mkdir($this->obj_dir, 0655, true);
		$ret = exec_single_log($cmd, $this->build_dir, $this->env);
		if (!$ret) {
			throw new \Exception('Configure failed');
		}
		$this->log_configure = $ret['log'];
	}

	function make($target = false)
	{
		$cmd = 'nmake' . ($target ?: $target);
		$ret = exec_single_log($cmd, $this->build_dir, $this->env);
		if (!$ret) {
			throw new \Exception('Make failed');
		}
		$this->log_make = $ret['log'];
	}

	function makeArchive()
	{
		$cmd = 'nmake snap';
		$ret = exec_single_log($cmd, $this->build_dir, $this->env);
		if (!$ret) {
			throw new \Exception('Make snap failed');
		}

		$this->log_archive = $ret['log'];

		if (!preg_match('/Build dir: (.*)/', $this->log_configure, $matches)) {
			throw new \Exception('Make archive failed, cannot find build dir');
		}
		$zip_dir = trim($matches[1]);

		if (!preg_match('/.*(php-\d\.\d\.\d.*\.zip)/', $this->log_archive, $matches)) {
			throw new \Exception('Make archive failed, cannot find php archive');
		}
		$zip_filename = trim($matches[1]);

		if (!preg_match('/.*(php-devel-pack-\d\.\d\.\d.*\.zip)/', $this->log_archive, $matches)) {
			throw new \Exception('Make archive failed, cannot find php-devel archive');
		}
		$zip_devel_filename = trim($matches[1]);
		$this->zip_devel_filename = $zip_devel_filename;

		if (!preg_match('/.*(php-debug-pack-\d\.\d\.\d.*\.zip)/', $this->log_archive, $matches)) {
			throw new \Exception('Make archive failed, cannot find php-debug archive');
		}
		$zip_debug_filename = trim($matches[1]);
		$this->zip_debug_filename = $zip_debug_filename;

		if (!preg_match('/.*(php-test-pack-\d\.\d\.\d.*\.zip)/', $this->log_archive, $matches)) {
			throw new \Exception('Make archive failed, cannot find php-test archive');
		}
		$zip_test_filename = trim($matches[1]);
		$this->zip_test_filename = $zip_test_filename;

		$this->archive_path = realpath($zip_dir . '/' . $zip_filename);
		$this->debug_path = realpath($zip_dir . '/' . $zip_debug_filename);
		$this->devel_path = realpath($zip_dir . '/' . $zip_devel_filename);
		$this->test_path = realpath($zip_dir . '/' . $zip_test_filename);

		$this->addLogsToArchive();
	}

	function getMakeLogParsed()
	{
		$parser = new MakeLogParserVc;
		$tmpfile = $this->obj_dir . '/' . 'make.txt';
		file_put_contents($tmpfile, $this->log_make);
		$parser->parse($tmpfile, $this->build_dir);
		unlink($tmpfile);
		$this->stats = $parser->stats;
		$this->compiler_log_parser = $parser;
		return $parser->toHtml($this->build_name);
	}

	function getStats()
	{
		return $this->stats;
	}

	function clean()
	{
		rmdir_rf($this->obj_dir);
	}

	function getLogs()
	{

	}
}
