<?php
declare(strict_types=1);

namespace Minimal\Events\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 此事件在 Worker 进程 / Task 进程启动时发生，这里创建的对象可以在进程生命周期内使用。
 */
#[Listener]
class OnWorkerStart implements ListenerInterface
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
        return ['Server:OnWorkerStart'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 进程编号
        $workerId = $arguments[1];
        // 输出信息
        // $this->log->notice('worker #' . $workerId . ' started');
        // 调整标题
        cli_set_process_title(sprintf('php swoole http server worker #%s', $workerId));
        // 继续执行
        return true;
    }
}