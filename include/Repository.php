<?php
namespace rmtools;

include __DIR__ . '/Svn.php';
include __DIR__ . '/Git.php';

class Repository {

	public static function fromBranchConfig(BranchConfig $config)
	{
		$name = $config->getRepoName();
		$config_path = __DIR__ . '/../data/config/repo/' . $name . '.ini';

		if (!is_readable($config_path)) {
			throw new \Exception('Cannot open repo data <' . $config_path . '>');
		}

		$repo = parse_ini_file($config_path, true,INI_SCANNER_RAW);
		if (!$repo) {
			throw new \Exception('Cannot parse config file <' . $config_path . '>');
		}

		if (!isset($repo['type'])) {
			throw new \Exception('Invalid repo config data, no type defined');
		}
		switch ($repo['type']) {
			case 'svn':
				$r = new Svn($repo['url']);
				return $r;
				break;
			case 'git':
				$r = new Git($repo['url'], isset($repo['gh_url']) ? $repo['gh_url'] : null);
				return $r;
				break;
            default:
                throw new \Exception('Invalid repo config data, invalid type defined');
		}
	}
}
