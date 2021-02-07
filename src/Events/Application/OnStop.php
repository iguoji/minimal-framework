<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Swoole\Process;
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
    public function __construct()
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

        // 进程存在
        while($exist = Process::kill($pid, 0)) {
            // 停止服务
            $force ? Process::kill($pid, SIGKILL) : Process::kill($pid);
            // 休息片刻
            usleep(1000);
        }

        // 返回结果
        return true;
    }
}