<?php

namespace Group\Coroutine;

class Task
{
    protected $callbackData;

    protected $taskId;

    protected $coStack;

    protected $coroutine;

    protected $exception = null;

    /**
     * [__construct 构造函数，生成器+taskId, taskId由 scheduler管理]
     * @param Generator $coroutine [description]
     * @param [type]    $task      [description]
     */
    public function __construct($taskId, \Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
        $this->coStack = new \SplStack();
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
     * @param  Generator $gen [description]
     * @return [type]         [description]
     */
    public function run(\Generator $gen)
    {
        while (true) {

            try {

                /*
                    异常处理
                 */
                if ($this->exception) {

                    $gen->throw($this->exception);
                    $this->exception = null;
                    continue;
                }

                $value = $gen->current();
                //\Log::info($this->taskId.__METHOD__ . " value === " . print_r($value, true), [__CLASS__]);

                /*
                    入栈
                 */
                if ($value instanceof \Generator) {

                    $this->coStack->push($gen);
                    //\Log::info($this->taskId.__METHOD__ . " coStack push ", [__CLASS__]);
                    $gen = $value;
                    continue;
                }

                /*
                    if value is null and stack is not empty pop and send continue
                 */
                // if (is_null($value) && !$this->coStack->isEmpty()) {

                //   //  //\Log::info($this->taskId.__METHOD__ . " values is null stack pop and send", [__CLASS__]);
                //     $gen = $this->coStack->pop();
                //     $gen->send($this->callbackData);
                //     continue;
                // }

                //
                if ($value instanceof Group\Coroutine\RetVal) {
                    return false;
                }

                /*
                    出栈，回射数据
                 */
                if ($this->coStack->isEmpty()) {
                    return;
                }

                $gen = $this->coStack->pop();
                $gen->send($value);
                  //\Log::info($this->taskId.__METHOD__ . " values  pop and send", [__CLASS__]);

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

    /**
     * [callback description]
     * @param  [type]   $r        [description]
     * @param  [type]   $key      [description]
     * @param  [type]   $calltime [description]
     * @param  [type]   $res      [description]
     * @return function           [description]
     */
    public function callback($r, $key, $calltime, $res)
    {
        /*
            继续run的函数实现 ，栈结构得到保存 
         */

        $gen = $this->coStack->pop();
        $this->callbackData = array('r' => $r, 'calltime' => $calltime, 'data' => $res);

      //  //\Log::info(__METHOD__ . " coStack pop and data == " . print_r($this->callbackData, true), [__CLASS__]);
        $value = $gen->send($this->callbackData);

        $this->run($gen);

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
