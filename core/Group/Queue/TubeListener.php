<?php

namespace Group\Queue;

use Pheanstalk\Pheanstalk;

class TubeListener
{	
	private $jobs;

	private $tubes;

	public function __construct()
	{
		$jobs = \Config::get("queue::queue_jobs");
		$this -> setJobs($jobs);
		$this -> setTubes();
	}

	public function setJobs($jobs)
	{
		foreach ($jobs as $job) {
			$this -> jobs[$job['tube']][$job['job']] = $job;
		}
	}

	public function setTubes()
	{
		foreach ($this -> jobs as $tube => $job) {
			$this -> tubes[] = $tube;
		}
	}

	public function getTubes()
	{
		return $this -> tubes;
	}

	public function getJobs()
	{
		return $this -> jobs;
	}

	public function getTubesCount()
	{
		return count($this -> tubes);
	}

	public function getJob($tube, Pheanstalk $pheanstalk)
	{	
		if (!isset($this -> jobs[$tube])) return false;
		
		$timeout = 3;
		$job = $pheanstalk -> watch($tube) -> reserve($timeout);
		if (empty($job) || !is_object($job) || $job -> getId() == 0 || empty($job -> getData())) return false;
		
		$data = [
			'job' => $job,
			'handle' => $this -> jobs[$tube],
		];
		return serialize($data);
	}
}