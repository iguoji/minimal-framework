<?php
declare(strict_types=1);

namespace Minimal\Events\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 当管理进程启动时触发此事件
 */
#[Listener]
class OnManagerStart implements ListenerInterface
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
        return ['Server:OnManagerStart'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 服务器对象
        $server = $arguments[0];
        // 输出信息
        // $this->log->notice(sprintf('manager #%s started', $server->manager_pid));
        // 调整标题
        cli_set_process_title('php swoole manager');
        // 继续执行
        return true;
    }
}