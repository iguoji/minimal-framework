<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 当工作进程收到由 $server->sendMessage() 发送的 unixSocket 消息时会触发 onPipeMessage 事件。
 * worker/task 进程都可能会触发 onPipeMessage 事件
 */
#[Listener]
class OnPipeMessage implements ListenerInterface
{
    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Server:OnPipeMessage'];
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