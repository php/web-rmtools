<?php

namespace rmtools;

class PickleDb extends \SQLite3
{
	protected $db_path;

	public function __construct($db_path)
	{
		$this->db_path = $this->createDir($db_path);
	}

	protected function isSlash($c)
	{
		return '\\' == $c || '/' == $c;
	}


	protected function buildUriLocalPath($uri)
	{
		if (!$this->isSlash($uri[0])) {
			$uri = DIRECTORY_SEPARATOR . $uri;
		}
		$uri = str_replace('/', DIRECTORY_SEPARATOR, $uri);

		return $this->db_path . $uri;
	}

	public function uriExists($uri)
	{
		return file_exists($this->buildUriLocalPath($uri));
	}

	public function getUri($uri)
	{
		$fname = $this->buildUriLocalPath($uri);

		if (!file_exists($fname)) {
			return false;
		}

		return file_get_contents($fname);
	}

	public function saveUriJson($uri, $data)
	{
		$json = json_encode($data, JSON_PRETTY_PRINT);

		return $this->saveUri($uri, $json);
	}

	public function saveUri($uri, $data)
	{
		$fname = $this->buildUriLocalPath($uri);

		$dir = dirname($fname);
		if (!is_dir($dir)) {
			$this->createDir($dir);
		}

		return strlen($data) == file_put_contents($fname, $data, LOCK_EX);
	}

	public function getUriJson($uri)
	{
		return json_decode($this->getUri($uri), true);
	}

	public function delUri($uri)
	{
		$fname = $this->buildUriLocalPath($uri);

		if (!file_exists($fname)) {
			return true;
		}

		return unlink($fname);
	}

	protected function createDir($path, $rec = true)
	{
		if (!is_dir($path)) {
			if (!mkdir($path, 0777, $rec)) {
				throw new \Exception("failed to create '$path'");
			}
		}

		return realpath($path);
	}
}
