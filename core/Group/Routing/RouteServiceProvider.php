<?php

namespace Group\Routing;

use ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {
        $this->app->singleton('route', function () {
            return app('Group\Routing\RouteService');
        });
    }

}
