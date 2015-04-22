<?php

namespace rmtools;

class PickleBuild
{
	protected $config_path;


	public function __construct($config_path)
	{
		if (!file_exists($config_path)) {
			throw new \Exception("'$config_path' doesn't exist");
		}
		$this->config_path = $config_path;
	}


	public function phpize()
	{

	}

	public function configure()
	{

	}

	public function make()
	{

	}


}

