<?php

namespace rmtools;

class Base {
	protected $db;
	const STATUS_SNAP_ONLY = 1;
	const STATUS_CLOSED = 2;
	const STATUS_RELEASE = 3;
	const STATUS_DEV = 4;

	function __construct() {
		$path = DB_PATH . '/rmtools.sqlite';
		$this->db = sqlite_open($path);
		if (!$this->db) {
			throw new \Exception('Cannot open storage ' . $path);
		}

		if (!static::isInitialized()) {
			static::createStorage();
		}
	}

	function isInitialized() {
		$res = sqlite_query($this->db, "SELECT name FROM sqlite_master WHERE type='table' AND name='release'");
		return (sqlite_num_rows($res) > 0);
	}

	function createStorage() {
		if (!sqlite_query($this->db, 'CREATE TABLE release (
		name VARCHAR(32),
		release_branch VARCHAR(32),
		dev_branch VARCHAR(32),
		status INTEGER,
		dev_first_revision INTEGER,
		dev_last_revision INTEGER,
		dev_last_update VARCHAR(32),
		release_last_revision INTEGER,
		release_last_snap_revision INTEGER,
		release_last_update VARCHAR(32))')) {
			throw new \Exception('Cannot initialize TABLE release');
		}

		if (!sqlite_query($this->db, 'CREATE TABLE rm (name VARCHAR(32), release_name VARCHAR(32))')) {
			throw new \Exception('Cannot initialize TABLE release');
		}
	}

	function createNewRelease($release, $release_branch, $dev_branch, $first_revision) {
		// TODO: valid release format
		$res = sqlite_query($this->db, "SELECT name FROM release WHERE name='" . sqlite_escape_string($release) . "'");
		if (!$res || sqlite_num_rows($res) > 0) {
			Throw new \Exception($release . ' already exists');
		}

		$res = sqlite_query($this->db, "INSERT INTO release (name, release_branch, dev_branch, dev_first_revision) VALUES('" .
				sqlite_escape_string($release) . "','" . sqlite_escape_string($release_branch) . "','" . sqlite_escape_string($dev_branch) . "'," .
				(int)$first_revision . ")");
		if (!$res) {
			Throw new \Exception('Cannot create release');
		}
	}

	function addRmToRelease($release, $rm){
		$release = sqlite_escape_string($release);
		$rm = sqlite_escape_string($rm);
		$res = sqlite_query($this->db, "SELECT name FROM rm WHERE name='$rm' AND release_name='$release'");
		if (sqlite_num_rows($res) > 0) {
			Throw new \Exception($release . ' cannot be found');
		}

		$res = sqlite_query($this->db, "INSERT INTO rm (name, release_name) VALUES('$rm','$release')");
		if (!$res) {
			Throw new \Exception('Cannot create release');
		}
	}

	function setLatestRevisionForRelease($release, $dev_revision, $release_revision) {
		$release = sqlite_escape_string($release);
		$dev_revision = (int)$dev_revision;
		$release_revision = (int)$release_revision;
		if (!$dev_revision) {
			Throw new \Exception('Invalid revision ' . $dev_revision);
		}
		if (!$release_revision) {
			Throw new \Exception('Invalid revision ' . $release_revision);
		}
		$res = sqlite_query($this->db, "UPDATE release SET dev_last_revision=$dev_revision, release_last_revision=$release_revision WHERE name='$release'");
		if (sqlite_changes($this->db) < 1) {
			Throw new \Exception('Release not found or update failed for ' . $release);
		}
	}

	function setLastUpdateForRelease($release, $date=FALSE) {
		if (!$date) {
			$date = date(DATE_RFC822);
		}
		$release = sqlite_escape_string($release);
		$date = sqlite_escape_string($date);
		$res = sqlite_query($this->db, "UPDATE release SET release_last_update='$date', dev_last_update='$date' WHERE name='$release'");

		if (sqlite_changes($this->db) < 1) {
			Throw new \Exception('Release not found ' . $release);
		}
	}

	function setLastRevisionSnapForRelease($release, $revision) {
		$release = sqlite_escape_string($release);
		$revision = (int)$revision;

		if (!$revision) {
			Throw new \Exception('Invalid revision ' . $revision);
		}

		$res = sqlite_query($this->db, "UPDATE release SET release_last_snap_revision=$revision WHERE name='$release'");

		if (sqlite_changes($this->db) < 1) {
			Throw new \Exception('Release not found ' . $release);
		}
	}

	function getRelease($release) {
		$release = sqlite_escape_string($release);
		$sql = "SELECT name, release_branch, dev_branch, status,
			dev_first_revision, dev_last_revision, dev_last_update,
			release_last_revision, release_last_snap_revision, release_last_update
		FROM release WHERE name='" . $release . "'";

		$res = sqlite_query($this->db, $sql, SQLITE_ASSOC);
		if (!$res) {
			Throw new \Exception('Query failed for ' . $release);
		}

		if (sqlite_num_rows($res) == 0) {
			Throw new \Exception($release . ' not found.');
		}

		return sqlite_fetch_array($res);
	}

	function getReleaseForRM($rm) {
		$sql = "SELECT release_name FROM rm WHERE name='" . sqlite_escape_string($rm) . "'";
		$res = sqlite_query($this->db, $sql);
		if (!$res) {
			Throw new \Exception('Query failed for ' . $release);
		}

		if (sqlite_num_rows($res) == 0) {
			Throw new \Exception($rm . ' not found.');
		}
		$rel = sqlite_fetch_all($res, SQLITE_NUM);
		$releases = array();
		foreach ($rel as $v) {
			$releases[] = $v[0];
		}

		return $releases;
	}

	function getAllReleases() {
		$sql = 'SELECT name FROM release ORDER BY name';
		$res = sqlite_query($this->db, $sql);
		if (!$res) {
			Throw new \Exception('Query failed for ' . $release);
		}

		if (sqlite_num_rows($res) == 0) {
			Throw new \Exception($rm . ' not found.');
		}
		$rel = sqlite_fetch_all($res, SQLITE_NUM);

		$releases = array();
		foreach ($rel as $v) {
			$releases[] = $v[0];
		}

		return $releases;
	}
	
	function exportReleasesAsJson() {
		$sql = 'SELECT * FROM release ORDER BY name';
		$res = sqlite_query($this->db, $sql);
		if (!$res) {
			Throw new \Exception('Query failed.');
		}

		$rel = sqlite_fetch_all($res, SQLITE_ASSOC);
		if (!$rel) {
			Throw new \Exception('No release found.');
		}
		return json_encode($rel);
	}
}
