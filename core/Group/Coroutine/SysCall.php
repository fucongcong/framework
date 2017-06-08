<?php

namespace Group\Coroutine;

class SysCall
{
    public static function end($words)
    {
        return new Retval($words);
    }
}