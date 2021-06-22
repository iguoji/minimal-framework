<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 在 task 进程内被调用。
 * worker 进程可以使用 task 函数向 task_worker 进程投递新的任务。
 * 当前的 Task 进程在调用 onTask 回调函数时会将进程状态切换为忙碌，这时将不再接收新的 Task，当 onTask 函数返回时会将进程状态切换为空闲然后继续接收新的 Task。
 */
class OnTask implements Listener
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 事件列表
     */
    public function events() : array
    {
        return [
            'Server:OnTask',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Server
        $server = $arguments[0];

        if ($arguments[1] instanceof \Swoole\Server\Task) {
            /**
             * Swoole\Server\Task Object
             *   (
             *       [data] => OnGameNext
             *       [dispatch_time] => 1612700724.3543
             *       [id] => 4
             *       [worker_id] => 0
             *       [flags] => 132
             *   )
             */
            $task = $arguments[1];
            $data = $task->data;

            // 调用类的某个方法
            if (is_array($data) && count($data) >= 2 && is_string($data[0]) && class_exists($data[0]) && is_string($data[1])) {
                try {
                    // 开启事务
                    $this->app->database->beginTransaction();

                    // 任务处理
                    $class = array_shift($data);
                    $method = array_shift($data);
                    $ins = $this->app->get($class);
                    $ins->$method(...$data);

                    // 提交事务
                    $this->app->database->commit();
                } catch (\Throwable $th) {
                    // 事务回滚
                    $this->app->database->commit();
                    // 记录错误
                    $this->app->log->error($th->getMessage(), [$data, $th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [], $th->getTrace() ]);
                }

                // 标记完成，不然无法触发回调
                $task->finish(true);
            }
        } else {
            // int 执行任务的 task 进程 id
            $task_id = $arguments[1];
            // int 投递任务的 worker 进程 id
            $src_worker_id = $arguments[2];
            // mixed 任务的数据内容
            $data = $arguments[3];
        }

        // 返回结果
        return true;
    }
}