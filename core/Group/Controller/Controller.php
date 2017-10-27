<?php

namespace Group\Controller;

use Group\Contracts\Controller\Controller as ControllerContract;
use Group\Exceptions\NotFoundException;
use Config;
use JsonResponse;
use Firebase\JWT\JWT;
use Cookie;

class Controller implements ControllerContract
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
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
        if ($this->getContainer()->isDebug()) {
            if ($this->app->singleton('debugbar')->hasCollector('view')) {
                $array['模板地址'] = $tpl;
                $this->app->singleton('debugbar')->getCollector('view')->setData($array);
            } else {
                $array['模板地址'] = $tpl;
                $this->app->singleton('debugbar')->addCollector(new \Group\Debug\Collector\VarCollector($array));
            }
        }
        
        return $this->twigInit()->render($tpl, $array);
    }

    public function twigInit()
    {
        return $this->app->singleton('twig');
    }

    /**
     * 实例化一个服务类
     *
     * @param  string  $serviceName
     * @return class
     */
    public function createService($serviceName)
    {
        return $this->app->singleton('service')->createService($serviceName);
    }

    /**
     * route的实例
     *
     * @return Group\Routing\Route
     */
    public function route()
    {
        return $this->app->singleton('route');
    }

    /**
     * 获取容器
     *
     * @return Group\Container\Container
     */
    public function getContainer()
    {
        return $this->app->singleton('container');
    }

    public function setFlashMessage($type, $message)
    {
        \Session::getFlashBag()->set($type, $message);
    }

    public function getFlashMessage()
    {
        return \Session::getFlashBag()->all();
    }

    public function redirect($url, $status = 302)
    {
        header("location: {$url}");
    }

    public function __call($method, $parameters)
    {
        throw new NotFoundException("Method [$method] does not exist.");
    }

    public function errorJsonResponse($msg = "", $data = [], $code = 400)
    {
        return new JsonResponse([
                'msg' => $msg,
                'data' => $data,
                'code' => $code
            ]
        );
    }

    public function successJsonResponse($msg = "", $data = [], $code = 200)
    {
        return new JsonResponse([
                'msg' => $msg,
                'data' => $data,
                'code' => $code
            ]
        );
    }

    public function isLogin()
    {   
        return $this->getUserId();
    }

    public function getUser()
    {
        return $this->getContainer()->getContext('user', []);
    }

    public function getUserId()
    {
        return $this->getContainer()->getContext('userId', 0);
    }

    public function setJwt($request, $data, $response)
    {   
        $httpHost = Config::get('jwt::domain');
        $exprieTime = time() + Config::get('jwt::exprieTime');
        $token = array(
            'data' => $data,
            "iss" => $httpHost,
            "aud" => $httpHost,
            "iat" => time(),
            "exp" => $exprieTime
        );

        $jwt = JWT::encode($token, Config::get('jwt::privateKey'), 'RS256');
        $response->headers->setCookie(new Cookie('JWT', $jwt, $exprieTime, '/', $httpHost));

        return $response;
    }

    public function pasreJwt($request)
    {   
        $jwt = $request->cookies->get('JWT');
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            return false;;
        }

        $data = JWT::decode($jwt, Config::get('jwt::publicKey'), array('RS256'));
        $data =  (array) $data;

        return $data['data'];
    }

    public function clearJwt($request, $response)
    {   
        $httpHost = Config::get('jwt::domain');
        $response->headers->clearCookie('JWT', '/', $httpHost);

        return $response;
    }

    public function messageResponse($type, $message, $goto = null, $duration = 0)
    {
        if (!in_array($type, array('info', 'warning', 'danger'))) {
            throw new \RuntimeException('type不正确');
        }

        return $this->render('Web/Views/message.html.twig', array(
            'type' => $type,
            'message' => $message,
            'duration' => $duration,
            'goto' => $goto,
        ));
    }
}
