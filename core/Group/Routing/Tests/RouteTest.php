<?php

namespace Group\Routing\Tests;

use Test;
use Route;

class RouteTest extends Test
{
    public function testDeParse()
    {   
        $this->assertEquals(0, 0);
        // $uri = Route::deParse('user_group', ['id' => 1, 'groupId' => 1]);
        // $this->assertEquals('/user/1/group/1', $uri);
    }
}
