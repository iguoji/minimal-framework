<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 当 WebSocket 服务器收到来自客户端的数据帧时会回调此函数。
 */
#[Listener]
class OnMessage implements ListenerInterface
{
    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Server:OnMessage'];
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