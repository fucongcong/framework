<?php

namespace Group\Async\Handler;

abstract class FinishHandler 
{	
	protected $serv;

	protected $fd;

	protected $data;

	protected $cache;

	protected $database;

	protected $table;

	public function __construct($serv, $fd, $data, $table)
	{
		$this->serv = $serv;
		$this->fd = $fd;
		$this->data = $data;
		$this->table = $table;
	}

	abstract public function handle();

	public function task($cmd, $data)
	{	
		//update count
		$count = $this->table->get($this->fd);
		$count['count'] = $count['count'] + 1;
		$this->table->set($this->fd, $count);

		//投递task
		$data = \Group\Async\DataPack::pack($cmd, $data, ['fd' => $this->fd]);
		$this->serv->task($data);
	}

	public function getData()
	{
		return $this->data;
	}

	public function getServ()
	{
		return $this->serv;
	}

	public function getFd()
	{
		return $this->fd;
	}

	public function setCache(obj $cache)
	{
		$this->cache = $cache;
	}

	public function getCache()
	{
		return $this->cache;
	}

	public function setDatabase(obj $database)
	{
		$this->database = $database;
	}

	public function getDatabase()
	{
		return $this->database;
	}

	public function createService($serviceName)
	{
		return \App::getInstance()->singleton('service')->createService($serviceName);
	}
}
