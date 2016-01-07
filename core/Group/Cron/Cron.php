<?php

use Group\Cron\ParseCrontab;
use Group\App\App;

class Cron
{
    protected $cacheDir;

    /**
     * 定时器轮询周期，精确到毫秒
     *
     */
    protected $tickTime;

    /**
     * 初始化环境
     *
     */
    public function __construct()
    {
        $loader = require __ROOT__.'/vendor/autoload.php';
        $loader -> setUseIncludePath(true);

        $app = new App();
        $app -> initSelf();
        $app -> doBootstrap($loader);
        $app -> registerServices();

        $this -> cacheDir = \Config::get('cron::cache_dir') ? : 'runtime/cron';
        $this -> cacheDir = $this -> cacheDir."/";
        $this -> tickTime = \Config::get('cron::tick_time') ? : 1000;
    }

    /**
     * 执行cron任务
     *
     */
    public function run()
    {   
        //将主进程设置为守护进程
        swoole_process::daemon(true);

        $this -> checkStatus();

        $this -> setPid();

        swoole_timer_tick($this -> tickTime, function($timerId){

            $jobs = \Config::get('cron::job');

            foreach ($jobs as $job) {

                if (\FileCache::isExist($job['name'], $this -> cacheDir)) continue;

                $this -> bindTick($job);
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

        call_user_func_array([new $job['command'], 'handle'], []);

        $job['timer'] = $timer;

        swoole_timer_tick(intval($timer * 1000), function($timerId, $job){

            call_user_func_array([new $job['command'], 'handle'], []);

            \FileCache::set($job['name'], ['nextTime' => date('Y-m-d H:i:s', time() + intval($job['timer']))], $this -> cacheDir);

        }, $job);

        \FileCache::set($job['name'], ['nextTime' => date('Y-m-d H:i:s', time() + intval($timer))], $this -> cacheDir);
    }

    /**
     * 将上一个进程杀死，并清除cron
     *
     */
    public function checkStatus()
    {
        $pid = $this -> getPid();

        if (!empty($pid) && $pid) {
            $filesystem = new \Filesystem();
            $filesystem -> remove($this -> cacheDir);
            if (swoole_process::kill($pid, 0)) {
                swoole_process::kill($pid, SIGTERM);
            }   
        }
    }

    public function setPid()
    {
        $pid = posix_getpid();
        $parts = explode('/', $this -> cacheDir."pid");
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
        if (file_exists($this -> cacheDir."pid"))
        return file_get_contents($this -> cacheDir."pid");
    }
}
