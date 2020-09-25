<?php
declare(strict_types=1);

namespace Minimal\Events\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 有新的连接进入时，在 worker 进程中回调。
 */
#[Listener]
class OnConnect implements ListenerInterface
{
    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Server:OnConnect'];
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