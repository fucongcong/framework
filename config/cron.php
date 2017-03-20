<?php
return [
    //false时,重启注意清除cache
    'daemon' => true,

    'cache_dir' => 'runtime/cron',

    'class_cache' => 'runtime/cron/bootstrap.class.cache',

    //log路径
    'log_dir' => 'runtime/cron',

    //定时器轮询周期，精确到毫秒
    'tick_time' => 1000,

    //每个定时任务执行到达该上限时，该子进程会自动重启，释放内存
    'max_handle' => 5,

    'job' => [

        [
            'name' => 'TestLog',//任务名
            'time' => '*/1 * * * *',//定时规则 分 小时 天 周 月
            'command' => 'src\Web\Cron\Test',//执行的类库
        ],

        [
            'name' => 'TestLog2',//任务名
            'time' => '*/2 * * * *',//定时规则 分 小时 天 周 月
            'command' => 'src\Web\Cron\TestCache',//执行的类库
        ],

        [
            'name' => 'TestLog4',//任务名
            'time' => '*/2 3 * * *',//定时规则 分 小时 天 周 月
            'command' => 'src\Web\Cron\TestCache',//执行的类库
        ],

        // [
        //     'name' => 'testCache',
        //     'time' => '25 */2 * * *',//定时规则 分 小时 天 周 月
        //     'command' => 'src\Web\Cron\TestCache',
        // ],

        // [
        //     'name' => 'testSql',
        //     'time' => '45 */2 * * *',//定时规则 分 小时 天 周 月
        //     'command' => 'src\Web\Cron\testSql',
        // ],

        // [
        //     'name' => 'testSql2',
        //     'time' => '*/3 * * * *',//定时规则 分 小时 天 周 月
        //     'command' => 'src\Web\Cron\TestSql2',
        // ],

    ],
];