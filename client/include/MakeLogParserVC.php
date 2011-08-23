<?php
namespace rmtools;
include __DIR__ . '/MakeLogParser.php';

class MakeLogParserVc extends MakeLogParser {
	function parse($path, $root_src_dir)
	{
		$this->stats = array('warning' => 0, 'error' => 0);
		$lines = file($path);
		if (empty($lines)) {
			return NULL;
		}
		$line_nr = 1;

		if ($root_src_dir && $root_src_dir[strlen($root_src_dir) -1] != '\\') {
			$root_src_dir .= '\\';
		}

		//win32\build\deplister.c(50) : warning C4090: 'function' : different 'const' qualifiers
		$log = '';
		$log = array();
		foreach ($lines as $line) {
			$out = '#' . ($line_nr++) . ' ';
			$res = preg_match($pcre, $line);
			if ($res) {
				/* absolute path + error/warning */
				$txt = $line;
				$re1='([a-z]:\\\\(?:[-\\w\\.\\d]+\\\\)*(?:[-\\w\\.\\d]+)?)';	# Windows Path 1
				$re2='.*?';	# Non-greedy match on filler
				$re3='(\\d+)';	# Integer Number 1
				$re4='.*?';	# Non-greedy match on filler
				$re5='((?:[a-z][a-z]+))';	# Word 1
				$re6='.*?';	# Non-greedy match on filler
				$re7='((?:[a-z][a-z]*[0-9]+[a-z0-9]*))';	# Alphanum 1
				$re8 =':(.*?)$';
				if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4.$re5.$re6.$re7.$re8."/is", $txt, $matches))
				{
						$winpath1  = $matches[1][0];
						$int1      = $matches[2][0];
						$word1     = $matches[3][0];
						$alphanum1 = $matches[4][0];
						$error_msg = trim(str_replace(array("\r","\n"), array('',''),$matches[5][0]));
						$row      = "match 1: ($winpath1) ($int1) ($word1) ($alphanum1) ($error_msg)\n";
						$row = array(
										'file'    => $winpath1,
										'line'    => $int1,
										'level'   => $word1,
										'code'    => $alphanum1,
										'message' => $error_msg
										);
				} else {
						/* relative path + error/warning */
					$re1='(.*?)';	# Non-greedy match on filler
					$re2='';	# Uninteresting: int
					$re3='';	# Non-greedy match on filler
					$re4='\((\\d+)\)';	# Integer Number 1
					$re5='.*?';	# Non-greedy match on filler
					$re6='((?:[a-z][a-z]+))';	# Word 1
					$re7='.*?';	# Non-greedy match on filler
					$re8='((?:[a-z][a-z]*[0-9]+[a-z0-9]*))';	# Alphanum 1
					$re9 =':(.*?)$';

					if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4.$re5.$re6.$re7.$re8.$re9."/is", $txt, $matches))
					{
							$path=$matches[1][0];
							$int1=$matches[2][0];
							$word1=$matches[3][0];
							$alphanum1=$matches[4][0];
							$error_msg=trim(str_replace(array("\r","\n"), array('',''),$matches[5][0]));
							$out .= "match 2: ($path) ($int1) ($word1) ($alphanum1) ($error_msg)\n";
							$row = array(
											'file'    => $path,
											'line'    => $int1,
											'level'   => $word1,
											'code'    => $alphanum1,
											'message' => $error_msg
											);
					} else {
						/* cl.exe, link, etc. + error/warning */
						$re1='((?:[a-z][a-z]+))';	# Word 1
						$re2='.*?';	# Non-greedy match on filler
						$re3='((?:[a-z][a-z]+))';	# Word 2
						$re4='.*?';	# Non-greedy match on filler
						$re5='((?:[a-z][a-z]*[0-9]+[a-z0-9]*))';	# Alphanum 1
						$re6 =':(.*?)$';

						if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4.$re5.$re6."/is", $txt, $matches))
						{
							$word1=$matches[1][0];
							$word2=$matches[2][0];
							$alphanum1=$matches[3][0];

							$error_msg = trim(str_replace(array("\r","\n"), array('',''), $matches[4][0]));
							$out .= "match 3: ($word1) ($word2) ($alphanum1) ($error_msg)\n";
							if (substr($alphanum1, 0, 3) == 'LNK') {
								if (strpos($txt, '.lib') || strpos($txt, '.obj')) {
									$pos = strpos($txt, ':');
									$file = substr($txt, 0, $pos);
									$re  = preg_match('/^.*: (warning|error) LNK[0-9]+:.*$/', $txt, $matches);
									if ($re) {
										$level = $matches[1];
									} else {
										$level = '';
									}
								}

								$row = array(
												'file'    => $file,
												'line'    => '',
												'level'   => $level,
												'code'    => $alphanum1,
												'message' => $error_msg
												);
							} else {
								$row = array(
												'file'    => '',
												'line'    => $word1,
												'level'   => $word2,
												'code'    => $alphanum1,
												'message' => $error_msg
												);
							}
						} else {
							continue;
						}
					}
				}

				$row['file'] = str_ireplace($root_src_dir, '', $row['file']);
				$row['section'] = strtolower(dirname($row['file']));
				$row['file'] = str_ireplace($row['section'] . '\\', '', $row['file']);
				if ($row['level'] == 'fatal') {
					$row['level'] = 'error';
				}

				if (isset($this->stats[$row['level']])) {
					$this->stats[$row['level']]++;
				}
				$log[] = $row;
			} else {
				continue;
			}
		}
		$this->log = $log;
	}
}
