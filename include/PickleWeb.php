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

		$this->db->delUri($uri); /* XXX pure dev stuff, remove in prod */
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


	protected function diffProviders(array $remote, array $local)
	{
		$ret = array();

		if (!isset($remote["providers"]) || !is_array($remote["providers"]) || empty($remote["providers"])) {
			return array();
		} else if (!isset($llocal["providers"]) || !is_array($local["providers"]) || empty($local["providers"])) {
			return $remote["providers"];
		}

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
			$pkgs_new = (array)$this->fetchUriJson($uri);
			$pkgs = (array)$this->db->getUriJson($uri);

			if (!$this->db->saveUriJson($uri, $pkgs_new)) {
				throw new \Exception("Failed to save '$uri'");
			}

			$ret = array_merge($ret, $this->diffProviders($pkgs_new, $pkgs));
		}

		return $ret;
	}

	protected function isUniqueTag($name, $version, array $tags)
	{
		foreach ($tags as $tag) {
			if ($tag["name"] == $name && $tag["version"] == $version) {
				return false;
			}
		}

		return true;
	}

	protected function isValidTag(array $tag)
	{
		/* XXX raise some errors on this, or be more verbose at least ??? */
		return isset($tag["name"]) &&
			!empty($tag["name"]) &&
			isset($tag["version"]) &&
			!empty($tag["version"]) &&
			isset($tag["source"]["url"]) &&
			!empty($tag["source"]["url"]) /* XXX might check at least the URL format */;
	}

	public function diffTags(array $remote, array $local)
	{
		$ret = array();

		if (empty($remote) || !isset($remote["packages"])) {
			return array();
		} else if (empty($local) || !isset($local["packages"])) {
			foreach ($remote["packages"] as $name => $tags) {
				foreach ($tags as $version => $data) {
					/*if (!$this->isValidTag($data)) {
						continue;
					}*/
					/* $version is from the tag name, be strict and use the oone from the actual tag data*/
					if ($this->isUniqueTag($name, $data["version"], $ret)) {
						$ret[] = $data;
					}
				}
			}

			return $ret;
		}

		foreach ($remote["packages"] as $name => $tags) {
			if (!isset($local["packages"][$name])) {
				$ret[$name] = $data;
				break;
			}
			
			foreach ($tags as $version => $data) {
				/*if (!$this->isValidTag($data)) {
					continue;
				}*/
				if (!isset($local["packages"][$name][$version]) && $this->isUniqueTag($name, $data["version"], $ret)) {
					$ret[] = $data;
				}
			}
		}

		return $ret;
	}

	public function getNewTags()
	{
		$provs = $this->fetchProviderUpdates();
		$ret = array();

		/* $name the ext name is, vendor/foo it looks like */
		foreach ($provs as $name => $sha) {
			$uri = "/json/$name.json";

			$remote = (array)$this->fetchUriJson($uri);
			$local = (array)$this->db->getUriJson($uri);

			if (!$this->db->saveUriJson($uri, $remote)) {
				throw new \Exception("Failed to save '$uri'");
			}

			$ret = array_merge($ret, $this->diffTags($remote, $local));
		}

		return $ret;
	}

	public function pingBack($data)
	{
		// TODO send the build data to pickle web
	}

}

