<?php

namespace Group\Queue;

use Pheanstalk\Pheanstalk;

class QueueService
{
	protected $host;

	protected $port;

	protected $priority;

	protected $delaytime;

	protected $lifetime;

	protected $pheanstalk;

	public function __construct()
	{
		$server = \Config::get("queue::server");
		$this -> host = $server['host'] ? : "127.0.0.1";
		$this -> port = $server['port'] ? : 11300;
		$this -> priority = \Config::get("queue::priority") ? : 10;
		$this -> delaytime = \Config::get("queue::delaytime") ? : 0;
		$this -> lifetime = \Config::get("queue::lifetime") ? : 60;
		$this -> pheanstalk = new Pheanstalk($this -> host, $this -> port);
	}

	public function put($tube, $data)
	{
		return $this -> pheanstalk -> useTube($tube) -> put($data, $this -> priority, $this -> delaytime, $this -> lifetime);
	}
}
