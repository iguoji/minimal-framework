<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Swoole\Timer;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 此事件在 Worker 进程终止时发生。在此函数中可以回收 Worker 进程申请的各类资源。
 */
#[Listener]
class OnWorkerStop implements ListenerInterface
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
        return ['Server:OnWorkerStop'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 打印信息
        // $this->log->notice(__CLASS__ . '::' . $event);

        // 清除定时器
        // 并不管用，只有在 OnWorkerExit 中才能清除
        // 但官方又说 Task 进程不会触发 OnWorkerExit 事件
        Timer::clearAll();

        // 继续执行
        return true;
    }
}