<?php

namespace Group\Async;

use ServiceProvider;
use Group\Async\Client\Client;

class AsyncServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {
        $this -> app -> singleton('async', function () {

            return new Client;
        });
    }

}
