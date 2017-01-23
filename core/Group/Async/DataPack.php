<?php

namespace Group\Async;

class DataPack 
{
    public static function pack($cmd = '', $data = [], $info = [])
    {
        return json_encode(['cmd' => $cmd, 'data' => $data, 'info' => $info]);
    }

    public static function unpack($data = [])
    {
        $data = json_decode($data, true);
        return [$data['cmd'], $data['data'], isset($data['info']) ? $data['info'] : []];
    }
}