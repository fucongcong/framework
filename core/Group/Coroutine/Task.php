<?php

namespace Group\Coroutine;

use Generator;
use SplStack;

class Task
{
    protected $taskId;

    protected $coroutine;

    public function __construct($taskId, Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $this->stackedCoroutine($coroutine);
        //$this->coroutine = $coroutine;
    }
 
    public function getTaskId()
    {
        return $this->taskId;
    }
 
    public function run()
    {   
        $retval = $this->coroutine->current();
        $this->coroutine->next();
        return $retval;
    }
 
    public function isFinished()
    {
        return !$this->coroutine->valid();
    }

    private function stackedCoroutine(Generator $coroutine)
    {
        $stack = new SplStack;
 
        for (;;) {
            $value = $coroutine->current();

            if ($value instanceof Generator) {
                $stack->push($coroutine);
                $coroutine = $value;
                continue;
            }

            $isReturnValue = $value instanceof CoroutineReturnValue;
            if (!$coroutine->valid() || $isReturnValue) {
                if ($stack->isEmpty()) {
                    return;
                }
     
                $coroutine = $stack->pop();
                $coroutine->send($isReturnValue ? $value->getValue() : NULL);
                continue;
            }

            $coroutine->send(yield $coroutine->key() => $value);
        }
    }
}
