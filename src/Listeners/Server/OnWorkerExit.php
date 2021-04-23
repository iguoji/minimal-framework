<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 仅在开启 reload_async 特性后有效。
 */
class OnWorkerExit implements Listener
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
            'Server:OnWorkerExit',
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

        // 清除定时器
        \Swoole\Timer::clearAll();

        // 返回结果
        return true;
    }
}