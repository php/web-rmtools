<?php

namespace rmtools;

class PickleWeb
{
	protected $db;
	protected $host;

	protected $info;

	protected $updatesAvailableFlag = false;

	public function __construct($host, PickleDB $db)
	{
		$this->host = $host;
		$this->db = $db;

		$this->init();
	}

	protected function fetchUri($uri, $allow_empty = false)
	{

		$url = "{$this->host}$uri";

		$__tmp = file_get_contents($url);


		if (false === $__tmp) {
			throw new \Exception("Error encountered while receiving '$url'");
		}

		if (!$allow_empty && !$__tmp) {
			throw new \Exception("Empty content received from '$url'");
		}

		return $__tmp;
	}

	protected function fetchUriJson($uri, $allow_empty = false)
	{
		$__tmp = $this->fetchUri($uri, $allow_empty);

		$ret = json_decode($__tmp, true);
		if (!$ret) {
			throw new \Exception("Couldn't decode JSON from '$uri' on '{$this->host}'");
		}

		return $ret;
	}

	public function init()
	{
		$uri = "/packages.json";
		$this->info = $this->fetchUriJson($uri);

		if (!isset($this->info["provider-includes"])) {
			throw new \Exception("No provider includes found");
		}

		if (!$this->db->uriExists($uri)) {
			$this->updatesAvailableFlag = true;
		} else {
			$packages_info = $this->db->getUriJson($uri);
			foreach ($this->info["provider-includes"] as $provider_uri => $hash) {
				if (!isset($packages_info["provider-includes"][$provider_uri]) ||
					$packages_info["provider-includes"][$provider_uri] != $hash) {
					$this->updatesAvailableFlag = true;
					break;
				}
			}
		}
		if ($this->updatesAvailableFlag && !$this->db->saveUriJson($uri, $this->info)) {
			throw new \Exception("Couldn't save '$uri'");
		}
	}

	public function updatesAvailable()
	{
		return $this->updatesAvailableFlag;
	}


	protected function saveUriLocal($uri)
	{

	}

	protected function getUriLocal($uri)
	{

	}


	protected function diffProviders($remote, $local)
	{
		$ret = array();

		foreach ($remote as $vendor => $sha) {
			if (isset($local[$vendor]) && $local[$vendor] != $sha) {
				$ret[$vendor] = $sha;
			}
		}

		return $ret;
	}
	public function fetchProviderUpdates()
	{
		$ret = array();

		foreach ($this->info["provider-includes"] as $uri => $hash) {
			$pkgs_new = $this->fetchUriJson($uri);
			if (!is_array($pkgs_new) || !isset($pkgs_new["providers"]) || !is_array($pkgs_new["providers"]) || empty($pkgs_new["providers"])) {
				continue;
			}

			$pkgs = $this->db->getUriJson($uri);
			if (!is_array($pkgs) || !isset($pkgs["providers"]) || !is_array($pkgs["providers"]) || empty($pkgs["providers"])) {
				$this->db->saveUriJson($uri, $pkgs_new);
				$ret = array_merge($ret, $pkgs_new["providers"]);
				continue;
			}

			$this->db->saveUriJson($uri, $pkgs_new);

			$ret = array_merge($ret, $this->diffProviders($pkgs_new["providers"], $pkgs["providers"]));
		}

		return $ret;
	}

	public function getNewTags()
	{
		$provs = $this->fetchProviderUpdates();


		return $provs;
	}

	public function pingBack($data)
	{
		// TODO send the build data to pickle web
	}

}

