<?php

namespace Group;

use Group\App\App;
use swoole_http_server;

class SwooleKernal
{
	public function init($path, $loader)
	{
		$http = new swoole_http_server("127.0.0.1", 9777);
		$http -> set(array(
			'reactor_num' => 4,
		    'worker_num' => 25,    //worker process num
		    'backlog' => 128,   //listen backlog
		    'max_request' => 2000,
	        'heartbeat_idle_time' => 30,
    		'heartbeat_check_interval' => 10,
		    'dispatch_mode' => 3, 
		));
		$http -> on('request', function ($request, $response) use ($path, $loader) {
			$request -> get = isset($request -> get) ? $request -> get : [];
			$request -> post = isset($request -> post) ? $request -> post : [];
			$request -> cookie = isset($request -> cookie) ? $request -> cookie : [];
			$request -> files = isset($request -> files) ? $request -> files : [];
			$request -> server = isset($request -> server) ? $request -> server : [];
			$request -> server['REQUEST_URI'] = isset($request -> server['request_uri']) ? $request -> server['request_uri'] : '';
			preg_match_all("/^(.+\.php)(\/.*)$/", $request -> server['REQUEST_URI'], $matches);
	
			$request -> server['REQUEST_URI'] = isset($matches[2]) ? $matches[2][0] : '';
			
			if ($request->server['request_uri'] == '/favicon.ico') {
				$response->end();
				return;
			}
			
			$this -> fix_gpc_magic($request);
			$app = new App();		
		 	$app -> initSwoole($path, $loader, $request);

		 	$data = $app -> handleHttp();
		 	$response -> status($data -> getStatusCode());
		    $response -> end($data -> getContent());
		    return;
		});
		$http -> start();
	}

	public function fix_gpc_magic($request)
	{
		static $fixed = false;
		if (!$fixed && ini_get('magic_quotes_gpc')) {

			array_walk($request -> get, '_fix_gpc_magic');
			array_walk($request -> post, '_fix_gpc_magic');
			array_walk($request -> cookie, '_fix_gpc_magic');
			array_walk($request -> files, '_fix_gpc_magic_files');

		}
		$fixed = true;
	}

	private static function _fix_gpc_magic(&$item)
	{
		if (is_array($item)) {
			array_walk($item, '_fix_gpc_magic');
		}
		else {
			$item = stripslashes($item);
		}
	}

	private static function _fix_gpc_magic_files(&$item, $key)
	{
		if ($key != 'tmp_name') {

			if (is_array($item)) {
			  array_walk($item, '_fix_gpc_magic_files');
			}
			else {
			  $item = stripslashes($item);
			}

		}
	}
}
