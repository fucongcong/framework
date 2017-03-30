<?php

namespace Group\Services;

class Service
{
    // protected $serviceName;

	public function createDao($serviceName)
	{
		list($group, $serviceName) = explode(":", $serviceName);
		$class = $serviceName."DaoImpl";
		$serviceName = "src\\Dao\\".$group."\\Impl\\".$class;

        return app()->singleton(strtolower($serviceName), function() use ($serviceName) {
            return new $serviceName();
        });
	}

    //需要支持不同目录
    public function createService($serviceName)
    {
        list($group, $serviceName) = explode(":", $serviceName);
        // return  \Rpc::service("{$group}:{$serviceName}");
        $class = $serviceName."ServiceImpl";
        $serviceName = "src\\Services\\".$group."\\Impl\\".$class;

        return app()->singleton(strtolower($serviceName), function() use ($serviceName) {
            return new $serviceName();
        });
    }
}
