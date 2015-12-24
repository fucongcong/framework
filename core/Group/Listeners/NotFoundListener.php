<?php

namespace Group\Listeners;

use Group\Events\HttpEvent;
use Group\Events\KernalEvent;

class ExceptionListener extends \Listener
{
    public function setMethod()
    {
        return 'onException';
    }

    public function onException(\Event $event)
    {   
    	$controller = new \Controller(\App::getInstance());
        $page = $controller -> twigInit() -> render(\Config::get('view::notfound_page'));
        $response = new \Response($page, 404);
        \EventDispatcher::dispatch(KernalEvent::RESPONSE, new HttpEvent($event -> getRequest(), $response));
    }
}
