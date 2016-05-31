<?php

namespace Group\Rpc;

use ServiceProvider;
use Group\Rpc\RpcService;

class RpcServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {
        $this -> app -> singleton('rpc', function () {
            return new RpcService();
        });
    }

}
