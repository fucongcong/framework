<?php

namespace Group\Coroutine;

class Task
{
    protected $taskId;

    protected $coStack;

    protected $coroutine;

    protected $scheduler;

    protected $exception = null;

    protected $sendValue = null;

    /**
     * [__construct 构造函数，生成器+taskId, taskId由 scheduler管理]
     * @param Generator $coroutine [description]
     * @param [type]    $task      [description]
     */
    public function __construct($taskId, \Generator $coroutine, Scheduler $scheduler)
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
        $this->coStack = new \SplStack();
        $this->scheduler = $scheduler;
    }

    /**
     * [getTaskId 获取task id]
     * @return [type] [description]
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * [setException  设置异常处理]
     * @param [type] $exception [description]
     */
    public function setException($exception)
    {
        $this->exception = $exception;
    }

    /**
     * [run 协程调度]
     * @param  Generator $coroutine [description]
     * @return [type]         [description]
     */
    public function run()
    {
        while (true) {
            try {
                if ($this->exception) {

                    $this->coroutine->throw($this->exception);
                    $this->exception = null;
                    continue;
                }

                $value = $this->coroutine->current();
                \Log::info($this->taskId.__METHOD__ . " value === " . print_r($value, true), [__CLASS__]);

                //如果是coroutine，入栈
                if ($value instanceof \Generator) {
                    $this->coStack->push($this->coroutine);
                    $this->coroutine = $value;
                    continue;
                }

                /*
                    if value is null and stack is not empty pop and send continue
                 */
                if (is_null($value) && !$this->coStack->isEmpty()) {

                    //\Log::info($this->taskId.__METHOD__ . " values is null stack pop and send", [__CLASS__]);
                    $this->coroutine = $this->coStack->pop();
                    $this->coroutine->send($this->sendValue);
                    continue;
                }

                //如果是系统调用
                if ($value instanceof SysCall || is_subclass_of($value, SysCall::class)) {
                    call_user_func($value, $this);
                    return;
                }

                //如果是异步IO
                if ($value instanceof \Group\Async\Client\Base || is_subclass_of($value, \Group\Async\Client\Base::class)) {
                    $this->coStack->push($this->coroutine);
                    $value->call(array($this, 'callback'));
                    return;
                }

                //
                // if ($value instanceof Group\this->Coroutine\RetVal) {
                //     return false;
                // }

                /*
                    出栈，回射数据
                 */
                if ($this->coStack->isEmpty()) {
                    return;
                }

                $this->coroutine = $this->coStack->pop();
                $this->coroutine->send($value);
                \Log::info($this->taskId.__METHOD__ . " values  pop and send", [__CLASS__]);

            } catch (\Exception $e) {
                if ($this->coStack->isEmpty()) {
                    /*
                        throw the exception 
                    */
                    \Log::error($this->taskId.__METHOD__ . " exception ===" . $e->getMessage(), [__CLASS__]);
                    return;
                }
            }
        }
    }

    public function callback($response, $error, $calltime)
    {
        $this->coroutine = $this->coStack->pop();
        $callbackData = array('response' => $response, 'error' => $error, 'calltime' => $calltime);
        $this->send($callbackData);
        $this->run();
    }

    public function send($sendValue) {
        $this->sendValue = $sendValue;
        return $this->coroutine->send($sendValue);
    }

    /**
     * [isFinished 判断该task是否完成]
     * @return boolean [description]
     */
    public function isFinished()
    {
        return !$this->coroutine->valid();
    }

    public function getCoroutine()
    {
        return $this->coroutine;
    }
}
