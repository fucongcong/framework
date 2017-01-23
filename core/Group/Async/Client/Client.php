<?php

namespace Group\Async\Client;

use Config;

class Client 
{
    public function call($server, $cmd, $data, $getRecv = true)
    {   
        $data = \Group\Async\DataPack::pack($cmd, $data);

        static $client = null;
        if (is_null($client)){
            $servers = Config::get("async::server");
            if (!isset($servers[$server])) throw new Exception("Not Found the {$server}", 1);
            
            $client = pfsockopen($servers[$server]['serv'], $servers[$server]['port']);
        }
        if (!$client){
            //能否fallback到同步的模式?
            return false;
        }
        fwrite($client, $data . "\r\n");
        if ($getRecv){
            $content = '';
            // stream_set_blocking($client, FALSE );
            //设置一个5s的超时
            stream_set_timeout($client, 3);
            $info = stream_get_meta_data($client);
            while (!$info['timed_out']) {
                $content .= fread($client, 8192);
                if (stristr($content,"\r\n")){
                    break;
                }
                $info = stream_get_meta_data($client);
            }
            //不一定一定是json对象
            return trim($content);
        }
    }

}