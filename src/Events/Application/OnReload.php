<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Swoole\Process;
use Minimal\Application;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 重载事件
 * 主进程
 */
#[Listener]
class OnReload implements ListenerInterface
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
        return ['Application:OnReload'];
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
        if (false === $pid) {
            echo 'Server is not running', PHP_EOL;
            return true;
        }

        // 重载服务
        $bool = Process::kill($pid, SIGUSR1);
        // 返回结果
        return true;
    }
}