<?php

require __DIR__.'/vendor/hprose/hprose/src/Hprose.php';
$client = new HproseSwooleClient('tcp://127.0.0.1:9396');

var_dump($client->User_UserService_getUser(1));