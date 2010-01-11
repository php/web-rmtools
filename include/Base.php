<?php

namespace rmtools;


class Base {
	protected $db;

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
		first_revision INTEGER,
		last_revision INTEGER,
		last_snap_revision INTEGER,
		last_update VARCHAR(32))')) {
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
		
		$res = sqlite_query($this->db, "INSERT INTO release (name, release_branch, dev_branch, first_revision) VALUES('" .
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
	
	function setLatestRevisionForRelease($release, $revision) {
		$release = sqlite_escape_string($release);
		$revision = (int)$revision;
		$res = sqlite_query($this->db, "UPDATE release SET last_revision=$revision WHERE name='$release'");
		if (sqlite_changes($this->db) < 1) {
			Throw new \Exception('Release not found ' . $release);
		}
	}

	function getRelease($release) {
		$release = sqlite_escape_string($release);
		$sql = "SELECT name, release_branch, dev_branch, status, first_revision, last_revision, last_update, last_snap_revision FROM release WHERE name='" . $release . "'";
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
		return sqlite_fetch_array($res, SQLITE_ASSOC);
	}
}
