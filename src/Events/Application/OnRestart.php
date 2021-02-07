<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Swoole\Process;
use Minimal\Application;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 重启事件
 * 主进程
 */
#[Listener]
class OnRestart implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Application:OnRestart'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 先停止服务
        $this->app->trigger('Application:OnStop');
        // 再启动服务
        $this->app->trigger('Application:OnStart');
        // 查看状态
        $this->app->trigger('Application:OnStatus');
        // 返回结果
        return true;
    }
}