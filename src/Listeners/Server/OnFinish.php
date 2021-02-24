<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 此回调函数在 worker 进程被调用，当 worker 进程投递的任务在 task 进程中完成时，task 进程会通过 Swoole\Server->finish() 方法将任务处理的结果发送给 worker 进程。
 */
#[Listener]
class OnFinish implements ListenerInterface
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
        return ['Server:OnFinish'];
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