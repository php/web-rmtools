<?php

namespace rmtools;

class PickleWeb
{
	protected $host;

	protected $info;

	public function __construct($host)
	{
		$this->host = $host;

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

	public function updateDb()
	{

	}
}

