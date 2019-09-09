<?php
return [
    // prod|dev
    'environment' => 'dev',

    //只有在dev环境下才生效。tip: swoole http server下无法正常debug
    'debug' => false,

    //zh|en|fr...
    'locale' => 'zh',

    //时区
    'timezone' => 'Asia/Shanghai',

    //类的映射
    'aliases' => [
        //like  'demo'       => 'src\Service\demo',
    ],

    //系统会提前加载服务
    'serviceProviders' => [
        //'Group\Controller\TwigServiceProvider',
        'Group\Redis\RedisServiceProvider',
        'Group\Session\SessionServiceProvider',
        'Group\Queue\QueueServiceProvider',
        //'Group\Rpc\RpcServiceProvider',  //开启后提供rpc服务  需要安装swoole
    ],

    //需要实例化的单例
    'singles' => [
        //like  'demo'       => 'src\demo\demo',
    ],

    //aop切面
    'aop' => [
        //singles实例化的单例的别名
        'src\Services\User\Impl\UserServiceImpl' => [
            //方法执行前
            'before' => [
                //对应 方法 -> 通知处理 
                'testAop' => ['src\Web\Aop\UserServiceAop::before', 'src\Web\Aop\UserServiceAop::before']
            ],
            'after' => [
                'testAop' => 'src\Web\Aop\UserServiceAop::after'
            ],
            'throw' => [
                //对应 方法 -> 通知处理
                'testAop' => 'src\Web\Aop\UserServiceAop::exception'
            ],
        ], 
    ],

    //扩展console命令行控制台
    'console_commands' => [
        'log.clear' => [
            'command' => 'src\Web\Command\LogClearCommand', //执行的类
            'help' => '清除日志', //提示
        ],
    ],

    //当使用swoole http server 时，需要指定host,port
    'swoole_host' => '127.0.0.1',
    'swoole_port' => 9777,
    'swoole_setting' => [
        'reactor_num' => 4,
        'worker_num' => 40,    //worker process num
        'backlog' => 256,   //listen backlog
        'max_request' => 20000,
        'heartbeat_idle_time' => 30,
        'heartbeat_check_interval' => 10,
        'dispatch_mode' => 3, 
    ],

];