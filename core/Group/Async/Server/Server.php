<?php

namespace Group\Async\Server;

use swoole_server;

class Server {

	protected $serv;

    protected $servName;

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

        $this -> servName = $servName;
	}

	public function start() 
	{
        $this -> serv -> start();
	}

    public function onStart(swoole_server $serv) {
        if (PHP_OS !== 'Darwin') {
            swoole_set_process_name("php {$this -> servName}: master");
        }
        echo $this -> servName." Start...", PHP_EOL;
    }

    public function onWorkerStart(swoole_server $serv, $worker_id) {
        opcache_reset();
        $loader = require __ROOT__.'/vendor/autoload.php';
        $loader->setUseIncludePath(true);

        //设置不同进程名字,方便grep管理
        if (PHP_OS !== 'Darwin') {
            if ($worker_id >= $serv -> setting['worker_num']) {
                swoole_set_process_name("php {$this -> servName}: task");
            } else {
                swoole_set_process_name("php {$this -> servName}: worker");
            }
        }
        // 判定是否为Task Worker进程
        if( $worker_id >= $serv -> setting['worker_num'] ) {
        }
    }

    public function onWorkerStop(swoole_server $serv, $worker_id) {
        if($worker_id >= $serv->setting['worker_num']){
            echo 'Task #'. ($worker_id - $serv->setting['worker_num']). ' Ended.'. PHP_EOL;
        }else{
            echo 'Worker #'. $worker_id, ' Ended.'. PHP_EOL;
        }
    }

    public function onWorkerError(swoole_server $serv, $worker_id, $worker_pid, $exit_code) {
        //出错写log
        echo "[", date('Y-m-d H:i:s'), "] Process Crash : Wid : $worker_id error_code : $exit_code", PHP_EOL;
    }

    public function onReceive(swoole_server $serv, $fd, $from_id, $data) {
        
    }

    public function oneWork(swoole_server $serv, $fd, $from_id, $data ) {

    }

    public function onTask(swoole_server $serv, $task_id, $from_id, $data) {

    }

    public function onFinish(swoole_server $serv, $task_id, $data) {

    }
}