<?php

namespace Group\Services;

use ServiceProvider;
use Service;

class ServiceRegister extends ServiceProvider
{
	/**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {
		$this -> app -> singleton('service', function () {
            return new Service();
        });
    }
}