<?php
declare(strict_types=1);

namespace Minimal\Events\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 启动后在主进程（master）的主线程回调此函数
 */
#[Listener]
class OnStart implements ListenerInterface
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
        return ['Server:OnStart'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 服务器对象
        $server = $arguments[0];
        // 输出信息
        // $this->log->notice(sprintf('master #%s started', $server->master_pid));
        // 调整标题
        cli_set_process_title('php swoole master');
        // 继续执行
        return true;
    }
}