<?php

namespace rmtools;

include __DIR__ . '/BranchConfig.php';

class Pickle
{
	
	protected $pickle_phar = 'c:\apps\bin\pickle.phar';

	public function __construct($pickle = NULL)
	{
		if ($pickle) {
			$this->pickle_phar = $pickle;
		}
	}
}

