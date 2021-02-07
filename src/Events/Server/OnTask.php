<?php
declare(strict_types=1);

namespace Minimal\Events\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 在 task 进程内被调用。worker 进程可以使用 task 函数向 task_worker 进程投递新的任务。
 * 当前的 Task 进程在调用 onTask 回调函数时会将进程状态切换为忙碌，这时将不再接收新的 Task，当 onTask 函数返回时会将进程状态切换为空闲然后继续接收新的 Task。
 */
#[Listener]
class OnTask implements ListenerInterface
{
    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Server:OnTask'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 服务对象
        $server = $arguments[0];

        // 任务对象
        $task = $arguments[1];
        /**
         *
         * Swoole\Server\Task Object
         *   (
         *       [data] => OnGameNext
         *       [dispatch_time] => 1612700724.3543
         *       [id] => 4
         *       [worker_id] => 0
         *       [flags] => 132
         *   )
         */

        // 继续执行
        return true;
    }
}