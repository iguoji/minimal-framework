<?php
declare(strict_types=1);

namespace Minimal\Listeners\Application;

use Swoole\Process;
use Minimal\Application;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 停止事件
 * 主进程
 */
#[Listener]
class OnStop implements ListenerInterface
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
        return ['Application:OnStop'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 强行停止
        $force = isset($arguments['force']);

        // 基础目录
        $basePath = $this->app->getContext()['basePath'] . DIRECTORY_SEPARATOR;

        // 运行状态
        $pid = OnStatus::running($basePath);
        if (false === $pid) {
            echo 'Server is not running', PHP_EOL;
            return true;
        }

        // 进程存在
        echo 'Stop';
        $count = 0;
        while($exist = Process::kill($pid, 0)) {
            // 停止服务
            $count >= 20 || $force ? Process::kill($pid, SIGKILL) : Process::kill($pid);
            // 次数增加
            $count++;
            // 休息片刻
            usleep(500000);
            // 输出进度
            echo '.';
        }
        echo ' success', PHP_EOL;

        // 返回结果
        return true;
    }
}