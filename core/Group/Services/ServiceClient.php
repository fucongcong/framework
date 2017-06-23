<?php

namespace Group\Services;

use Config;

class ServiceClient
{   
    protected $service;

    protected $serv;

    protected $port;

    protected $package_eof;

    protected $timeout = 1;

    public function __construct($service)
    {
        $this->service = $service;
        $servers = Config::get("async::server");
        if (!isset($servers[$service])) throw new \Exception("Not Found the {$service}", 1);
        $this->serv = $servers[$service]['serv'];
        $this->port = $servers[$service]['port'];
        $this->package_eof = $servers[$service]['config']['package_eof'];
    }

    public function setTimeout($time)
    {
        $this->timeout = $time;
    }

    public function call($cmd, $data)
    {
        $data = \Group\Async\DataPack::pack($cmd, $data);
        $data .= $this->package_eof;
        $res = (yield new \Group\Async\Client\TCP($this->serv, $this->port, $data, $this->timeout));
        if ($res) {
            $res['response'] = explode($this->package_eof, $res['response']);
            $res['response'] = json_decode($res['response'][0], true);
            yield $res;
        } 
    }
}
