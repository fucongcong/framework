<?php
use Hprose\Future;
require_once "vendor/autoload.php";
$client = new \Hprose\Swoole\Client("tcp://127.0.0.1:9396");

// $client->Group_GroupService('getGroup', function($result) {
//     var_dump($result);
// });




$var_dump = Future\wrap('var_dump');
$sum = $client->Group_GroupService->getGroup;
$var_dump($sum(1));