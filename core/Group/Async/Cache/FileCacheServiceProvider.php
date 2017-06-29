<?php

namespace Group\Async\Cache;

use ServiceProvider;
use Group\Async\Cache\LocalFileCacheService;

class FileCacheServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {
        $this->app->singleton('localFileCache', function () {
            return new LocalFileCacheService();
        });
    }

    public function getName()
    {
        return 'localFileCache';
    }
}
