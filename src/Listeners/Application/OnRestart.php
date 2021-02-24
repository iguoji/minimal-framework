<?php
declare(strict_types=1);

namespace Minimal\Listeners\Application;

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
        // 基础目录
        $basePath = $this->app->getContext()['basePath'] . DIRECTORY_SEPARATOR;

        // 运行状态
        $pid = OnStatus::running($basePath);
        if (false !== $pid) {
            // 停止服务
            $this->app->trigger('Application:OnStop');
        }
        // 启动服务
        $this->app->trigger('Application:OnStart');

        // 返回结果
        return true;
    }
}