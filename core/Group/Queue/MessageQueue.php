<?php
/*
首先会起一个守护进程。
载入config的queue.php配置文件。放于主进程中
启动配置数量的work进程
每个work下面都从队列中读取待处理的任务，启动task进行处理

热重启：重新载入配置文件
结束：杀死所有task，work最后kill掉主进程
status:队列的数据统计，失败，成功次数。共享内存实现

中间有一个轮询监听器来监控是否有队列任务，如果存在随机派发到work中处理，使用管道进行work间的通信
*/
namespace Group\Queue;

use Group\Queue\Bear;

class MessageQueue
{
    protected $argv;

    protected $help = "
\033[34m
 ----------------------------------------------------------

     -----        ----      ----      |     |   / ----
    /          | /        |      |    |     |   |      |
    |          |          |      |    |     |   | ----/
    |   ----   |          |      |    |     |   |
     -----|    |            ----       ----     |

 ----------------------------------------------------------
\033[0m
\033[31m 使用帮助: \033[0m
\033[33m Usage: core/queue [start|restart|stop|status] \033[0m
";

    public function __construct($argv)
    {
        $this -> argv = $argv;
    }

    /**
     * run the console
     *
     */
    public function run()
    {
        $this -> checkArgv();
        die($this -> help);
    }

    /**
     * 检查输入的参数与命令
     *
     */
    protected function checkArgv()
    {
        $argv = $this -> argv;
        if (!isset($argv[1])) return;
        if (!in_array($argv[1], ['start', 'restart', 'stop', 'status'])) return;
        $bear = new Bear();
        $bear -> $argv[1]();
        die;
    }
}
