<?php

namespace Group\Listeners;

use Group\Events\HttpEvent;
use Group\Events\KernalEvent;
use Group\Session\CsrfSessionService;

class KernalRequestListener extends \Listener
{
    public function setMethod()
    {
        return 'onKernalRequest';
    }

    public function onKernalRequest(\Event $event)
    {	
    	$request = $event->getRequest();

        if (strtoupper($request->getMethod()) == "POST" && \Config::get("session::csrf_check")) {
            
            if ($request->isXmlHttpRequest()) {
                $token = $request->headers->get('X-CSRF-Token');
            } else {
                $token = $request->request->get('csrf_token');
            }

            if (!$token) {
                $response = new \JsonResponse(['name' => 'badCSRFToken'], 403);
                \EventDispatcher::dispatch(KernalEvent::RESPONSE, new HttpEvent(null, $response));
                return;
                //throw new \Exception("缺少csrf_token参数!", 1);
            }
            $csrfProvider = new CsrfSessionService();
            if (!$csrfProvider->isCsrfTokenValid($token)) {
                //throw new \Exception("csrf_token参数验证失败!", 1);
                $response = new \JsonResponse(['name' => 'badCSRFToken'], 403);
                \EventDispatcher::dispatch(KernalEvent::RESPONSE, new HttpEvent(null, $response));
                return;
            }
         
        }
    }
}
