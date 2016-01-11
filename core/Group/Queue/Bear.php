<?php
//queue核心
namespace Group\Queue;

use swoole_process;
use Group\Queue\TubeTick;
use Pheanstalk\Pheanstalk;
use Group\Cache\BootstrapClass;

class Bear
{
    protected $log_dir;

    protected $class_cache;

    protected $worker_num;

    protected $queue_jobs;

    protected $worker_pids;

    protected $workers;

    protected $tubes;

    protected $pheanstalk;

    protected $linstener;

    public function __construct($loader)
    {
        $this -> initParam($loader); 
    }

    public function start()
    {	
        \Log::info("异步队列服务启动", [], 'queue.bear');
        //将主进程设置为守护进程
        swoole_process::daemon(true);
        //设置信号
        $this -> setSignal();

        //启动N个work工作进程
        $this -> startWorkers();

        //启动队列监听器
        $this -> bindTubeTick();

        $this -> setPid(); 
    }

    public function restart()
    {
        $this -> stop();
        sleep(1);
        $this -> start();
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
    	// swoole_process::signal(SIGUSR1, function ($signo) {

    	// });
    }

    public function startWorkers()
    {   
        //启动worker进程
        for ($i=0; $i < $this -> worker_num; $i++) { 
            $process = new swoole_process(array($this, 'workerCallBack'), false);
            $processPid = $process->start();
            $this -> setWorkerPids($processPid);
            $this -> workers[$processPid] = [
                'process' => $process,
                'tube' => $this -> tubes[$i],
            ];
        }
    }

    public function workerCallBack(swoole_process $worker) 
    {   
        $pheanstalk = $this -> pheanstalk;
        $listener = $this -> listener;
        //worker进程
        swoole_event_add($worker -> pipe, function($pipe) use ($worker, $pheanstalk, $listener) {
            $recv = $worker -> read();
            $recv = $this -> listener -> getJob($recv);
            $recv = unserialize($recv); 
            if (is_object($recv['job'])) {
                try{
                    foreach ($recv['handle'] as $handerClass => $job) {
                       $handler = new $handerClass($recv['job'] -> getId(), $recv['job'] -> getData());
                       $handler -> handle();
                    }
                    //删除任务
                    $pheanstalk -> delete($recv['job']);
                    \Log::info("jobId:".$recv['job'] -> getId()."任务完成", [], 'queue.worker');
                }catch(\Exception $e){
                    \Log::error("jobId:".$recv['job'] -> getId()."任务出错了！", ['jobId' => $recv['job'] -> getId(), 'jobData' => $recv['job'] -> getData()], 'queue.worker');
                }
            } 

        });
    }

    public function setWorkerPids($pid)
    {
        $this -> worker_pids[] = $pid;
        \FileCache::set('work_ids', $this -> worker_pids, $this -> log_dir."/");
    }

    public function bindTubeTick()
    {
        $tick = new TubeTick($this -> workers, $this -> pheanstalk);
        $tick -> work();
    }

    public function initParam($loader)
    {
    	$this -> log_dir = \Config::get("queue::log_dir"); 
        \Log::$cache_dir = $this -> log_dir;
    	
        $this -> class_cache = \Config::get("queue::class_cache"); 
        $server = \Config::get("queue::server");
        //换一个类库 基本跑通
        $this -> pheanstalk = new Pheanstalk($server['host'], $server['port'], 10, true);

        //开始队列任务的监听
        $this -> listener = new TubeListener($this -> pheanstalk);
        $this -> worker_num = $this -> listener -> getTubesCount();
        $this -> tubes = $this -> listener -> getTubes();
        $this -> bootstrapClass($loader, $this -> listener -> getJobs());  
    }

    public function bootstrapClass($loader, $jobs)
    {
        $classCache = new BootstrapClass($loader, $this -> class_cache);
        foreach ($jobs as $job) {
            foreach ($job as $handerClass => $value) {
                $classCache -> setClass($handerClass);
            }  
        }
        $classCache -> bootstrap();
        require $this -> class_cache;
    }
}
