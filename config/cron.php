<?php
return [
    //false时,重启注意清除cache
    'daemon' => true,

    'timezone' => "PRC",

    //log路径
    'log_dir' => 'runtime/cron',

    'job' => [
        // [
        //     'name' => 'TestLog3',//任务名
        //     'time' => '* * * * *',//定时规则 分 小时 天 周 月
        //     'command' => 'src\Web\Cron\TestSql',//执行的类库
        // ],

        [
            'name' => 'TestLog',//任务名
            'time' => '* * * * *',//定时规则 分 小时 天 周 月
            'command' => 'src\Web\Cron\TestSql',//执行的类库
        ],

        [
            'name' => 'TestLog2',//任务名
            'time' => '*/2 */4 * * *',//定时规则 分 小时 天 周 月
            'command' => 'src\Web\Cron\TestCache',//执行的类库
        ],

        [
            'name' => 'TestLog4',//任务名
            'time' => '* * * * *',//定时规则 分 小时 天 周 月
            'command' => 'src\Web\Cron\TestSql',//执行的类库
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