<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 此事件在 Worker 进程 / Task 进程 启动时发生，这里创建的对象可以在进程生命周期内使用。
 */
class OnWorkerStart implements Listener
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
            'Server:OnWorkerStart',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Server
        $server = $arguments[0];
        // int Worker 进程 id（非进程的 PID）
        $workerId = $arguments[1];

        // process
        cli_set_process_title(sprintf('php swoole %s worker #%s', $server->taskworker ? 'task' : 'normal', $workerId));

        // task
        if (1 === $workerId) {
            $classes = $this->app->config->get('task', []);
            foreach ($classes as $class) {
                $task = $this->app->make($class);

                if ($task->active()) {
                    \Swoole\Timer::tick($task->interval(), [$task, 'handle']);
                }
            }
        }

        // 返回结果
        return true;
    }
}