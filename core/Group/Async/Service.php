<?php

namespace Group\Async;

class Service
{   
    protected $serv;

    protected $fd;

    protected $fromId;
    
    public function __construct($serv, $fd, $fromId)
    {
        $this->serv = $serv;
        $this->fd = $fd;
        $this->fromId = $fromId;
    }
    // protected $serviceName;

	public function createDao($serviceName)
	{
		list($group, $serviceName) = explode(":", $serviceName);
		$class = $serviceName."DaoImpl";
		$serviceName = "src\\Async\\$group\\Dao\\Impl\\$class";

        return app()->singleton(strtolower($serviceName), function() use ($serviceName) {
            return new $serviceName();
        });
	}

 //    //需要支持不同目录
 //    public function createService($serviceName)
 //    {
 //        list($group, $serviceName) = explode(":", $serviceName);
 //        // return  \Rpc::service("{$group}:{$serviceName}");
 //        $class = $serviceName."ServiceImpl";
 //        $serviceName = "src\\Services\\".$group."\\Impl\\".$class;

 //        return app()->singleton(strtolower($serviceName), function() use ($serviceName) {
 //            return new $serviceName();
 //        });
 //    }
}
