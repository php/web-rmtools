<?php

namespace rmtools;

class PeclMail {
	protected $buffer_file;
	protected $aggregate;
	protected $aggregated_sent = false;
	protected $buffer = array (
		'from' => array(),
		'to' => array(),
		'subject' => '',
		'text' => '',
		'attachment' => array()
	);

	public function __construct($pkg_path, $aggregate = false)
	{
		if (!file_exists($pkg_path)) {
			throw new \Exception("'$pkg_path' does not exist");
		} 

		$this->buffer_file = TMP_DIR . DIRECTORY_SEPARATOR . md5($pkg_path);
		$this->aggregate = (boolean)$aggregate;

		if ($this->aggregate && file_exists($this->buffer_file)) {
			$tmp = file_get_contents($this->buffer_file);
			$this->buffer = unserialize($tmp);
		}
	}

	public function saveState()
	{
		$tmp = serialize($this->buffer);
		$ret = file_put_contents($this->buffer_file, $tmp);
	}

	public function cleanup()
	{
		unlink($this->buffer_file);
	}

	public function isAggregated()
	{
		return $this->aggregate;
	}

	/* XXX the state with from, to and subject is somehow unclean */
	public function xmail($from, $to, $subject, $text, array $attachment = array())
	{
		if (!$this->aggregate) {
			return xmail($from, $to, $subject, $text, $attachment);
		}

		/* aggregate all the stuff below */
		$this->buffer['to'][] = $to;
		$this->buffer['from'][] = $from;
		/* subject can't be aggregated anyway , or?*/
		$this->buffer['subject'] = $subject;
		$this->buffer['text'] = $this->buffer['text'] ."\n" . $text;
		$this->buffer['attachment'] = array_merge($attachment, $this->buffer['attachment']);

		$this->saveState();

		/* fake we've sent it */
		return true;
	}

	public function mailAggregated($from, $to, $subject, $open, $close, $with_attachment)
	{
		if (!$to) {
			$to = implode(',', array_unique($this->buffer['to']));
		}
		if (!$from) {
			$from = implode(',', array_unique($this->buffer['from']));
		}
		$text = "$open\n\n" . $this->buffer['text'] . "\n\n$close";

		$attachment = $with_attachment ? $this->buffer['attachment'] : array();

		$this->aggregated_sent = true;
		
		return xmail($from, $to, $subject, $text, $attachment);
	}

	public function setFrom($from)
	{
		$this->buffer['from'] = $from;
	}

	public function setTo($to)
	{
		$this->buffer['to'] = $to;
	}

	public function setSubject($subject)
	{
		$this->buffer['subject'] = $subject;
	}

}
