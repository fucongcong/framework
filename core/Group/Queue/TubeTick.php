<?php

namespace Group\Queue;

use Pheanstalk\Pheanstalk;

class TubeTick
{	
	protected $pheanstalk;

	protected $workers;

	public function __construct($workers)
	{	
		$server = \Config::get("queue::server");
		$this -> pheanstalk = new Pheanstalk($server['host'], $server['port']);
		$this -> workers = $workers;
	}

	public function work()
	{
		swoole_timer_tick(5000, function($timerId){
		    
		    if(!$this -> pheanstalk -> getConnection() -> isServiceListening()) {
		    	\Log::emergency("队列服务器崩溃了!TubeTick监听器退出", [], 'tube.tick');
		    	swoole_timer_clear($timerId);
		    }
    		//是否有队列任务，有的话给worker进程发消息
	        foreach ($this -> workers as $pid => $worker) {  	
				$data = "有任务来了";
	        	$worker -> write($data);
	    	}
		});

	}
}