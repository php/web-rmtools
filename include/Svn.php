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

		$cmd = "svn log -r $first_revision:HEAD --xml";

		$path = SVN_REPO_PATH . '/php-src/branches/' . $branch;

		$process = proc_open($cmd, $descriptorspec, $pipes, $path);
		$log_xml = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		proc_close($process);
file_put_contents('/home/pierre/t.xml', $log_xml);
		$sx = new \SimpleXMLElement($log_xml);
		if (!$sx) {
			throw new \Exception('svn log failed ' . $path);
		}
		return $sx;
	}

	function update($branch, $nolog = false) {
		if (!static::isValidBranch($branch)) {
			throw new \Exception('Invalid branch name ' . $branch);
			return FALSE;
		}

		$descriptorspec = array(
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("pipe", "w") // stderr is a file to write to
		);

		$cmd = "svn update";
		if ($branch != 'trunk') {
			$path = SVN_REPO_PATH . '/php-src/branches/' . $branch;
		} else {
			$path = SVN_REPO_PATH . '/php-src/trunk';
		}

		$process = proc_open($cmd, $descriptorspec, $pipes, $path);
		$out = stream_get_contents($pipes[1]);
		$revision = (int)str_replace('At revision ', '', $out);
		fclose($pipes[1]);
		proc_close($process);

		$cmd = 'svn info --xml ' . $path;
		$process = proc_open($cmd, $descriptorspec, $pipes, $path);
		$out = stream_get_contents($pipes[1]);
		$revision = (int)str_replace('At revision ', '', $out);
		fclose($pipes[1]);
		proc_close($process);
		$sx = new \SimpleXMLElement($out);
		if (!$sx) {
			throw new \Exception('svn log failed ' . $path);
		}
var_dump($sx->entry[0]->commit['revision']);
		$revision = (int)$sx->entry[0]->commit['revision'];
		if (!$revision) {
			throw new \Exception('svn log failed, invalid revision ' . $revision . ' in ' . $path);
		}
		return $revision;
	}

	function export($branch, $targetdir) {
		if (!static::isValidBranch($branch)) {
			throw new \Exception('Invalid branch name ' . $branch);
		}

		if (!is_dir(dirname($targetdir))) {
			throw new \Exception('Invalid target directory ' . $branch);
		}

		$descriptorspec = array(
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("pipe", "w") // stderr is a file to write to
		);

		if ($branch != 'trunk') {
			$path = SVN_REPO_URL . '/branches/' . $branch ;
		} else {
			$path = SVN_REPO_URL . '/' . $branch ;
		}
		$cmd = "svn --quiet  export " . $path . ' ' . $targetdir;

		$process = proc_open($cmd, $descriptorspec, $pipes, $path);
		$out = stream_get_contents($pipes[1]);
		$err = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		proc_close($process);
		if (!is_dir($targetdir)) {
			throw new \Excpetion('svn export failed: ' . $err);
		}
	}

	/* TODO: do some sanity check if the branch is actually co'ed */
	static function isValidBranch($branch) {
		if ($branch == 'trunk') {
			$path = SVN_REPO_PATH . '/php-src/trunk';
		} else {
			$path = SVN_REPO_PATH . '/php-src/branches/' . $branch;
		}

		if (is_dir($path)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}
