<?php

define('__ROOT__', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
require_once "vendor/autoload.php";
require_once __ROOT__.'core/Group/RpcKernal.php';

$kernal = new RpcKernal('tcp');
$kernal->init();
