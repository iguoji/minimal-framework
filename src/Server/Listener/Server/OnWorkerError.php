<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 当 Worker/Task 进程发生异常后会在 Manager 进程内回调此函数。
 */
class OnWorkerError implements Listener
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
            'Server:OnWorkerError',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Server
        $server = $arguments[0];
        // int 异常 worker 进程的 id
        $worker_id = $arguments[1];
        // int 异常 worker 进程的 pid
        $worker_pid = $arguments[2];
        // int 退出的状态码，范围是 0～255
        $exit_code = $arguments[3];
        // int 进程退出的信号
        $signal = $arguments[4];

        // 错误日志
        $this->app->log->error(sprintf('worker_id: %s, exit_code: %s, signal: %s', $worker_id, $exit_code, $signal), error_get_last() ?? []);

        // 返回结果
        return true;
    }
}