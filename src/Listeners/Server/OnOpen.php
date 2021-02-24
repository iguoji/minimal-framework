<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 当 WebSocket 客户端与服务器建立连接并完成握手后会回调此函数。
 */
#[Listener]
class OnOpen implements ListenerInterface
{
    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Server:OnOpen'];
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