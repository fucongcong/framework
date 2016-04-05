<?php

namespace Group\Async;

class DataPack 
{
	public static function pack($cmd = '', $data = [])
	{
		return json_encode(['cmd' => $cmd, 'data' => $data]);
	}

	public static function unpack($data = [])
	{
		$data = json_decode($data, true);
		return [$data['cmd'], $data['data']];
	}
}