<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 接收到 UDP 数据包时回调此函数，发生在 worker 进程中。
 */
#[Listener]
class OnPacket implements ListenerInterface
{
    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Server:OnPacket'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 继续执行
        return true;
    }
}