<?php

namespace Group\Console\Command;

use Group\Console\Command as Command;
use Filesystem;

class GenerateControllerCommand extends Command
{
    public function init()
    {
        $input = $this -> getArgv();

        if (!isset($input[0])) {
            throw new \RuntimeException("名称不能为空！");
        }

        $names = explode(":", $input[0]);
        if (count($names) == 2) {
            $group = ucfirst($names[0]);
            $name = $names[1];
        } else {
            $group = 'Web';
            $name = $input[0];
        }
        
        if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
            throw new \RuntimeException("名称只能为英文！");
        }

        $controllerName = ucfirst($name);
        $this -> outPut('开始初始化'.$controllerName.'Controller...');

        $dir = __ROOT__."src/".$group;

        $this -> outPut('正在生成目录...');
        if (is_dir($dir."/Controller/".$controllerName)) {

            $this -> outPut('目录已存在...初始化失败');
            die;
        }

        $filesystem = new Filesystem();
        $filesystem -> mkdir($dir."/Controller/".$controllerName);
        $filesystem -> mkdir($dir."/Views/".$controllerName);

        $this -> outPut('开始创建模板...');
        $data = $this -> getFile("Controller.tpl", $controllerName, $group);
        file_put_contents ($dir."/Controller/".$controllerName."/".$controllerName."Controller.php", $data);

        $data = $this -> getFile("view.tpl", $controllerName, $group);
        file_put_contents ($dir."/Views/".$controllerName."/"."index.html.twig", $data);

        $this -> outPut('初始化'.$controllerName.'Controller完成');
    }

    private function getFile($tpl, $controllerName, $group)
    {
        $data = file_get_contents(__DIR__."/../tpl/{$tpl}");

        return $this -> getData($data, $controllerName, $group);
    }

    private function getData($data, $controllerName, $group)
    {   
        $data = str_replace("{{group}}", $group, $data);
        return str_replace("{{name}}", $controllerName, $data);
    }
}
