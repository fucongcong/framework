<?php

namespace Group\Async\Handler;

abstract class FinishHandler 
{	
	protected $serv;

	protected $fd;

	protected $data;

	public function __construct($serv, $fd, $data)
	{
		$this -> serv = $serv;
		$this -> fd = $fd;
		$this -> data = $data;
	}

	abstract public function handle();

	public function task($data)
	{
		$this -> serv -> task($data);
	}

	public function getData()
	{
		return $this -> data;
	}

	public function getServ()
	{
		return $this -> serv;
	}

	public function getFd()
	{
		return $this -> fd;
	}
}
