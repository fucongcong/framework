<?php

namespace Group\Queue;

abstract class QueueJob
{
    abstract function handle();
}