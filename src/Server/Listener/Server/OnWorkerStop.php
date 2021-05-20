<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 此事件在 Worker 进程终止时发生。在此函数中可以回收 Worker 进程申请的各类资源。
 */
class OnWorkerStop implements Listener
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
            'Server:OnWorkerStop',
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

        // 返回结果
        return true;
    }
}