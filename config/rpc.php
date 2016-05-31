<?php
return [
    
    'cache_dir' => 'runtime/rpc',

    'server' => [
        //连接类型
        'tcp' => [

            'host' => '127.0.0.1',
            'port' => '9396',
        ],

        'http' => [

            'host' => '127.0.0.1',
            'port' => '9397',
        ],

        'ws' => [

            'host' => '127.0.0.1',
            'port' => '9394',
        ],
    ]

];