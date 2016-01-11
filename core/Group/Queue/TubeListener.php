<?php

namespace Group\Queue;

class TubeListener
{	
	private $pheanstalk;

	private $jobs;

	private $tubes;

	public function __construct($pheanstalk)
	{
		$this -> pheanstalk = $pheanstalk;

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

	public function getJob($tube)
	{	
		$timeout = 10;
		$job = $this -> pheanstalk -> watch($tube) -> reserve($timeout);
		if (empty($job)) return false;
		$data = [
			'job' => $job,
			'handle' => $this -> jobs[$tube],
		];
		return serialize($data);
	}
}