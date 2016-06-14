<?php

namespace Group\Controller;

use Group\Twig\WebExtension;
use Group\Contracts\Controller\Controller as ControllerContract;
use Group\Exceptions\NotFoundException;

class Controller implements ControllerContract
{
	protected $app;

	public function __construct($app)
	{
		$this -> app = $app;
	}

	/**
	 * 渲染模板的方法
	 *
	 * @param  string  $tpl
	 * @param  array   $array
	 * @return response
	 */
	public function render($tpl, $array = array())
	{	
		if ($this -> getContainer() -> isDebug()) {
			if ($this -> app -> singleton('debugbar') -> hasCollector('view')) {
				$array['模板地址'] = $tpl;
				$this -> app -> singleton('debugbar') -> getCollector('view') -> setData($array);
			} else {
				$array['模板地址'] = $tpl;
				$this -> app -> singleton('debugbar') -> addCollector(new \Group\Debug\Collector\VarCollector($array));
			}
		}
		
		return $this -> twigInit() -> render($tpl, $array);
	}

	public function twigInit()
	{
		return $this -> app -> singleton('twig', function() {
			$loader = new \Twig_Loader_Filesystem(\Config::get('view::path'));

			if (\Config::get('view::cache')) {
				$env = array(
			    	'cache' => \Config::get('view::cache_dir')
				);
			}

			$twig = new \Twig_Environment($loader, isset($env) ? $env : array());

			//开启twig的cache后默认不显示twig数据收集。这里有一点小bug
			if ($this -> getContainer() -> isDebug() && empty($env)) {
				$twig = new \DebugBar\Bridge\Twig\TraceableTwigEnvironment($twig);
				$this -> app -> singleton('debugbar') -> addCollector(new \DebugBar\Bridge\Twig\TwigCollector($twig));
			}
			
			$twig -> addExtension(new WebExtension());
			$extensions = \Config::get('view::extensions');
			foreach ($extensions as $extension) {
				if($this -> getContainer() -> buildMoudle($extension) -> isSubclassOf('Twig_Extension'))
					$twig -> addExtension(new $extension);
			}

			return $twig;
		});
	}

	/**
	 * 实例化一个服务类
	 *
	 * @param  string  $serviceName
	 * @return class
	 */
	public function createService($serviceName)
	{
		return $this -> app -> singleton('service') -> createService($serviceName);
	}

	/**
	 * route的实例
	 *
	 * @return Group\Routing\Route
	 */
	public function route()
	{
		return $this -> app -> singleton('route');
	}

	/**
	 * 获取容器
	 *
	 * @return Group\Container\Container
	 */
	public function getContainer()
	{
		return $this -> app -> singleton('container');
	}

	public function setFlashMessage($type, $message)
	{
		\Session::getFlashBag() -> set($type, $message);
	}

	public function getFlashMessage()
	{
		return \Session::getFlashBag() -> all();
	}

    public function redirect($url, $status = 302)
    {
        header("location: {$url}");
    }

	public function __call($method, $parameters)
	{
		throw new NotFoundException("Method [$method] does not exist.");
	}
}
