<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Swoole\Process;
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
    public function __construct()
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
        $basePath = $arguments['context']['basePath'] . DIRECTORY_SEPARATOR;

        // PidFile
        $file = $basePath . 'pid';
        // 没有文件
        if (!is_file($file)) {
            echo '很抱歉、服务器尚未运行！', PHP_EOL;
            return false;
        }
        // 读取文件
        $pid = file_get_contents($file);
        $pid = $pid ? (int) $pid: 0;
        // 没有启动
        if (!Process::kill($pid, 0)) {
            echo '很抱歉、服务器尚未启动！', PHP_EOL;
            return false;
        }

        // 重载服务
        $bool = Process::kill($pid, SIGUSR1);

        // 返回结果
        return true;
    }
}