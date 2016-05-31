<?php

class RpcKernal
{
    protected $server_type;

    protected $server;

    protected $cacheDir;

    protected $config;

    public function __construct($server_type)
    {
        $this -> server_type = $server_type;
        $this -> config = require(__ROOT__."config/rpc.php");
        $this -> cacheDir = $this -> config['cache_dir'];
    }

    public function init()
    {
        $this -> checkStatus();

        $pid = posix_getpid();
        file_put_contents($this -> cacheDir."/pid", $pid);

        $server_type = $this -> server_type;
        $server = $this -> config['server'];
        $server = $server[$server_type];
        $server = $server_type."://".$server['host'].":".$server['port']."/";
        $server = new HproseSwooleServer($server);
        $server -> set(array(
            'reactor_num' => 4,
            'worker_num' => 25,    //worker process num
            'backlog' => 128,   //listen backlog
            'max_request' => 2000,
            'heartbeat_idle_time' => 30,
            'heartbeat_check_interval' => 10,
            'dispatch_mode' => 3, 
        ));
        
        $this -> server = $server;
        $server->server->on('WorkerStart', array($this, 'blindClass'));
        $server->start();
    }

    public function addClass($classes, $server)
    {
        foreach ($classes as $class) {
           $server -> add(new $class[0],'',$class[1]);
        }
    }

    public function blindClass()
    {
        opcache_reset();

        $loader = require __ROOT__.'/vendor/autoload.php';
        $loader->setUseIncludePath(true);

        $app = new \Group\App\App();
        $app -> initSelf();
        $app -> doBootstrap($loader);
        $app -> registerServices();

        $classMap = new Group\Common\ClassMap();
        $classes = $classMap -> doSearch();

        \FileCache::set('services', $classes, $this -> cacheDir);
        $this -> addClass($classes, $this -> server);
    }

    public function checkStatus()
    {   
        $pid = file_get_contents($this -> cacheDir."/pid");
        $args = getopt('s:');
        if(isset($args['s'])) {

            switch ($args['s']) {
                case 'reload':
                    echo "当前进程".$pid."\n";
                    echo "热重启中\n";
                    if ($pid) {
                        if (swoole_process::kill($pid, 0)) {
                            swoole_process::kill($pid, SIGUSR1);
                        }
                    }
                    echo "重启完成\n";
                    swoole_process::daemon();
                    break;
                default:
                    break;
            }
            exit;
        }
    }
}
