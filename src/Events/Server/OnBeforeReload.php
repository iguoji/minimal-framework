<?php
declare(strict_types=1);

namespace Minimal\Events\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * Worker 进程 Reload 之前触发此事件，在 Manager 进程中回调
 */
#[Listener]
class OnBeforeReload implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct()
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Server:OnBeforeReload'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 打印信息
        // $this->log->notice(__CLASS__ . '::' . $event);
        // 继续执行
        return true;
    }
}