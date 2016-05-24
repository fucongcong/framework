<?php

namespace Group\Cron;

use swoole_http_server;

class CronAdmin
{   
    protected $http;

    public function __construct()
    {
        $http = new swoole_http_server('127.0.0.1', '9999');
        $http -> set(array(
            'reactor_num' => 1,
            'worker_num' => 2,    //worker process num
            'backlog' => 128,   //listen backlog
            'max_request' => 500,
            'heartbeat_idle_time' => 30,
            'heartbeat_check_interval' => 10,
            'dispatch_mode' => 3, 
        ));

        $http -> on('request', function ($request, $response){
            $request -> get = isset($request -> get) ? $request -> get : [];
            $request -> post = isset($request -> post) ? $request -> post : [];
            $request -> cookie = isset($request -> cookie) ? $request -> cookie : [];
            $request -> files = isset($request -> files) ? $request -> files : [];
            $request -> server = isset($request -> server) ? $request -> server : [];
   
            if ($request->server['request_uri'] == '/favicon.ico') {
                $response->end();
                return;
            }
            
            $cache_dir = \Config::get('cron::cache_dir') ? : 'runtime/cron';
            $pid = \FileCache::get('pid', $cache_dir);
            $work_ids = \FileCache::get('work_ids', $cache_dir);
            ob_start();

            require(__DIR__."/View/console.php");


            $output = ob_get_contents();
            ob_end_clean();
            $response -> status(200);
            $response -> end($output);
            return;
        });
        
        $this -> http = $http;
    }

    public function start()
    {
        $this -> http -> start();
    }
}