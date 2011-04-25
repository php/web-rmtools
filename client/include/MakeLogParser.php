<?php

namespace rmtools;

class MakeLogParser {
	public $log;
	public $stats;

	function toHtml($title)
	{
		$log = $this->log;
		ob_start();
		include __DIR__ . '/../template/make_log.tpl.php';
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	function getErrors()
	{
		if ($this->stats['error'] > 0) {
			$res = array();
			foreach ($this->log as $e) {
					if ($e['level'] == 'error' || $e['level'] == 'fatal') {
						$res[] = $e;
					}
			}
			return $res;
		} else {
			return NULL;
		}
	}
}
