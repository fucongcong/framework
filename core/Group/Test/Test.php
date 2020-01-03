<?php

namespace Group\Test;

use PHPUnit\Framework\TestCase;

abstract class Test extends TestCase
{
    public function __construct()
    {
        if (method_exists($this, '__initialize'))
            $this->__initialize();

        parent::__construct();
    }
}
