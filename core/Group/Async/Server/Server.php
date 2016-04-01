<?php

namespace Group\Async\Server;

use swoole_server;
use Group\Common\ArrayToolkit;

class Server 
{
	protected $serv;

    protected $servName;

    protected $config;

	public function __construct($config =[], $servName)
	{
        $this -> serv = new swoole_server($config['serv'], $config['port']);
        $this -> serv -> set($config['config']);

        $this -> serv -> on('Start', [$this, 'onStart']);
        $this -> serv -> on('WorkerStart', [$this, 'onWorkerStart']);
        $this -> serv -> on('WorkerStop', [$this, 'onWorkerStop']);
        $this -> serv -> on('WorkerError', [$this, 'onWorkerError']);
        $this -> serv -> on('Receive', [$this, 'onReceive']);
        $this -> serv -> on('Task', [$this, 'onTask']);
        $this -> serv -> on('Finish', [$this, 'onFinish']);

        $this -> serv -> start();

        $this -> servName = $servName;
	}

    public function onStart(swoole_server $serv)
    {
        if (PHP_OS !== 'Darwin') {
            swoole_set_process_name("php {$this -> servName}: master");
        }
        echo $this -> servName." Start...", PHP_EOL;
    }

    public function onWorkerStart(swoole_server $serv, $workerId)
    {
        opcache_reset();
        // $loader = require __ROOT__.'/vendor/autoload.php';
        // $loader->setUseIncludePath(true);

        //设置不同进程名字,方便grep管理
        if (PHP_OS !== 'Darwin') {
            if ($workerId >= $serv -> setting['worker_num']) {
                swoole_set_process_name("php {$this -> servName}: task");
            } else {
                swoole_set_process_name("php {$this -> servName}: worker");
            }
        }
        // 判定是否为Task Worker进程
        if ($workerId >= $serv -> setting['worker_num']) {
        }
    }

    public function onWorkerStop(swoole_server $serv, $workerId)
    {
        if ($workerId >= $serv -> setting['worker_num']) {
            echo 'Task #'. ($workerId - $serv -> setting['worker_num']). ' Ended.'. PHP_EOL;
        } else {
            echo 'Worker #'. $workerId, ' Ended.'. PHP_EOL;
        }
    }

    public function onWorkerError(swoole_server $serv, $workerId, $workerPid, $exitCode)
    {
        echo "[", date('Y-m-d H:i:s'), "] Process Crash : Wid : $workerId error_code : $exitCode", PHP_EOL;
    }

    public function onReceive(swoole_server $serv, $fd, $fromId, $data)
    { 
        $data = trim($data);
        $data = explode($serv -> setting['package_eof'], $data);
        $return = '';
        try {
            $config = $this -> config;
            foreach($data as $one){
                list($cmd, $one) = \Group\Async\DataPack::unpack($one);
                
                if (isset($config['onWork'][$cmd])) {
                    $handler = new $config['onWork'][$cmd]['handler']($serv, $fd, $fromId, $one);

                    $handler -> handle();
                }
            }
        } catch (\Exception $e) {
            echo $e -> getMessage();
        }
    }

    public function onTask(swoole_server $serv, $taskId, $fromId, $data)
    {
        try {
            list($cmd, $one) = \Group\Async\DataPack::unpack($data);
            $config = $this -> config;
            if (isset($config['onTask'][$cmd])) {
                $handler = new $config['onTask'][$cmd]['handler']($serv, $taskId, $fromId, $one);
                return $handler -> handle();
            }
        } catch (\Exception $e) {
            echo $e -> getMessage();
        }
        return null;
    }

    public function onFinish(swoole_server $serv, $taskId, $data)
    {   
        try {
            list($cmd, $one) = \Group\Async\DataPack::unpack($data);
            $config = $this -> config;
            if (isset($config['onTask'][$cmd])) {
                $handler = new $config['onTask'][$cmd]['onFinish']($serv, $taskId, $one);
                $handler -> handle();
            }
        } catch (\Exception $e) {
            echo $e -> getMessage();
        }
    }

    public function initConfig($config) 
    {
        $config['onWork'] = ArrayToolkit::index($config['onWork'], 'cmd');
        $config['onTask'] = ArrayToolkit::index($config['onTask'], 'cmd');
        $this -> config = $config;
    }
}
