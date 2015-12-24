<?php

namespace Group\Listeners;

class KernalResponseListener extends \Listener
{
    public function setMethod()
    {
        return 'onKernalResponse';
    }

    public function onKernalResponse(\Event $event)
    {	
    	$response = $event -> getResponse();
        if($response instanceof \Response)
         	$response -> send();
    }
}
