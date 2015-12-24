<?php

namespace Group\Listeners;

class HttpFinishListener extends \Listener
{
    public function setMethod()
    {
        return 'onFinish';
    }

    public function onFinish(\Event $event)
    {     
    	$event -> getResponse() -> closeOutputBuffers(0, true);
    }
}
