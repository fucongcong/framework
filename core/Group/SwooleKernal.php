<?php

namespace Group;

use Group\App\App;
use swoole_http_server;

class SwooleKernal
{   
    protected $http;

    protected $path;

    protected $loader;

    protected $app;

    public function init($path, $loader)
    {   
        define('SWOOLE_HTTP', true);

        $this->path = $path;
        $this->loader = $loader;

        $host = \Group\Config\Config::get('app::swoole_host') ? : "127.0.0.1";
        $port = \Group\Config\Config::get('app::swoole_port') ? : 9777;
        $setting = \Group\Config\Config::get('app::swoole_setting');

        $this->http = new swoole_http_server($host, $port);
        $this->http->set($setting);

        $this->http->on('Start', [$this, 'onStart']);
        $this->http->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->http->on('Request', [$this, 'onRequest']);

        $this->start();
    }

    public function onStart($serv)
    {
        if (PHP_OS !== 'Darwin') {
            swoole_set_process_name("php http server: master");
        }
    }

    public function onWorkerStart($serv, $workerId)
    {   
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $this->app = new App();
        $this->app->initSwoole($this->path, $this->loader);
        //设置不同进程名字,方便grep管理
        if (PHP_OS !== 'Darwin') {
            swoole_set_process_name("php http server: worker");
        }

        echo "HTTP Worker Start...".PHP_EOL;
    }

    public function onRequest($request, $response)
    {
        $request->get = isset($request->get) ? $request->get : [];
        $request->post = isset($request->post) ? $request->post : [];
        $request->cookie = isset($request->cookie) ? $request->cookie : [];
        $request->files = isset($request->files) ? $request->files : [];
        $request->server = isset($request->server) ? $request->server : [];
        $request->server['REQUEST_URI'] = isset($request->server['request_uri']) ? $request->server['request_uri'] : '';
        preg_match_all("/^(.+\.php)(\/.*)$/", $request->server['REQUEST_URI'], $matches);
        
        $request->server['REQUEST_URI'] = isset($matches[2]) ? $matches[2][0] : '';
        
        if ($request->server['request_uri'] == '/favicon.ico') {
            $response->end();
            return;
        }
        
        $this->fix_gpc_magic($request);
        $this->app->initSwooleRequest($request);

        $data = $this->app->handleSwooleHttp();
        $response->status($data->getStatusCode());
        $response->end($data->getContent());
        return;
    }

    public function start()
    {   
        echo "HTTP Server Start...".PHP_EOL;
        $this->http->start();
    }

    public function fix_gpc_magic($request)
    {
        static $fixed = false;
        if (!$fixed && ini_get('magic_quotes_gpc')) {

            array_walk($request->get, '_fix_gpc_magic');
            array_walk($request->post, '_fix_gpc_magic');
            array_walk($request->cookie, '_fix_gpc_magic');
            array_walk($request->files, '_fix_gpc_magic_files');

        }
        $fixed = true;
    }

    private static function _fix_gpc_magic(&$item)
    {
        if (is_array($item)) {
            array_walk($item, '_fix_gpc_magic');
        }
        else {
            $item = stripslashes($item);
        }
    }

    private static function _fix_gpc_magic_files(&$item, $key)
    {
        if ($key != 'tmp_name') {

            if (is_array($item)) {
              array_walk($item, '_fix_gpc_magic_files');
            }
            else {
              $item = stripslashes($item);
            }

        }
    }
}

