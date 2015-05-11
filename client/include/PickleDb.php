<?php

namespace rmtools;

class PickleDb extends \SQLite3
{
	public function __construct($db_path, $autoinit = true)
	{
		$flags = SQLITE3_OPEN_READWRITE;
		$existent = file_exists($db_path);

		if (!$existent) {
				$flags |= SQLITE3_OPEN_CREATE;
		}
		
		$this->open($db_path, $flags);

		if (!$existent && $autoinit) {
			$this->initDb();
		}
	}

	public function initDb()
	{
		/* no primary keys here, reling on the hashes delivered by pickleweb. */
		$sql = "CREATE TABLE package_release (package_hash STRING, package_name STRING, ts_placed INTEGER, ts_finished INTEGER);";
		$this->exec($sql);
	}

	public function add($name, $hash, $force = false)
	{
		if ($force) {
			$this->remove($name, $hash);
		}

		if ($this->exists($name, $hash)) {
			return false;
		}

		$name = $this->escapeString($name);
		$hash = $this->escapeString($hash);
		$sql = "INSERT INTO package_release (package_name, package_hash, ts_placed, ts_finished) VALUES ('$name', '$hash', " . time() . ", 0);";
		$this->exec($sql);

		return true;
	}

	public function remove($name, $hash)
	{
		$name = $this->escapeString($name);
		$hash = $this->escapeString($hash);
		$sql = "DELETE FROM package_release WHERE package_name = '$name' AND package_hash = '$hash';";
		$this->exec($sql);
	}

	public function exists($name, $hash, $where = '')
	{
		/* cant check such thing, so trust :) */
		if ($where) {
			$where = "AND $where";
		}

		$name = $this->escapeString($name);
		$hash = $this->escapeString($hash);
		$sql = "SELECT ts_finished FROM package_release WHERE package_name = '$name' AND package_hash = '$hash' $where;";

		$res = $this->query($sql);

		$ret = false !== $res->fetchArray(SQLITE3_NUM);
		//return $res->numColumns() > 0;

		$res->finalize();

		return $ret;
	}

	public function done($name, $hash)
	{
		/* XXX That's an assumption as the latest timestamp should be about 30 mit old. 
		   Need to extend pecl.php to set the real statuses when in't really done */
		return $this->exists($name, $hash, "ts_finished - " . time() . " > 1800");
	}

	public function dump($where = '')
	{
		/* cant check such thing, so trust :) */
		if ($where) {
			$where = "WHERE $where";
		}

		$res = $this->query("SELECT * FROM package_release $where ORDER BY package_name, package_hash ASC");
		echo "DUMP package_release " . PHP_EOL . PHP_EOL;
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
		$this->dump("ts_finished <= 0");
	}

	public function finished($name, $hash) 
	{
		$name = $this->escapeString($name);
		$hash = $this->escapeString($hash);
		$sql = "UPDATE package_release SET ts_finished=" . time() . " WHERE lower(package_name) = lower('$name') AND lower(package_hash) = lower('$hash');";
		$this->exec($sql);
	}
}

