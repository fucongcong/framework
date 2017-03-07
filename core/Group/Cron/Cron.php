<?php

namespace Group\Cron;

use Group\Cron\ParseCrontab;
use Group\App\App;
use Group\Cache\BootstrapClass;
use swoole_process;
use swoole_table;

class Cron
{
    protected $cacheDir;

    /**
     * 定时器轮询周期，精确到毫秒
     *
     */
    protected $tickTime;

    protected $argv;

    protected $loader;

    protected $jobs;

    protected $workerNum;

    protected $workers;

    protected $classCache;

    protected $logDir;

    protected $table;

    protected $max_handle;

    protected $daemon = false;

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
\033[33m Usage: app/cron [start|restart|stop|status|exec (cron name)|rejob (cron name)] \033[0m
";
    /**
     * 初始化环境
     *
     */
    public function __construct($argv, $loader)
    {
        $this->cacheDir = \Config::get('cron::cache_dir') ? : 'runtime/cron';
        $this->tickTime = \Config::get('cron::tick_time') ? : 1000;
        $this->argv = $argv;
        $this->loader = $loader;
        $this->jobs = \Config::get('cron::job');
        $this->workerNum = count($this->jobs);
        $this->classCache = \Config::get("cron::class_cache"); 
        $this->logDir = \Config::get("cron::log_dir");
        $this->max_handle = \Config::get("cron::max_handle");
        $this->daemon = \Config::get("cron::daemon") ? : false;
        \Log::$cacheDir = $this->logDir;
    }

    /**
     * 执行cron任务
     *
     */
    public function run()
    {
        $this->checkArgv();

        $this->bootstrapClass();
    }

    public function start()
    {   
        unlink($this->logDir."/work_ids");
        
        $this->checkStatus();
        \Log::info("定时服务启动", [], 'cron');
        //将主进程设置为守护进程
        if ($this->daemon) swoole_process::daemon(true);

        //启动N个work工作进程
        $this->startWorkers();

        //设置信号
        $this->setSignal();

        swoole_timer_tick($this->tickTime, function($timerId) {
            foreach ($this->jobs as $key => $job) {
                $workers = $this->table->get('workers');
                $workers = json_decode($workers['workers'], true);
                //这里可以优化 如果用redis等等持久化的缓存来存的话  就可以做到对子进程的管理了，比如重新跑脚本，现在swoole table只能用于当前进程
                if (isset($workers[$job['name']]['nextTime'])) continue;

                if (empty($workers[$job['name']])) {
                    $this->newProcess($key);
                }

                $this->workers[$job['name']]['process']->write(json_encode($this->workers[$job['name']]['job']));
            }
        });

        $this->setPid();
    }

    public function status()
    {
        if (!$this->getPid()) {
            exit("cron服务未启动\n");
        }
        print_r(\FileCache::get('cronAdmin', $this->cacheDir));
    }

    public function restart()
    {
        $this->stop();
        sleep(1);
        $this->start();
    }

    /**
     * 将上一个进程杀死，并清除cron
     *
     */
    public function stop()
    {
        $pid = $this->getPid();

        if (!empty($pid) && $pid) {
            if (swoole_process::kill($pid, 0)) {
                //杀掉worker进程
                foreach (\FileCache::get('work_ids', $this->cacheDir) as $work_id) {
                    swoole_process::kill($work_id, SIGKILL);
                }
            }
        }
    }

    /**
     * 设置信号监听
     *
     */
    private function setSignal()
    {
        //子进程结束时主进程收到的信号
        swoole_process::signal(SIGCHLD, function ($signo) {
            //kill掉所有worker进程 必须为false，非阻塞模式
            static $worker_count = 0;
            while($ret = swoole_process::wait(false)) {
                $worker_count++;
                $workerNum = $this->table->get('workers_num');
                \Log::info("PID={$ret['pid']}worker进程退出!", [$workerNum, $signo], 'cron');

                if ($worker_count >= $workerNum['workers_num']){
                    \Log::info("主进程退出!", [], 'cron');
                    unlink($this->logDir."/work_ids");
                    unlink($this->logDir."/pid");
                    // foreach ($this->jobs as $job) {
                    //     unlink($this->cacheDir."/".$job['name']);
                    // }
                    swoole_process::kill($this->getPid(), SIGKILL); 
                }
            }
        });
    }

    /**
     * 启动worker进程处理定时任务
     *
     */
    private function startWorkers()
    {
        $this->table = new swoole_table(1024);
        $this->table->column('workers', swoole_table::TYPE_STRING, 1024 * 20);
        $this->table->column("workers_num", swoole_table::TYPE_INT);

        foreach ($this->jobs as $job) {
            $this->table->column($job['name']."_count", swoole_table::TYPE_INT);
        }
        $this->table->create();
        $this->table->set('workers_num', ["workers_num" => 0]);
        $this->table->incr('workers_num', 'workers_num', $this->workerNum);

        //启动worker进程
        for ($i = 0; $i < $this->workerNum; $i++) {
            $this->newProcess($i);
        }
    }

    /**
     * 检查输入的参数与命令
     *
     */
    protected function checkArgv()
    {
        $argv = $this->argv;
        if (!isset($argv[1])) die($this->help);

        if (!in_array($argv[1], ['start', 'restart', 'stop', 'status', 'exec', 'rejob'])) die($this->help);

        $function = $argv[1];
        $this->$function();
    }

    public function exec()
    {
        $argv = $this->argv;
        $jobName = isset($argv[2]) ? $argv[2] :'';
        foreach ($this->jobs as $job) {
            if ($job['name'] == $jobName) {
                call_user_func_array([new $job['command'], 'handle'], []);
                exit("{$jobName}执行完成\n");
            }

            continue;
        }

        exit("job不存在\n");
    }

    public function rejob()
    {
        $argv = $this->argv;
        $jobName = isset($argv[2]) ? $argv[2] :'';
        foreach ($this->jobs as $job) {
            if ($job['name'] == $jobName) {
                $workers = \FileCache::get('cronAdmin', $this->cacheDir);
                if (isset($workers['workers'][$jobName])) {
                    $processPid = $workers['workers'][$jobName]['pid'];
                    exec("kill -USR1 {$processPid}");
                    exit("{$jobName}重启完成\n");
                }
            }
            continue;
        }

        exit("job不存在\n");
    }

    public function workerCallBack(swoole_process $worker)
    {   
        $this->init();
        
        swoole_event_add($worker->pipe, function($pipe) use ($worker) { 
            $recv = $worker->read(); 
            $recv = json_decode($recv, true);
            if (!is_array($recv)) return;

            $this->bindTick($recv);
        });

        //接受重启的信号
        swoole_process::signal(SIGUSR1, function ($signo) use ($worker) {
            $pid = $worker->pid;
            $workers = \FileCache::get('cronAdmin', $this->cacheDir);
            $workers = $workers['workers'];
            foreach ($workers as $worker) {
                if ($worker['pid'] == $pid) {
                    $timerId = isset($worker['timerId']) ? $worker['timerId'] : 0;
                    $this->restartJob($timerId, $worker['job']);
                }
            }
        });
    }

    /**
     * 绑定cron job
     *
     */
    public function bindTick($job)
    {
        $timer = ParseCrontab::parse($job['time']);

        if (is_null($timer)) return;

        $job['timer'] = $timer;

        swoole_timer_tick(intval($timer * 1000), function($timerId, $job) {
            //计数
            $count = $this->table->incr($job['name'].'_maxNum', $job['name']."_count");
            if ($count && $count >= $this->max_handle) {
                //计数超过上限 重启该任务
                $this->restartJob($timerId, $job);
            }

            $workers = $this->table->get('workers');
            $workers = json_decode($workers['workers'], true);
            $workers[$job['name']]['timerId'] = $timerId;
            $workers[$job['name']]['startTime'] = date('Y-m-d H:i:s', time());
            $workers[$job['name']]['nextTime'] = date('Y-m-d H:i:s', time() + intval($job['timer']));
            $this->table->set('workers', ['workers' => json_encode($workers)]);

            \FileCache::set('cronAdmin', ['workers' => $workers], $this->cacheDir);

            call_user_func_array([new $job['command'], 'handle'], []);

        }, $job);

        $this->jobStart($job);
    }

    private function checkStatus()
    {
        if ($this->getPid()) {
            if (swoole_process::kill($this->getPid(), 0)) {
                exit('定时服务已启动！');
            }
        }
    }

    /**
     * 设置worker进程的pid
     *
     * @param pid int
     */
    private function setWorkerPids($pid)
    {
        if (!\FileCache::isExist('work_ids', $this->cacheDir)) {
            \FileCache::set('work_ids', [$pid => $pid], $this->cacheDir);
        } else {
            $workerPids = \FileCache::get('work_ids', $this->cacheDir);
            $workerPids[$pid] = $pid;
            \FileCache::set('work_ids', $workerPids, $this->cacheDir);
        }
    }

    /**
     * remove worker进程的pid
     *
     * @param pid int
     */
    private function removeWorkerPids($pid)
    {
        $workerPids = \FileCache::get('work_ids', $this->cacheDir);
        unset($workerPids[$pid]);
        \FileCache::set('work_ids', $workerPids, $this->cacheDir);
    }

    public function setPid()
    {
        $pid = posix_getpid();
        $parts = explode('/', $this->cacheDir."/pid");
        $file = array_pop($parts);
        $dir = '';
        foreach ($parts as $part) {
            if (!is_dir($dir .= "$part/")) {
                 mkdir($dir);
            }
        }
        file_put_contents("$dir/$file", $pid);
    }

    public function getPid()
    {
        if (file_exists($this->cacheDir."/pid"))
        return file_get_contents($this->cacheDir."/pid");
    }

    private function jobStart($job)
    {
        $workers = $this->table->get('workers');
        $workers = json_decode($workers['workers'], true);
        $workers[$job['name']]['startTime'] = date('Y-m-d H:i:s', time());
        $workers[$job['name']]['nextTime'] = date('Y-m-d H:i:s', time() + intval($job['timer']));
        $this->table->set('workers', ['workers' => json_encode($workers)]);

        \FileCache::set('cronAdmin', ['workers' => $workers], $this->cacheDir);
        \Log::info('定时任务启动'.$job['name'], [], 'cron.start');

        //开启计数
        $this->table->set($job['name'].'_maxNum', [$job['name']."_count" => 0]);
        $this->table->incr($job['name'].'_maxNum', $job['name']."_count");

        //先执行一次任务
        call_user_func_array([new $job['command'], 'handle'], []);
    }

    private function restartJob($timerId = 0, $job)
    {
        foreach ($this->jobs as $key => $one) {
            if ($one['name'] == $job['name']) {
                //清除该计数器
                if ($timerId) swoole_timer_clear($timerId);
                \Log::info('restart'.$job['name'], [$job], 'cron.restart');
                $workers = $this->table->get('workers');
                $workers = json_decode($workers['workers'], true);
                $workers[$job['name']] = [];
                $this->table->set('workers', ['workers' => json_encode($workers)]);

                $this->table->incr('workers_num', 'workers_num');

                $this->removeWorkerPids($job['workId']);
                swoole_process::kill($job['workId'], SIGKILL);
                break;
            }
        }
    }

    private function newProcess($i)
    {
        $process = new swoole_process(array($this, 'workerCallBack'), true);
        $processPid = $process->start();

        $this->setWorkerPids($processPid);

        $this->jobs[$i]['workId'] = $processPid;
        $this->workers[$this->jobs[$i]['name']] = [
            'process' => $process,
            'job' => $this->jobs[$i],
        ];

        $workers = $this->table->get('workers');
        $workers = json_decode($workers['workers'], true);
        $workers[$this->jobs[$i]['name']] = [
            'job' => $this->jobs[$i],
            'pid' => $processPid,
            'process' => $process,
            'startTime' => date('Y-m-d H:i:s', time()),
        ];
        $this->table->set('workers', ['workers' => json_encode($workers)]);

        \Log::info("工作worker{$processPid}启动", [], 'cron.work');
    }

    /**
     * 缓存类文件
     *
     */
    private function bootstrapClass()
    {
        $classCache = new BootstrapClass($this->loader, $this->classCache);
        foreach ($this->jobs as $job) {
            $classCache->setClass($job['command']); 
        }
        $classCache->bootstrap();
        
        require $this->classCache;
    }

    private function init()
    {
        $loader = require __ROOT__.'/vendor/autoload.php';
        $loader->setUseIncludePath(true);
        $app = new \Group\App\App();
        $app->initSelf();
        $app->doBootstrap($loader);
        $app->registerServices();
        $app->singleton('container')->setAppPath(__ROOT__);
    }
}
