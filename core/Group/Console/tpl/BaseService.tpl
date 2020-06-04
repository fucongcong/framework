<?php

namespace src\Services\{{group}}\Rely;

use Service;

abstract class {{name}}BaseService extends Service
{
    public function get{{name}}Dao()
    {
        return $this->createDao("{{name}}:{{name}}");
    }
}