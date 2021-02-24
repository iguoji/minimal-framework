<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 当管理进程结束时触发
 */
#[Listener]
class OnManagerStop implements ListenerInterface
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
        return ['Server:OnManagerStop'];
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