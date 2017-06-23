<?php

namespace Group\Async\Server;

use swoole_server;
use Group\Common\ArrayToolkit;
use swoole_table;
use Group\Exceptions\NotFoundException;

class Server 
{
	protected $serv;

    protected $servName;

    protected $config;

    protected $task_res;

	public function __construct($config =[], $servName)
	{  
        $this->serv = new swoole_server($config['serv'], $config['port']);
        $this->serv->set($config['config']);

        $this->serv->on('Start', [$this, 'onStart']);
        $this->serv->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->serv->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->serv->on('WorkerError', [$this, 'onWorkerError']);
        $this->serv->on('Receive', [$this, 'onReceive']);
        $this->serv->on('Task', [$this, 'onTask']);
        $this->serv->on('Finish', [$this, 'onFinish']);

        $this->config = $config;
        $this->servName = $servName;
        
        $this->serv->start();
	}

    public function onStart(swoole_server $serv)
    {
        if (PHP_OS !== 'Darwin') {
            swoole_set_process_name("php {$this->servName}: master");
        }
        echo $this->servName." Start...", PHP_EOL;
    }

    public function onWorkerStart(swoole_server $serv, $workerId)
    {
        opcache_reset();
        $loader = require __ROOT__.'/vendor/autoload.php';
        $loader->setUseIncludePath(true);
        $app = new \Group\App\App();
        $app->initSelf();
        $app->doBootstrap($loader);
        $app->registerServices();
        $app->singleton('container')->setAppPath(__ROOT__);

        //设置不同进程名字,方便grep管理
        if (PHP_OS !== 'Darwin') {
            if ($workerId >= $serv->setting['worker_num']) {
                swoole_set_process_name("php {$this->servName}: task");
            } else {
                swoole_set_process_name("php {$this->servName}: worker");
            }
        }
        // 判定是否为Task Worker进程
        // if ($workerId >= $serv->setting['worker_num']) {
        // } else {
        //     //$this->createTaskTable();
        // }
    }

    public function onWorkerStop(swoole_server $serv, $workerId)
    {
        if ($workerId >= $serv->setting['worker_num']) {
            echo 'Task #'. ($workerId - $serv->setting['worker_num']). ' Ended.'. PHP_EOL;
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
        $data = explode($serv->setting['package_eof'], $data);
        $return = '';
        try {
            $config = $this->config;
            foreach($data as $one){

                $serv->task(['data' => $one, 'fd' => $fd]);
                // list($cmd, $one, $info) = \Group\Async\DataPack::unpack($one);

                // $server = [
                //     'serv' => $serv,
                //     'fd' => $fd,
                //     'fromId' => $fromId,
                // ];

                // $this->doAction($cmd, $one, $server);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function onTask(swoole_server $serv, $fd, $fromId, $data)
    {
        try {
            list($cmd, $one, $info) = \Group\Async\DataPack::unpack($data['data']);

            $server = [
                'serv' => $serv,
                'fd' => $data['fd'],
                'fromId' => $fromId,
            ];

            return $this->doAction($cmd, $one, $server);

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function onFinish(swoole_server $serv, $fd, $data)
    {
        try {
            $this->sendData($serv, $data['fd'], $data['data']);

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function sendData(swoole_server $serv, $fd, $data){
        $fdinfo = $serv->connection_info($fd);
        if($fdinfo){
            //如果这个时候客户端还连接者的话说明需要返回返回的信息,
            //如果客户端已经关闭了的话说明不需要server返回数据
            //判断下data的类型
            if (is_array($data)){
                $data = json_encode($data);
            }
            $serv->send($fd, $data . $serv->setting['package_eof']);
        }
    }

    private function doAction($cmd, array $parameters, $server)
    {   
        list($class, $action) = explode("::", $cmd);
        list($group, $class) = explode("\\", $class);
        $service = "src\\Async\\$group\\Service\\Impl\\{$class}ServiceImpl";
        if (!class_exists($service)) {
            throw new NotFoundException("Service $service not found !");
        }

        $reflector = new \ReflectionClass($service);

        if (!$reflector->hasMethod($action)) {
            throw new NotFoundException("Service ".$service." exist ,But the Action ".$action." not found");
        }

        $instanc = $reflector->newInstanceArgs($server);
        $method = $reflector->getmethod($action);
        $args = [];
        foreach ($method->getParameters() as $arg) {
            $paramName = $arg ->getName();
            if (isset($parameters[$paramName])) $args[$paramName] = $parameters[$paramName];
        }

        return ['data' => $method->invokeArgs($instanc, $args), 'fd' => $server['fd']];
    }
}
