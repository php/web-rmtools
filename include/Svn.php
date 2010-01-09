<?php
namespace rmtools;

class Svn {
	function __construct() {
	}

	function fetchLogFromBranch($branch, $first_revision) {
		if (!static::isValidBranch($branch)) {
			throw new \Exception('Invalid branch name ' . $branch);
			return FALSE;
		}

		$descriptorspec = array(
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("pipe", "w") // stderr is a file to write to
		);

		$env = array('some_option' => 'aeiou');
		$cmd = "svn log -r $first_revision:HEAD --xml";

		$path = SVN_REPO_PATH . '/php-src/branches/' . $branch;

		$process = proc_open($cmd, $descriptorspec, $pipes, $path, $env);
		$log_xml = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		proc_close($process);

		$sx = new \SimpleXMLElement($log_xml);
		if (!$sx) {
			throw new \Exception('svn log failed ' . $path);
		}
		return $sx;
	}

	/* TODO: do some sanity check if the branch is actually co'ed */
	static function isValidBranch($branch) {
		$path = SVN_REPO_PATH . '/php-src/branches/' . $branch;
		if (is_dir($path)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}
