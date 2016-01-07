<?php
//queue核心
namespace Group\Queue;

use swoole_process;
use Group\Queue\TubeTick;

class Bear
{
    protected $log_dir;

    protected $worker_num;

    protected $task_worker_num;

    protected $queue_jobs;

    protected $worker_pids;

    protected $workers;

    public function __construct()
    {
        $this -> initParam(); 
    }

    public function start()
    {	
        //将主进程设置为守护进程
        swoole_process::daemon(true);
        //设置信号
        $this -> setSignal();

        //启动N个work工作进程
        $this -> startWorkers();

        //启动队列监听器
        $this -> bindTubeTick();

        $this -> setPid();

        \Log::info("异步队列服务启动", [], 'queue.bear');
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
                //杀掉worker进程
                foreach (\FileCache::get('work_ids', $this -> log_dir."/") as $work_id) {
                    swoole_process::kill($work_id, SIGKILL);
                }
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
        $parts = explode('/', $this -> log_dir."/pid");
        $file = array_pop($parts);
        $dir = '';
        foreach ($parts as $part) {
            if (!is_dir($dir .= "$part/")) {
                 mkdir($dir);
            }
        }
        file_put_contents("$dir/$file", $pid);
    }

    public function setSignal()
    {	
        //子进程结束时主进程收到的信号
        swoole_process::signal(SIGCHLD, function ($signo) {
            //kill掉所有worker进程 必须为false，非阻塞模式
            static $worker_count = 0;
            while($ret = swoole_process::wait(false)) {
                $worker_count++;
                \Log::info("PID={$ret['pid']}worker进程退出!", [], 'queue.bear');
                if ($worker_count >= $this -> worker_num){
                    \Log::info("主进程退出!", [], 'queue.bear');
                    swoole_process::kill($this -> getPid(), SIGKILL); 
                }
            }   
        });

    	//主进程重启时收到的信号,该信号用于用户自定义
    	swoole_process::signal(SIGUSR1, function ($signo) {

    	});
    }

    public function startWorkers()
    {   
        //启动worker进程
        for ($i=0; $i < $this -> worker_num; $i++) { 
            $process = new swoole_process(array($this, 'workerCallBack'), false);
            $processPid = $process->start();
            $this -> setWorkerPids($processPid);
            $this -> workers[$processPid] = $process;
        }
    }

    public function workerCallBack(swoole_process $worker) 
    {
        //子进程
        swoole_event_add($worker -> pipe, function($pipe) use ($worker) {
            $recv = $worker -> read();
            \Log::info("收到 {$recv}", [], 'queue.worker');  
        });
    }

    public function setWorkerPids($pid)
    {
        $this -> worker_pids[] = $pid;
        \FileCache::set('work_ids', $this -> worker_pids, $this -> log_dir."/");
    }

    public function bindTubeTick()
    {
        $tick = new TubeTick($this -> workers);
        $tick -> work();
    }

    public function initParam()
    {
    	$this -> log_dir = \Config::get("queue::log_dir"); 
        \Log::$cache_dir = $this -> log_dir;
    	$this -> worker_num = \Config::get("queue::worker_num"); 
    	$this -> task_worker_num = \Config::get("queue::task_worker_num"); 
    }

    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([$this, $method], $parameters);
    }
}
