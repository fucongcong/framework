<?php

namespace Group\App;

use Group\App\Bean;
use Group\Handlers\AliasLoaderHandler;
use Group\Config\Config;
use Group\Routing\Router;
use Group\Handlers\ExceptionsHandler;
use Group\Events\HttpEvent;
use Group\Events\KernalEvent;
use Group\Cache\BootstrapClass;
use Group\Container\Container;

class App
{
    /**
     * array instances
     *
     */
    protected $instances;

    private static $instance;

    public $container;

    public $router;

    /**
     * array aliases
     *
     */
    protected $aliases = [
        'App'               => 'Group\App\App',
        'Async'             => 'Group\Async\AsyncServcie',
        'Cache'             => 'Group\Cache\Cache',
        'Config'            => 'Group\Config\Config',
        'Container'         => 'Group\Container\Container',
        'Controller'        => 'Group\Controller\Controller',
        'Cookie'            => 'Group\Http\Cookie',
        'Dao'               => 'Group\Dao\Dao',
        'Event'             => 'Group\Events\Event',
        'EventDispatcher'   => 'Group\EventDispatcher\EventDispatcher',
        'Filesystem'        => 'Group\Common\Filesystem',
        'FileCache'         => 'Group\Cache\FileCache',
        'StaticCache'       => 'Group\Cache\StaticCache',
        'Route'             => 'Group\Routing\Route',
        'Request'           => 'Group\Http\Request',
        'Response'          => 'Group\Http\Response',
        'Rpc'               => 'Group\Rpc\Rpc',
        'JsonResponse'      => 'Group\Http\JsonResponse',
        'RedirectResponse'  => 'Group\Http\RedirectResponse',
        'Service'           => 'Group\Services\Service',
        'ServiceProvider'   => 'Group\Services\ServiceProvider',
        'Session'           => 'Group\Session\Session',
        'Test'              => 'Group\Test\Test',
        'Log'               => 'Group\Log\Log',
        'Listener'          => 'Group\Listeners\Listener',
        'Queue'             => 'Group\Queue\Queue',
    ];

    /**
     * array singles
     *
     */
    protected $singles = [
        'dao' => 'Group\Dao\Dao',
    ];

    protected $serviceProviders = [
        'Group\Services\ServiceRegister',
        'Group\Routing\RouteServiceProvider',
        'Group\EventDispatcher\EventDispatcherServiceProvider',
        'Group\Cache\CacheServiceProvider',
        'Group\Cache\FileCacheServiceProvider',
        'Group\Cache\StaticCacheServiceProvider',
        'Group\Async\AsyncServiceProvider',
    ];

    protected $aops;

    public function __construct()
    { 
        $this->aliasLoader();

        $this->doSingle();

        $this->doSingleInstance();
    }

    /**
     * init appliaction
     *
     */
    public function init($path)
    {   
        $this->initSelf();

        $request = \Request::createFromGlobals();

        $this->registerServices();
       
        \EventDispatcher::dispatch(KernalEvent::INIT);

        $this->container = $this->singleton('container');
        $this->container->setAppPath($path);
        
        if ($this->container->isDebug()) {
            $debugbar = new \Group\Debug\DebugBar();
            self::getInstance()->instances['debugbar'] = $debugbar;
        }

        $handler = new ExceptionsHandler();
        $handler->bootstrap($this);

        $this->container->setRequest($request);

        $this->router = new Router($this->container);
        self::getInstance()->router = $this->router;
        $this->router->match();
    }

    /**
     * init appliaction
     *
     */
    public function initSwoole($path)
    {
        $this->initSelf();

        $this->registerServices();
       
        \EventDispatcher::dispatch(KernalEvent::INIT);

        $this->container = $this->singleton('container');
        $this->container->setAppPath($path);
        
        if ($this->container->isDebug()) {
            $debugbar = new \Group\Debug\DebugBar();
            self::getInstance()->instances['debugbar'] = $debugbar;
        }

        $handler = new ExceptionsHandler();
        $handler->bootstrap($this);
    }

    public function initSwooleRequest($request)
    {
        $request = new \Request($request->get, $request->post, [], $request->cookie
            , $request->files, $request->server);

        $this->container->setRequest($request);

        $this->router = new Router($this->container);
        self::getInstance()->router = $this->router;
        $this->router->match();
    }

    /**
     * do the class alias
     *
     */
    public function aliasLoader()
    {
        $aliases = Config::get('app::aliases');
        $this->aliases = array_merge($aliases, $this->aliases);
        AliasLoaderHandler::getInstance($this->aliases)->register();
    }

    /**
     *  向App存储一个单例对象
     *
     * @param  name，callable
     * @return object
     */
    public function singleton($name, $callable = null)
    {
        if (!isset($this->instances[$name]) && $callable) {
            $obj = call_user_func($callable);
            if (!is_object($obj)) {
                $this->instances[$name] = null;
            } else {
                if (isset($this->aops[$name])) {
                    $this->instances[$name] = new Bean($name, $obj);
                } else {
                    $this->instances[$name] = $obj;
                }
            }
        }

        return $this->instances[$name];
    }

    /**
     *  在网站初始化时就已经生成的单例对象
     *
     */
    public function doSingle()
    {   
        $singles = Config::get('app::singles');
        $this->singles = array_merge($singles, $this->singles);
        foreach ($this->singles as $alias => $class) {
            $this->instances[$alias] = new $class();
        }

        $this->aops = Config::get("app::aop");
    }

    public function doSingleInstance()
    {
        $this->instances['container'] = Container::getInstance();
    }

    /**
     *  注册服务
     *
     */
    public function registerServices()
    {   
        $this->setServiceProviders();

        $this->register();
    }

    /**
     * return single class
     *
     * @return core\App\App App
     */
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)){
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * 处理响应请求
     *
     */
    public function handleHttp()
    {
        $response = $this->container->getResponse();
        $request = $this->container->getRequest();
        \EventDispatcher::dispatch(KernalEvent::RESPONSE, new HttpEvent($request, $response));
    }

    public function handleSwooleHttp()
    {
        $response = $this->container->getResponse();
        $request = $this->container->getRequest();
        return $response;
        //\EventDispatcher::dispatch(KernalEvent::RESPONSE, new HttpEvent($request,$response));
    }

    public function initSelf()
    {
        self::$instance = $this;
    }

    public function rmInstances($name)
    {
        if(isset($this->instances[$name]))
            unset($this->instances[$name]);
    }

    /**
     * set ServiceProviders
     *
     */
    public function setServiceProviders()
    {
        $providers = Config::get('app::serviceProviders');
        $this->serviceProviders = array_merge($providers, $this->serviceProviders);
    }

    /**
     * ingore ServiceProviders
     *
     */
    public function ingoreServiceProviders($provider)
    {   
        foreach ($this->serviceProviders as $key => $val) {
            if ($val == $provider) unset($this->serviceProviders[$key]);
        } 
    }

    /**
     *  注册服务
     *
     */
    public function register()
    {   
        foreach ($this->serviceProviders as $provider) {
            $provider = new $provider(self::$instance);
            $provider->register();
        }
    }

    /**
     * 处理一个抽象对象
     * @param  string  $abstract
     * @return mixed
     */
    public function make($abstract)
    {
        //如果是已经注册的单例对象
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $reflector = app('container')->buildMoudle($abstract);
        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable!");
        }

        //有单例
        if ($reflector->hasMethod('getInstance')) {
            $object = $abstract::getInstance();
            $this->instances[$abstract] = $object;
            return $object;
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $abstract;
        }

        return null;
    }
}
