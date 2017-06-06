<?php
return [
    
    'cache_dir' => 'runtime/rpc',

    'current_server' => 'tcp',
     
    'server' => [
        //连接类型
        'tcp' => [
            'host' => '0.0.0.0',
            'port' => '9396',
        ],
    ],

    'setting' => [
        //'daemonize' => true,
        'reactor_num' => 4,
        'worker_num' => 25,    //worker process num
        'backlog' => 128,   //listen backlog
        'max_request' => 2000,
        'heartbeat_idle_time' => 30,
        'heartbeat_check_interval' => 10,
        'dispatch_mode' => 3,
    ],
];