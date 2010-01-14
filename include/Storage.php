<?php
namespace rmtools;
include_once 'Svn.php';

class Storage {
	protected $db = NULL;
	protected $release = NULL;
	protected $dev_branch = NULL;
	protected $release_branch = NULL;
	protected $dev_first_revision = NULL;

	function __construct($release) {
	
		$svn = new Base;
		$this->base = $svn;
		$release = $this->release = $svn->getRelease($release);
		$this->dev_branch = $release['dev_branch'];
		$this->release_branch = $release['release_branch'];
		$this->dev_first_revision = $release['dev_first_revision'];

		$path = DB_PATH . '/' . $this->release_branch . '-' . $this->dev_branch . '.sqlite';

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
		if (!sqlite_query($this->db, 'CREATE TABLE revision (
			revision VARCHAR(32),
			release VARCHAR(32),
			date VARCHAR(32),
			author VARCHAR(80),
			msg VARCHAR(255),
			status integer,
			comment TEXT,
			news TEXT)')) {
			throw new \Exception('Cannot initialize storage ' . $path);
		}
	}

	function updateRelease() {

		$svn = new Svn;
		$dev_last_revision = $svn->update($this->dev_branch);
		if ($this->dev_branch != $this->release_branch) {
			$release_last_revision = $svn->update($this->release_branch);
			$status = 0;
		} else {
			// if same branches, status is by default 'merged'
			$status = 1;
		}
		$date_now = date('Y-m-d h:m O');

		if (0 && $this->dev_branch == $this->release_branch) {
			$this->release['release_last_update'] = $this->release['dev_last_update'] = $date_now;
			$this->release['dev_last_revision'] = $dev_last_revision;
			$this->release['release_last_revision'] = $release_last_revision;
			$this->base->setLatestRevisionForRelease($this->release['name'], $dev_last_revision, $release_last_revision);
			$this->base->setLastUpdateForRelease($this->release['name'], $this->release['release_last_update']);
			return TRUE;
		}

		$log_xml = $svn->fetchLogFromBranch($this->dev_branch, $this->dev_first_revision);

		if (!$log_xml) {
			return FALSE;
		}

		foreach ($log_xml->logentry as  $v) {
			$msg = (string) $v->msg;
			$msg = substr(substr($msg, 0, strpos($msg . "\n", "\n")), 0, 80);
			$rev =  (string) $v['revision'];
			$author = (string) $v->author;
			$date = (string) $v->date;

			$res = sqlite_query($this->db, "SELECT status FROM revision WHERE revision='" . $rev . "'");

			if ($res && sqlite_num_rows($res) > 0) {
				$row = sqlite_fetch_array($res);
				if ($row && empty($row['author'])) {
					$res = sqlite_query($this->db, "UPDATE revision SET author='" . sqlite_escape_string($author) .
						"', status=$status WHERE revision='" . sqlite_escape_string($rev) . "'");
					if (!$res) {
						Throw new \Exception('Update query failed for ' . $rev);
					}
				}
			} else {
				$res = sqlite_query($this->db, "INSERT INTO revision (revision, release, date, author, status, msg, comment, news)
						VALUES('$rev' , '" . $this->release['name'] . "','" . $date . "','" . $author . "', $status, '" . sqlite_escape_string($msg) . "', '', '');");
					if (!$res) {
						Throw new \Exception('Insert query failed for ' . $rev);
					}
			}
		}

		$this->release['dev_last_revision'] = $dev_last_revision;
		$this->release['release_last_revision'] = ($this->dev_branch == $this->release_branch) ? $dev_last_revision : $release_last_revision;

		$this->base->setLatestRevisionForRelease($this->release['name'], $this->release['dev_last_revision'],  $this->release['release_last_revision']);
		$this->release['last_update'] = $date_now ;
		$this->base->setLastUpdateForRelease($this->release['name'], $date_now);
		return TRUE;
	}

	function createSnapshot($filename = FALSE, $force = FALSE) {
		if ($filename && !is_dir(dirname($filename))) {
			throw new \Exception('Invalid filename ' . $filename);
		}
		$time = time();
		if (!$filename) {
			$filename = SNAPS_PATH . '/php-' . $this->release['name'] . '-src-' . date("YmdHi", $time) . '.zip';
		}

		if ($this->release['release_last_revision'] == $this->release['release_last_snap_revision'] && !$force) {
			return TRUE;
		}

		$tmpname = tempnam(sys_get_temp_dir(), 'rmtools');
		$tmpname_dir = $tmpname . '.dir';

		$svn = new Svn;
		$svn->export($this->release_branch, $tmpname_dir);

		$odir = getcwd();
		chdir($tmpname_dir);
		if (!$filename) {
			$snaps_archive_name = SNAPS_PATH . '/test.zip';
		} else {
			$snaps_archive_name = $filename;
		}

		$now = date(DATE_RFC822, $time);

		$text = "
PHP source snapshot generated on $now. The last revision in this snap is
 " . $this->release['release_last_revision'];

		file_put_contents("SNAPSHOT.txt", $text);
		$cmd = "zip -r $snaps_archive_name *";
		exec($cmd);
		chdir($odir);

		if (!file_exists($snaps_archive_name)) {
			throw new \Exception('Fail to create archive ' . $snaps_archive_name);
		}

		$this->base->setLastRevisionSnapForRelease($this->release['name'], $this->release['release_last_revision']);
		$this->release['release_last_snap_revision'] = $this->release['release_last_revision'];

		return $filename;
	}

	function getLatestRevision() {
		$res = sqlite_query($this->db, 'SELECT MAX(revision) as revision FROM revision WHERE release=' . "'" . $this->release['name'] . "'", SQLITE_ASSOC);
		if ($res && sqlite_num_rows($res) > 0) {
			$latest_rev = sqlite_fetch_array($res);
			return $latest_rev['revision'];
		}
		return NULL;
	}

	function getAll() {
		$res = sqlite_query($this->db, "SELECT * FROM revision  WHERE release='" . $this->release['name'] . "' ORDER by revision", SQLITE_ASSOC);
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
		$error = FALSE;
		if (!isset($revision['status']) || !isset($revision['comment']) || !isset($revision['news']) || ((int)$revision['revision'] < $this->release['dev_first_revision'])) {
			Throw new \Exception('Invalid revision, incomplete update');
		}

		$sql = "UPDATE revision
			set status=" . $revision['status'] . ", comment='" . sqlite_escape_string($revision['comment']) . "', news='" . sqlite_escape_string($revision['news']) . "' WHERE revision=" . (integer)$revision['revision'];

		$res = sqlite_query($this->db, $sql);
		if ($res) {
			return TRUE;
		} else {
			Throw new \Exception('Failed to update revision ' . $revision);
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
