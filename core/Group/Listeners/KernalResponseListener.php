<?php

namespace Group\Listeners;

use Group\Events\HttpEvent;
use Group\Events\KernalEvent;

class KernalResponseListener extends \Listener
{
    public function setMethod()
    {
        return 'onKernalResponse';
    }

    public function onKernalResponse(\Event $event)
    {	
    	$response = $event->getResponse();
        $swooleHttpResponse = $event->getSwooleHttpResponse();
        
        if ($response instanceof \Response 
            || $response instanceof \RedirectResponse 
            || $response instanceof \JsonResponse) {
            $swooleHttpResponse->status($response->getStatusCode());
            $swooleHttpResponse->end($response->getContent());
        }

        \EventDispatcher::dispatch(KernalEvent::HTTPFINISH, new HttpEvent($event->getRequest(), $response));
    }
}
