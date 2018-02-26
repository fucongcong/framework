<?php

namespace Group\App;

use Config;
use Exception;

class Bean
{   
    protected $name;

    protected $bean;

    public function __construct($name, $bean)
    {   
        $this->name = $name;
        $this->bean = $bean;
    }

    public function getBean()
    {
        return $this->bean;
    }
    
    public function __call($method, $parameters)
    {   
        $this->execution('before', $method, $parameters);

        $res = null;
        try {
            $res = call_user_func_array([$this->bean, $method], $parameters);
        } catch (Exception $e) {
            $parameters[] = $e;
            $this->execution('throw', $method, $parameters);
            return $res;
        }
        
        $parameters[] = $res;
        $this->execution('after', $method, $parameters);

        return $res;
    }

    private function execution($addr, $method, $parameters)
    {   
        $aopConfig = Config::get("app::aop");
        if (isset($aopConfig[$this->name][$addr][$method])) {
            if (is_string($aopConfig[$this->name][$addr][$method])) {
                $this->callAspect($aopConfig[$this->name][$addr][$method], $parameters);
            } else {
                foreach ($aopConfig[$this->name][$addr][$method] as $one) {
                    $this->callAspect($one, $parameters);
                }
            }
        }
    }

    private function callAspect($map, $parameters)
    {
        list($class, $action) = explode("::", $map);
        $aspect = app()->singleton($class, function() use ($class) {
            return new $class;
        });

        call_user_func_array([$aspect, $action], $parameters);
    }
}
