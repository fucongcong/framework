<?php

namespace Group\Cron;

use Group\Services\ServiceMap;

class FileCache extends ServiceMap
{
    public static function getMap()
    {
        return 'cronFileCache';
    }
}
