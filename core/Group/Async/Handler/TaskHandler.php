<?php

namespace Group\Async\Handler;

abstract class TaskHandler 
{	
	protected $serv;

	protected $taskId;

	protected $fromId;

	protected $data;

	protected $cache;

	protected $database;
	
	public function __construct($serv, $taskId, $fromId, $data)
	{
		$this -> serv = $serv;
		$this -> taskId = $taskId;
		$this -> fromId = $fromId;
		$this -> data = $data;
	}

	abstract public function handle();

	public function getData()
	{
		return $this -> data;
	}

	public function getServ()
	{
		return $this -> serv;
	}

	public function getTaskId()
	{
		return $this -> taskId;
	}

	public function getfromId()
	{
		return $this -> fromId;
	}

	public function setCache(obj $cache)
	{
		$this -> cache = $cache;
	}

	public function getCache()
	{
		return $this -> cache;
	}

	public function setDatabase(obj $database)
	{
		$this -> database = $database;
	}

	public function getDatabase()
	{
		return $this -> database;
	}
}
