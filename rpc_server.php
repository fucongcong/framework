<?php

define('__ROOT__', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
require_once __ROOT__.'core/Group/Plugin/Rpc/Hprose.php';
require_once __ROOT__.'core/Group/RpcKernal.php';

new \Group\Plugin\Rpc\Hprose();
$kernal = new RpcKernal('tcp');
$kernal->init();
