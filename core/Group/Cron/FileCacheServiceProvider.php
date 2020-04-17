<?php

namespace Group\Cron;

use ServiceProvider;
use Group\Cron\LocalFileCacheService;

class FileCacheServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {
        $this->app->singleton('cronFileCache', function () {
            return new LocalFileCacheService();
        });
    }
}
