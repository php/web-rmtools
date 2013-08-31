<?php

namespace rmtools;

include_once __DIR__ . '/../include/Tools.php';
include_once __DIR__ . '/../include/PeclBranch.php';

class PeclExt
{
	protected $tgz_path;
	protected $name;
	protected $version;
	protected $build;
	protected $tar_cmd = 'c:\apps\git\bin\tar.exe';
	protected $gzip_cmd = 'c:\apps\git\bin\gzip.exe';
	protected $zip_cmd = 'c:\php-sdk\bin\zip.exe';
	protected $deplister_cmd = 'c:\apps\bin\deplister.exe';
	protected $tmp_extract_path = NULL;
	protected $tmp_package_xml_path = NULL;
	protected $ext_dir_in_src_path = NULL;
	protected $package_xml = NULL;
	protected $configure_data = NULL;

	public function __construct($tgz_path, $build)
	{
		if (!file_exists($tgz_path)) {
			throw new \Exception("'$tgz_path' does not exist");
		} else if ('.tgz' != substr($tgz_path, -4)) {
			throw new \Exception("Pecl package should end with .tgz");
		}

		/* Should open package.xml then and retry, but normally the below should work. */
		$tmp = explode('-', basename($tgz_path, '.tgz'));
		$this->name = $tmp[0];
		$this->version = $tmp[1];

		if (!$this->name || !$this->version) {
			/* XXX do unpack here already and get the name/version data */
			throw new \Exception("Couldn't parse extension name or version from the filename");
		}

		$this->tgz_path = $tgz_path;
		$this->build = $build;
	}

	public function getPackageName()
	{
		// looks like php_http-2.0.0beta4-5.3-nts-vc9-x86
		return 'php_' . $this->name
			. '-' . $this->version 
			. '-' . $this->build->branch->config->getBranch()
			. '-' . ($this->build->thread_safe ? 'ts' : 'nts')
			. '-' . $this->build->compiler
			. '-' . $this->build->architecture;
	}

	public function unpack()
	{
		$tmp_path =  TMP_DIR . '/' . $this->getPackageName() . '-unpack';
		if (!file_exists($tmp_path) && !mkdir($tmp_path)) {
			throw new \Exception("Couldn't create temporary dir");
		}

		$tmp_name =  $tmp_path . '/' . basename($this->tgz_path);
		if (!copy($this->tgz_path, $tmp_name)) {
			throw new \Exception("Couldn't move the tarball to '$tmp_name'");
		}

		$tar_name =  basename($this->tgz_path, '.tgz') . '.tar';

		/* The tar/gzip versions from the msys package won't work properly with
		the windows paths, but they will if running those just in the current dir.*/
		$old_cwd = getcwd();

		chdir($tmp_path);

		$gzip_cmd = $this->gzip_cmd . ' -df ' . basename($this->tgz_path);
		system($gzip_cmd, $ret);
		if ($ret) {
			throw new \Exception("Failed to guzip the tarball");
		}

		$tar_cmd = $this->tar_cmd . ' -xf ' . $tar_name;
		system($tar_cmd, $ret);
		if ($ret) {
			throw new \Exception("Failed to guzip the tarball");
		}
		unlink($tar_name);

		chdir($old_cwd);

		$this->tmp_extract_path = realpath($tmp_path . '/' .  basename($this->tgz_path, '.tgz'));

		if (file_exists($tmp_path . DIRECTORY_SEPARATOR . 'package.xml')) {
			$this->tmp_package_xml_path = $tmp_path . DIRECTORY_SEPARATOR . 'package.xml';
			$this->package_xml = new \SimpleXMLElement($this->tmp_package_xml_path, 0, true);
		}

		$this->tgz_path = NULL;

		return $this->tmp_extract_path;
	}

	public function putSourcesIntoBranch()
	{
		if (!$this->tmp_extract_path) {
			$this->tmp_extract_path = $this->unpack();
		}
		$ext_dir = $this->build->getSourceDir() . DIRECTORY_SEPARATOR . 'ext';

		$this->ext_dir_in_src_path = $ext_dir . DIRECTORY_SEPARATOR . $this->name;
		$res = copy_r($this->tmp_extract_path, $this->ext_dir_in_src_path);
		if (!$res) {
			throw new \Exception("Failed to copy to '{$this->ext_dir_in_src_path}'");
		}

		return $this->ext_dir_in_src_path;
	}

	protected function buildConfigureLine(array $data)
	{
		$ret = '';

		if (!isset($data['type'])
			|| !in_array($data['type'], array('with', 'enable'))) {
			throw new \Exception("Unknown extention configure type, expected enable/with");
		}

		$ret = ' "--' . $data['type'] . '-' . $this->name . '=shared" ';

		if (isset($data['libs']) && $data['libs']) {
			$data['libs'] = !is_array($data['libs']) ? array($data['libs']) : $data['libs'];
			$deps_path = $this->build->branch->config->getPeclDepsBase();
			$extra_lib = $extra_inc = array();

			foreach($data['libs'] as $lib) {
				if (!$lib) {
					continue;
				}

				$lib_path = $deps_path . DIRECTORY_SEPARATOR . $lib;

				$some_lib_path = $lib_path . DIRECTORY_SEPARATOR . 'lib';
				if (!file_exists($some_lib_path)) {
					throw new \Exception("Path '$some_lib_path' doesn't exist");
				}
				$extra_lib[] = $some_lib_path;	

				/* Many libs put headers into include and include/somelib, so lets cover both */
				$some_lib_inc_path =  $lib_path . DIRECTORY_SEPARATOR . 'include';
				if (!file_exists($some_lib_inc_path)) {
					throw new \Exception("Path '$some_lib_inc_path' doesn't exist");
				}

				$extra_inc[] = $some_lib_inc_path;	
				if (file_exists($some_lib_inc_path . DIRECTORY_SEPARATOR . $lib)) {
					$extra_inc[] = $some_lib_inc_path . DIRECTORY_SEPARATOR . $lib;	
				}
			}

			if (!empty($extra_lib)) {
				$ret .= ' "--with-extra-libs=' . implode(';', $extra_lib) . '" '
					. ' "--with-extra-includes=' . implode(';', $extra_inc) . '" ';
			}
		}

		if (isset($data['opts']) && $data['opts']) {
			$data['opts'] = !is_array($data['opts']) ? array($data['opts']) : $data['opts'];
			foreach($data['opts'] as $opt) {
				if ($opt) {
					/* XXX simple check for opt syntax */
					$ret .= ' "' . $opt . '" ';
				}
			}
		}

		if (isset($data['exts']) && $data['exts']) {
			/* TODO */
		}

		$this->configure_data = $data;

		return $ret;
	}

	public function getConfigureLine()
	{
		/* XXX check if it's enable or with,
			what deps it has
			what additional options it has
			what non core exts it deps on 
		*/

		$config = array();

		/* look if it's on the known ext list */
		$known_path = __DIR__ . '/../data/config/pecl/exts.ini';
		$exts = parse_ini_file($known_path, true, INI_SCANNER_RAW);

		foreach ($exts as $name => $conf) {
			if ($name === $this->name) {
				$config = $conf;
			}
		}

		/* if it's not known yet, we have to gather the info */
		$config_w32_path = $this->tmp_extract_path . DIRECTORY_SEPARATOR . 'config.w32';
		foreach (array('enable' => 'ARG_ENABLE', 'with' => 'ARG_WITH') as $arg => $str) {
			if (preg_match(',' . $str .'.*' . $this->name . ',Sm', file_get_contents($config_w32_path))) {
				$config['type'] = $arg;
			}
		}
		if (!isset($config['type'])) {
			throw new Exception("Couldn't determine whether 'with' or 'enable' configure option to use");
		}

		/* XXX Extension deps have to be checked here using
		$this->package_xml->dependencies; */

		/* XXX Library deps have to be checked using 
		$this->tmp_extract_path . DIRECTORY_SEPARATOR . 'lib_versions.txt'; */

		return $this->buildConfigureLine($config);

	}

	public function preparePackage()
	{
		/* XXX check if there are any dep dll/pdb to put together */
		$sub = $this->build->thread_safe ? 'Release_TS' : 'Release';
		$base = $this->build->getObjDir() . DIRECTORY_SEPARATOR . $sub;
		$target = TMP_DIR . DIRECTORY_SEPARATOR . $this->getPackageName();
		$files_to_zip = array();

		$dll_name = 'php_' . $this->name . '.dll';
		$dll_file = $target . DIRECTORY_SEPARATOR . $dll_name;
		if (!file_exists($base . DIRECTORY_SEPARATOR . $dll_name)) {
			throw new \Exception("'$dll_name' doesn't exist after build, build failed");
		}
		if (!copy($base . DIRECTORY_SEPARATOR . $dll_name, $dll_file)) {
			throw new \Exception("Couldn't copy '$dll_name' into '$target'");
		}
		$files_to_zip[] = $dll_file;
		
		$pdb_name = 'php_' . $this->name . '.pdb';
		$pdb_file = $target . DIRECTORY_SEPARATOR . $pdb_name;
		if (!file_exists($base . DIRECTORY_SEPARATOR . $pdb_name)) {
			throw new \Exception("'$pdb_name' doesn't exist after build");
		}
		if (!copy($base . DIRECTORY_SEPARATOR . $pdb_name, $pdb_file)) {
			throw new \Exception("Couldn't copy '$pdb_name' into '$target'");
		}
		$files_to_zip[] = $pdb_file;

		/* Walk the deps if any, but look for them in the lib deps folders only.
			the deplister will for sure find something like kernel32.dll,
			but that's not what we need. */
		$depl_cmd = $this->deplister_cmd . ' ' . $dll_file;
		$deps_path = $this->build->branch->config->getPeclDepsBase();
		exec($depl_cmd, $out);
		foreach($out as $ln) {
			$dll_name = explode(',', $ln)[0];
			$dll_file = $target . DIRECTORY_SEPARATOR . $dll_name;
			$pdb_name = basename($dll_name, '.dll') . '.pdb';
			$pdb_file = $target . DIRECTORY_SEPARATOR . $pdb_name;

			foreach ($this->configure_data['libs'] as $lib) {
				$look_for = $deps_path
					. DIRECTORY_SEPARATOR . $lib
					. DIRECTORY_SEPARATOR . 'bin'
					. DIRECTORY_SEPARATOR . $dll_name;

				if(file_exists($look_for)) {
					if (!copy($look_for, $dll_file)) {
						throw new \Exception("The dependency dll '$dll_name' "
						. "was found but couldn't be copied into '$target'");
					}
					$files_to_zip[] = $dll_file;
				}
				
				$look_for = $deps_path
					. DIRECTORY_SEPARATOR . $lib
					. DIRECTORY_SEPARATOR . 'bin'
					. DIRECTORY_SEPARATOR . $pdb_name;


				if(file_exists($look_for)) {
					if (!copy($look_for, $pdb_file)) {
						throw new \Exception("The dependency pdb '$dll_name' "
						. "was found but couldn't be copied into '$target'");
					}
					$files_to_zip[] = $pdb_file;
				}	
			}
		}


		/* pack */
		$zip_file = TMP_DIR . DIRECTORY_SEPARATOR . $this->getPackageName() . '.zip';
		$zip_cmd = $this->zip_cmd . ' -9 -D -j ' . $zip_file . ' ' . implode(' ', $files_to_zip);
		system($zip_cmd, $status);
		if ($status) {
			throw new \Exception("Couldn't zip files for $zip_file");
		}

		return $zip_file;
	}

	public function __call($name, $args)
	{
		if ('get' == substr($name, 0, 3)) {
			$prop_name = strtolower(substr($name, 3));
			if (isset($this->$prop_name)) {
				return $this->$prop_name;
			} else {
				return NULL;
			}
		}

		throw new \Exception("Unknown dynamic method called");
	}

	public function cleanup()
	{
		if ($this->tmp_extract_path) {
			rmdir_rf(dirname($this->tmp_extract_path));
		}
		/*if ($this->tmp_package_xml_path) {
			unlink($this->tmp_package_xml_path);
		}*/
		if ($this->ext_dir_in_src_path) {
			rmdir_rf($this->ext_dir_in_src_path);
		}
	}

	public function check()
	{
		if (!$this->tmp_extract_path) {
			throw new \Exception("Tarball isn't yet extracted");
		}

		if (!file_exists($this->tmp_extract_path . DIRECTORY_SEPARATOR . 'config.w32')) {
			throw new \Exception("config.w32 doesn't exist in the tarball");
		}

		$min_php_ver = (string)$this->package_xml->dependencies->required->php->min;
		$max_php_ver = (string)$this->package_xml->dependencies->required->php->max;
		$php_ver = '';

		$ver_hdr = $this->build->getSourceDir() . '/main/php_version.h';
		if(preg_match(',#define PHP_VERSION "(.*)",Sm', file_get_contents($ver_hdr), $m)) {
			$php_ver = $m[1];
		} else {
			throw new \Exception("Couldn't parse PHP sources for version");
		}

		if ($min_php_ver && version_compare($php_ver, $min_php_ver) < 0) {
			throw new \Exception("At least PHP '$min_php_ver' required, got '$php_ver'");
		}

		if ($max_php_ver && version_compare($php_ver, $max_php_ver) >= 0) {
			throw new \Exception("At most PHP '$max_php_ver' required, got '$php_ver'");
		}

		//var_dump($php_ver, $min_php_ver, $max_php_ver, $this->package_xml->dependencies);die;


	}
}

