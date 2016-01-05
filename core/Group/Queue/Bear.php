<?php
//queue核心
namespace Group\Queue;

use swoole_process;

class Bear
{
    protected $log_dir;

    protected $work_num;

    protected $task_worker_num;

    public function __construct()
    {
        //将主进程设置为守护进程
        swoole_process::daemon();

        $this -> initParam();

        $this -> setPid();
    }

    public function start()
    {	
    	//设置主进程别名
        $this -> setProcessName();

        //设置信号
        $this -> setSignal();

        //启动N个work工作进程

        //启动队列监听器
    }

    public function restart()
    {
      	$pid = $this -> getPid();
        if (!empty($pid) && $pid) {
            if (swoole_process::kill($pid, 0)) {
                swoole_process::kill($pid, SIGUSR1);
            }   
        }
    }

    public function stop()
    {
      	$pid = $this -> getPid();
        if (!empty($pid) && $pid) {
            if (swoole_process::kill($pid, 0)) {
                swoole_process::kill($pid, SIGTERM);
            }   
        }
    }

    public function status()
    {

    }

    public function getPid()
    {
    	if (file_exists($this -> log_dir."/pid"))
        return file_get_contents($this -> log_dir."/pid");
    }

    public function setPid()
    {
        $pid = posix_getpid();
        file_put_contents($this -> log_dir."/pid", $pid);
    }

    public function setProcessName()
    {
        swoole_process::name('group async queue manager');
    }

    public function setSignal()
    {	
    	//子进程结束时主进程收到的信号
    	swoole_process::signal(SIGCHLD, function ($signo) {

    	});

    	//主进程结束时收到的信号
    	swoole_process::signal(SIGTERM, function ($signo) {

    	});

    	//主进程重启时收到的信号,该信号用于用户自定义
    	swoole_process::signal(SIGUSR1, function ($signo) {

    	});
    }

    public function initParam()
    {
    	$this -> log_dir = \Config::get("queue::log_dir"); 
    	$this -> work_num = \Config::get("queue::work_num"); 
    	$this -> task_worker_num = \Config::get("queue::task_worker_num"); 
    }
}
