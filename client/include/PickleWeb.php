<?php

namespace rmtools;

class PickleWeb
{
	protected $db_path;
	protected $host;

	protected $info;

	protected $updatesAvailableFlag = false;

	public function __construct($host, $db_path)
	{
		$this->host = $host;

		if (!is_dir($db_path)) {
			if (!mkdir($db_path)) {
				throw new \Exception("failed to create '$db_path'");
			}
		}

		$this->db_path = $db_path;

		$this->init();
	}

	public function init()
	{
		$uri = "{$this->host}/packages.json";
		$__tmp = file_get_contents($uri);
		if (!$__tmp) {
			throw new \Exception("Empty content received from '$url'");
		}

		$this->info = json_decode($__tmp, true);
		if (!$this->info) {
			throw new \Exception("Couldn't decode JSON from '$url'");
		}

		if (!isset($this->info["provider-includes"])) {
			throw new \Exception("No provider includes found");
		}

		$packages_json = $this->db_path . DIRECTORY_SEPARATOR . "packages.json";
		if (!file_exists($packages_json)) {
			$this->updatesAvailableFlag = true;
		} else {
			$packages_info = json_decode(file_get_contents($packages_json), true);
			foreach ($this->info["provider-includes"] as $uri => $hash) {
				if (!isset($packages_info["provider-includes"][$uri]) ||
					$packages_info["provider-includes"][$uri] != $hash) {
					$this->updatesAvailableFlag = true;
					break;
				}
			}
		}
		if ($this->updatesAvailableFlag && strlen($__tmp) != file_put_contents($packages_json, $__tmp)) {
			throw new \Exception("Couldn't save '$packages_json'");
		}
	}

	public function updatesAvailable()
	{
		return $this->updatesAvailableFlag;
	}

	public function fetchProviders()
	{
		$ret = array();

		/* XXX What meaning does the $hash have? */
		foreach ($this->info["provider-includes"] as $uri => $hash) {
			$url = "{$this->host}$uri";
			$__tmp = file_get_contents($url);

			if (!$__tmp) {
				//echo "Empty content received from '$url'";
				continue;
			}

			$pkgs = json_decode($__tmp, true);
			if (!is_array($pkgs) || !isset($pkgs["providers"]) || !is_array($pkgs["providers"]) || empty($pkgs["providers"])) {
				//echo "No packages provided from '$url'";
				continue;
			}
			$pkgs = $pkgs["providers"];

			foreach ($pkgs as $pkg => $phash) {

			}

			$ret = array_merge($ret, $pkgs);
		}

		return $ret;
	}

	public function pingBack($data)
	{
		// TODO send the build data to pickle web
	}

	public function updateDb()
	{

	}
}

