<?php

namespace rmtools;

include __DIR__ . '/BranchConfig.php';
include __DIR__ . '/MakeLogParserVC.php';

class PickleBuildVC
{
	public $branch;
	public $build_name;
	protected $pickle_phar = 'c:\apps\bin\pickle.phar';

	public function __construct(PickleBranch $branch, $build_name)
	{
		$this->branch = $branch;
		$this->build_name = $build_name;


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

	public function clean()
	{

	}

	public function archive()
	{

	}


}

