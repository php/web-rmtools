<?php
namespace rmtools;
include_once 'Svn.php';

class Storage {
	protected $db = NULL;
	protected $dev_branch = NULL;
	protected $release_branch = NULL;
	protected $first_revision = NULL;

	function __construct($release) {
	
		$svn = new Base;
		$release = $svn->getRelease($release);
		$this->dev_branch = $release['dev_branch'];
		$this->release_branch = $release['release_branch'];
		$this->first_revision = $release['first_revision'];

		$path = DB_PATH . '/' . $this->release_branch . '.sqlite';
		$this->db = sqlite_open($path);

		if (!$this->db) {
			throw new \Exception('Cannot open storage ' . $path);
		}

		if (!static::isInitialized()) {
			static::createStorage();
		}
	}

	function isInitialized() {
		$res = sqlite_query($this->db, "SELECT name FROM sqlite_master WHERE type='table' AND name='revision'");
		return (sqlite_num_rows($res) > 0);
	}

	function createStorage() {
		if (!sqlite_query($this->db, 'CREATE TABLE revision (revision VARCHAR(32), date VARCHAR(32), author VARCHAR(80), msg VARCHAR(255), status integer, comment TEXT, news TEXT)')) {
			throw new \Exception('Cannot initialize storage ' . $path);
		}
	}

	function updateRelease() {

		$svn = new Svn;
		$svn->update($this->dev_branch);
		$logxml = $svn->fetchLogFromBranch($this->dev_branch, $this->first_revision);

		if (!$logxml) {
			return FALSE;
		}

		foreach ($logxml->logentry as  $v) {
			$msg = (string) $v->msg;
			$msg = substr(substr($msg, 0, strpos($msg . "\n", "\n")), 0, 80);
			$rev =  (string) $v['revision'];
			$author = (string) $v->author;
			$date = (string) $v->date;

			$res = sqlite_query($this->db, "SELECT status FROM revision WHERE revision='" . $rev . "'");

			if ($res && sqlite_num_rows($res) > 0) {
				$row = sqlite_fetch_array($res);
				if ($row && empty($row['author'])) {
					$res = sqlite_query($this->db, "UPDATE revision SET author='" . sqlite_escape_string($author). "' WHERE revision='" . sqlite_escape_string($rev) . "'");
					if (!$res) {
						Throw new \Exception('Update query failed for ' . $rev);
					}
				}
			} else {
				$res = sqlite_query($this->db, "INSERT INTO revision (revision, date, author, status, msg, comment, news) VALUES('$rev' ,'" . $date . "','" . $author . "', 0, '" . sqlite_escape_string($msg) . "', '', '');");
					if (!$res) {
						Throw new \Exception('Insert query failed for ' . $rev);
					}
			}
		}
	}

	function getAll() {
		$res = sqlite_query($this->db, 'SELECT * FROM revision ORDER by revision', SQLITE_ASSOC);
		if ($res && sqlite_num_rows($res) > 0) {
			return sqlite_fetch_all($res);
		}
		return NULL;
	}

	function getOne($revision) {
		$res = sqlite_query($this->db, 'SELECT * FROM revision WHERE revision=' . (integer) $revision, SQLITE_ASSOC);
		if ($res && sqlite_num_rows($res) > 0) {
			return sqlite_fetch_array($res);
		}
		return NULL;
	}

	function updateRevision($revision) {
		$sql = "UPDATE revision
			set status=" . $revision['status'] . ", comment='" . sqlite_escape_string($revision['comment']) . "', news='" . sqlite_escape_string($revision['news']) . "' WHERE revision=" . (integer)$revision['revision'];
		$res = sqlite_query($this->db, $sql);
		if ($res) {
			return TRUE;
		}
		return FALSE;
	}

	function exportAsJson() {
		$log = $this->getAll();
		if ($log) {
			$json = new \StdClass;
			$json->totalRecords = count($log);
			$json->data = $log;
			return json_encode($json);
		}
	}
}
