<?php
return [

    //异步消息队列的配置

    'server' => [
    	'host' => "127.0.0.1",
    	'port' => 11300
    ],

    //延迟秒数
    'delaytime' => 0,

    //过期秒数
    'lifetime' => 60,

    //log路径
    'log_dir' => 'runtime/queue',

    'queue_jobs' => [

    	[
    		'tube' => 'testjob1',//队列的名称
    		'job'  => 'src\Web\Queue\TestJob',//需要执行的任务
    		'priority' => 10,//该任务的重要程度，越小优先处理
    		//以下参数选填 不填默认读取外层的配置
    		'delaytime' => 0,
    		'lifetime' => 20,
            'task_worker_num' => 5,
    	]





    ],

    'worker_num' => 4,
    //默认开启10个task处理队列任务 也可以在queue中覆盖此选项
    'task_worker_num' => 10,

];
