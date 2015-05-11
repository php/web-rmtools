<?php

namespace rmtools;

/* XXX need to add locking for job file ops, might be essential if there are too much exts/builds */

class PickleJob
{
	protected $job_dir;

	public function __construct($job_dir)
	{
		if (!is_dir($job_dir)) {
			throw new \Exception("Job dir '$job_dir' doesn't exist");
		}
		$this->job_dir = $job_dir;
	}

	public function add($sha, $name, $uri)
	{
		$fn = "{$this->job_dir}/$sha.job";
		$data = array(
			"name" => $name,
			"hash" => $sha,
			"uri"  => $uri,
			"status" => "new",
		);

		$this->save($fn, $data);
	}

	protected function save($fn, $data)
	{
		$json = json_encode($data, JSON_PRETTY_PRINT);

		if (strlen($json) != file_put_contents($fn, $json)) {
			throw new \Exception("Error while writing data to '$fn'");
		}
	}

	protected function validStatus($st)
	{
		return "new" == $st ||
			"fail" == $st ||
			"pass" == $st;
	}

	public function setStatus($sha, $status)
	{
		$fn = "{$this->job_dir}/$sha.job";

		if (!$this->validStatus($status)) {
			throw new \Exception("Invalid job status '$status'");
		}

		if (!file_exists($fn)) {
			throw new \Exception("Job '$fn' doesn't exist");
		}

		$data = json_decode(file_get_contents($fn), true);

		$data["status"] = $status;

		$this->save($fn, $data);
	}

	public function getNextNew()
	{
		$jobs = glob("{$this->job_dir}/*.job");

		foreach ($jobs as $fn) {
			$data = json_decode(file_get_contents($fn), true);
			if ("new" == $data["status"]) {
				return $data;
			}
		}

		return NULL;
	}

	public function cleanup()
	{
		$jobs = glob("{$this->job_dir}/*.job");
		
		foreach ($jobs as $fn) {
			$data = json_decode(file_get_contents($fn), true);
			if ("fail " == $data["status"] || "fail " == $data["status"]) {
				/* XXX save this to PickleDB */
				unlink($fn);
			}
		}
	}
}

