<?php

namespace rmtools;

class MakeLogParser {
	public $log;

	function toHtml($title)
	{
		$log = $this->log;
		ob_start();
		include __DIR__ . '/../template/make_log.tpl.php';
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}
