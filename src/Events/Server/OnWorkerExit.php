<?php
declare(strict_types=1);

namespace Minimal\Events\Server;

use Swoole\Timer;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 仅在开启 reload_async 特性后有效。参见 如何正确的重启服务
 */
#[Listener]
class OnWorkerExit implements ListenerInterface
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
        return ['Server:OnWorkerExit'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 打印信息
        // $this->log->notice(__CLASS__ . '::' . $event);

        // 清除定时器
        Timer::clearAll();

        // 继续执行
        return true;
    }
}