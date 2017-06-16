<?php

namespace Group\Events;

final class HttpEvent extends \Event
{	
	protected $request;

    protected $response;

    protected $swooleHttpResponse;

    public function __construct(\Request $request = null, $response = null, $swooleHttpResponse)
    {	
    	$this->request = $request;
        $this->response = $response;
        $this->swooleHttpResponse = $swooleHttpResponse;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getSwooleHttpResponse()
    {
        return $this->swooleHttpResponse;
    }
}
