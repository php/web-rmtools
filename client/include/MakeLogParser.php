<?php

namespace rmtools;

class MakeLogParser {
	public $log;
	public $stats;
	public $diff = NULL;

	function toHtml($title)
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
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

	function diff($prev) {
		 $result = array();

		 foreach ($cur as $k => $v) {
			  if (array_key_exists($k, $prev)) {
					if (is_array($v)) {
						 $tmp_ar = log_diff($v, $prev[$k]);
						 if (count($tmp_ar)) { $result[$k] = $tmp_ar; }
					} else {
						 if ($v != $prev[$k]) {
							  $result[$k] = $v;
						 }
					}
			  } else {
					$result[$k] = $v;
			  }
		 }

		 return $result;
	}
}
