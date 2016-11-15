<?php

namespace rmtools;


class PeclDb extends \SQLite3 {

	public function __construct($db_path, $autoinit = true)
	{
		$flags = SQLITE3_OPEN_READWRITE;
		$existent = file_exists($db_path);

		if (!$existent) {
				$flags |= SQLITE3_OPEN_CREATE;
		}
		
		$this->open($db_path);

		if (!$existent && $autoinit) {
			$this->initDb();
		}
	}

	public function initDb()
	{
		$sql = "CREATE TABLE ext_release (ext_name STRING, ext_version STRING, ts_built INTEGER);";
		$this->exec($sql);

	}

	public function add($name, $version, $force = false)
	{
		if ($force) {
			$this->remove($name, $version);
		}

		if ($this->exists($name, $version)) {
			return false;
		}

		$name = $this->escapeString($name);
		$version = $this->escapeString($version);
		$sql = "INSERT INTO ext_release (ext_name, ext_version, ts_built) VALUES ('$name', '$version', 0);";
		$this->exec($sql);

		return true;
	}

	public function remove($name, $version)
	{
		$name = $this->escapeString($name);
		$version = $this->escapeString($version);
		$sql = "DELETE FROM ext_release WHERE ext_name = '$name' AND ext_version = '$version';";
		$this->exec($sql);
	}

	public function exists($name, $version, $where = '')
	{
		/* cant check such thing, so trust :) */
		if ($where) {
			$where = "AND $where";
		}

		$name = $this->escapeString($name);
		$version = $this->escapeString($version);
		$sql = "SELECT ts_built FROM ext_release WHERE ext_name = '$name' AND ext_version = '$version' $where;";

		$res = $this->query($sql);

		$ret = false !== $res->fetchArray(SQLITE3_NUM);
		//return $res->numColumns() > 0;

		$res->finalize();

		return $ret;
	}

	public function done($name, $version)
	{
		/* XXX That's an assumption as the latest timestamp should be about 30 mit old. 
		   Need to extend pecl.php to set the real statuses when in't really done */
		return $this->exists($name, $version, "ts_built - " . time() . " > 1800");
	}

	public function dump($where = '')
	{
		/* cant check such thing, so trust :) */
		if ($where) {
			$where = "WHERE $where";
		}

		$res = $this->query("SELECT * FROM ext_release $where ORDER BY ext_name, ext_version ASC");
		echo "DUMP ext_release " . PHP_EOL . PHP_EOL;
		while(false !== ($row = $res->fetchArray(SQLITE3_ASSOC))) {
			foreach ($row as $col => $val) {
				echo "$col=$val" . PHP_EOL;
			}
			echo PHP_EOL;
		}
		$res->finalize();
	}

	public function dumpQueue()
	{
		$this->dump("ts_built <= 0");
	}

	public function touch($name, $version) 
	{
		$name = $this->escapeString($name);
		$version = $this->escapeString($version);
		$sql = "UPDATE ext_release SET ts_built=" . time() . " WHERE lower(ext_name) = lower('$name') AND lower(ext_version) = lower('$version');";
		$this->exec($sql);
	}
}

