<?php

namespace Group\Rpc;

class RpcService
{   
    protected $client;

    public function __construct()
    {   
        $path = \Container::getInstance() -> getAppPath();

        $type = \Config::get('rpc::current_server');
        $server = \Config::get('rpc::server');
        $host = $server[$type]['host'];
        $port = $server[$type]['port'];

        $this -> client = new \Hprose\Socket\Client("{$type}://{$host}:{$port}", false);
    }

    public function call($name, $function, $args) 
    {
        list($group, $name) = explode(":", $name);
        $func = "{$group}_{$name}Service_{$function}";
        try {
            return call_user_func_array([$this -> client, $func], $args);
        } catch(\Exception $e) {dump($e);
            return false;
        }
    }
}